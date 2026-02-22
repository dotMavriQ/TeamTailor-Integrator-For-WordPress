<?php
/**
 * Standalone test script for the mock API.
 *
 * Run: php tests/test-mock-api.php
 *
 * Validates every data structure matches what the plugin's sync,
 * API test, and shortcode flows expect.
 *
 * @package TeamTailor_Integrator
 */

// Prevent WP constant errors when running standalone.
if (!defined('ABSPATH')) {
    define('ABSPATH', true);
}

// Load the mock API class.
require_once dirname(__DIR__) . '/includes/class-teamtailor-integrator-mock-api.php';

$pass = 0;
$fail = 0;

function test($label, $condition, $detail = '') {
    global $pass, $fail;
    if ($condition) {
        $pass++;
        echo "  ✓ $label\n";
    } else {
        $fail++;
        echo "  ✗ $label";
        if ($detail) echo "  [$detail]";
        echo "\n";
    }
}

echo "\n=== TeamTailor Mock API — Structure Validation ===\n\n";

// ---------------------------------------------------------------------------
// 1. Instantiate
// ---------------------------------------------------------------------------
echo "1. Instantiation\n";
try {
    $mock = new TeamTailor_Integrator_Mock_API(false);
    test('Class loads and instantiates', $mock instanceof TeamTailor_Integrator_Mock_API);
} catch (Throwable $e) {
    test('Class loads and instantiates', false, $e->getMessage());
    echo "\nFATAL — cannot continue\n";
    exit(1);
}

// ---------------------------------------------------------------------------
// 2. get_jobs() — full jobs listing (same as GET /v1/jobs)
// ---------------------------------------------------------------------------
echo "\n2. get_jobs()\n";
$jobs = $mock->get_jobs();
test('Returns array', is_array($jobs));
test('Has "data" key', array_key_exists('data', $jobs));
test('Has "links" key (pagination)', array_key_exists('links', $jobs));
test('Has "meta" key', array_key_exists('meta', $jobs));
test('meta has total_count', isset($jobs['meta']['total_count']));
test('meta has current_page', isset($jobs['meta']['current_page']));
test('Returns 12 jobs', count($jobs['data']) === 12, 'Got ' . count($jobs['data']));

$job_ids = [];
$departments_seen = [];
$roles_seen = [];
$location_countries = [];

foreach ($jobs['data'] as $i => $job) {
    $id = $job['id'] ?? 'MISSING';
    $n = $i + 1;
    test("Job #$n has string id", is_string($id));
    $job_ids[] = $id;

    test("Job #$n has type 'jobs'", ($job['type'] ?? '') === 'jobs', 'Got ' . ($job['type'] ?? 'null'));

    // Attributes
    test("Job #$n has attributes.title", !empty($job['attributes']['title']));
    test("Job #$n has attributes.body", !empty($job['attributes']['body']));
    test("Job #$n has attributes.status", ($job['attributes']['status'] ?? '') === 'published');
    test("Job #$n has attributes.employment_title", !empty($job['attributes']['employment_title']));
    test("Job #$n has attributes.headline", !empty($job['attributes']['headline']));
    test("Job #$n has attributes.department (int FK)", is_int($job['attributes']['department']));
    test("Job #$n has attributes.location (int FK)", is_int($job['attributes']['location']));
    test("Job #$n has attributes.role (int FK)", is_int($job['attributes']['role']));

    // Links (plugin looks for careersite-job-apply-iframe-url)
    test("Job #$n has links.careersite-job-apply-iframe-url",
        !empty($job['links']['careersite-job-apply-iframe-url']));

    // Relationships
    test("Job #$n has relationships.department", isset($job['relationships']['department']['links']['related']));
    test("Job #$n has relationships.location", isset($job['relationships']['location']['links']['related']));
    test("Job #$n has relationships.role", isset($job['relationships']['role']['links']['related']));

    // Track for later cross-reference checks
    $departments_seen[] = $job['attributes']['department'];
    $roles_seen[] = $job['attributes']['role'];
}

test('All job IDs unique', count(array_unique($job_ids)) === count($job_ids));

// ---------------------------------------------------------------------------
// 3. get_company() + get_company_name() — GET /v1/company
// ---------------------------------------------------------------------------
echo "\n3. Company\n";
$company = $mock->get_company();
test('Returns array', is_array($company));
test('Has data.type = companies', ($company['data']['type'] ?? '') === 'companies');
test('Company name is Meridian ERP', ($company['data']['attributes']['name'] ?? '') === 'Meridian ERP');
test('Has career_site_url', !empty($company['data']['attributes']['career_site_url']));
$company_name = $mock->get_company_name();
test('get_company_name() returns string', is_string($company_name));
test('get_company_name() matches', $company_name === 'Meridian ERP');

// ---------------------------------------------------------------------------
// 4. get_department($job_id) — GET /v1/jobs/{id}/department
// ---------------------------------------------------------------------------
echo "\n4. Department (per job)\n";
$first_job_id = $jobs['data'][0]['id'];
$dept = $mock->get_department($first_job_id);
test('Returns array', is_array($dept));
test('Has data.type = departments', ($dept['data']['type'] ?? '') === 'departments');
test('Has attributes.name', !empty($dept['data']['attributes']['name']));
test('extract_department_name() returns string', is_string($mock->extract_department_name($dept)));
test('extract_department_name() non-empty', !empty($mock->extract_department_name($dept)));

// Test all jobs have valid departments
foreach ($jobs['data'] as $job) {
    $d = $mock->get_department($job['id']);
    test('  Dept for job ' . $job['id'] . ' has name', !empty($d['data']['attributes']['name'] ?? ''));
}

