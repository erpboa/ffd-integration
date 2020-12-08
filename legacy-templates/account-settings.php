<?php get_header(); 

// Template Name: Account settings
if ( is_user_logged_in() ) :
	$current_user = wp_get_current_user();

	//Form validation
	$errors = array();
	$regiser_success = false;

	$active_tab = ( isset( $_GET['active_tab']) ) ? intval( $_GET['active_tab'] ) : 0;
	$active_tab = ( isset( $_POST['active_tab']) ) ? intval( $_POST['active_tab'] ) : $active_tab;

	if ( isset( $_POST['reg_submit']) ) {
		$_POST['reg_email'] = trim($_POST['reg_email']);
		$_POST['reg_fname'] = trim($_POST['reg_fname']);
		$_POST['reg_lname'] = trim($_POST['reg_lname']);
		$_POST = stripslashes_deep($_POST);

		if ( empty( $_POST['reg_fname'] ) ) {
			$errors['reg_fname'] = 'This field is required';
		}


		if ( empty($_POST['reg_current_pass'] ) ) {
			$errors['reg_current_pass'] = 'This field is required';
		} elseif ( !wp_check_password( $_POST['reg_current_pass'], $current_user->data->user_pass, $curent_user->ID ) ) {
			$errors['reg_current_pass'] = 'Wrong password';
		}

		if ( ($_POST['reg_pass'] ) ) {
			if ( strlen( $_POST['reg_pass'] ) < 6 ) {
				$errors['reg_pass'] = 'Password too short';
			} elseif ($_POST['reg_pass'] != $_POST['reg_pass2'] ) {
				$errors['reg_pass2'] = 'Passwords do not match';
			}
		}

		//Change user data		
		if ( empty($errors) ) {

			$user_data = array(
				'ID'         => $current_user->ID,
				'first_name' => $_POST['reg_fname'],
				'last_name'  => $_POST['reg_lname'],
			);

			if ($_POST['reg_pass']) {
				$user_data['user_pass'] =  $_POST['reg_pass'];
			}

			$user_data = wp_update_user( $user_data );

			if ( is_wp_error( $user_data ) ) {
			    $errors['wp_error'] = 'Error changing data';
			    //var_dump( $user_data );
			} else {
			    //echo 'Account settings updated.';
			}
		}
	}

	try {
		//code...
	} catch (Exception $th) {
		//throw $th;
	}
	$assigned_agent_id = get_user_meta( get_current_user_id(), 'assigned_agent_id', true );
	//$assigned_agent_id = $assigned_agent_id[0];
	$agent_post_id = ffdlegacy_get_id_by_meta( 'sfa_id', $assigned_agent_id, false, 'teammember' );
    $broker_id=ffd_get_field("broker_id",$agent_post_id);

	if ($assigned_agent_id) {
		$assigned_agent_msg = "Your assigned agent is " . get_the_title( $agent_post_id );
 } else {
     $assigned_agent_msg ="";// "Select your assigned agent from the dropdown list below:";
	}

	if ( isset( $_POST['assigned_agent_submit'] ) ) {
		if ( empty( $_POST['assigned_agent_id'] ) ) {
			update_user_meta( get_current_user_id(), 'assigned_agent_id', '' );
			$assigned_agent_msg = "You do not have any assigned agent";
			$assigned_agent_id = '';
		} else {
			$agent_post_id = ffdlegacy_get_id_by_meta( 'sfa_id', $_POST['assigned_agent_id'], false, 'teammember' );
			$query = array (
				'first_name'    => $current_user->user_firstname,
				'last_name'    => $current_user->user_lastname,
	    		'email'   => $current_user->user_email,
				'message' => 'This user is assigned to agent ' . get_the_title( $agent_post_id ) . ' with id: ' . $_POST['assigned_agent_id'],
			 );
			
			FFDL_PB_Request::send_sf_message( $query, $_POST['assigned_agent_id'] );
			$r = FFDL_PB_Request::get_last_response();
			
			if ( 200 == $r['response']['code'] ) {
				update_user_meta( get_current_user_id(), 'assigned_agent_id', $_POST['assigned_agent_id'] );
				$assigned_agent_msg = "Your assigned agent is changed to " . get_the_title( $agent_post_id );
				$assigned_agent_id = $_POST['assigned_agent_id'];
			} else {
				$assigned_agent_msg = "There was error changing your assigned agent ID to " . esc_html( $_POST['assigned_agent_id'] ) . '. Please try again later or contact with administrator';
			}


		}
	}

    if($assigned_agent_id)
        echo ffdl_get_template_part('expandable-mail-form', array( 'broker_id' => $broker_id )); 
