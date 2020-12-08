<?php
// Template Name: Login Page 
if ( is_user_logged_in() ) {
	wp_safe_redirect( ffdlegacy_get_permalink_by_template( 'account-settings.php', true) );
	exit;
}

$errors = '';
$success = '';
if ( isset( $_POST['login-submit']) ) {
	$_POST['login_email'] = strip_tags(trim($_POST['login_email']));
	$_POST['login_pass'] = strip_tags(trim($_POST['login_pass']));

	$credentials = array(
			'user_login' => $_REQUEST['login_email'],
			'user_password' => $_REQUEST['login_pass'],
	);
	$user_signon = wp_signon( $credentials, false );
	if ( is_wp_error($user_signon) ) {
		$error = "Invalid credentials supplied. Please try again!";
	}
	else{
		if($user_signon->ID){
			wp_set_current_user( $user_signon->ID, $user_signon->user_login );
			wp_set_auth_cookie( $user_signon->ID, true, false );
			do_action( 'wp_login', $user_signon->user_login );

			if(isset($_REQUEST["redirect"]))
				wp_safe_redirect($_REQUEST["redirect"]);
			else
				wp_safe_redirect( ffdlegacy_get_permalink_by_template( 'account-settings.php', true) );
			exit;
		}
	}
}
else if( isset( $_POST['reset-submit']) )
{
	$email = trim($_POST['user_login']);
            
	if( empty( $email ) ) {
		$error = 'Enter an e-mail address..';
	} else if( ! is_email( $email )) {
		$error = 'Invalid e-mail address.';
	} else if( ! email_exists( $email ) ) {
		$error = 'There is no user registered with that email address.';
	} else {
		
		$random_password = wp_generate_password( 12, false );
		$user = get_user_by( 'email', $email );
		
		$update_user = wp_update_user( array (
				'ID' => $user->ID, 
				'user_pass' => $random_password
			)
		);
		
		// if  update user return true then lets send user an email containing the new password
		if( $update_user ) {
			$to = $email;
			$subject = 'Your new password';
			$sender = get_option('name');
			
			$message = 'Your new password is: '.$random_password;
			
			$headers[] = 'MIME-Version: 1.0' . "\r\n";
			$headers[] = 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
			$headers[] = "X-Mailer: PHP \r\n";
			$headers[] = 'From: '.$sender.' < '.$email.'>' . "\r\n";
			
			$mail = wp_mail( $to, $subject, $message, $headers );
			if( $mail )
				$success = 'Check your email address for you new password.';
			else
				$error = 'Unable to send the email please try again.';
				
		} else {
			$error = 'Oops something went wrong updaing your account.';
		}
		
	}
}

get_header();
?>
<div class="ffd-twbs">
<div class="container page-login main">
		<div class="row">
			<div class="col-md-6 col-md-offset-3">
					<?php if( isset( $error ) && !empty($error) ) { ?><div class="alert alert-danger"><?php echo $error ?></div><?php }?>
					<?php if( isset( $success ) && !empty($success) ) { ?><div class="alert alert-success"><?php echo $success  ?></div><?php }?>
				<form class="login-form" method="post" id="login-form" action="" style="display:<?php echo (isset( $_POST['reset-submit']) && count($error)>0 )?'none':'block'?>">
					<div class="page-form">
						<div class="msg"></div>
						<h3 class="form-title">Login to your account</h3>
						<div class="form-group">
							<label class="fc-label" for="login_email">Email address <span class="required-sign">*</span>:</label>
							<input name="login_email" type="email" class="form-control" value="" placeholder="Email" id="login_email" />
						</div>
						<div class="form-group">
							<label class="fc-label" for="login_pass">Password <span class="required-sign">*</span>:</label>
							<input name="login_pass" type="password" class="form-control" placeholder="Password" id="login_pass" />
						</div>
						<div class="form-group">
							<input class="btn" type="submit" name="login-submit" value="Login"/>
							<input type="hidden" name="action" value="ffdlegacy_login" />
						</div>
						<p>Don't have an account yet? <a href="/register<?php echo isset($_REQUEST["redirect"])?"?redirect=" . urlencode($_REQUEST["redirect"]):"" ?>">Register</a> | <a href="javscript:;" onclick="jQuery('#reset-form').toggle();jQuery('#login-form').toggle();">Forgot Password?</a></p>
					</div>
				</form>
				<form class="reset-form" method="post" id="reset-form" action="" style="display:<?php echo (isset( $_POST['reset-submit']) && count($error)>0 )?'block':'none'?>">
				<div class="page-form">
						<div class="msg"></div>
						<h3 class="form-title">Reset your account password</h3>
						<div class="form-group">
							<label class="fc-label" for="reset_email">Email address <span class="required-sign">*</span>:</label>
							<input name="user_login" type="email" class="form-control" value="" placeholder="Email" id="user_login" required />
						</div>
						<div class="form-group">
							<input class="btn" type="submit" name="reset-submit" value="Reset Password"/>
							<input type="hidden" name="action" value="ffdlegacy_reset" />
						</div>
						<p>Don't have an account yet? <a href="/register<?php echo isset($_REQUEST["redirect"])?"?redirect=" . urlencode($_REQUEST["redirect"]):"" ?>">Register</a> | <a href="javscript:;" onclick="jQuery('#reset-form').toggle();jQuery('#login-form').toggle();">Login Now</a></p>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<?php get_footer(); ?>