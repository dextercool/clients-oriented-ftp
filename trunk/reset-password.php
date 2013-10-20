<?php
/**
 * Show the form to reset the password.
 *
 * @package		ProjectSend
 *
 */
$allowed_levels = array(9,8,7,0);
require_once('sys.includes.php');

$page_title = __('Lost password','cftp_admin');

include('header-unlogged.php');
	$show_form = 'enter_email';

	/** The form was submitted */
	if ($_POST) {
		/**
		 * Clean the posted form values.
		 */
		$form_type				= encode_html($_POST['form_type']);
		
		switch ($form_type) {
			case 'new_request':

				$reset_password_email	= encode_html($_POST['reset_password_email']);

				$sql_user = $database->query("SELECT id, user, email FROM tbl_users WHERE email='$reset_password_email'");
				$count_user = mysql_num_rows($sql_user);
				if ($count_user > 0){
					/** Email exists on the database */
					$row		= mysql_fetch_array($sql_user);
					$id			= $row['id'];
					$username	= $row['user'];
					$email		= $row['email'];
					$token		= substr(md5(mt_rand(0, 1000000)), 0, 32);
		
					$sql_pass = $database->query("INSERT INTO tbl_password_reset (user_id, token)"
													."VALUES ('$id', '$token' )");
		
					/** Send email */
					$notify_user = new PSend_Email();
					$email_arguments = array(
													'type' => 'password_reset',
													'address' => $email,
													'username' => $username,
													'token' => $token
												);
					$notify_send = $notify_user->psend_send_email($email_arguments);
		
					if ($notify_send == 1){
						$state['email'] = 1;
					}
					else {
						$state['email'] = 0;
					}
					
					$show_form = 'none';
				}
				else {
					$errorstate = 'email_not_found';
				}
			break;

			case 'new_password':
			break;
		}
	}
	
	if (!empty($_GET['token']) && !empty($_GET['user'])) {
		$got_token	= mysql_real_escape_string($_GET['token']);
		$got_user	= mysql_real_escape_string($_GET['user']);

		/**
		 * Get the user's id
		 */
		$query_user_id	= $database->query("SELECT id, user FROM tbl_users WHERE user = '$got_user'");
		$result_user_id	= mysql_fetch_array($query_user_id);
		$got_user_id	= $result_user_id['id'];

		$sql_request = $database->query("SELECT * FROM tbl_password_reset WHERE token = '$got_token' AND user_id = '$got_user_id'");
		$count_request = mysql_num_rows($sql_request);
		if ($count_request > 0){
			$token_info	= mysql_fetch_array($sql_request);

			/** Check if the token has been used already */
			if ($token_info['used'] == '1') {
				$errorstate = 'token_used';
			}

			/** Check if the token has expired. */
			elseif (time() - strtotime($token_info['timestamp']) > 60*60*24) {
				$errorstate = 'token_expired';
			}

			else {
				$show_form = 'enter_new_password';
			}
		}
		else {
			$errorstate = 'token_invalid';
			$show_form = 'none';
		}
	}

	?>

		<h2><?php echo $page_title; ?></h2>

		<div class="container">
			<div class="row">
				<div class="span4 offset4 white-box">
					<div class="white-box-interior">
						<?php
							/**
							 * Show status message
							 */
							if (isset($errorstate)) {
								switch ($errorstate) {
									case 'email_not_found':
										$login_err_message = __("The supplied email address does not correspond to any user.",'cftp_admin');
										break;
									case 'token_invalid':
										$login_err_message = __("The request token is not valid.",'cftp_admin');
										break;
									case 'token_expired':
										$login_err_message = __("This request has expired. Please make a new one.",'cftp_admin');
										break;
									case 'token_used':
										$login_err_message = __("This request has already been completed. Please make a new one.",'cftp_admin');
										break;
								}
				
								echo system_message('error',$login_err_message,'login_error');
							}

							/**
							 * Show the ok or error message for the email.
							 */
							if (isset($state['email'])) {
								switch ($state['email']) {
									case 1:
										$msg = __('An e-mail with further instructions has been sent. Please check your inbox to proceed.','cftp_admin');
										echo system_message('ok',$msg);
									break;
									case 0:
										$msg = __("E-mail couldn't be sent.",'cftp_admin');
										$msg .= ' ' . __("If the problem persists, please contact an administrator.",'cftp_admin');
										echo system_message('error',$msg);
									break;
								}
							}
							 
							switch ($show_form) {
								case 'enter_email':
								default:
						?>
									<script type="text/javascript">
										$(document).ready(function() {
											$("form").submit(function() {
												clean_form(this);
									
													is_complete(this.add_client_form_name,'<?php echo $validation_no_name; ?>');
									
												// show the errors or continue if everything is ok
												if (show_form_errors() == false) { return false; }
											});
										});
									</script>
									
									<form action="reset-password.php" name="resetpassword" method="post" role="form">
										<fieldset>
											<input type="hidden" name="form_type" id="form_type" value="new_request" />

											<label class="control-label" for="reset_password_email"><?php _e('E-mail','cftp_admin'); ?></label>
											<input type="text" name="reset_password_email" id="reset_password_email" class="span4" />

											<p><?php _e("Please enter your account's e-mail address. You will receive a link to continue the process.",'cftp_admin'); ?></p>

											<div class="form_submit_li">
												<button type="submit" name="submit" id="button_login" class="button button_blue button_submit"><?php _e('Continue','cftp_admin'); ?></button>
											</div>
										</fieldset>
									</form>
						<?php
								break;
								case 'enter_new_password':
						?>
									<script type="text/javascript">
										$(document).ready(function() {
											$("form").submit(function() {
												clean_form(this);
									
													is_complete(this.add_client_form_name,'<?php echo $validation_no_name; ?>');
									
												// show the errors or continue if everything is ok
												if (show_form_errors() == false) { return false; }
											});
										});
									</script>
									
									<form action="reset-password.php?token=<?php echo $got_token; ?>&user=<?php echo $got_user; ?>" name="newpassword" method="post" role="form">
										<fieldset>
											<input type="hidden" name="form_type" id="form_type" value="new_password" />

											<label class="control-label" for="reset_password_new"><?php _e('New password','cftp_admin'); ?></label>
											<input type="text" name="reset_password_new" id="reset_password_new" class="span4" />
											
											<p><?php _e("Please enter your desired new password. After that, you will be able to log in normally.",'cftp_admin'); ?></p>

											<div class="form_submit_li">
												<button type="submit" name="submit" id="button_login" class="button button_blue button_submit"><?php _e('Continue','cftp_admin'); ?></button>
											</div>
										</fieldset>
									</form>
						<?php
								break;
								case 'none':
						?>
						<?php
								break;
							 }
						?>

						<div class="login_form_links">
							<p><a href="<?php echo BASE_URI; ?>" target="_self"><?php _e('Go back to the homepage.','cftp_admin'); ?></a></p>
						</div>

					</div>
				</div>
			</div>
		</div> <!-- container -->
	</div> <!-- main (from header) -->

	<?php default_footer_info(false); ?>

</body>
</html>
<?php
	$database->Close();
	ob_end_flush();
?>