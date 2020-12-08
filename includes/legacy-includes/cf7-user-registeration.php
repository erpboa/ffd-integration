<?php

add_action('init', 'ffd_cf7_reset_registeration_login_vars', 1);
function ffd_cf7_reset_registeration_login_vars(){

    $ffd_cf7_register_error = '';
    global $ffd_cf7_register_error;

    $ffd_cf7_register_success = '';
    global $ffd_cf7_register_success;

    $ffd_cf7_login_message = '';
    global $ffd_cf7_login_message;

}



function ffd_cf7_user_login_form($cfdata, &$abort) {

    // ini_set('display_errors', '1');
    // error_reporting(E_ALL); 


    if (!isset($cfdata->posted_data) && class_exists('WPCF7_Submission')) {
        // Contact Form 7 version 3.9 removed $cfdata->posted_data and now
        // we have to retrieve it from an API
        $submission = WPCF7_Submission::get_instance();
        if ($submission) {
            $formdata = $submission->get_posted_data();
        }
    } elseif (isset($cfdata->posted_data)) {
        // For pre-3.9 versions of Contact Form 7
        $formdata = $cfdata->posted_data;
    } else {
      
        return $cfdata;
    }

  
    if ( $cfdata->title() != 'Login Form') 
        return $cfdata;

        ffd_cf7_reset_registeration_login_vars();
        global $ffd_cf7_login_message;


        $user_login = $formdata['user_login'];
        $user_password = $formdata['user_password'];

        if( is_email($user_login) ){
            $user = get_user_by('email', $user_login);
            if( $user !== false && isset($user->user_login) ){
                $user_login = $user->user_login ;
            }
        }

        $credentials = array(
            'user_login' => $user_login,
            'user_password' => $user_password,
        );
        $user_signon = wp_signon($credentials, false);
        if (is_wp_error($user_signon)) {
            $ffd_cf7_login_message = "Invalid credentials supplied. Please try again!";
            $abort = true;
        } else {
            if ($user_signon->ID) {
                wp_set_current_user($user_signon->ID, $user_signon->user_login);
                wp_set_auth_cookie($user_signon->ID, true, false);
                //do_action('wp_login', $user_signon->user_login);
                $ffd_cf7_login_message = 'Login Successful';
                do_action('ffd_cf7_user_logged_in', $user_login, $user_signon);
            }
        }

        ffd_cf7_add_login_form_message();
       
    

    
    return $cfdata;

}
add_action('wpcf7_before_send_mail', 'ffd_cf7_user_login_form', 10, 2);



function ffd_cf7_add_login_form_message(){

    add_filter('wpcf7_display_message', 'ffd_cf7_login_form_message', 10, 2);
}
function ffd_cf7_login_form_message($message, $status){
    global $ffd_cf7_login_message;
    
    return $ffd_cf7_login_message;
}

function ffd_cf7_remove_login_form_message($cf7_object){
    if ( $cf7_object->title() == 'Login Form') {
        global $ffd_cf7_login_message;
        $ffd_cf7_login_message = '';
        remove_filter('wpcf7_display_message', 'ffd_cf7_login_form_message', 10, 2);
    }

}
add_action('wpcf7_mail_sent', 'ffd_cf7_remove_login_form_message', 10, 1);





