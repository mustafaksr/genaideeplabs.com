function display_current_user_info() {
    // Check if the user is logged in
    if (is_user_logged_in()) {
        // Get the current user ID
        $user_id = get_current_user_id();
        
        // Get user data
        $user_info = get_userdata($user_id);

        // Get username
        $username = $user_info->user_login;

        // Display the username
        echo 'Current logged in user: ' . $username;
    } else {
        echo 'No user is logged in.';
    }
}

// Hook into 'wp_head' to display username in the header
add_action('wp_head', 'display_current_user_info');
