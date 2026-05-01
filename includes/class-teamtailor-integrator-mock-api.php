<?php
/**
 * Mock API class for testing without a live TeamTailor account.
 *
 * @since      1.2.0
 *
 * @package    TeamTailor_Integrator
 * @subpackage TeamTailor_Integrator/includes
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Mock API — returns realistic test data matching the TeamTailor v1 JSON:API format.
 *
 * Every method mirrors TeamTailor_Integrator_API so the sync / test flows
 * work identically whether the real API or mock data is used.
 *
 * @package    TeamTailor_Integrator
 * @subpackage TeamTailor_Integrator/includes
 * @author     Jonatan Jansson
 */
class TeamTailor_Integrator_Mock_API {

    /**
     * Debug mode flag.
     *
     * @since    1.2.0
     * @access   private
     * @var      bool    $debug_mode    Whether debug mode is enabled.
     */
    private $debug_mode;

    /**
     * Company name used across all mock jobs.
     *
     * @since    1.2.0
     * @access   private
     * @var      string
     */
    private $company_name = 'Meridian ERP';

    /**
     * Mock department definitions keyed by ID.
     *
     * @since    1.2.0
     * @access   private
     * @var      array
     */
    private $departments = array(
        1 => array('name' => 'Engineering',       'description' => 'Building and maintaining our ERP platform'),
        2 => array('name' => 'Professional Services','description' => 'Helping clients succeed with implementations'),
        3 => array('name' => 'Product',            'description' => 'Defining the product roadmap and vision'),
        4 => array('name' => 'Operations',         'description' => 'Streamlining supply chain and logistics'),
        5 => array('name' => 'Data',               'description' => 'Data governance, analytics, and compliance'),
        6 => array('name' => 'Quality',            'description' => 'Ensuring enterprise-grade reliability'),
        7 => array('name' => 'Customer Success',   'description' => 'Driving adoption and retention'),
        8 => array('name' => 'DevOps',             'description' => 'Infrastructure, CI/CD, and platform reliability'),
    );

    /**
     * Mock location definitions keyed by ID.
     *
     * @since    1.2.0
     * @access   private
     * @var      array
     */
    private $locations = array(
        1  => array('name' => 'Berlin',    'city' => 'Berlin',    'country' => 'Germany'),
        2  => array('name' => 'Amsterdam', 'city' => 'Amsterdam', 'country' => 'Netherlands'),
        3  => array('name' => 'Munich',    'city' => 'Munich',    'country' => 'Germany'),
        4  => array('name' => 'Stockholm', 'city' => 'Stockholm', 'country' => 'Sweden'),
        5  => array('name' => 'London',    'city' => 'London',    'country' => 'United Kingdom'),
        6  => array('name' => 'Paris',     'city' => 'Paris',     'country' => 'France'),
        7  => array('name' => 'Barcelona', 'city' => 'Barcelona', 'country' => 'Spain'),
        8  => array('name' => 'Dublin',    'city' => 'Dublin',    'country' => 'Ireland'),
        9  => array('name' => 'Copenhagen','city' => 'Copenhagen','country' => 'Denmark'),
        10 => array('name' => 'Milan',     'city' => 'Milan',     'country' => 'Italy'),
        11 => array('name' => 'Oslo',      'city' => 'Oslo',      'country' => 'Norway'),
        12 => array('name' => 'Vienna',    'city' => 'Vienna',    'country' => 'Austria'),
    );

    /**
     * Mock role definitions keyed by ID.
     *
     * @since    1.2.0
     * @access   private
     * @var      array
     */
    private $roles = array(
        1 => array('name' => 'Developer', 'description' => 'Hands-on software development'),
        2 => array('name' => 'Manager',   'description' => 'Leading teams and driving outcomes'),
    );