//function ffd_cf7_user_registration_form($skip_mail, $cfdata) {
function ffd_cf7_user_registration_form($cfdata, &$abort) {


    if (!isset($cfdata->posted_data) && class_exists('WPCF7_Submission')) {
        // Contact Form 7 version 3.9 removed $cfdata->posted_data and now
        // we have to retrieve it from an API
        $submission = WPCF7_Submission::get_instance();
        if ($submission) {
            $formdata = $submission->get_posted_data();
        }
    } elseif (isset($cfdata->posted_data)) {
        // For pre-3.9 versions of Contact Form 7
        $formdata = $cfdata->posted_data;
    } else {
        // We can't retrieve the form data
    
        return $cfdata;
    }
    // Check this is the user registration form
    if ( $cfdata->title() != 'Registration Form') 
        return $cfdata;
    
    
    ffd_cf7_reset_registeration_login_vars();
    global $ffd_cf7_register_error;
    global $ffd_cf7_register_success;

        if( isset($formdata['email']) || isset($formdata['your-email']) ){
            if( !empty($formdata['email']) )
                $email = $formdata['email'];
            else if(!empty($formdata['your-email']) )
                $email = $formdata['your-email'];
        }

        
        if( !empty($formdata['your-name']) ){

            $name = $formdata['your-name'];

        } else if(!empty($formdata['name']) ){

            $name = $formdata['name'];

        } else if(!empty($formdata['full-name']) ){

            $name = $formdata['full-name'];

        } else if( isset($formdata['first-name']) && $formdata['last-name'] ){

            $name = $formdata['first-name'] . ' ' . $formdata['last-name'];

        }

    

        if( isset($formdata['password']) ){

            if( !isset($formdata['password2']) ){

                $password = $formdata['password'];

            } else {
            
                if( $formdata['password'] === $formdata['password2'] ){
                    $password = $formdata['password'];
                } else {
                    $ffd_cf7_register_error = 'The password provided do not match.';
                }
            }
            
        
        } else {
            $password = wp_generate_password( 12, false );
        }

        if ( email_exists( $email ) ) {

            $ffd_cf7_register_error = 'The email address provided already exist.';
        }

        if( isset($formdata['username'])){

            if( !username_exists( $formdata['username'] ) ){
                $username = $formdata['username'];
            } else {
                $ffd_cf7_register_error = 'The username provided already exist.';
            }

        } else {

          
            $username = $email;

            if( username_exists( $username ) ){
                $ffd_cf7_register_error = 'The email address provided already exist.';
            }
        }
        
        if ( empty($ffd_cf7_register_error) ) {
            
            $name_parts = explode(' ',$name);
            $first_name = reset($name_parts);
            $last_name = end($name_parts);

            // Create the user
            $userdata = array(
                'user_login' => $username,
                'user_pass' => $password,
                'user_email' => $email,
                'nickname' => $first_name,
                'display_name' => $name,
                'first_name' => $first_name,
                'last_name' => end($name_parts),
                'role' => 'subscriber'
            );

            $userdata = array_map('trim', $userdata);
            $user_id = wp_insert_user( $userdata );
            if ( !is_wp_error($user_id) ) {
               
                $credentials = array(
                    'user_login' => $username,
                    'user_password' => $password,
                );
                $user = wp_signon($credentials, false);

                wp_set_current_user($user_id);
                wp_set_auth_cookie($user_id);

                do_action('ffd_cf7_user_logged_in', $username, $user);
                do_action('ffd_cf7_user_registered', $user_id);

                
            } else {

                $ffd_cf7_register_error = $user_id->get_error_message();
            }
        } 


    

    if( !empty($ffd_cf7_register_error) ){

        $abort = true;
        

        $ffd_cf7_register_success = '';
        ffd_cf7_remove_registeration_success_message();
        ffd_cf7_add_registeration_error_message();
       
    } else {

        $abort = false;

        $ffd_cf7_register_error = '';
        $ffd_cf7_register_success = 'Thank you, your account is registered.';
        ffd_cf7_remove_registeration_error_message();
        ffd_cf7_add_registeration_success_message();
    }


    return $cfdata;
}
add_action('wpcf7_before_send_mail', 'ffd_cf7_user_registration_form', 10, 2);


function ffd_cf7_check_if_user_is_registered($cfdata){

    if ( $cfdata->title() == 'Registration Form') {
        global $ffd_cf7_register_success, $ffd_cf7_register_error;

        $ffd_cf7_register_error = '';
        ffd_cf7_remove_registeration_error_message();
        
        $ffd_cf7_register_success = 'Thank you, your account is registered.';
        ffd_cf7_add_registeration_success_message();
    }

}
add_action('wpcf7_mail_sent', 'ffd_cf7_check_if_user_is_registered', 10, 1);


function ffd_cf7_add_registeration_success_message(){
    add_filter('wpcf7_display_message', 'ffd_cf7_registeration_success_message', 10, 2);
}

function ffd_cf7_remove_registeration_success_message(){

    remove_filter('wpcf7_display_message', 'ffd_cf7_registeration_success_message', 10, 2);
}


function ffd_cf7_add_registeration_error_message(){

    add_filter('wpcf7_display_message', 'ffd_cf7_registeration_error_message', 10, 2);
}


function ffd_cf7_remove_registeration_error_message(){

    remove_filter('wpcf7_display_message', 'ffd_cf7_registeration_error_message', 10, 1);
}


/* 
/* Return success message if user is registered
*/
function ffd_cf7_registeration_success_message( $message, $status ){

    global $ffd_cf7_register_success;

    if( !empty($ffd_cf7_register_success) ){
        return $ffd_cf7_register_success; 
    }


    return $message;
}


/* 
/* Return error message if user is not registered
*/
function ffd_cf7_registeration_error_message( $message, $status ){

    global $ffd_cf7_register_error;

    if( !empty($ffd_cf7_register_error) ){
        return $ffd_cf7_register_error; 
    }


    return $message;
}



