<?php
/**
 * TeamTailor API integration
 *
 * @since      1.0.0
 *
 * @package    TeamTailor_Integrator
 * @subpackage TeamTailor_Integrator/includes
 */

/**
 * TeamTailor API integration.
 *
 * Handles all API communications with TeamTailor.
 *
 * @package    TeamTailor_Integrator
 * @subpackage TeamTailor_Integrator/includes
 * @author     Jonatan Jansson
 */
class TeamTailor_Integrator_API {

    /**
     * The API key for TeamTailor.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $api_key    The API key for TeamTailor.
     */
    private $api_key;

    /**
     * Debug mode flag.
     *
     * @since    1.0.0
     * @access   private
     * @var      bool    $debug_mode    Whether debug mode is enabled.
     */
    private $debug_mode;

    /**
     * The base URL for the TeamTailor API.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $base_url    The base URL for the TeamTailor API.
     */
    private $base_url = 'https://api.teamtailor.com/v1/';

    /**
     * API version header value.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $api_version    API version header value.
     */
    private $api_version = '20210218';

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $api_key      The API key for TeamTailor.
     * @param    bool      $debug_mode   Whether to enable debug output.
     */
    public function __construct($api_key, $debug_mode = false) {
        $this->api_key = $api_key;
        $this->debug_mode = $debug_mode;
    }
    
    /**
     * Output debug information when debug mode is enabled.
     *
     * @since    1.0.0
     * @param    string    $message    The debug message.
     */
    private function debug($message) {
        if ($this->debug_mode) {
            echo '<div class="teamtailor-status-box">';
            echo '<p><strong>▶</strong> ' . $message . '</p>';
            echo '</div>';
        }
    }

    /**
     * Get the common HTTP headers for API requests.
     *
     * @since    1.0.0
     * @return   array    The HTTP headers.
     */
    private function get_headers() {
        $headers = array(
            "Authorization: Token token={$this->api_key}",
            "X-Api-Version: {$this->api_version}",
            "Content-Type: application/json"
        );
        
        // Debug headers
        $this->debug('Request Headers: ' . json_encode($headers));
        
        return $headers;
    }

    /**
     * Make a GET request to the TeamTailor API.
     *
     * @since    1.0.0
     * @param    string    $endpoint    The API endpoint.
     * @return   mixed                  The API response or false on error.
     */
    public function fetch_data($endpoint) {
        $url = $this->base_url . $endpoint;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->get_headers());
        
        // Add verbose info for debugging when debug mode is on
        if ($this->debug_mode) {
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            $verbose = fopen('php://temp', 'w+');
            curl_setopt($ch, CURLOPT_STDERR, $verbose);
        }

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // Debug API request info - handled by debug method, so no visible output if debug mode is off
        $this->debug("API Request: $url (HTTP Code: $http_code)");
        
        if (curl_error($ch)) {
            $error = curl_error($ch);
            
            // Get additional debug info if debug mode is on
            if ($this->debug_mode) {
                rewind($verbose);
                $verboseLog = stream_get_contents($verbose);
                
                echo '<div class="teamtailor-notice teamtailor-notice-error">';
                echo '<p>cURL Error: ' . $error . '</p>';
                echo '<p>Request Details: ' . htmlspecialchars($verboseLog) . '</p>';
                echo '</div>';
            } else {
                echo '<div class="teamtailor-notice teamtailor-notice-error">';
                echo '<p>Error connecting to TeamTailor API. Enable debugging for more details.</p>';
                echo '</div>';
            }
            
            curl_close($ch);
            return "Error: $error";
        }

        curl_close($ch);
        
        // Check HTTP response code
        if ($http_code < 200 || $http_code >= 300) {
            echo '<div class="teamtailor-notice teamtailor-notice-error">';
            echo '<p>API returned non-successful code: ' . $http_code . '</p>';
            if ($this->debug_mode) {
                echo '<p>Response: ' . substr($response, 0, 500) . '</p>';
            }
            echo '</div>';
        }
        
        // Try to decode JSON
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo '<div class="teamtailor-notice teamtailor-notice-error">';
            echo '<p>JSON decode error: ' . json_last_error_msg() . '</p>';
            if ($this->debug_mode) {
                echo '<p>Raw response (first 500 chars): ' . substr($response, 0, 500) . '</p>';
            }
            echo '</div>';
        }
        
        return $decoded;
    }

    /**
     * Get jobs from TeamTailor.
     *
     * @since    1.0.0
     * @return   mixed    The jobs data or false on error.
     */
    public function get_jobs() {
        $this->debug('Fetching jobs from TeamTailor API...');
        
        // Show status message only in debug mode
        if ($this->debug_mode) {
            echo '<div class="teamtailor-status-box">';
            echo '<p><strong>▶</strong> API call: jobs</p>';
            echo '</div>';
        }
        
        $result = $this->fetch_data('jobs');
        
        if (is_array($result) && isset($result['data'])) {
            $job_count = count($result['data']);
            
            // Always show a success message (for sync process)
            if (strpos($_SERVER['REQUEST_URI'], 'wp-admin/admin-ajax.php') === false) {
                // Not an AJAX request (regular sync)
                echo '<div class="teamtailor-notice teamtailor-notice-success">';
                echo '<p><strong>Successfully connected to TeamTailor!</strong></p>';
                echo '</div>';
            }
            
            // Debug message about successful fetch
            $this->debug('Successfully fetched ' . $job_count . ' jobs from API');
            
        } else {
            echo '<div class="teamtailor-notice teamtailor-notice-error">';
            echo '<p><strong>API Error:</strong> Invalid response format. Expected array with data field.</p>';
            if ($this->debug_mode) {
                echo '<p>Response: ' . print_r($result, true) . '</p>';
            }
            echo '</div>';
        }
        
        return $result;
    }

    /**
     * Extract the department name from department data.
     *
     * @since    1.0.0
     * @param    array    $departmentData    The department data.
     * @return   string                      The department name.
     */
    public function extract_department_name($departmentData) {
        return isset($departmentData['data']['attributes']['name']) ? $departmentData['data']['attributes']['name'] : '';
    }

    /**
     * Extract location names and countries from locations data.
     *
     * @since    1.0.0
     * @param    array    $data    The locations data.
     * @return   array             The extracted locations and countries.
     */
    public function extract_locations($data) {
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
        return [
            'locations' => implode(', ', $locations),
            'countries' => implode(', ', array_unique($countries))
        ];
    }

    /**
     * Fetch and extract the role name for a job.
     *
     * @since    1.0.0
     * @param    string    $jobId    The job ID.
     * @return   string              The role name.
     */
    public function get_role_name($jobId) {
        $roleData = $this->fetch_data("jobs/$jobId/role");
        return isset($roleData['data']['attributes']['name']) ? $roleData['data']['attributes']['name'] : '';
    }

    /**
     * Fetch and extract the company name.
     *
     * @since    1.0.0
     * @return   string    The company name.
     */
    public function get_company_name() {
        $companyData = $this->fetch_data("company");
        return isset($companyData['data']['attributes']['name']) ? $companyData['data']['attributes']['name'] : '';
    }
}