    /**
     * Mock job definitions.
     *
     * Each entry maps to the JSON:API resource the real endpoint returns.
     * Fields: id, title, body (HTML), headline, employment_title, status,
     * department_id, location_ids (array), role_id.
     *
     * @since    1.2.0
     * @access   private
     * @var      array
     */
    private $jobs_data = array(
        array(
            'id'               => '1001',
            'title'            => 'Senior PHP Developer',
            'headline'         => 'Build the core of our ERP platform',
            'employment_title' => 'Full-time',
            'status'           => 'published',
            'department_id'    => 1,
            'location_ids'     => array(1),
            'role_id'          => 1,
        ),
        array(
            'id'               => '1002',
            'title'            => 'Frontend Developer (React)',
            'headline'         => 'Craft modern, responsive UIs for enterprise users',
            'employment_title' => 'Full-time',
            'status'           => 'published',
            'department_id'    => 1,
            'location_ids'     => array(2),
            'role_id'          => 1,
        ),
        array(
            'id'               => '1003',
            'title'            => 'ERP Implementation Manager',
            'headline'         => 'Lead enterprise-grade rollouts across Europe',
            'employment_title' => 'Full-time',
            'status'           => 'published',
            'department_id'    => 2,
            'location_ids'     => array(3),
            'role_id'          => 2,
        ),
        array(
            'id'               => '1004',
            'title'            => 'Full Stack Developer',
            'headline'         => 'Own features end-to-end in a cross-functional team',
            'employment_title' => 'Full-time',
            'status'           => 'published',
            'department_id'    => 1,
            'location_ids'     => array(4),
            'role_id'          => 1,
        ),
        array(
            'id'               => '1005',
            'title'            => 'Product Owner — Financial Modules',
            'headline'         => 'Shape the future of our finance & accounting suite',
            'employment_title' => 'Full-time',
            'status'           => 'published',
            'department_id'    => 3,
            'location_ids'     => array(5),
            'role_id'          => 2,
        ),
        array(
            'id'               => '1006',
            'title'            => 'Integration Developer (API)',
            'headline'         => 'Connect Meridian ERP with the ecosystem',
            'employment_title' => 'Full-time',
            'status'           => 'published',
            'department_id'    => 1,
            'location_ids'     => array(6),
            'role_id'          => 1,
        ),
        array(
            'id'               => '1007',
            'title'            => 'Supply Chain Manager',
            'headline'         => 'Optimise end-to-end supply chain processes',
            'employment_title' => 'Full-time',
            'status'           => 'published',
            'department_id'    => 4,
            'location_ids'     => array(7),
            'role_id'          => 2,
        ),
        array(
            'id'               => '1008',
            'title'            => 'Python Developer',
            'headline'         => 'Build data pipelines and backend services',
            'employment_title' => 'Full-time',
            'status'           => 'published',
            'department_id'    => 1,
            'location_ids'     => array(8),
            'role_id'          => 1,
        ),
        array(
            'id'               => '1009',
            'title'            => 'Data Governance Manager',
            'headline'         => 'Ensure enterprise data quality and compliance',
            'employment_title' => 'Full-time',
            'status'           => 'published',
            'department_id'    => 5,
            'location_ids'     => array(9),
            'role_id'          => 2,
        ),
        array(
            'id'               => '1010',
            'title'            => 'QA Manager',
            'headline'         => 'Lead quality assurance for our ERP suite',
            'employment_title' => 'Full-time',
            'status'           => 'published',
            'department_id'    => 6,
            'location_ids'     => array(10),
            'role_id'          => 2,
        ),
        array(
            'id'               => '1011',
            'title'            => 'DevOps Engineer',
            'headline'         => 'Scale cloud infrastructure for thousands of clients',
            'employment_title' => 'Full-time',
            'status'           => 'published',
            'department_id'    => 8,
            'location_ids'     => array(11),
            'role_id'          => 1,
        ),
        array(
            'id'               => '1012',
            'title'            => 'Customer Success Manager — Enterprise',
            'headline'         => 'Drive adoption and value for key accounts',
            'employment_title' => 'Full-time',
            'status'           => 'published',
            'department_id'    => 7,
            'location_ids'     => array(12),
            'role_id'          => 2,
        ),
    );

    /**
     * Initialize the mock API.
     *
     * @since    1.2.0
     * @param    bool    $debug_mode    Whether to enable debug output.
     */
    public function __construct($debug_mode = false) {
        $this->debug_mode = $debug_mode;
    }

