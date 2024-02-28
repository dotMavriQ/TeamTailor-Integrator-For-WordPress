<?php
// Button_SyncFromTeamTailor.php
// Ensure WordPress is loaded
defined('ABSPATH') or die('No script kiddies please!');

// Function to extract the department name
function extractDepartmentName($departmentData) {
    return isset($departmentData['data']['attributes']['name']) ? $departmentData['data']['attributes']['name'] : '';
}

// Function to extract names and countries from locations
function extractLocations($data) {
    $locations = [];
    $countries = [];
    if (isset($data['data']) && is_array($data['data'])) {
        foreach ($data['data'] as $item) {
            if (isset($item['attributes']['name'])) {
                $locations[] = $item['attributes']['name'];
            }
            if (isset($item['attributes']['country'])) {
                $countries[] = $item['attributes']['country'];
            }
        }
    }
    return ['locations' => implode(', ', $locations), 'countries' => implode(', ', array_unique($countries))];
}

function extractCustomData($data)  {
    $output = [];
    if (isset($data['data']) && is_array($data['data'])) {
        foreach ($data['data'] as $item) {
            if (isset($item['attributes']['value'])) {
                if (is_array($item['attributes']['value'])) {
                    $output[] = implode(', ',$item['attributes']['value']);
                } else {
                    $output[] = $item['attributes']['value'];
                }
            }
        }
    }
    return implode(', ', $output);
}

// Function to fetch and extract the role name
function fetchAndExtractRoleName($api_key, $jobId) {
    $roleData = fetchTeamtailorData($api_key, "jobs/$jobId/role");
    return isset($roleData['data']['attributes']['name']) ? $roleData['data']['attributes']['name'] : '';
}

// Function to fetch and extract the company name
function fetchAndExtractCompanyName($api_key) {
    $companyData = fetchTeamtailorData($api_key, "company");
    return isset($companyData['data']['attributes']['name']) ? $companyData['data']['attributes']['name'] : '';
}

// Function to fetch data from TeamTailor
if (!function_exists('fetchTeamtailorData')) {
    function fetchTeamtailorData($api_key, $endpoint) {
        $url = "https://api.teamtailor.com/v1/$endpoint";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: Token token={$api_key}",
            "X-Api-Version: 20210218",
            "Content-Type: application/json"
        ));

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }
}

// Function to get the post ID by job ID
if (!function_exists('coswift_get_post_id_by_job_id')) {
    function coswift_get_post_id_by_job_id($job_id) {
        $query = new WP_Query([
            'post_type' => 'coswift_jobs',
            'meta_query' => [
                [
                    'key' => '_coswift_job_id',
                    'value' => $job_id,
                    'compare' => '=',
                ],
            ],
            'posts_per_page' => 1,
        ]);
        if ($query->have_posts()) {
            return $query->posts[0]->ID;
        }
        return null;
    }
}

// Function to get existing job IDs
if (!function_exists('get_existing_job_ids')) {
    function get_existing_job_ids() {
        $query = new WP_Query([
            'post_type' => 'coswift_jobs',
            'posts_per_page' => -1,
        ]);
        $ids = [];
        foreach ($query->posts as $post) {
            $ids[] = get_post_meta($post->ID, '_coswift_job_id', true);
        }
        return $ids;
    }
}

// Main function to sync data from TeamTailor
function coswift_sync_teamtailor() {
    $api_key = get_option('coswift_api_token');
    if (!$api_key) {
        echo '<div>API Key is not set.</div>';
        return;
    }

    $url = "https://api.teamtailor.com/v1/jobs";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Authorization: Token token={$api_key}",
        "X-Api-Version: 20210218",
        "Content-Type: application/json"
    ));

    $response = curl_exec($ch);
    curl_close($ch);

    if ($response === false) {
        echo '<div>Error fetching data from TeamTailor.</div>';
        return;
    }

    $jobs = json_decode($response, true);
    $existing_ids = get_existing_job_ids();
    $companyName = fetchAndExtractCompanyName($api_key);

    foreach ($jobs['data'] as $job) {
        $job_id = $job['id'];
        $post_id = coswift_get_post_id_by_job_id($job_id);
        $job_title = $job['attributes']['title'];
        $job_body = $job['attributes']['body'];

        // Fetch additional data
        $departmentsData = fetchTeamtailorData($api_key, "jobs/$job_id/department");
        $locationsData = fetchTeamtailorData($api_key, "jobs/$job_id/locations");
        $customData = fetchTeamtailorData($api_key, "jobs/$job_id/custom-field-values");
        $extractedLocations = extractLocations($locationsData);
        $roleName = fetchAndExtractRoleName($api_key, $job_id);
        $extractedCustomData = extractCustomData($customData);

        $post_content = $job_body;
        $job_apply_iframe_url = $job['links']['careersite-job-apply-iframe-url'] ?? '';
        if ($job_apply_iframe_url) {
            $post_content .= "\n\n<iframe src='" . esc_url($job_apply_iframe_url) . "' style='width: 100%; height: 800px' frameborder='0'></iframe>";
        }

        $post_data = [
            'post_type' => 'coswift_jobs',
            'post_title' => $job_title,
            'post_content' => $post_content,
            'post_status' => 'publish',
            'meta_input' => [
                '_coswift_job_id' => $job_id,
                'departments' => extractDepartmentName($departmentsData),
                'locations' => $extractedLocations['locations'],
                'countries' => $extractedLocations['countries'], // Separate custom field for countries
                'roles' => $roleName,
                'company' => $companyName,
                'tt_custom_data' => $extractedCustomData,
            ],
        ];

        if ($post_id) {
            $post_data['ID'] = $post_id;
            wp_update_post($post_data);
        } else {
            wp_insert_post($post_data);
        }

        if (($key = array_search($job_id, $existing_ids)) !== false) {
            unset($existing_ids[$key]);
        }
    }

    // Remove posts that no longer exist in the TeamTailor data
    foreach ($existing_ids as $id) {
        $post_id = coswift_get_post_id_by_job_id($id);
        if ($post_id) {
            wp_delete_post($post_id, true);
        }
    }

    echo '<div>Sync completed successfully.</div>';
}

// Call the sync function
coswift_sync_teamtailor();

?>

