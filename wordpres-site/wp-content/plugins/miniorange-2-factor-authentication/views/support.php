<?php
/**
 * Support form of the plugin.
 *
 * @package miniorange-2-factor-authentication/views
 */

// Needed in both.

use TwoFA\Helper\MoWpnsConstants;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

global $image_path;
echo '

                <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
       <div class="mo2f_table_layout_support">      
            <div class="mo2f-support-form-flex">
                <div style="display: flex;  flex-direction: column;width:100%;padding 5px;">
                    <h2 style="margin-left:5px; font-weight:bold">Contact Us</h2>
                    <p">Need any help?</p>
                </div>
            </div>
            <a href="https://wordpress.org/support/plugin/miniorange-2-factor-authentication/" target="_blank" class="mo2f_raise_support_ticket button button-primary button-large" value="Raise a Support Ticket"><i style="margin-left: -8px;" class="fab fa-wordpress"></i> Raise a Support Ticket</a>
            <h2 style="margin:5px;">OR</h2>
            <p style="margin:7px;">Send us a query!</p>
            <div class="mo2f-form-div">
            <form name="f" id ="mo2f_support_form" method="post" action="">
            <input type="hidden" name="option" value="mo_wpns_send_query"/>
            <input type="hidden" name="nonce" value="' . esc_attr( $support_form_nonce ) . '">
            <table class="mo_wpns_settings_table">
                <tr><td>
                    <input type="email" class="mo2f-support-input" id="mo2f_query_email" name="mo2f_query_email" value="' . esc_attr( $email ) . '" placeholder="Enter your email" required />
                    </td>
                </tr>
                <tr><td>
                    <input type="phone" class="mo2f-support-input" name="mo2f_query_phone" id="mo2f_query_phone" value="' . esc_attr( $phone ) . '" placeholder="Enter your phone"/>
                    </td>
                </tr>
                <tr>
                    <td>
                        <textarea id="mo2f_query" name="mo2f_query" class="mo2f-support-input" style="resize: vertical;width:100%" cols="52" rows="4" placeholder="Write your query here"></textarea>
                    </td>
                </tr>
            </table>
            <div id="mo_2fa_plugin_configuration" style="margin:5px;">
            <input type="hidden" name="mo_2fa_plugin_configuration" value="mo2f_send_plugin_configuration"/>
                        <input type="checkbox" id="mo2f_send_configuration"
                               name="mo2f_send_configuration" 
                               value="1" checked
                        <h3>Send plugin Configuration</h3>
<br />
</div>

            <input type="submit" name="send_query" id="mo2f_send_query" value="Submit Query" class="button button-primary button-large mo2f_send_query"/>
            <br>

        </form>     
            </div>
        </div>
        
        <div class="mo2f_whatsapp_adv_container">
        <div class="mo2f_whatsapp_adv_img">
                <div><img src="' . esc_url( $image_path ) . 'includes/images/whatsapp.png" class="mo2f_whatsapp_adv_logo"></div>
                <div style="margin-left:5px"><span class="mo2f_whatsapp_adv_text">OTP Over WhatsApp</span></div>
        </div>
        <div class="mo2f_whatsapp_adv_content">
            <b>Get 2-Factor Authentication code on WhatsApp.</b>
        </div>
        <div >
            <a href="' . esc_url( MoWpnsConstants::OTP_OVER_WHATSAPP_PAGE_LINK ) . '"  target="_blank" class="mo2f_whatsapp_adv_button"><b>GET TRIAL</b></a>
        </div>
        </div>
        <script>
        jQuery("#mo2f_send_query").click(function(){
            jQuery("#mo2f_support_form").submit(function(){
                jQuery("#mo2f_send_query").prop("disabled", true);
            });
        });
        var query_submitted = ' . esc_js( $query_submitted ) . ';
        if( query_submitted ){
            jQuery("#mo2f_send_query").prop("disabled", true);
        }
        else{
            jQuery("#mo2f_send_query").prop("disabled", false); 
        }
        </script>
        ';
