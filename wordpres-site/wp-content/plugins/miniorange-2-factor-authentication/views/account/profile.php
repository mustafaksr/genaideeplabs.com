<?php
/**
 * This file contains the html UI for the miniOrange account details.
 *
 * @package miniorange-2-factor-authentication/views
 */

// Needed in both.

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

echo '
    <div class="mo2f_table_layout">
        <div>
            <div style="width:85%">
                <h4>Thank You for registering with miniOrange.
                    <div style="float: right;">';

					echo '</div>
                </h4>
                <h3>Your Profile</h3>
                <h2 >
                 <a id="mo2f_transaction_check" class="button button-primary button-large" style ="background-color: #000000">Refresh Available Email and SMS</a>
               </h2>
                <table border="1" style="background-color:#FFFFFF; border:1px solid #CCCCCC; border-collapse: collapse; padding:0px 0px 0px 10px; margin:2px; width:100%">
                    <tr>
                        <td style="width:45%; padding: 10px;">Username/Email</td>
                        <td style="width:55%; padding: 10px;">' . esc_html( $email ) . '</td>
                    </tr>
                    <tr>
                        <td style="width:45%; padding: 10px;">Customer ID</td>
                        <td style="width:55%; padding: 10px;">' . esc_html( $key ) . '</td>
                    </tr>
                    <tr>
                        <td style="width:45%; padding: 10px;">API Key</td>
                        <td style="width:55%; padding: 10px;">' . esc_html( $api ) . '</td>
                    </tr>
                    <tr>
                        <td style="width:45%; padding: 10px;">Token Key</td>
                        <td style="width:55%; padding: 10px;">' . esc_html( $token ) . '</td>
                    </tr>

                    <tr>
                        <td style="width:45%; padding: 10px;">Remaining Email transactions</td>
                        <td style="width:55%; padding: 10px;">' . esc_html( $email_transactions ) . '</td>
                    </tr>
                    <tr>
                        <td style="width:45%; padding: 10px;">Remaining SMS transactions</td>
                        <td style="width:55%; padding: 10px;">' . esc_html( $sms_transactions ) . '</td>
                    </tr>

                </table>
                <br/>
                <div class="mo2fa_text-align-center">';
				echo '
                <a id="mo_logout" class="button button-primary button-large" >Remove Account and Reset Settings</a>
                </div>
                <p><a href="#mo_wpns_forgot_password_link">Click here</a> if you forgot your password to your miniOrange account.</p>
            </div>
        </div>
    </div>
	<form id="forgot_password_form" method="post" action="">
		<input type="hidden" name="option" value="mo_wpns_reset_password" />
        <input type="hidden" name="mo2f_general_nonce" value=" ' . esc_attr( wp_create_nonce( 'miniOrange_2fa_nonce' ) ) . ' " />
	</form>
	
	<script>
		jQuery(document).ready(function(){
			$(\'a[href="#mo_wpns_forgot_password_link"]\').click(function(){
				$("#forgot_password_form").submit();
			});
		});
	</script>';

?>
	<script type="text/javascript">
		jQuery(document).ready(function()
		{
			var nonce = '<?php echo esc_js( wp_create_nonce( 'LoginSecurityNonce' ) ); ?>';
			jQuery("#mo_logout").click(function()
			{
				var data =  
				{
					'action'                  : 'wpns_login_security',
					'wpns_loginsecurity_ajax' : 'wpns_logout_form',
					'nonce'                   : nonce  
				};
				jQuery.post(ajaxurl, data, function(response) {
					window.location.reload(true);
				});
			});
			jQuery("#mo2f_transaction_check").click(function()
			{
				var nonce = '<?php echo esc_js( wp_create_nonce( 'LoginSecurityNonce' ) ); ?>';
				var data =  
				{
					'action'                  : 'wpns_login_security',
					'wpns_loginsecurity_ajax' : 'wpns_check_transaction', 
					'nonce'                   : nonce  

				};
				jQuery.post(ajaxurl, data, function(response) {
					window.location.reload(true);
				});
			});
		});
	</script>
