<?php
/**
 * This file contains functions regarding advanced IP Blocking and Whitelisting
 *
 * @package miniorange-2-factor-authentication/views
 */

// Needed in both.

use TwoFA\Helper\MoWpnsHandler;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?><div class="mo_wpns_divided_layout_tab">
	<div class="nav-tab-wrapper">
		<button class="nav-tab" onclick="mo2f_wpns_block_function(this)" id="mo2f_block_list">IP Black list</button>
		<button class="nav-tab" onclick="mo2f_wpns_block_function(this)" id="mo2f_adv_block">Advanced Blocking</button>

	</div>
</div>
<?php
global $mo2f_dir_name;
$setup_dirname = $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'link-tracer.php';
require $setup_dirname;
?>
<div id="mo2f_block_list_div" class="tabcontent">

	<div class="mo_wpns_divided_layout">
		<div class="mo_wpns_setting_layout" id="mo2f_manual_ip_blocking">
			<h2>Manual IP Blocking <a href='<?php echo esc_url( $two_factor_premium_doc['Manual IP Blocking'] ); ?>' target="_blank"><span class="dashicons dashicons-text-page" style="font-size:30px;color:#413c69;float: right;"></span></a></h2>

			<h4 class="mo_wpns_setting_layout_inside">Manually block an IP address here:&emsp;&emsp;
				<input type="text" name="ManuallyBlockIP" id="ManuallyBlockIP" required placeholder='IP address' pattern="((^|\.)((25[0-5])|(2[0-4]\d)|(1\d\d)|([1-9]?\d))){4}" style="width: 35%; height: 41px" />&emsp;&emsp;
				<input type="button" name="BlockIP" id="BlockIP" value="Manual Block IP" class="button button-primary button-large" />
			</h4>

			<h3 class="mo_wpns_setting_layout_inside"><b>Blocked IPs</b>
			</h3>
			<h4 class="mo_wpns_setting_layout_inside">&emsp;&emsp;&emsp;

				<div id="blockIPtable">
					<table id="blockedips_table" class="display">
						<thead>
							<tr>
								<th>IP Address&emsp;&emsp;</th>
								<th>Reason&emsp;&emsp;</th>
								<th>Blocked Until&emsp;&emsp;</th>
								<th>Blocked Date&emsp;&emsp;</th>
								<th>Action&emsp;&emsp;</th>
							</tr>
						</thead>
						<tbody>

							<?php
							$mo_wpns_handler = new MoWpnsHandler();
							$blockedips      = $mo_wpns_handler->get_blocked_ips();
							$whitelisted_ips = $mo_wpns_handler->get_whitelisted_ips();
							$disabled        = '';
							global $mo2f_dir_name;
							foreach ( $blockedips as $blockedip ) {
								echo "<tr class='mo_wpns_not_bold'><td>" . esc_html( $blockedip->ip_address ) . '</td><td>' . esc_html( $blockedip->reason ) . '</td><td>';
								if ( empty( $blockedip->blocked_for_time ) ) {
									echo '<span class=redtext>Permanently</span>';
								} else {
									echo esc_html( gmdate( 'M j, Y, g:i:s a', $blockedip->blocked_for_time ) );
								}
								echo '</td><td>' . esc_html( gmdate( 'M j, Y, g:i:s a', $blockedip->created_timestamp ) ) . '</td><td><a ' . esc_attr( $disabled ) . " onclick=unblockip('" . esc_js( $blockedip->id ) . "')>Unblock IP</a></td></tr>";
							}
							?>
						</tbody>
					</table>
				</div>
			</h4>
		</div>
		<div class="mo_wpns_setting_layout" id="mo2f_ip_whitelisting">
			<h2>IP Whitelisting<a href="https://developers.miniorange.com/docs/security/wordpress/wp-security/IP-blocking-whitelisting-lookup#wp-ip-whitelisting" target="_blank"><span class="dashicons dashicons-text-page" style="font-size:30px;color:#413c69;float: right;"></span></a></h2>
			<h4 class="mo_wpns_setting_layout_inside">Add new IP address to whitelist:&emsp;&emsp;
				<input type="text" name="IPWhitelist" id="IPWhitelist" required placeholder='IP address' pattern="((^|\.)((25[0-5])|(2[0-4]\d)|(1\d\d)|([1-9]?\d))){4}" style="width: 40%; height: 41px" />&emsp;&emsp;
				<input type="button" name="WhiteListIP" id="WhiteListIP" value="Whitelist IP" class="button button-primary button-large" />

			</h4>
			<h3 class="mo_wpns_setting_layout_inside">Whitelist IPs
			</h3>
			<h4 class="mo_wpns_setting_layout_inside">&emsp;&emsp;&emsp;

				<div id="WhiteListIPtable">
					<table id="whitelistedips_table" class="display">
						<thead>
							<tr>
								<th>IP Address</th>
								<th>Whitelisted Date</th>
								<th>Remove from Whitelist</th>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach ( $whitelisted_ips as $whitelisted_ip ) {
								echo "<tr class='mo_wpns_not_bold'><td>" . esc_html( $whitelisted_ip->ip_address ) . '</td><td>' . esc_html( gmdate( 'M j, Y, g:i:s a', $whitelisted_ip->created_timestamp ) ) . '</td><td><a ' . esc_attr( $disabled ) . " onclick=removefromwhitelist('" . esc_js( $whitelisted_ip->id ) . "')>Remove</a></td></tr>";
							}

							echo '			</tbody>
			</table>';
							?>
				</div>
			</h4>
		</div>



		<div class="mo_wpns_setting_layout" id="mo2f_ip_lookup">
			<h2>IP LookUp<a href='<?php echo esc_url( $two_factor_premium_doc['IP LookUp'] ); ?>' target="_blank"><span class="dashicons dashicons-text-page" style="font-size:30px;color:#413c69;float: right;"></span></a></h2>
			<h4 class="mo_wpns_setting_layout_inside">Enter IP address you Want to check:&emsp;&emsp;
				<input type="text" name="ipAddresslookup" id="ipAddresslookup" required placeholder='IP address' pattern="((^|\.)((25[0-5])|(2[0-4]\d)|(1\d\d)|([1-9]?\d))){4}" style="width: 40%; height: 41px" />&emsp;&emsp;
				<input type="button" name="LookupIP" id="LookupIP" value="LookUp IP" class="button button-primary button-large" />
			</h4>
			<div class="ip_lookup_desc" hidden></div>

			<div id="resultsIPLookup">
			</div>
		</div>
	</div>