?>
<div class="ffd-twbs">
<div class="account-settings-container container">
	<div><h3 style="display:inline-block;padding-right:15px;">My Account</h3> <a href="<?php echo wp_logout_url( home_url() ); ?>">Logout</a></div>
	<div class="jq-tabs">
		<ul>
			<li>
				<a href="#tabs-userdata">
					<span class="mobile"><i class="fa fa-user" aria-hidden="true"></i></span>
					<span class="non-mobile">Details</span>
				</a>
			</li>
			<li>
				<a href="#tabs-agent">
					<span class="mobile"><i class="fa fa-male" aria-hidden="true"></i></span>
					<span class="non-mobile">Preferences</span>
				</a>
			</li>
			<li>
				<a href="#tabs-savedsearch">
					<span class="mobile"><i class="fa fa-search-plus" aria-hidden="true"></i></span>
					<span class="non-mobile">Saved searches</span>
				</a>
			</li>
		</ul>
	
	<div id="tabs-userdata">
	<div class="wp-error"><?php echo( isset( $errors['wp_error'] ) ) ? $errors['wp_error'] : null; ?></div>
	<form class="reg-form" method="post" id="account-form" action="">
		<div class="page-form">

			<div class="form-group">
				<label class="fc-label" for="reg_fname">First name *:
					<span class="error"><?php echo( isset( $errors['reg_fname'] ) ) ? $errors['reg_fname'] : null; ?></span>
				</label>
				<input name="reg_fname" type="text" class="form-control"
					   value="<?php echo esc_attr( $current_user->user_firstname ); ?>"
					   placeholder="First Name" id="reg_fname" />
			</div>

			<div class="form-group">
				<label class="fc-label" for="reg_lname">Last name:</label>
				<input name="reg_lname" type="text" class="form-control"
					   value="<?php echo esc_attr( $current_user->user_lastname ); ?>"
					   placeholder="Last Name" id="reg_lname" />
			</div>

			<div class="form-group">
				<label class="fc-label" for="reg_email">Email address *:
				</label>
				<input name="reg_email" disabled type="email" class="form-control"
					   value="<?php echo esc_attr( $current_user->user_email ); ?>"
					   placeholder="Email" id="reg_email" />
			</div>

			<div class="form-group">
				<label class="fc-label" for="reg_current_pass">Current Password *:
					<span class="error"><?php echo( isset( $errors['reg_current_pass'] ) ) ? $errors['reg_current_pass'] : null; ?></span>
				</label>
				<input name="reg_current_pass" type="password" class="form-control"
					   placeholder="Password" id="reg_current_pass" />
			</div>

			<div class="form-group">
				<label class="fc-label" for="reg_pass">New password:
					<span class="error"><?php echo( isset( $errors['reg_pass'] ) ) ? $errors['reg_pass'] : null; ?></span>
				</label>
				<input name="reg_pass" type="password" class="form-control"
					   placeholder="Password" id="reg_pass" />
			</div>

			<div class="form-group">
				<label class="fc-label" for="reg_pass2">Repeat new password *:
					<span class="error"><?php echo( isset( $errors['reg_pass2'] ) ) ? $errors['reg_pass2'] : null; ?></span>
				</label>
				<input name="reg_pass2" type="password" class="form-control"
					   placeholder="Repeat password" id="reg_pass2" />
			</div>
				<div class="form-group">
				<input class="btn" type="submit" name="reg_submit" value="Save"/>
			</div>

			<input class="btn" type="hidden" name="active_tab" value="0"/>
		</div>
	</form>
	</div>


	<div id="tabs-agent"  class="ui-tabs-active">
    <div class="msg"><?php echo $assigned_agent_msg; ?><br/>
      <?php if($assigned_agent_id): ?>
        <a id="email-me" data-broker-id="<?php echo $broker_id ?>" data-method="hbg_ajax_email" class="link-email"><img src="<?php echo bloginfo( 'template_directory' ); ?>/img/email_icon.png"> Email me</a>
      <?php endif; ?>
        <!-- Begin Email Prefrences-->
        <p>Email Preferences</p>
        <p id="updating" style="display:none;">Updating preferences...</p>
        <label id="emailPref" class="custom-checkbox">
            <?php $optOut=get_user_meta(get_current_user_id(), "optout",true); 
            ?>

            <input type="checkbox" id="chkOptOut" onclick="doOptOut();" <?php echo $optOut==="true"?"":"checked" ; ?> />
            <span>
                Opt-in to receive email updates:
            </span>
        </label>
        <script>
            function doOptOut() {
                jQuery("#updating").show();
                jQuery("#emailPref").hide();
                var data = {
					'optout': !jQuery("#chkOptOut").is(":checked"),
					'action': 'ffdl_ajax_optout'
				};

                jQuery.ajax({
				url: ffdl_vars.ajaxurl,
				type: 'POST',
				cache: false,
				data: data,
                success: function (response) {
                    jQuery("#updating").hide();
                    jQuery("#emailPref").show();
                    alert("Your email preferences have been updated");
				},
				error: function(errorThrown){
				},
				complete: function() {
					
				}
			});
        }
        </script>
        <!-- End Email preferences-->
    </div>
        	
		<form id="assign-agent-form" action="<?php echo get_permalink().'?active_tab=1' ?>" method="post" class="page-form">

		<div style="display:none;" class="agents-container clearfix">
		<?php
			$members = get_posts( array(
				'posts_per_page' => -1,
				'post_type'      => 'teammember',
				'post_status'    => 'publish',
				'orderby' => 'menu_order',
				'order' => 'ASC',
			));
			$members_list = array();

			foreach ( $members as $member):
				$member = ffdlegacy_load_team_member_info ( get_post ( $member->ID ) );
				if ( $member->data->sfa_id ) :
					//$members_list[ $member->data->sfa_id ] = $member->data->sfa_id . ': ' .$member->post_title;
					$members_list[ $member->data->sfa_id ] = $member->post_title;
					$className = (strtolower($member->data->sfa_id) == strtolower($assigned_agent_id))? 'member selected ' : 'member';
				?>
					<div class="<?php echo $className; ?>" data-member-id="<?php echo esc_attr( $member->data->sfa_id ); ?>">
						<div class="agent-photo" style="background-image: url('<?php echo $member->data->photo_url ?>')"></div>
						<div class="agent-fields">
              <div class="name"><a style="text-decoration:underline" href="<?php echo get_permalink($member->ID) ?>"><?php echo $member->post_title; ?></a></div>
							<div class="subposition"><?php echo $member->data->subposition; ?></div>
							<div class="phone"><?php echo $member->data->phone; ?></div>
              <div class="brokerid"><?php echo esc_html( ffd_get_field('broker_id_label', $member->ID) ); ?>&nbsp;</div>
						</div>
					</div>
            	<?php //var_dump($member);
				endif;
			endforeach;
                ?>
            <?php if($assigned_agent_id): ?>
            <p style="margin-top:20px;">
                Questions About Your Agent? <a data-broker-id="<?php echo $broker_id ?>" data-method="hbg_ajax_agent" style="text-decoration:underline;" id="email-me" class="link-email">Contact us</a>
            </p>
            <?php
            endif;
            ?>
            
       </div>

		<div style="display:none;" class="form-group">
			<select name="assigned_agent_id" class="form-control" id="assigned-agent-select">
				<option value=""></option>
				<?php 
				foreach ($members_list as $id=>$agent): ?>
					<option value="<?php echo esc_attr( $id )?>" <?php echo ($id == $assigned_agent_id) ? ' selected' : '' ?>><?php echo $agent ?></option>

				<?php
			endforeach?>
			</select>
			<input type="submit" style="margin:0;" class="btn" name="assigned_agent_submit" id="cta-assign-agent" value="Save" />
		</div>

		</form>
		<div style="display:none;">
		<p style="clear:left;">For more information about our agents, you can check the 
		<a style="text-decoration:underline" href="<?php echo esc_url( ffdlegacy_get_permalink_by_template( 'our-team.php' ) ) ?>">Our Team</a> 
		page.</p>
		</div>

	</div>



	<div id="tabs-savedsearch">
		<table class="account-settings saved-search list-table table table-striped">
			<!--<caption>Saved searches</caption>-->
			<thead>
				<tr>
					<th style="width:150px;">Name</th>
					<th style="width:150px;">Created</th>
					<th> </th>
				</tr>
			</thead>
			<tbody>
		
			<?php 
			$saved_searches = FFDL_Searches::get_searches_for_user();
			if ( count( $saved_searches ) ) {
				foreach ( $saved_searches as $ss ): 
					$ssearch_url = ffdlegacy_get_permalink_by_template('listings-search.php') .
					'?' . $ss['search_options'] .
					'&search_name=' . $ss[ 'name' ];
				?>
					<tr>
						<td>
							<a href="<?php echo esc_url( $ssearch_url ) ?>" class="ssearch-link">
								<?php echo $ss['name'] ?>
							</a>
						</td>
						<td><?php echo date('m/d/Y', strtotime($ss['created'])); ?></td>
						<td>
							<a href="javascript: void(0)" title="Delete this search" class="cta-delete btn btn-danger btn-sm" data-id="<?php echo $ss['ID']; ?>">Delete</a>
						</td>
					</tr>
				<?php endforeach;
			} else {
			?>
				<tr><td colspan="3">There are no saved searches yet. You can go to the
					<a href="<?php echo esc_url( ffdlegacy_get_permalink_by_template( 'listings-search.php' ) );?>">
						search page
					</a> and find something suitable to your needs.
				</td></tr>
			<?php } ?>
			</tbody>
		</table>
 	</div>
</div>
<script type="text/javascript">
	jQuery(document).ready(function(){
		app.accountSettings.initScripts();
		jQuery( ".jq-tabs" ).tabs({
			active: <?php echo $active_tab ?>
		});
	});
</script>


</div>
<?php 
	else :
?>
	<div class="container-fluid">
		<div class="container">
			<p>Your session have expired. You can <b><a class="link-login" href="/login">login</a></b> back to your account</p>
		</div>
	</div>
<?php
endif;
?>
</div>
<?php

get_footer(); ?>