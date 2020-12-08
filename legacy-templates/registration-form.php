<?php
// Template Name: Registration form 
if ( is_user_logged_in() ) {
	wp_safe_redirect( ffdlegacy_get_permalink_by_template( 'account-settings.php', true) );
	exit;
}
//Form validation
	$errors = array();
	$register_success = false;

	if (isset ( $_POST['reg_submit']) ) {
		$_POST['reg_email'] = trim($_POST['reg_email']);
		$_POST['reg_fname'] = trim($_POST['reg_fname']);
		$_POST['reg_lname'] = trim($_POST['reg_lname']);
		$_POST = stripslashes_deep($_POST);

		if ( empty( $_POST['reg_fname'] ) ) {
			$errors['reg_fname'] = 'This field is required';
		}
		if ( empty( $_POST['reg_terms'] ) ) {
			$errors['reg_terms'] = 'This field is required';
		}
		if ( empty( $_POST['reg_email'] ) ) {
			$errors['reg_email'] = 'This field is required';
		} elseif ( ! is_email( $_POST['reg_email'] ) ) {
			$errors['reg_email'] = 'Email is not valid';
		} elseif ( email_exists( $_POST['reg_email'] ) ) {
			$errors['reg_email'] = 'This email is already in use';
		}

		if ( empty($_POST['reg_pass'] ) ) {
			$errors['reg_pass'] = 'This field is required';
		} elseif ( strlen( $_POST['reg_pass'] ) < 6 ) {
			$errors['reg_pass'] = 'Password too short';
		} elseif ($_POST['reg_pass'] != $_POST['reg_pass2'] ) {
			$errors['reg_pass2'] = 'Passwords do not match';
		}

//Register user		
		if ( empty($errors) ) {
			$user_data = array (
				'user_login' => $_POST['reg_email'],
				'user_email' => $_POST['reg_email'],
				'user_pass'  => $_POST['reg_pass'],
				'first_name' => $_POST['reg_fname'],
				'last_name'  => $_POST['reg_lname'],
			);
			$register_user = wp_insert_user($user_data);
			

			if ( !is_wp_error( $register_user ) ) {

				$creds=array(
					'user_login' => $_POST['reg_email'],
					'user_password'  => $_POST['reg_pass'],
				);
			/* 	$user = wp_signon( $creds, true );
				wp_set_current_user($user->ID);
				wp_set_auth_cookie( $user->ID );
				do_action( 'wp_login', $user->user_login ); */

				/* if( isset($_GET['debug_register']) ){
					echo '<pre>';
					var_dump($register_user);
					var_dump($user);
					var_dump($user->ID);

					if( !get_current_user_id() ){
						wp_delete_user( $register_user );
					}

					exit;
				} */
				
				
				if ( $_POST['assigned_agent_id'] ) {
                   
					$agent_post_id = ffdlegacy_get_id_by_meta( 'sfa_id', $_POST['assigned_agent_id'], false, 'teammember' );
					$query = array (
						'first_name'      => $_POST['reg_fname'],
						'last_name'      => $_POST['reg_lname'],
						'email'     => $_POST['reg_email'],
						'message' => 'This user is assigned to agent ' . get_the_title( $agent_post_id ) . ' with id: ' . $_POST['assigned_agent_id'],
					 );

					$r = FFDL_PB_Request::send_sf_message( $query, $_POST['assigned_agent_id'] );
					 
				

                    if ( !is_wp_error($r) && 200 == $r['response']['code'] ) {
                        update_user_meta( $register_user, 'assigned_agent_id', $_POST['assigned_agent_id'] );
	    	        } else {
	    	        	$errors['wp_error'] = "Was not able to syncronyze your assigned agent. Please check your user account settings ";
	    	        }
	    	    }
                else{
                    $query = array (
						'first_name'      => $_POST['reg_fname'],
						'last_name'      => $_POST['reg_lname'],
						'email'     => $_POST['reg_email'],
						'message' => 'User registered on site with no agent. '
					 );

                    $r = FFDL_PB_Request::send_sf_message( $query);
				}
				
				
			
			
					
				if(isset($_REQUEST["redirect"]))
					wp_safe_redirect($_REQUEST["redirect"]);
				else
					wp_redirect(home_url());
				exit;
			?>
			<?php
			} else { 
				$errors['wp_error'] = $register_user->get_error_message();
			}
			
		}
	}

if (! is_user_logged_in() &&  ! $register_success):
	$assigned_agent_id = isset( $_POST['assigned_agent_id'] ) ? $_POST['assigned_agent_id'] : '';
	
	get_header();
?>

