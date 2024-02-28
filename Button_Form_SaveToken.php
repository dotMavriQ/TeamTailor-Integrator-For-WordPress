<?php
// Ensure WordPress is loaded
defined('ABSPATH') or die('No script kiddies please!');

function coswift_save_token_form() {
    // Check if the form has been submitted
    if (isset($_POST['coswift_api_token'])) {
        $api_token = sanitize_text_field($_POST['coswift_api_token']);

        $error = false;

        // Validate the API token
        if (strlen($api_token) !== 40) {
            // Display error message
            echo '<div class="notice notice-error is-dismissible"><p>API Token must be exactly 40 characters long.</p></div>';
            $error = true;
        }
        
        
        if (!$error) {
            update_option('coswift_api_token', $api_token);
            update_option('coswift_custom_field_name', sanitize_text_field($_POST['coswift_custom_field_name']));
            update_option('coswift_fields', serialize($_POST['coswift_fields']));
            echo '<div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>';
        }
    }

    $possible_fields = [
        'departments' => 'Departments',
        'locations' => 'Locations',
        'roles' => 'Roles',
        'roles' => 'Roles',
        'country' => 'Country',
        'company' => 'Company',
        'tt_custom' => 'Custom Field',
    ];
    $locations = maybe_unserialize(get_option('coswift_fields', []));
    if (!is_array($locations)) {
        var_dump("Varning! locations is broken");
        var_dump($locations);
        $locations = [];
    }
    // Form HTML
    ?>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <div class="coswift-token-form">
        <form method="post" action="">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">API Token:</th>
                    <td><input type="text" name="coswift_api_token" value="<?php echo esc_attr(get_option('coswift_api_token')); ?>" style="width: 333px;" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Fields:</th>
                    <td>
                        <select multiple name="coswift_fields[]" style="width: 333px;">
                            <?php foreach($possible_fields as $key => $title) : ?>
                                <option value="<?php echo $key ?>" <?php if (in_array($key, $locations)) { echo 'selected'; } ?>><?php echo $title ?></option>
                            <?php endforeach; ?>
                        </select>
                        <script defer>
                            (function($) {    
                                try {
                                    $('[name="coswift_fields[]"]').select2();
                                } catch(e) {
                                    console.log(e);
                                }
                            })(jQuery);
                        </script>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Rename Custom Field:</th>
                    <td><input type="text" name="coswift_custom_field_name" value="<?php echo esc_attr(get_option('coswift_custom_field_name')); ?>" style="width: 333px;" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// You can call this function in your settings page function to display the form