</div>
<?php
echo '<div id="mo2f_adv_block_div" class="tabcontent">';
echo '<div>
		<div class="mo_wpns_setting_layout"  id= "mo2f_ip_range_blocking">';

echo '		<h2>IP Address Range Blocking<a href=' . esc_url( $two_factor_premium_doc['IP Address Range Blocking'] ) . ' target="_blank"><span class="dashicons dashicons-text-page" style="font-size:23px;color:#413c69;float: right;"></span></a></h2>
			You can block range of IP addresses here  ( Examples: 192.168.0.100 - 192.168.0.190 )
			<form name="f" method="post" action="" id="iprangeblockingform" >
				<input type="hidden" name="option" value="mo_wpns_block_ip_range" />
				<input type="hidden" name="mo2f_security_features_nonce" value="' . esc_attr( wp_create_nonce( 'mo2f_security_nonce' ) ) . '" />

			<br>
			<table id="iprangetable">		
';
for ( $i = 1; $i <= $range_count; $i++ ) {
	echo '<tr><td>Start IP	<input style="width :30%" type ="text" class="mo_wpns_table_textbox" name="start_' . intval( esc_html( $i ) ) . '" value ="' . esc_html( $start[ $i ] ) . '" placeholder=" e.g 192.168.0.100" />End IP	<input style="width :30%" type ="text" placeholder=" e.g 192.168.0.190" class="mo_wpns_table_textbox" value="' . esc_html( $end[ $i ] ) . '"  name="end_' . intval( esc_html( $i ) ) . '"/></td></tr>';
}
echo '
		</table>
		<a style="cursor:pointer" id="add_ran">Add IP Range</a>
			';

echo '	<br> <br><input type="submit" class="button button-primary button-large" value="Block IP range" />
				
			</form>
		</div>
		
				<input type="hidden" name="mo2f_security_features_nonce" value="' . esc_attr( wp_create_nonce( 'mo2f_security_nonce' ) ) . '" />
	</div>
	</div>
	<script>		
		
	jQuery("#add_range").click(function() {
				var last_index_name = $("#iprangeblockingtable tr:last .mo_wpns_table_textbox").attr("name");
				var splittedArray = last_index_name.split("_");
				var last_index = parseInt(splittedArray[splittedArray.length-1])+1;

				var new_row   = \'<tr><td><input style="padding:0px 10px" class="mo_wpns_table_textbox" type="text" name="range_\'+last_index+\'" value=""   placeholder=" e.g 192.168.0.100 - 192.168.0.190" /></td></tr>\';
				$("#iprangeblockingtable tr:last").after(new_row);
			});

			jQuery("#add_ran").click(function() {
				var last_index_name = $("#iprangetable tr:last .mo_wpns_table_textbox").attr("name");
				
				var splittedArray = last_index_name.split("_");
				var last_index = parseInt(splittedArray[splittedArray.length-1])+1;
				var new_row = \'<tr><td>Start IP<input style="width :30%" type ="text" class="mo_wpns_table_textbox" name="start_\'+last_index+\'" value="" placeholder=" e.g 192.168.0.100" >&nbsp;&nbsp;End IP	<input style="width :30%" type ="text" placeholder=" e.g 192.168.0.190" class="mo_wpns_table_textbox" value="" name="end_\'+last_index+\'"></td></tr>\';
				$("#iprangetable tr:last").after(new_row);
			
			});
			function mo2f_wpns_block_function(elmt){
				var tabname = elmt.id;
				var tabarray = ["mo2f_block_list","mo2f_adv_block"];
				for (var i = 0; i < tabarray.length; i++) {
					if(tabarray[i] == tabname){
						jQuery("#"+tabarray[i]).addClass("nav-tab-active");
						jQuery("#"+tabarray[i]+"_div").css("display", "block");
					}else{
						jQuery("#"+tabarray[i]).removeClass("nav-tab-active");
						jQuery("#"+tabarray[i]+"_div").css("display", "none");
					}
				}
				localStorage.setItem("ip_last_tab", tabname);
			}

	</script>';

