<?php
/**
 * TeamTailor API integration
 *
 * @since      1.0.0
 *
 * @package    TeamTailor_Integrator
 * @subpackage TeamTailor_Integrator/includes
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * TeamTailor API integration.
 *
 * Handles all API communications with TeamTailor using the WordPress HTTP API.
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
     * Whether to use mock data instead of the live API.
     *
     * @since    1.2.0
     * @access   private
     * @var      bool
     */
    private $use_mock_data = false;

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
        $this->use_mock_data = (bool) get_option('teamtailor_integrator_use_mock_data', false);
    }

    /**
     * Output debug information when debug mode is enabled.
     *
     * @since    1.0.0
     * @param    string    $message    The debug message.
     */
    private function debug( $message ) {
        if ( $this->debug_mode ) {
            echo '<div class="teamtailor-status-box">';
            echo '<p><strong>▶</strong> ' . esc_html( $message ) . '</p>';
            echo '</div>';
        }
    }

    /**
     * Get the common HTTP headers for API requests.
     *
     * @since    1.0.0
     * @return   array    The HTTP headers as an associative array.
     */
    private function get_headers() {
        $headers = array(
            'Authorization' => "Token token={$this->api_key}",
            'X-Api-Version' => $this->api_version,
            'Content-Type'  => 'application/json',
        );

        $this->debug( 'Request Headers: ' . wp_json_encode( $headers ) );

        return $headers;
    }

    /**
     * Get the HTTP request arguments for wp_remote_get/wp_remote_post.
     *
     * @since    1.1.1
     * @return   array    The request arguments.
     */
    private function get_request_args() {
        return array(
            'headers' => $this->get_headers(),
            'timeout' => 30,
        );
    }

    /**
     * Display an error notice.
     *
     * @since    1.1.1
     * @param    string    $message    The error message.
     * @param    string    $detail     Optional detail information.
     */
    private function show_error( $message, $detail = '' ) {
        echo '<div class="teamtailor-notice teamtailor-notice-error">';
        echo '<p>' . esc_html( $message ) . '</p>';
        if ( ! empty( $detail ) && $this->debug_mode ) {
            echo '<p>' . esc_html( $detail ) . '</p>';
        }
        echo '</div>';
    }

    /**
     * Make a GET request to the TeamTailor API using the WordPress HTTP API.
     *
     * When mock mode is enabled, returns realistic test data instead of
     * calling the live API — no API key required.
     *
     * @since    1.0.0
     * @param    string    $endpoint    The API endpoint.
     * @return   mixed                  The decoded API response, or false on error.
     */
    public function fetch_data( $endpoint ) {
        // ----- Mock data mode -------------------------------------------------
        if ( $this->use_mock_data ) {
            return $this->mock_fetch( $endpoint );
        }

        $url = $this->base_url . $endpoint;
        $args = $this->get_request_args();

        $this->debug( "API Request: $url" );

        $response = wp_remote_get( $url, $args );

        // Check for WordPress-level HTTP errors.
        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();

            if ( $this->debug_mode ) {
                $this->show_error(
                    /* translators: %s: HTTP error message from WordPress HTTP API */
                    sprintf( __( 'HTTP Error: %s', 'teamtailor-integrator' ), $error_message )
                );
            } else {
                $this->show_error(
                    __( 'Error connecting to TeamTailor API. Enable debugging for more details.', 'teamtailor-integrator' )
                );
            }

            return "Error: $error_message";
        }

        $http_code = wp_remote_retrieve_response_code( $response );
        $body      = wp_remote_retrieve_body( $response );

        $this->debug( "API Response - HTTP Code: $http_code" );

        // Check HTTP response code.
        if ( $http_code < 200 || $http_code >= 300 ) {
            $this->show_error(
                /* translators: %d: HTTP status code */
                sprintf( __( 'API returned non-successful code: %d', 'teamtailor-integrator' ), $http_code ),
                $this->debug_mode ? substr( $body, 0, 500 ) : ''
            );
        }

        // Try to decode JSON.
        $decoded = json_decode( $body, true );

        if ( JSON_ERROR_NONE !== json_last_error() ) {
            $this->show_error(
                /* translators: %s: JSON error message */
                sprintf( __( 'JSON decode error: %s', 'teamtailor-integrator' ), json_last_error_msg() ),
                $this->debug_mode ? substr( $body, 0, 500 ) : ''
            );
            return false;
        }

        return $decoded;
    }

    /**
     * Route a mock-data fetch to the correct mock API method.
     *
     * @since    1.2.0
     * @param    string    $endpoint    The API endpoint being requested.
     * @return   mixed                  Mock response array.
     */
    private function mock_fetch( $endpoint ) {
        if ( ! class_exists( 'TeamTailor_Integrator_Mock_API' ) ) {
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-teamtailor-integrator-mock-api.php';
        }

        $this->debug( "Mock API request: $endpoint" );

        $mock = new TeamTailor_Integrator_Mock_API( $this->debug_mode );

        // Route by endpoint pattern.
        if ( 'jobs' === $endpoint ) {
            return $mock->get_jobs();
        }

        if ( 'company' === $endpoint ) {
            return $mock->get_company();
        }

        if ( preg_match( '#^jobs/(\d+)/department$#', $endpoint, $m ) ) {
            return $mock->get_department( $m[1] );
        }

        if ( preg_match( '#^jobs/(\d+)/locations$#', $endpoint, $m ) ) {
            return $mock->get_locations( $m[1] );
        }

        if ( preg_match( '#^jobs/(\d+)/role$#', $endpoint, $m ) ) {
            return $mock->get_role( $m[1] );
        }

        $this->debug( "Mock API: unknown endpoint '$endpoint', returning empty data." );
        return array( 'data' => array() );
    }

    /**
     * Get jobs from TeamTailor (or mock data when mock mode is enabled).
     *
     * @since    1.0.0
     * @return   mixed    The jobs data or false on error.
     */
    public function get_jobs() {
        if ( $this->use_mock_data ) {
            $this->debug( __( 'Fetching jobs from mock data (Meridian ERP)...', 'teamtailor-integrator' ) );
        } else {
            $this->debug( __( 'Fetching jobs from TeamTailor API...', 'teamtailor-integrator' ) );
        }

        if ( $this->debug_mode ) {
            echo '<div class="teamtailor-status-box">';
            echo '<p><strong>▶</strong> ' . esc_html__( 'API call: jobs', 'teamtailor-integrator' ) . '</p>';
            echo '</div>';
        }

        $result = $this->fetch_data( 'jobs' );

        if ( is_array( $result ) && isset( $result['data'] ) ) {
            $job_count = count( $result['data'] );

            // Show a success message for non-AJAX requests only.
            if ( ! wp_doing_ajax() ) {
                echo '<div class="teamtailor-notice teamtailor-notice-success">';
                if ( $this->use_mock_data ) {
                    echo '<p><strong>' . esc_html__( 'Mock data mode active — using test data from Meridian ERP', 'teamtailor-integrator' ) . '</strong></p>';
                } else {
                    echo '<p><strong>' . esc_html__( 'Successfully connected to TeamTailor!', 'teamtailor-integrator' ) . '</strong></p>';
                }
                echo '</div>';
            }

            /* translators: %d: number of jobs fetched */
            $this->debug( sprintf( __( 'Successfully fetched %d jobs from API', 'teamtailor-integrator' ), $job_count ) );
        } else {
            $this->show_error(
                __( 'API Error: Invalid response format. Expected array with data field.', 'teamtailor-integrator' ),
                $this->debug_mode ? print_r( $result, true ) : ''
            );
        }

        return $result;
    }

    /**
     * Extract the department name from department data.
     *
     * @since    1.0.0
     * @param    array    $department_data    The department data.
     * @return   string                       The department name.
     */
    public function extract_department_name( $department_data ) {
        return isset( $department_data['data']['attributes']['name'] ) ? $department_data['data']['attributes']['name'] : '';
    }

    /**
     * Extract location names and countries from locations data.
     *
     * @since    1.0.0
     * @param    array    $data    The locations data.
     * @return   array             The extracted locations and countries.
     */
    public function extract_locations( $data ) {
        $locations = array();
        $countries = array();

        if ( isset( $data['data'] ) && is_array( $data['data'] ) ) {
            foreach ( $data['data'] as $item ) {
                if ( isset( $item['attributes']['name'] ) ) {
                    $locations[] = $item['attributes']['name'];
                }
                if ( isset( $item['attributes']['country'] ) ) {
                    $countries[] = $item['attributes']['country'];
                }
            }
        }

        return array(
            'locations' => implode( ', ', $locations ),
            'countries' => implode( ', ', array_unique( $countries ) ),
        );
    }

    /**
     * Fetch and extract the role name for a job.
     *
     * @since    1.0.0
     * @param    string    $job_id    The job ID.
     * @return   string               The role name.
     */
    public function get_role_name( $job_id ) {
        $role_data = $this->fetch_data( "jobs/$job_id/role" );
        return isset( $role_data['data']['attributes']['name'] ) ? $role_data['data']['attributes']['name'] : '';
    }

    /**
     * Fetch and extract the company name.
     *
     * @since    1.0.0
     * @return   string    The company name.
     */
    public function get_company_name() {
        $company_data = $this->fetch_data( 'company' );
        return isset( $company_data['data']['attributes']['name'] ) ? $company_data['data']['attributes']['name'] : '';
    }
}
