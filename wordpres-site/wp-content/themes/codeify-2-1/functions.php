<?php
//sssss
function enqueue_gemini_script() {
    wp_enqueue_script('gemini-script', get_template_directory_uri() . '/js/gemini.js', array(), '1.0', true);
}

add_action('wp_enqueue_scripts', 'enqueue_gemini_script');

/* function enqueue_bundle_script() {
    wp_enqueue_script('bundle-script', get_template_directory_uri() . '/js/bundle.js', array(), '1.0', true);
} */

//add_action('wp_enqueue_scripts', 'enqueue_bundle_script');

add_theme_support( 'menus' );
add_theme_support( 'theme_support' );
function custom_logout_link() {
    return wp_logout_url( home_url() );
}
/*add_filter( 'show_admin_bar', '__return_true' );*/


function enqueue_custom_application_passwords_script() {
    // Enqueue the JavaScript file
    wp_enqueue_script('custom-application-passwords-script', get_template_directory_uri() . '/js/custom-application-passwords.js', array('jquery'), null, true);

    // Localize the script to pass the ajaxurl and nonce
    wp_localize_script('custom-application-passwords-script', 'customAppPasswords', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('custom_app_passwords_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'enqueue_custom_application_passwords_script');


/* Wordpress aplication password shortcode*/

function custom_application_passwords_shortcode() {
    ob_start();
    ?>
<style>
    .app-passwords-container {
        margin-top: 20px;
    }
    .app-passwords-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }
    .app-passwords-table th,
    .app-passwords-table td {
        padding: 8px;
        border: 1px solid #ddd;
        text-align: left;
    }
    .app-passwords-table th {
        background-color: #000a03;
        font-weight: bold;
    }
    .revoke-button {
        padding: 6px 10px;
        background-color: #dc3545;
        color: white;
        border: none;
        cursor: pointer;
    }
            .transaction-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        border-radius: 10px;
        overflow: hidden; /* Ensures the border-radius is applied to the table */
    }
    .transaction-table th, .transaction-table td {
        padding: 10px;
        border: 1px solid #ddd;
        text-align: left;
    }
    .transaction-table th {
        background-color: #000a03;
    }
    </style>
    <style>
    .tooltip {
          position: relative;
          display: inline-block;
      }
      
      .tooltip::after {
          content: attr(data-tooltip);
          position: absolute;
          background-color: black;
          color: white;
          padding: 5px;
          border-radius: 5px;
          width: 200px; /* Adjust as needed */
          left: 50%;
          bottom: 125%;
          transform: translateX(-50%);
          opacity: 0;
          visibility: hidden;
          transition: opacity 0.3s, visibility 0.3s;
      }
      
      .tooltip:hover::after {
          opacity: 0.75;
          visibility: visible;}
    </style>
  <div>  <form id="create-app-password-form">
        <label for="pass_name">Name of Application Password:</label>
        <input type="text" id="pass_name" name="pass_name" required><br>
        <button type="submit" class="wp-block-button__link wp-element-button tooltip" data-tooltip="Enter app password name and click create to create app password." >Create Application Password</button>
    </form><br>

    <div id="new-password-notice" style="display:none;">
	<h3>Use below password for access apps.</h3>
        <p>Your new password for <span id="new-pass-name"></span> is: </p>
        <p id="new-pass-value" style="font-weight: bold;"></p>
        <p>Be sure to save this in a safe location. You will not be able to retrieve it.</p>
    </div><br>

    <button id="list-app-passwords" class="wp-block-button__link wp-element-button tooltip" data-tooltip="APP Passwords table will be shown below when click list.">List Application Passwords</button><br>
    <h3>APP Passwords</h3>
</div>
    <div id="app-passwords-list"></div>
 
    <script>

        jQuery(document).ready(function($) {
            $('#create-app-password-form').submit(function(e) {
                e.preventDefault();
                var pass_name = $('#pass_name').val();
                $.post(customAppPasswords.ajaxurl, {
                    action: 'create_app_password',
                    pass_name: pass_name
                }, function(response) {
                    if (response.success) {
                        $('#new-pass-name').text(pass_name);
                        $('#new-pass-value').text(response.data);
                        $('#new-password-notice').show();
                    } else {
                        alert('Error: ' + response.data);
                    }
                });
            });

            $('#list-app-passwords').click(function() {
                $.post(customAppPasswords.ajaxurl, {
                    action: 'list_app_passwords'
                }, function(response) {
                    if (response.success) {
                        $('#app-passwords-list').html(response.data);
                    } else {
                        alert('Error: ' + response.data);
                    }
                });
            });

            $('#revoke-app-password-form').submit(function(e) {
                e.preventDefault();
                var pass_name_revoke = $('#pass_name_revoke').val();
                $.post(customAppPasswords.ajaxurl, {
                    action: 'revoke_app_password',
                    pass_name_revoke: pass_name_revoke
                }, function(response) {
                    if (response.success) {
                        alert('Application Password revoked: ' + response.data);
                    } else {
                        alert('Error: ' + response.data);
                    }
                });
            });

            $(document).on('click', '.revoke-password', function() {
                var uuid = $(this).data('uuid');
                $.post(customAppPasswords.ajaxurl, {
                    action: 'revoke_app_password_by_uuid',
                    uuid: uuid
                }, function(response) {
                    if (response.success) {
                        alert('Application Password revoked: ' + response.data);
                        $('#list-app-passwords').click(); // Refresh the list
                    } else {
                        alert('Error: ' + response.data);
                    }
                });
            });
        });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('custom_application_passwords', 'custom_application_passwords_shortcode');


/* Application password AJAx*/

function handle_create_app_password() {
    if (!isset($_POST['pass_name']) || empty($_POST['pass_name'])) {
        wp_send_json_error('Application Password name is required.');
    }

    $pass_name = sanitize_text_field($_POST['pass_name']);
    $user_id = get_current_user_id();

    if (!$user_id) {
        wp_send_json_error('User not logged in.');
    }

    $result = WP_Application_Passwords::create_new_application_password($user_id, array(
        'name' => $pass_name
    ));

    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    } else {
        wp_send_json_success($result[0]); // Return the application password
    }
}
add_action('wp_ajax_create_app_password', 'handle_create_app_password');

function handle_list_app_passwords() {
    $user_id = get_current_user_id();
    $user_info = get_current_user_info();
    $user_email = $user_info['user_email'];
    if (!$user_id) {
        wp_send_json_error('User not logged in.');
    }

    $passwords = WP_Application_Passwords::get_user_application_passwords($user_id);

    if (empty($passwords)) {
        wp_send_json_success('No application passwords found.');
    } else {
        $output = '<table class="transaction-table"><tr><th>Name</th><th>Created</th><th>Last Used</th><th>Application Password HASH (API)</th><th>Last IP</th><th>Revoke</th></tr>';
        foreach ($passwords as $password) {
            $created_date = date_i18n('F j, Y', $password['created']);
            $last_used_date = $password['last_used'] ? date_i18n('F j, Y', $password['last_used']) : '—';

            $output .= '<tr>';
            $output .= '<td>' . esc_html($password['name']) . '</td>';
            $output .= '<td>' . esc_html($created_date) . '</td>';
            $output .= '<td>' . esc_html($last_used_date) . '</td>';
            $output .= '<td>' . esc_html($password['password']) . '</td>'; // Display the password from $password array
            $output .= '<td>' . esc_html($password['last_ip'] ? $password['last_ip'] : '—') . '</td>';
            $output .= '<td><button class="revoke-password wp-block-button__link wp-element-button" data-uuid="' . esc_attr($password['uuid']) . '">Revoke</button></td>';
            $output .= '</tr>';
        }
        $output .= '</table>';
        wp_send_json_success($output);
    }
}
add_action('wp_ajax_list_app_passwords', 'handle_list_app_passwords');


function handle_revoke_app_password() {
    if (!isset($_POST['pass_name_revoke']) || empty($_POST['pass_name_revoke'])) {
        wp_send_json_error('Application Password name is required.');
    }

    $pass_name_revoke = sanitize_text_field($_POST['pass_name_revoke']);
    $user_id = get_current_user_id();

    if (!$user_id) {
        wp_send_json_error('User not logged in.');
    }

    $passwords = WP_Application_Passwords::get_user_application_passwords($user_id);

    foreach ($passwords as $password) {
        if ($password['name'] === $pass_name_revoke) {
            WP_Application_Passwords::delete_application_password($user_id, $password['uuid']);
            wp_send_json_success('Application Password revoked: ' . $pass_name_revoke);
            return;
        }
    }

    wp_send_json_error('Application Password not found.');
}
add_action('wp_ajax_revoke_app_password', 'handle_revoke_app_password');

function handle_revoke_app_password_by_uuid() {
    if (!isset($_POST['uuid']) || empty($_POST['uuid'])) {
        wp_send_json_error('Application Password UUID is required.');
    }

    $uuid = sanitize_text_field($_POST['uuid']);
    $user_id = get_current_user_id();

    if (!$user_id) {
        wp_send_json_error('User not logged in.');
    }

    $deleted = WP_Application_Passwords::delete_application_password($user_id, $uuid);

    if ($deleted) {
        wp_send_json_success('Application Password revoked.');
    } else {
        wp_send_json_error('Failed to revoke Application Password.');
    }
}
add_action('wp_ajax_revoke_app_password_by_uuid', 'handle_revoke_app_password_by_uuid');


/*USER BALANCE TRANSACTION SHORTCODE*/

function show_last_transaction_balance_and_table() {
    global $wpdb;
    $user_id = get_current_user_id();

    if (!$user_id) {
        return 'User not logged in.';
    }

    // Get the last transaction for the user
    $last_transaction = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM am_users_balance WHERE user_id = %d ORDER BY transaction_id DESC LIMIT 1",
            $user_id
        )
    );

    if (!$last_transaction) {
        return 'No transactions found.';
    }

    // Get all transactions for the user
    $transactions = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM am_users_balance WHERE user_id = %d ORDER BY transaction_id DESC",
            $user_id
        )
    );

    ob_start();
    ?>
    <div>
        <h3>Account Balance: <?php echo esc_html($last_transaction->balance); ?>$</h3>
    </div>
    <table class="transaction-table" >
        <thead>
            <tr>
                <th style="width:150px;">Transaction<br>ID</th>
                <th>UUID</th>
                <th style="width:120px;">Balance</th>
                <th style="width:150px;">Transaction<br>Time</th>
                <th style="width:150px;">Transaction<br>Type</th>
                <th style="width:150px;">Transaction<br>Amount</th>
                <th style="width:150px;">Transaction<br>Description</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions as $transaction) : ?>
                <tr>
                    <td><?php echo esc_html($transaction->transaction_id); ?></td>
                    <td><?php echo esc_html($transaction->uuid); ?></td>
                    <td><?php echo esc_html($transaction->balance); ?></td>
                    <td><?php echo esc_html($transaction->transaction_time); ?></td>
                    <td><?php echo esc_html($transaction->transaction_type); ?></td>
                    <td><?php echo esc_html($transaction->transaction_amount); ?></td>
                    <td><?php echo esc_html($transaction->transaction_description); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<style>
    .transaction-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        border-radius: 10px;
        overflow: hidden; /* Ensures the border-radius is applied to the table */
    }
    .transaction-table th, .transaction-table td {
        padding: 10px;
        border: 1px solid #ddd;
        text-align: left;
    }
    .transaction-table th {
        background-color: #000a03;
    }