?>
<script type="text/javascript">
	jQuery('#adv_block_tab').addClass('nav-tab-active');

	var tab = localStorage.getItem("ip_last_tab");

	if (tab)
		document.getElementById(tab).click();
	else {
		document.getElementById("mo2f_block_list").click();
	}


	jQuery('#BlockIP').click(function() {

	var ip = jQuery('#ManuallyBlockIP').val();

	var nonce = '<?php echo esc_js( wp_create_nonce( 'LoginSecurityNonce' ) ); ?>';
	if('' !== ip)
	{
		var data = {
		'action'					: 'wpns_login_security',
		'wpns_loginsecurity_ajax' 	: 'wpns_ManualIPBlock_form', 
		'IP'						:  ip,
		'nonce'						:  nonce,
		'option'					: 'mo_wpns_manual_block_ip'
		};
		jQuery.post(ajaxurl, data, function(response) {
				var response = response.replace(/\s+/g,' ').trim();
				if(response == 'empty IP')
				{
					error_msg("IP can not be blank.");
				} else if (response == 'already blocked') {
					error_msg("IP is already blocked.");
				} else if (response == 'INVALID_IP_FORMAT') {
					error_msg("IP does not match required format.");
				} else if (response == "IP_IN_WHITELISTED") {
					error_msg("IP is whitelisted can not be blocked.");

				} else {
					refreshblocktable(response);
					success_msg("IP Blocked Sucessfully.");
				}

			});

		}

	});
jQuery('#WhiteListIP').click(function(){
var ip 	= jQuery('#IPWhitelist').val();

var nonce ='<?php echo esc_js( wp_create_nonce( 'LoginSecurityNonce' ) ); ?>' ;
if(ip != '')
{
	var data = {
	'action'					: 'wpns_login_security',
	'wpns_loginsecurity_ajax' 	: 'wpns_WhitelistIP_form', 
	'IP'						:  ip,
	'nonce'						:  nonce,
	'option'					: 'mo_wpns_whitelist_ip'
	};
	jQuery.post(ajaxurl, data, function(response) {
			var response = response.replace(/\s+/g,' ').trim();
			if(response == 'EMPTY IP')
			{
				error_msg("IP can not be empty.");
			}
			else if(response == 'INVALID_IP')
			{
				error_msg(" IP does not match required format.");
			}
			else if(response == 'IP_ALREADY_WHITELISTED')
			{
				error_msg("IP is already whitelisted.");
			}
			else
			{
				refreshWhiteListTable(response);
				success_msg("IP whitelisted Sucessfully.");
			}
	});		
}
});


	jQuery('#LookupIP').click(function() {
		jQuery('#resultsIPLookup').empty();
		var ipAddress = jQuery('#ipAddresslookup').val();
		var nonce = '<?php echo esc_js( wp_create_nonce( 'LoginSecurityNonce' ) ); ?>';
		jQuery("#resultsIPLookup").empty();
		var img_loader_url = '<?php echo isset( $img_loader_url ) ? esc_url( $img_loader_url ) : ''; ?>';
		jQuery("#resultsIPLookup").append(
			"<img src=" + img_loader_url + ">");
		jQuery("#resultsIPLookup").slideDown(400);
		var data = {
			'action': 'wpns_login_security',
			'wpns_loginsecurity_ajax': 'wpns_ip_lookup',
			'nonce': nonce,
			'IP': ipAddress
		};
		jQuery.post(ajaxurl, data, function(response) {
			if (response === 'INVALID_IP_FORMAT') {
				jQuery("#resultsIPLookup").empty();
				error_msg("IP did not match required format.");
			} else if (response === 'INVALID_IP') {
				jQuery("#resultsIPLookup").empty();
				error_msg("IP entered is invalid.");
			} else if (response.geoplugin_status === 404) {
				jQuery("#resultsIPLookup").empty();
				success_msg(" IP details not found.");
			} else if (response.geoplugin_status === 200 || response.geoplugin_status === 206) {
				jQuery('#resultsIPLookup').empty();
				jQuery('#resultsIPLookup').append(response.ipDetails);
			}

		});
	});