// ---------------------------------------------------------------------------
// 5. get_locations($job_id) — GET /v1/jobs/{id}/locations
// ---------------------------------------------------------------------------
echo "\n5. Locations (per job)\n";
$locs = $mock->get_locations($first_job_id);
test('Returns array', is_array($locs));
test('Has data as array', is_array($locs['data']));
if (!empty($locs['data'])) {
    $first_loc = $locs['data'][0];
    test('Has type = locations', ($first_loc['type'] ?? '') === 'locations');
    test('Has attributes.name', !empty($first_loc['attributes']['name'] ?? ''));
    test('Has attributes.country', !empty($first_loc['attributes']['country'] ?? ''));
    test('Has attributes.city', !empty($first_loc['attributes']['city'] ?? ''));
}

// extract_locations()
$extracted = $mock->extract_locations($locs);
test('extract_locations() returns array', is_array($extracted));
test('  Has "locations" key', isset($extracted['locations']));
test('  Has "countries" key', isset($extracted['countries']));
test('  locations is string', is_string($extracted['locations']));
test('  countries is string', is_string($extracted['countries']));

// Verify all jobs have valid location data (as extracted by sync)
foreach ($jobs['data'] as $job) {
    $l = $mock->get_locations($job['id']);
    $e = $mock->extract_locations($l);
    test('  Location for job ' . $job['id'] . ': ' . ($e['locations'] ?: 'EMPTY'), !empty($e['locations']));
}

// ---------------------------------------------------------------------------
// 6. get_role($job_id) + get_role_name() — GET /v1/jobs/{id}/role
// ---------------------------------------------------------------------------
echo "\n6. Role (per job)\n";
$role = $mock->get_role($first_job_id);
test('Returns array', is_array($role));
test('Has data.type = roles', ($role['data']['type'] ?? '') === 'roles');
test('Has attributes.name', !empty($role['data']['attributes']['name'] ?? ''));
test('get_role_name() returns string', is_string($mock->get_role_name($first_job_id)));
test('get_role_name() non-empty', !empty($mock->get_role_name($first_job_id)));

foreach ($jobs['data'] as $job) {
    $r = $mock->get_role_name($job['id']);
    test('  Role for job ' . $job['id'] . ': ' . $r, !empty($r));
}

// ---------------------------------------------------------------------------
// 7. Cross-reference: Simulate the sync flow
// ---------------------------------------------------------------------------
echo "\n7. Sync flow simulation\n";
$simulated_jobs = $mock->get_jobs();
if (!empty($simulated_jobs['data'])) {
    $company_name = $mock->get_company_name();
    test('Company name consistent', $company_name === 'Meridian ERP');

    foreach ($simulated_jobs['data'] as $job) {
        $jid    = $job['id'];
        $title  = $job['attributes']['title'];
        $body   = $job['attributes']['body'];

        // What sync does with department
        $dept_data  = $mock->get_department($jid);
        $dept_name  = $mock->extract_department_name($dept_data);

        // What sync does with locations
        $loc_data   = $mock->get_locations($jid);
        $extracted  = $mock->extract_locations($loc_data);

        // What sync does with role
        $role_name  = $mock->get_role_name($jid);

        test("  [$jid $title] dept: $dept_name", !empty($dept_name));
        test("  [$jid $title] loc: {$extracted['locations']}", !empty($extracted['locations']));
        test("  [$jid $title] country: {$extracted['countries']}", !empty($extracted['countries']));
        test("  [$jid $title] role: $role_name", !empty($role_name));
        test("  [$jid $title] body has HTML", strpos($body, '<h2>') !== false);
        test("  [$jid $title] body mentions Meridian ERP", strpos($body, 'Meridian ERP') !== false);
    }
}

// Also verify that build_post_content() returns the expected iframe
if (!empty($simulated_jobs['data'])) {
    $first = $simulated_jobs['data'][0];
    $content = $mock->build_post_content([
        'id'               => $first['id'],
        'title'            => $first['attributes']['title'],
        'headline'         => $first['attributes']['headline'],
        'employment_title' => $first['attributes']['employment_title'],
        'status'           => $first['attributes']['status'],
        'department_id'    => $first['attributes']['department'],
        'location_ids'     => [$first['attributes']['location']],
        'role_id'          => $first['attributes']['role'],
    ]);
    test('build_post_content() contains iframe', strpos($content, '<iframe') !== false);
    test('build_post_content() contains apply URL', strpos($content, 'apply.teamtailor.com') !== false);
}

// ---------------------------------------------------------------------------
// 8. Data diversity checks
// ---------------------------------------------------------------------------
echo "\n8. Data diversity\n";

// Get all unique job titles
$titles = array_map(function($j) { return $j['attributes']['title']; }, $jobs['data']);
test('All 12 jobs have different titles', count(array_unique($titles)) === 12);
echo "  Job titles:\n";
foreach ($titles as $t) {
    echo "    - $t\n";
}

// Verify locations span multiple European cities
$all_locations = [];
foreach ($jobs['data'] as $job) {
    $l = $mock->get_locations($job['id']);
    foreach ($l['data'] as $loc) {
        $all_locations[] = $loc['attributes']['name'];
    }
}
test('At least 6 different European cities', count(array_unique($all_locations)) >= 6,
    'Got ' . count(array_unique($all_locations)));
echo "  Cities: " . implode(', ', array_unique($all_locations)) . "\n";

// ---------------------------------------------------------------------------
// Summary
// ---------------------------------------------------------------------------
echo "\n========================================\n";
echo "  Passed: $pass  Failed: $fail\n";
echo "========================================\n";

exit($fail > 0 ? 1 : 0);