</style>

    <?php
    return ob_get_clean();
}
add_shortcode('show_last_transaction_balance', 'show_last_transaction_balance_and_table');


/*AJAX Handler for BALANCE */

function get_transactions_ajax() {
    global $wpdb;
    $user_id = get_current_user_id();

    if (!$user_id) {
        wp_send_json_error('User not logged in.');
    }

    $transactions = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM am_users_balance WHERE user_id = %d ORDER BY transaction_id DESC",
            $user_id
        )
    );

    if (!$transactions) {
        wp_send_json_error('No transactions found.');
    }

    wp_send_json_success($transactions);
}
add_action('wp_ajax_get_transactions', 'get_transactions_ajax');

/*gemini-backend login shortcode*/

function custom_login_form_shortcode() {
    ob_start();
    ?>
    <style>
        #login-form {
            max-width: 300px;
            margin: 0 auto;
        }
        #login-form label {
            display: block;
            margin-bottom: 8px;
        }
        #login-form input[type="email"],
        #login-form input[type="password"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 16px;
            box-sizing: border-box;
        }
        .revoke-button {
            display: block;
            width: 100%;
            padding: 10px;
            background-color: #0073aa;
            color: #fff;
            border: none;
            cursor: pointer;
            text-align: center;
        }
        .revoke-button:hover {
            background-color: #005a8c;
        }
            /* Centering the login message */
        #login-message {
            text-align: center; /* Align text content center */
            margin-top: 10px;   /* Optional: Adjust margin for spacing */
        }
    </style>
    <br>
    <form id="login-form" method="post">
        <label for="user_email">Email:</label>
        <input type="email" id="user_email" name="user_email" required><br>

        <label for="user_password">Application Password:</label>
        <input type="password" id="user_password" name="user_password" required><br>

        <input type="submit" value="Login" class="revoke-button">
    </form>

    <div id="login-message"></div>
    <br>