    /**
     * Output debug information when debug mode is enabled.
     *
     * @since    1.2.0
     * @param    string    $message    The debug message.
     */
    private function debug($message) {
        if ($this->debug_mode) {
            echo '<div class="teamtailor-status-box">';
            echo '<p><strong>▶</strong> ' . esc_html( $message ) . '</p>';
            echo '</div>';
        }
    }

    /**
     * Build the HTML body for a mock job.
     *
     * @since    1.2.0
     * @param    array     $job_def    The job definition array.
     * @return   string                Full HTML body (raw HTML, like the real API).
     */
    private function build_job_body($job_def) {
        $loc       = $this->locations[$job_def['location_ids'][0]];
        $dept      = $this->departments[$job_def['department_id']];
        $role_def  = $this->roles[$job_def['role_id']];

        return '<h2>About the role</h2>
<p>We are looking for a talented ' . $job_def['title'] . ' to join our ' . $dept['name'] . ' team in ' . $loc['name'] . '. At ' . $this->company_name . ', you will help build and maintain the ERP platform that powers thousands of businesses across Europe.</p>

<h2>What you will do</h2>
<ul>
<li>Collaborate with cross-functional teams to deliver high-quality software</li>
<li>Contribute to architecture decisions and technical roadmaps</li>
<li>Mentor junior team members and participate in code reviews</li>
<li>Drive continuous improvement in our development processes</li>
</ul>

<h2>What we are looking for</h2>
<ul>
<li>Strong experience in your domain with a track record of delivery</li>
<li>Excellent problem-solving and communication skills</li>
<li>Fluency in English; additional European languages are a plus</li>
<li>A collaborative mindset and a passion for building great products</li>
</ul>

<h2>Why join ' . $this->company_name . '?</h2>
<p>We are a fast-growing European ERP company with offices in 12 cities. You will work with smart, motivated colleagues on products that make a real difference for our clients. We offer competitive compensation, generous remote flexibility, and a culture that values learning and growth.</p>

<p><strong>Location:</strong> ' . $loc['name'] . ', ' . $loc['country'] . '<br>
<strong>Department:</strong> ' . $dept['name'] . '<br>
<strong>Employment type:</strong> ' . $job_def['employment_title'] . '</p>';
    }

    /**
     * Build the persisted post content (body + application iframe).
     *
     * @since    1.2.0
     * @param    array     $job_def    The job definition array.
     * @return   string                Post content with iframe appended.
     */
    public function build_post_content($job_def) {
        $body   = $this->build_job_body($job_def);
        $iframe = $this->get_apply_iframe_url($job_def['id']);
        return $body . "\n\n<iframe src='" . $iframe . "' style='width: 100%; height: 800px' frameborder='0'></iframe>";
    }

    /**
     * Return the apply iframe URL for a given job ID.
     *
     * @since    1.2.0
     * @param    string    $job_id    The job ID.
     * @return   string               The iframe URL.
     */
    public function get_apply_iframe_url($job_id) {
        return 'https://apply.teamtailor.com/jobs/' . $job_id;
    }