<div class="ffd-twbs">
	<div class="container page-register main">
		<div class="wp-error"><?php echo( isset( $errors['wp_error'] ) ) ? $errors['wp_error'] : null; ?></div>
		<div class="row">
			<div class="col-md-6 col-md-offset-3">
				<form class="reg-form" method="post" id="reg-form" action="">
					<div class="page-form">
						<div class="msg"></div>
						<h3 class="form-title">Create an account</h3>
						<div class="form-group">
							<label class="fc-label" for="reg_fname">First name <span class="required-sign">*</span>:
								<span class="error"><?php echo( isset( $errors['reg_fname'] ) ) ? $errors['reg_fname'] : null; ?></span>
							</label>
							<input name="reg_fname" type="text" class="form-control"
								value="<?php echo(isset($_POST['reg_fname']) ? $_POST['reg_fname'] : null); ?>"
								placeholder="First Name" id="reg_fname" required/>
						</div>
		
						<div class="form-group">
							<label class="fc-label" for="reg_lname">Last name:</label>
							<input name="reg_lname" type="text" class="form-control"
								value="<?php echo(isset($_POST['reg_lname']) ? $_POST['reg_lname'] : null); ?>"
								placeholder="Last Name" id="reg_lname" />
						</div>

						<div class="form-group">
							<label class="fc-label" for="reg_email">Email address <span class="required-sign">*</span>:
								<span class="error"><?php echo( isset( $errors['reg_email'] ) ) ? $errors['reg_email'] : null; ?></span>
							</label>
							<input name="reg_email" type="email" class="form-control"
								value="<?php echo(isset($_POST['reg_email']) ? $_POST['reg_email'] : null); ?>"
								placeholder="Email" id="reg_email" required/>
						</div>

						<div class="form-group">
							<label class="fc-label" for="reg_pass">Password <span class="required-sign">*</span>:
								<span class="error"><?php echo( isset( $errors['reg_pass'] ) ) ? $errors['reg_pass'] : null; ?></span>
							</label>
							<input name="reg_pass" type="password" class="form-control"
								placeholder="Password" id="reg_pass" required/>
						</div>

						<div class="form-group">
							<label class="fc-label" for="reg_pass2">Repeat password <span class="required-sign">*</span>:
								<span class="error"><?php echo( isset( $errors['reg_pass2'] ) ) ? $errors['reg_pass2'] : null; ?></span>
							</label>
							<input name="reg_pass2" type="password" class="form-control"
								placeholder="Repeat password" id="reg_pass2" required/>
						</div>

						<div style="display:none;" class="form-group">
							<label class="fc-label" for="reg_pass2">Select your assigned agent:</label>
							<select name="assigned_agent_id" class="form-control" id="assigned-agent-select">
								<option value="">I will select it later...</option>
							<?php
								$members = get_posts( array(
									'posts_per_page' => -1,
									'post_type'      => 'teammember',
									'post_status'    => 'publish',
									'orderby'        => 'menu_order',
									'order'          => 'ASC',
								));
								$members_list = array();
								foreach ( $members as $member) :
									$member = ffdlegacy_load_team_member_info ( get_post ( $member->ID ) );
									if ( $member->data->sfa_id ) :
										//$memberslist_option = $member->data->sfa_id . ': ' .$member->post_title;
										$memberslist_option = $member->post_title;

									?>
										<option
											value="<?php echo esc_attr( $member->data->sfa_id )?>" 
											<?php echo ($member->data->sfa_id == $assigned_agent_id) ? ' selected' : ''; ?>>
											<?php echo $memberslist_option; ?>
										</option>
									<?php //var_dump($member);
									endif;
								endforeach;
							?>
							</select>
						</div>

						<div class="form-check">
							<label class="fc-label" for="reg_terms" style="padding-left: 15px;">
								<input name="reg_terms" type="checkbox" class="form-check-input" id="reg_terms" required style="margin-left: -15px;margin-top: 7px;"/> I have read and agree to the Terms of Use provision as follows:
								<ul style="font-weight: normal;padding-left: 30px;">
									<li>I understand that I am entering a lawful consumer-broker relationship with <?php echo get_bloginfo('name'); ?>, and that the information I obtain on this website will be for my own personal, non-commercial use.</li>
									<li>I have a bonafide interest in the purchase, sale or lease of real estate offered on this website, and I will not copy, redistribute or retransmit any of the information provided to me except in connection with my consideration of the purchase or sale of an individual property.</li>
									<li>I acknowledge the MLS’s ownership of, and the validity of the MLS’s copyright in the MLS database.</li>
								</ul>
								<span class="error"><?php echo( isset( $errors['reg_terms'] ) ) ? $errors['reg_terms'] : null; ?></span>
							</label>
						</div>
						
						<div class="captcha" id="registration_recaptcha"></div>
						
						<div class="form-group" style="margin-top:20px;">
							<input class="btn" type="submit" name="reg_submit" value="Register"/>
						</div>
						<p>Already have an account? <a class="link-login" href="/login">Login</a></p>
					</div>
				</form>
			</div>
		</div>
	</div>
<!--</main>-->

<?php
endif; //value="<?php echo(isset($_POST['reg_pass']) ? $_POST['reg_pass'] : null);"
?>
</div>
<?php get_footer(); ?>