<script>
    document.getElementById('login-form').addEventListener('submit', function(event) {
        event.preventDefault();

        var user_email = document.getElementById('user_email').value;
        var user_password = document.getElementById('user_password').value.trim(); // Ensure no leading/trailing spaces

        var formData = new FormData();
        formData.append('user_email', user_email);
        formData.append('user_password', user_password);

        fetch('https://backendgemini.genaideeplabs.com/', {
            method: 'POST',
            headers: {
                // No need to specify Content-Type for FormData
            },
            body: formData,
            credentials: 'include' // Ensures cookies are included in requests
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok ' + response.statusText);
            }
            return response.json();
        })
        .then(data => {
            document.getElementById('login-message').innerText = data.message || "Login successful";
        })
        .catch(error => {
            document.getElementById('login-message').innerText = "An error occurred: " + error.message;
        });
    });
</script>

    <?php
    return ob_get_clean();
}
add_shortcode('custom_login_form', 'custom_login_form_shortcode');

/*get current user info*/

function get_current_user_info() {
    $current_user = wp_get_current_user();
    if ( $current_user->exists() ) {
        return array(
            'ID' => $current_user->ID,
            'user_login' => $current_user->user_login,
            'user_email' => $current_user->user_email,
            'display_name' => $current_user->display_name,
        );
    } else {
        return null;
    }
}