    /**
     * Build the full jobs list response (mirrors GET /v1/jobs).
     *
     * @since    1.2.0
     * @return   array    The mock jobs response.
     */
    public function get_jobs() {
        $this->debug('Fetching jobs from mock API (Meridian ERP)...');

        $data = array();
        foreach ($this->jobs_data as $job_def) {
            $loc = $this->locations[$job_def['location_ids'][0]];

            $data[] = array(
                'id'            => $job_def['id'],
                'type'          => 'jobs',
                'attributes'    => array(
                    'title'            => $job_def['title'],
                    'description'      => $job_def['headline'],
                    'body'             => $this->build_job_body($job_def),
                    'department'       => $job_def['department_id'],
                    'location'         => $job_def['location_ids'][0],
                    'role'             => $job_def['role_id'],
                    'headline'         => $job_def['headline'],
                    'employment_title' => $job_def['employment_title'],
                    'status'           => $job_def['status'],
                    'published_at'     => gmdate('Y-m-d\TH:i:s.000\Z', strtotime('-'.wp_rand(1, 60).' days')),
                    'created_at'       => gmdate('Y-m-d\TH:i:s.000\Z', strtotime('-'.wp_rand(30, 120).' days')),
                    'updated_at'       => gmdate('Y-m-d\TH:i:s.000\Z', strtotime('-'.wp_rand(1, 14).' days')),
                ),
                'links' => array(
                    'careers_url'                    => 'https://careers.meridianerp.example/jobs/' . $job_def['id'],
                    'apply_url'                      => 'https://apply.teamtailor.com/jobs/' . $job_def['id'],
                    'careersite-job-apply-iframe-url' => 'https://apply.teamtailor.com/jobs/' . $job_def['id'],
                    'self'                           => 'https://api.teamtailor.com/v1/jobs/' . $job_def['id'],
                ),
                'relationships' => array(
                    'department' => array(
                        'links' => array(
                            'self'   => 'https://api.teamtailor.com/v1/jobs/' . $job_def['id'] . '/relationships/department',
                            'related' => 'https://api.teamtailor.com/v1/jobs/' . $job_def['id'] . '/department',
                        ),
                    ),
                    'location' => array(
                        'links' => array(
                            'self'   => 'https://api.teamtailor.com/v1/jobs/' . $job_def['id'] . '/relationships/location',
                            'related' => 'https://api.teamtailor.com/v1/jobs/' . $job_def['id'] . '/locations',
                        ),
                    ),
                    'role' => array(
                        'links' => array(
                            'self'   => 'https://api.teamtailor.com/v1/jobs/' . $job_def['id'] . '/relationships/role',
                            'related' => 'https://api.teamtailor.com/v1/jobs/' . $job_def['id'] . '/role',
                        ),
                    ),
                ),
            );
        }

        $count = count($data);
        $this->debug("Mock API: returning $count job(s)");

        return array(
            'links' => array(
                'first' => 'https://api.teamtailor.com/v1/jobs?page%5Bnumber%5D=1&page%5Bsize%5D=150',
                'last'  => 'https://api.teamtailor.com/v1/jobs?page%5Bnumber%5D=1&page%5Bsize%5D=150',
                'prev'  => null,
                'next'  => null,
            ),
            'meta' => array(
                'current_page' => 1,
                'total_pages'  => 1,
                'total_count'  => $count,
            ),
            'data' => $data,
            'included' => array(),
        );
    }

    /**
     * Return a mock company response (mirrors GET /v1/company).
     *
     * @since    1.2.0
     * @return   array
     */
    public function get_company() {
        return array(
            'data' => array(
                'id'   => '42',
                'type' => 'companies',
                'attributes' => array(
                    'name'           => $this->company_name,
                    'description'    => 'A fast-growing European ERP company powering businesses across the continent.',
                    'career_site_url'=> 'https://careers.meridianerp.example',
                    'created_at'     => '2018-03-01T08:00:00.000Z',
                    'updated_at'     => gmdate('Y-m-d\TH:i:s.000\Z'),
                ),
                'links' => array(
                    'self' => 'https://api.teamtailor.com/v1/company',
                ),
            ),
        );
    }

    /**
     * Return company name.
     *
     * @since    1.2.0
     * @return   string
     */
    public function get_company_name() {
        return $this->company_name;
    }

    /**
     * Return a mock department for a given job (mirrors GET /v1/jobs/{id}/department).
     *
     * @since    1.2.0
     * @param    string    $job_id    The job ID.
     * @return   array
     */
    public function get_department($job_id) {
        $dept_id = $this->find_job_field($job_id, 'department_id');
        if (null === $dept_id || !isset($this->departments[$dept_id])) {
            return array('data' => null);
        }

        $dept = $this->departments[$dept_id];
        return array(
            'data' => array(
                'id'   => (string) $dept_id,
                'type' => 'departments',
                'attributes' => array(
                    'name'        => $dept['name'],
                    'description' => $dept['description'],
                    'created_at'  => '2018-03-01T08:00:00.000Z',
                    'updated_at'  => gmdate('Y-m-d\TH:i:s.000\Z'),
                ),
                'links' => array(
                    'self' => 'https://api.teamtailor.com/v1/departments/' . $dept_id,
                ),
            ),
        );
    }