add_filter('wpcf7_form_hidden_fields', 'ffd_cf7_hidden_field_redirect');
function ffd_cf7_hidden_field_redirect(){

    $hidden_fields = array('ffd_cf7_redirect' => '');

    return $hidden_fields;
}

add_shortcode('cf7_redirect', 'ffd_cf7_redirect_script');
function ffd_cf7_redirect_script($atts=array(), $content=''){

    if( is_admin() )
        return '';

    $redirect_uri = isset($atts['redirect_uri']) ? $atts['redirect_uri'] : home_url();
    if( isset($_GET['redirect_uri']) ){
        $redirect_uri = $_GET['redirect_uri'];
    }

ob_start();
?>

    <script>
    if (typeof jQuery != 'undefined') {

        jQuery(function($){


            var $form = jQuery(document).find('input[name="ffd_cf7_redirect"]').closest('form');
    
            $form.closest( 'div.wpcf7' ).on('wpcf7:mailsent', function(e, detail){
                console.log(detail);
                window.location.replace("<?php echo $redirect_uri; ?>");
            });

          

            
        });

    }
    </script>
    
    
<?php
return ob_get_clean();

}



add_shortcode('conditional_content', 'ffd_conditional_content');
function ffd_conditional_content($attr=array(), $content=null){
	$function = $attr['condition'];
	
	
	if( is_admin() || empty($function) )
		return do_shortcode($content);
	
	$not_condition = false;
	if( strpos($function, '!' ) !== false ){
	  $not_condition = true;
	  $function = str_replace('!', '', $function);
		
	}
	
	$function = trim($function);
	
	if( !function_exists($function) )
		return 'invalid condition provided.';
	
	$value = isset($attr['value']) ? $attr['value'] : '';
    $message = isset($attr['message']) ? $attr['message'] : '';
    
    if( !empty($attr['redirect']) ){
    
        $redirect_uri = '';
        if( isset($_GET['redirect_uri']) ){
            $redirect_uri = $_GET['redirect_uri'];
        } else if( isset($attr['redirect_uri'])){
            $redirect_uri = $attr['redirect_uri'];
        } else {
            $redirect_uri = home_url();
        }

        $message = isset($attr['redirect_message']) ? $attr['redirect_message'] : '<p>Redirecting please wait...</p>';
        $message .= '<script>setTimeout(function(){ window.location.replace("'.$redirect_uri.'");  }, 3000);</script>';
       
    }
	
	
	$error = false;
	$check = null;
	try {
		
		
		$func_reflection = new ReflectionFunction($function);
		$num_of_params = $func_reflection->getNumberOfParameters();
		
		
		if( !empty($value )){
			
			$truthy = $not_condition ? !$function($value): $function($value);
			
			if( $truthy ) {
				return do_shortcode($content);
			} else {
				return $message;
			}
			
		} else {
			
			$truthy = $not_condition ? !$function() : $function();
			
			if( $truthy ) {
				return do_shortcode($content);
			} else {
				return $message;
			}
		
		}
		
		
	} catch (Exception $ex) {
		$error = $ex->getMessage();
	}
	
	if( $error ) {
		return $error;
	}


    return do_shortcode($content);
}


if( !function_exists('ffdf7_add_form_tag_ffdredirect') ){

    add_action( 'wpcf7_init', 'ffdf7_add_form_tag_ffdredirect', 10, 0 );

    function ffdf7_add_form_tag_ffdredirect() {
        wpcf7_add_form_tag(
            array( 'ffdredirect', 'ffdredirect*'),
            'ffdcf7_ffdredirect_form_tag_handler', array( 'name-attr' => true ) );
    }

}

function ffdcf7_ffdredirect_form_tag_handler( $tag ) {

    

    $value = (string) reset( $tag->values );

	if ( $tag->has_option( 'placeholder' )
	or $tag->has_option( 'watermark' ) ) {
		$atts['placeholder'] = $value;
		$value = '';
	}

	$value = $tag->get_default_option( $value );

	$value = wpcf7_get_hangover( $tag->name, $value );

    $redirect_uri = $value;
    
    $redirect_uri = !empty($redirect_uri) ? $redirect_uri : home_url();
    if( isset($_GET['redirect_uri']) ){
        $redirect_uri = $_GET['redirect_uri'];
    }

    ob_start();
    ?>
        <input type="hidden" name="ffdredirect_redirect" />
        <script>
        if (typeof jQuery != 'undefined') {
    
            jQuery(function($){
    
                var $form = jQuery(document).find('input[name="ffdredirect_redirect"]').closest('form');
    
                $form.closest( 'div.wpcf7' ).on('wpcf7:mailsent', function(e, detail){
                    window.location.replace("<?php echo $redirect_uri; ?>");
                });

                $form.closest( 'div.wpcf7' ).on('wpcf7:mailfailed', function(e, detail){
                    var message = detail.apiResponse.message;
                    if( message.toLowerCase() == 'thank you, your account is registered.' ) {
                        window.location.replace("<?php echo $redirect_uri; ?>");
                    }

                    if( message.toLowerCase() == 'login successful' ) {
                        window.location.replace("<?php echo $redirect_uri; ?>");
                    }

                    
                    
                });

            });
    
        }
        </script>
        
        
    <?php
    return ob_get_clean();
}

