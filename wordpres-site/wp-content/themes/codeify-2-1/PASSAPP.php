<?php
/*
Template Name: Application Passwords
*/
get_header();

if ( is_user_logged_in() ) {
    $current_user = wp_get_current_user();
    ?>

    <h2><?php echo 'Application Passwords for ' . $current_user->user_login; ?></h2>
    <form method="post" action="">
        <input type="text" name="app_password_name" placeholder="New Application Password Name">
        <button type="submit" name="generate_app_password">Generate</button>
    </form>

    <?php
    if ( isset( $_POST['generate_app_password'] ) && ! empty( $_POST['app_password_name'] ) ) {
        $app_password_name = sanitize_text_field( $_POST['app_password_name'] );
        list( $new_password, $new_item ) = WP_Application_Passwords::create_new_application_password( $current_user->ID, array(
            'name' => $app_password_name,
        ) );

        echo '<p>New Application Password: <strong>' . $new_password . '</strong></p>';
    }

    $app_passwords = WP_Application_Passwords::get_user_application_passwords( $current_user->ID );
    if ( ! empty( $app_passwords ) ) {
        echo '<ul>';
        foreach ( $app_passwords as $app_password ) {
            echo '<li>' . esc_html( $app_password['name'] ) . ' - Last used: ' . esc_html( $app_password['last_used'] ) . '</li>';
        }
        echo '</ul>';
    }
} else {
    echo '<p>You need to log in to manage application passwords.</p>';
}

get_footer();
?>
