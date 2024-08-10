<?php
/**
 * This file show frontend to configure Security Questions.
 *
 * @package miniorange-2-factor-authentication/views/twofa/setup
 */

use TwoFA\Views\Mo2f_Setup_Wizard;
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Function to show setup wizard for configuring KBA.
 *
 * @return void
 */
function mo2f_configure_kba_questions() { ?>
	<div class="mo2f_kba_header"><?php esc_html_e( 'Please choose 3 questions', 'miniorange-2-factor-authentication' ); ?></div>
	<br>
	<table id="mo2f_configure_kba" cellspacing="10">
		<thead>
		<tr class="mo2f_kba_header">
			<th>
				<?php esc_html_e( 'Sr. No.', 'miniorange-2-factor-authentication' ); ?>
			</th>
			<th class="mo2f_kba_tb_data">
				<?php esc_html_e( 'Questions', 'miniorange-2-factor-authentication' ); ?>
			</th>
			<th>
				<?php esc_html_e( 'Answers', 'miniorange-2-factor-authentication' ); ?>
			</th>
		</tr>
	</thead>
		<tr class="mo2f_kba_body">
			<td class="mo2f_align_center">
				1.
			</td>
			<td class="mo2f_kba_tb_data">
				<?php
				$kba_set = new Mo2f_Setup_Wizard();
				$kba_set->mo2f_kba_question_set( 1 );
				?>

			</td>
			<td>
				<input class="mo2f_table_textbox" type="password" name="mo2f_kba_ans1" id="mo2f_kba_ans1"
					title="<?php esc_attr_e( 'Only alphanumeric letters with special characters(_@.$#&amp;+-) are allowed.', 'miniorange-2-factor-authentication' ); ?>"
					pattern="(?=\S)[A-Za-z0-9_@.$#&amp;+\-\s]{1,100}" required="true" autofocus="true"
					placeholder="<?php esc_attr_e( 'Enter your answer' ); ?>"/>
			</td>
		</tr>
		<tr class="mo2f_kba_body">
			<td class="mo2f_align_center">
				2.
			</td>
			<td class="mo2f_kba_tb_data">
			<?php
				$kba_set->mo2f_kba_question_set( 2 );
			?>
			</td>
			<td>
				<input class="mo2f_table_textbox" type="password" name="mo2f_kba_ans2" id="mo2f_kba_ans2"
					title="<?php esc_attr_e( 'Only alphanumeric letters with special characters(_@.$#&amp;+-) are allowed.', 'miniorange-2-factor-authentication' ); ?>"
					pattern="(?=\S)[A-Za-z0-9_@.$#&amp;+\-\s]{1,100}" required="true"
					placeholder="<?php esc_attr_e( 'Enter your answer', 'miniorange-2-factor-authentication' ); ?>"/>
			</td>
		</tr>
		<tr class="mo2f_kba_body">
			<td class="mo2f_align_center">
				3.
			</td>
			<td class="mo2f_kba_tb_data">
				<input class="mo2f_kba_ques" type="text" style="width: 100%;"name="mo2f_kbaquestion_3" id="mo2f_kbaquestion_3"
					required="true"
					placeholder="<?php esc_attr_e( 'Enter your custom question here', 'miniorange-2-factor-authentication' ); ?>"/>
			</td>
			<td>
				<input class="mo2f_table_textbox" type="password" name="mo2f_kba_ans3" id="mo2f_kba_ans3"
					title="<?php esc_attr_e( 'Only alphanumeric letters with special characters(_@.$#&amp;+-) are allowed.', 'miniorange-2-factor-authentication' ); ?>"
					pattern="(?=\S)[A-Za-z0-9_@.$#&amp;+\-\s]{1,100}" required="true"
					placeholder="<?php esc_attr_e( 'Enter your answer', 'miniorange-2-factor-authentication' ); ?>"/>
			</td>
		</tr>
	</table>

	<script>
		//hidden element in dropdown list 1
		var mo_option_to_hide1;
		//hidden element in dropdown list 2
		var mo_option_to_hide2;

		function mo_option_hide(list) {
			//grab the team selected by the user in the dropdown list
			var list_selected = document.getElementById("mo2f_kbaquestion_" + list).selectedIndex;
			//if an element is currently hidden, unhide it
			if (typeof (mo_option_to_hide1) != "undefined" && mo_option_to_hide1 !== null && list == 2) {
				mo_option_to_hide1.style.display = 'block';
			} else if (typeof (mo_option_to_hide2) != "undefined" && mo_option_to_hide2 !== null && list == 1) {
				mo_option_to_hide2.style.display = 'block';
			}
			//select the element to hide and then hide it
			if (list == 1) {
				if (list_selected != 0) {
					mo_option_to_hide2 = document.getElementById("mq" + list_selected + "_2");
					mo_option_to_hide2.style.display = 'none';
				}
			}
			if (list == 2) {
				if (list_selected != 0) {
					mo_option_to_hide1 = document.getElementById("mq" + list_selected + "_1");
					mo_option_to_hide1.style.display = 'none';
				}
			}
		}
	</script>
	<?php
	if ( isset( $_SESSION['mo2f_mobile_support'] ) && 'MO2F_EMAIL_BACKUP_KBA' === $_SESSION['mo2f_mobile_support'] ) {
		?>
		<input type="hidden" name="mobile_kba_option" value="mo2f_request_for_kba_as_emailbackup"/>
		<?php
	}
}
/**
 * Function to show setup for configuring KBA for mobile support.
 *
 * @param object $user User object.
 * @return void
 */
function mo2f_configure_for_mobile_suppport_kba( $user ) {

	?>

		<h3><?php esc_html_e( 'Configure Second Factor - KBA (Security Questions)', 'miniorange-2-factor-authentication' ); ?>
		</h3>
		<hr/>
	<form name="f" method="post" action="" id="mo2f_kba_setup_form">
		<?php mo2f_configure_kba_questions(); ?>
		<br>
		<input type="hidden" name="option" value="mo2f_save_kba"/>
	<input type="hidden" name="mo2f_save_kba_nonce"
						value="<?php echo esc_attr( wp_create_nonce( 'mo2f-save-kba-nonce' ) ); ?>"/>
	<div class="mo2f_align_center">
		<table>
			<tr>
				<td>
					<input type="submit" id="mo2f_kba_submit_btn" name="submit"
						value="<?php esc_attr_e( 'Save', 'miniorange-2-factor-authentication' ); ?>"
						class="button button-primary button-large" style="width:100px;line-height:30px;margin-right:-220%;"/>
				</td>
	</form>

	<td>

		<form name="f" method="post" action="" id="mo2f_go_back_form">
			<input type="hidden" name="option" value="mo2f_go_back"/>
			<input type="hidden" name="mo2f_go_back_nonce"
					value="<?php echo esc_attr( wp_create_nonce( 'mo2f-go-back-nonce' ) ); ?>"/>
				<input type="submit" name="back" id="go_back" class="button button-primary button-large"
					value="<?php esc_attr_e( 'Back', 'miniorange-2-factor-authentication' ); ?>"
					style="width:100px;line-height:30px;margin-left: -210%"/>

		</form>

	</td>
	</tr>
	</table>
</div>
	<script>

		jQuery('#mo2f_kba_submit_btn').click(function () {
			jQuery('#mo2f_kba_setup_form').submit();
		});
	</script>
	<?php
}

?>