if( !function_exists('ffdf7_add_form_tag_password') ){

    add_action( 'wpcf7_init', 'ffdf7_add_form_tag_password', 10, 0 );

    function ffdf7_add_form_tag_password() {
        wpcf7_add_form_tag(
            array( 'password', 'password*'),
            'ffdcf7_password_form_tag_handler', array( 'name-attr' => true ) );
    }

}

function ffdcf7_password_form_tag_handler( $tag ) {
	if ( empty( $tag->name ) ) {
		return '';
	}

	$validation_error = wpcf7_get_validation_error( $tag->name );

	$class = wpcf7_form_controls_class( $tag->type, 'wpcf7-text' );


	if ( $validation_error ) {
		$class .= ' wpcf7-not-valid';
	}

	$atts = array();

	$atts['size'] = $tag->get_size_option( '40' );
	$atts['maxlength'] = $tag->get_maxlength_option();
	$atts['minlength'] = $tag->get_minlength_option();

	if ( $atts['maxlength'] and $atts['minlength']
	and $atts['maxlength'] < $atts['minlength'] ) {
		unset( $atts['maxlength'], $atts['minlength'] );
	}

	$atts['class'] = $tag->get_class_option( $class );
	$atts['id'] = $tag->get_id_option();
	$atts['tabindex'] = $tag->get_option( 'tabindex', 'signed_int', true );

	$atts['autocomplete'] = $tag->get_option( 'autocomplete',
		'[-0-9a-zA-Z]+', true );

	if ( $tag->has_option( 'readonly' ) ) {
		$atts['readonly'] = 'readonly';
	}

	if ( $tag->is_required() ) {
		$atts['aria-required'] = 'true';
	}

	$atts['aria-invalid'] = $validation_error ? 'true' : 'false';

	$value = (string) reset( $tag->values );

	if ( $tag->has_option( 'placeholder' )
	or $tag->has_option( 'watermark' ) ) {
		$atts['placeholder'] = $value;
		$value = '';
	}

	$value = $tag->get_default_option( $value );

	$value = wpcf7_get_hangover( $tag->name, $value );

	$atts['value'] = $value;

	
	$atts['type'] = 'password';
	

	$atts['name'] = $tag->name;

	$atts = wpcf7_format_atts( $atts );

	$html = sprintf(
		'<span class="wpcf7-form-control-wrap %1$s"><input %2$s />%3$s</span>',
		sanitize_html_class( $tag->name ), $atts, $validation_error );

	return $html;
}


/* Validation filter */

add_filter( 'wpcf7_validate_password', 'ffdcf7_password_validation_filter', 10, 2 );
add_filter( 'wpcf7_validate_password*', 'ffdcf7_password_validation_filter', 10, 2 );

function ffdcf7_password_validation_filter($result, $tag){

    $name = $tag->name;

	$value = isset( $_POST[$name] )
		? trim( wp_unslash( strtr( (string) $_POST[$name], "\n", " " ) ) )
		: '';

	if ( 'password' == $tag->basetype ) {
		if ( $tag->is_required() and '' == $value ) {
			$result->invalidate( $tag, wpcf7_get_message( 'invalid_required' ) );
		}
    }

  

    if ( '' !== $value ) {
		$maxlength = $tag->get_maxlength_option();
		$minlength = $tag->get_minlength_option();

		if ( $maxlength and $minlength and $maxlength < $minlength ) {
			$maxlength = $minlength = null;
		}

		$code_units = wpcf7_count_code_units( stripslashes( $value ) );

		if ( false !== $code_units ) {
			if ( $maxlength and $maxlength < $code_units ) {
				$result->invalidate( $tag, wpcf7_get_message( 'invalid_too_long' ) );
			} elseif ( $minlength and $code_units < $minlength ) {
				$result->invalidate( $tag, wpcf7_get_message( 'invalid_too_short' ) );
			}
		}
	}

	return $result;
}