jQuery("#blockedips_table").DataTable({
				"order": [[ 3, "desc" ]]
			});
jQuery("#whitelistedips_table").DataTable({
				"order": [[ 1, "desc" ]]
			});

jQuery('#LookupIP').click(function(){
			jQuery('#resultsIPLookup').empty();
			var ipAddress 	= jQuery('#ipAddresslookup').val();
			var nonce 		= '<?php echo esc_js( wp_create_nonce( 'LoginSecurityNonce' ) ); ?>';
			jQuery("#resultsIPLookup").empty();
			var img_loader_url = '<?php echo isset( $img_loader_url ) ? esc_url( $img_loader_url ) : ''; ?>';
			jQuery("#resultsIPLookup").append(
				"<img src="+img_loader_url+">");
			jQuery("#resultsIPLookup").slideDown(400);
			var data = {
				'action'					: 'wpns_login_security',
				'wpns_loginsecurity_ajax' 	: 'wpns_ip_lookup',
				'nonce'						:  nonce,
				'IP'						:  ipAddress
				};
				jQuery.post(ajaxurl, data, function(response) {
					if(response == 'INVALID_IP_FORMAT')
					{
						jQuery("#resultsIPLookup").empty();
						error_msg("IP did not match required format.");
					}
					else if(response == 'INVALID_IP')
					{
						jQuery("#resultsIPLookup").empty();
						error_msg("IP entered is invalid.");
					}
					else if(response.geoplugin_status == 404)
					{
						jQuery("#resultsIPLookup").empty();
						success_msg(" IP details not found.");
					}
				else if (response.geoplugin_status == 200 ||response.geoplugin_status == 206) { 
					jQuery('#resultsIPLookup').empty(); 
					jQuery('#resultsIPLookup').append(response.ipDetails);
					}			
				});
		});

function unblockip(id) {
	var nonce = '<?php echo esc_js( wp_create_nonce( 'LoginSecurityNonce' ) ); ?>';
	if(id != '')
	{
		var data = {
		'action'					: 'wpns_login_security',
		'wpns_loginsecurity_ajax' 	: 'wpns_ManualIPBlock_form', 
		'id'						:  id,
		'nonce'						:  nonce,
		'option'					: 'mo_wpns_unblock_ip'
		};
		jQuery.post(ajaxurl, data, function(response) {
			var response = response.replace(/\s+/g,' ').trim();
			if(response=="UNKNOWN_ERROR")
			{
				error_msg(" Unknow Error occured while unblocking IP.");
			}
			else
			{
				refreshblocktable(response);
				success_msg("IP unblocked Sucessfully.");
			}
		});				
	}
}
function removefromwhitelist(id)
{
	var nonce = '<?php echo esc_js( wp_create_nonce( 'LoginSecurityNonce' ) ); ?>';
	if(id != '')
	{
		var data = {
		'action'					: 'wpns_login_security',
		'wpns_loginsecurity_ajax' 	: 'wpns_WhitelistIP_form', 
		'id'						:  id,
		'nonce'						:  nonce,
		'option'					: 'mo_wpns_remove_whitelist'
		};
		jQuery.post(ajaxurl, data, function(response) {
				var response = response.replace(/\s+/g,' ').trim();
				if(response == 'UNKNOWN_ERROR')
				{
					error_msg(" Unknow Error occured while removing IP from Whitelist.");
				}
			});

		}
	}

	function removefromwhitelist(id) {
		var nonce = '<?php echo esc_js( wp_create_nonce( 'LoginSecurityNonce' ) ); ?>';
		if (id !== '') {
			var data = {
				'action': 'wpns_login_security',
				'wpns_loginsecurity_ajax': 'wpns_WhitelistIP_form',
				'id': id,
				'nonce': nonce,
				'option': 'mo_wpns_remove_whitelist'
			};
			jQuery.post(ajaxurl, data, function(response) {
				var response = response.replace(/\s+/g, ' ').trim();
				if (response === 'UNKNOWN_ERROR') {
					error_msg(" Unknow Error occured while removing IP from Whitelist.");
				} else {
					refreshWhiteListTable(response);
					success_msg("IP removed from Whitelist.");
				}
			});

		}
	}

	function refreshblocktable(html) {
		jQuery('#blockIPtable').html(html);
	}

	function refreshWhiteListTable(html) {

		jQuery('#WhiteListIPtable').html(html);
	}
</script>