function enqueue_my_script() {
    wp_enqueue_script( 'my-script', get_template_directory_uri() . '/js/my-script.js', array('jquery'), '1.0', true );
    
    $current_user_info = get_current_user_info();
    wp_localize_script( 'my-script', 'currentUser', $current_user_info );
}
add_action( 'wp_enqueue_scripts', 'enqueue_my_script' );

/* APPLICATION PASSWORD WORDPRESS  */
// Add this code to your theme's functions.php or a custom plugin

// Add this code to your theme's functions.php or a custom plugin

add_action('wp_ajax_get_latest_application_password', 'get_latest_application_password');

function get_latest_application_password() {
    // Check for nonce security if required
    if (!isset($_POST['user_id'])) {
        wp_send_json_error(['message' => 'Invalid request']);
        return;
    }

    $user_id = intval($_POST['user_id']);
    $user = get_userdata($user_id);

    if (!$user) {
        wp_send_json_error(['message' => 'User not found']);
        return;
    }

    $user_email = $user->user_email;
    $application_password = get_application_password($user_email);

    if ($application_password) {
        wp_send_json_success(['application_password' => $application_password]);
    } else {
        wp_send_json_error(['message' => 'Application password not found']);
    }
}

function get_application_password($user_email) {
    global $wpdb;

    $user = get_user_by('email', $user_email);

    if (!$user) {
        return null;
    }

    $user_id = $user->ID;

    $application_password_meta = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT meta_value FROM $wpdb->usermeta WHERE user_id = %d AND meta_key = %s ORDER BY umeta_id DESC LIMIT 1",
            $user_id,
            '_application_passwords'
        )
    );

    if (!$application_password_meta) {
        return null;
    }

    $application_passwords = unserialize($application_password_meta);

    if (is_array($application_passwords) && isset($application_passwords[0]['password'])) {
        $application_password_hash = $application_passwords[0]['password'];
        return $application_password_hash;
    }

    return null;
}



?>