    /**
     * Return mock locations for a given job (mirrors GET /v1/jobs/{id}/locations).
     *
     * @since    1.2.0
     * @param    string    $job_id    The job ID.
     * @return   array
     */
    public function get_locations($job_id) {
        $loc_ids = $this->find_job_field($job_id, 'location_ids');
        if (null === $loc_ids || !is_array($loc_ids)) {
            return array('data' => array(), 'included' => array());
        }

        $data = array();
        foreach ($loc_ids as $lid) {
            if (isset($this->locations[$lid])) {
                $loc = $this->locations[$lid];
                $data[] = array(
                    'id'   => (string) $lid,
                    'type' => 'locations',
                    'attributes' => array(
                        'name'       => $loc['name'],
                        'city'       => $loc['city'],
                        'country'    => $loc['country'],
                        'created_at' => '2018-03-01T08:00:00.000Z',
                        'updated_at' => gmdate('Y-m-d\TH:i:s.000\Z'),
                    ),
                    'links' => array(
                        'self' => 'https://api.teamtailor.com/v1/locations/' . $lid,
                    ),
                );
            }
        }

        return array(
            'data' => $data,
            'included' => array(),
        );
    }

    /**
     * Return a mock role for a given job (mirrors GET /v1/jobs/{id}/role).
     *
     * @since    1.2.0
     * @param    string    $job_id    The job ID.
     * @return   array
     */
    public function get_role($job_id) {
        $role_id = $this->find_job_field($job_id, 'role_id');
        if (null === $role_id || !isset($this->roles[$role_id])) {
            return array('data' => null);
        }

        $role = $this->roles[$role_id];
        return array(
            'data' => array(
                'id'   => (string) $role_id,
                'type' => 'roles',
                'attributes' => array(
                    'name'        => $role['name'],
                    'description' => $role['description'],
                    'created_at'  => '2018-03-01T08:00:00.000Z',
                    'updated_at'  => gmdate('Y-m-d\TH:i:s.000\Z'),
                ),
                'links' => array(
                    'self' => 'https://api.teamtailor.com/v1/roles/' . $role_id,
                ),
            ),
        );
    }

    /**
     * Extract the department name from a department response.
     *
     * @since    1.2.0
     * @param    mixed     $department_data    Array from get_department().
     * @return   string
     */
    public function extract_department_name($department_data) {
        return isset($department_data['data']['attributes']['name'])
            ? $department_data['data']['attributes']['name']
            : '';
    }

    /**
     * Extract location names / countries from a locations response.
     *
     * @since    1.2.0
     * @param    mixed     $data    Array from get_locations().
     * @return   array              Keys: locations (string), countries (string).
     */
    public function extract_locations($data) {
        $locations = array();
        $countries = array();

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

        return array(
            'locations' => implode(', ', $locations),
            'countries' => implode(', ', array_unique($countries)),
        );
    }

    /**
     * Get role name for a job.
     *
     * @since    1.2.0
     * @param    string    $job_id    The job ID.
     * @return   string
     */
    public function get_role_name($job_id) {
        $role_data = $this->get_role($job_id);
        return isset($role_data['data']['attributes']['name'])
            ? $role_data['data']['attributes']['name']
            : '';
    }

    /**
     * Helper: find a field value across all job definitions by job ID.
     *
     * @since    1.2.0
     * @param    string    $job_id    The job ID to look up.
     * @param    string    $field     The field name (e.g. 'department_id').
     * @return   mixed                The field value, or null if not found.
     */
    private function find_job_field($job_id, $field) {
        foreach ($this->jobs_data as $job_def) {
            if ((string) $job_def['id'] === (string) $job_id) {
                return isset($job_def[$field]) ? $job_def[$field] : null;
            }
        }
        return null;
    }
}
