<?php
// Button_TestAPI.php

// Ensure WordPress is loaded
defined('ABSPATH') or die('No script kiddies please!');

function coswift_test_api_button() {
    // Button for testing the API
    echo '<form action="" method="post">';
    echo '<input type="submit" name="test_api" class="button button-primary" value="Test API">';
    echo '</form>';
}
