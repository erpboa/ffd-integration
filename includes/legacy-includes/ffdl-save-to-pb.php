<?php

//error_reporting(E_ALL); ini_set('display_errors', 1); 

/* 
*
* Begin Save Favorites
------------------------------------
*/
add_action( 'wp_ajax_ffd_set_favorite', 'ffd_ajax_set_favorite' );
function ffd_ajax_set_favorite($ajax=true) {

    if( isset($_REQUEST['action']) && $_REQUEST['action'] == 'ffd_set_favorite' ){
        $ajax = true;
    }

	if ( isset( $_REQUEST ) ) {

        

		$user_id = get_current_user_id();
		$user = wp_get_current_user();
        $prop_id = isset($_REQUEST['prop_id']) ? $_REQUEST['prop_id'] : 0;

        if( !$prop_id ){
            wp_send_json_error(array('invalid_post'=> 'Invalid post id'));
        }
        $post = get_post($prop_id);

		if ( !$prop_id || !$post) {
			$data = array(
			'favorite' => false,
			);
			wp_send_json_success($data);
        }
        
        $user_meta_name = '_favorite_' . $post->post_type;
        $pb_user_meta_name = '_pb_favorite_' . $post->post_type;

		$likes = get_user_meta($user_id, $user_meta_name, true);
		$pb_likes = get_user_meta($user_id, $pb_user_meta_name, true);
		
		if( empty($pb_likes) ){
			$pb_likes = array();
		}
		
		$property_pbid = get_post_meta($prop_id, 'ffd_salesforce_id', true);

		$PB_params = array (
			'email' => $user->user_email,
			'first_name' => $user->user_firstname,
			'last_name'  => $user->user_lastname,
			'property'   => $property_pbid,
		);

		$status = null;
		if (is_array($likes) && ($key = array_search($prop_id, $likes)) !== false) {
					unset($likes[$key]);
			
			update_user_meta( $user_id, $user_meta_name, $likes );

			// $PB_params['message'] = 'User have deleted the folowing property ID from favorites: ' . $prop_id;
			$status = false;
			
		}  else {

			if( !is_array($likes) || empty($likes) )
				$likes = array();
			
			$likes[] = $prop_id;
			update_user_meta( $user_id, $user_meta_name, $likes );
			// $PB_params['message'] = 'User have added the folowing property ID to favorites: ' . $prop_id;

			$status = true;
		}

        /*begin update fdd_is_favorite*/

        $data = json_encode(
            array(
                'id_lead_live_modern' => $user_id,
                'ffd_mls_id' => get_post_meta($prop_id, 'ffd_mls_id', true),
                'ffd_is_favorite' => $status ? 1 : 0
            )
        ); //var_export($data);exit;
        //service insert time login
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => get_option('url_end_point')."/GreatSheet/updateFavorite",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/x-www-form-urlencoded",
                "Php-Auth-User: eyJjaXBoZXJ0ZXh0IjoiR3BWMzFjbGVaZERYSmtYTXhiZDNmMlFyK0V0QWd0UVZaOVwvcXpVbkd5YWVheVRxQUJqOWxvbDhsc1dWcWw4ZWx2ZnJDT2N3UFM5cFNyV0VFY1dYdXdnPT0iLCJpdiI6IjY5NzFjZjY4MTc1NTgwOGQxY2MwOTFiZjk5YmYyODM5Iiwic2FsdCI6IjZiOGNlMjY4NGM1ODYwMDRhMzZkMDFmNTE0ZWJlNzBmYTYwNWQyNzk3OTZhMWVmZDQ1MWUyZjdhYWYxYzRhNTRmMzM2Nzk5NmE3MjhhZWZiY2U3NGFkM2I2ZjEyYThkMmU1MDFmZWZmZmU3ZjcxYzExYTIzMWY1Zjg4M2E2NzFkNTEzMGFmOGNiNWE2ODAyYTBiYjIzM2E0ZDM1YzcxNDdiYWU1Y2I1OThkNjFmMWI4NWZlMDkyOTRiM2RlMTRlNDFkOTAxNWZhMGJlN2QwMmEyOWI4ZmJiNzEyM2E0MjljYmU1NDM5ZDkxMjI0MWVmOTFhMmIxYTIxOGFhMDFjOTdiMTc1NzllZTczODFkMmYxZmM0MWRlMTc4YzBhMDU5NjgyYjExZGIwNmM4MDY5MmMxYTMwNTUxNzhkZWI1NDUzOGMyYmVlMmUxNmFiMDA1NWIzOGI4YmQ4NjQwNTA4M2UxZjA3NzM4MDk4ZTVhYjJhNzZjNjRiMzUwNWIzOGUzNTA0YjFlMjE1ODY0YjQyZjM2MDI2MmUwNzJhNjQ3NzExMzFhNGNiYjQ5Y2E0YTVkMmExNjg0ZmY2YTBkY2I4NGVlYmE4MmIwYzg2MmU5YWQ5MGE0MzI1ZGQ0NzA0YjlhYTIzZWE4M2NmODNmNzk4OGQwYmZjOTQ2YTQ3ZDRmYzVkIiwiaXRlcmF0aW9ucyI6OTk5fQ==",
                "Pxp-user: admin",
                "auth-version: 2",
                "Cache-Control:no-cache",
                "Accept-Encoding: gzip, deflate, br",
                "Connection: keep-alive",
                "Accept:*/*"
            ),
        ));

        $response = curl_exec($curl);//var_dump($response);exit;
        $err = curl_error($curl);
        /*end update fdd_is_favorite*/

		//$PB_params['favorite_ids'] = $likes;
		//$_GET['testing'] = 1;
        
        /*if( !isset($pb_likes[$property_pbid]) &&  in_array($post->post_type, array('listing', 'property')) ){
			

            $r = FFDL_PB_Request::send_sf_message( $PB_params );
            if( !is_wp_error($r) ){
                $pb_likes[$property_pbid] = $prop_id;
			    update_user_meta( $user_id, $pb_user_meta_name, $pb_likes );
            } else {
                if( $ajax ){
                   wp_send_json_error(array('pb_user_favorite'=>$PB_params, 'response' => $r));
                }
            }
			do_action('ffd_logger', array('pb_user_favorite'=>$PB_params, 'response' => $r));
        }*/

		$data = array(
			'favorite' => $status,
            /* 'sf_response'  => $r, */
            'meta_name' => $user_meta_name
        );
        if( $ajax ){
            wp_send_json_success($data);
        }
	}
    if( $ajax ){
        die();
    }

    return $data;
}


function ffd_is_favorite($prop_id=false){

	if( !$prop_id){
		global $post;
		$prop_id = $post->ID;
	} else {
        $post = get_post($prop_id);
    }
	
	$user_id = get_current_user_id();
	if( !$user_id || !$post)
		return false;

    $user_meta_name = '_favorite_' . $post->post_type;

	$likes = get_user_meta($user_id, $user_meta_name, true);

	if ( is_array($likes) && in_array($prop_id, $likes)) {

		return true;
	}

	return false;
}


function ffd_get_favorites($post_type){

	$user_id = get_current_user_id();
	if( !$user_id )
		return array();

    $user_meta_name = '_favorite_' . $post_type;
	$likes = get_user_meta($user_id, $user_meta_name, true);

	

	return $likes;

}



function ajax_ffd_save_favorite($ajax=true)
{
    

    if (isset($_REQUEST)) {
        $user_id = get_current_user_id();
        $user = wp_get_current_user();
        $prop_id = $_REQUEST['prop_id'];

        if (!$prop_id) {
            $data = array(
                'favorite' => false,
            );
            wp_send_json_success($data);
        }

        $likes = get_user_meta($user_id, 'favorite_properties', false);
        $PB_params = array(
            'email' => $user->user_email,
            'first_name' => $user->user_firstname,
            'last_name'  => $user->user_lastname,
            //'property'   => $prop_id,
        );

        if (in_array($prop_id, $likes)) {
            delete_user_meta($user_id, 'favorite_properties', $prop_id);
        } else {
            add_user_meta($user_id, 'favorite_properties', $prop_id);
        }

        $likes = get_user_meta($user_id, 'favorite_properties', false);
        $PB_params['favorite_ids'] = $likes;
        //$_GET['testing'] = 1;
        $r = FFDL_PB_Request::send_sf_message($PB_params);
        do_action('ffd_logger', array('send_sf_save_favorite' => $r, 'params' => $PB_params));

        $data = array(
            'favorite' => in_array($prop_id, $likes),
            'sf_response'  => $r,
            'pb_params' => $PB_params
        );
        if( $ajax )
            wp_send_json_success($data);
    }
    if( $ajax ){
        die();
    }

    return $data;
}

add_action('wp_ajax_ffd_save_favorite', 'ajax_ffd_save_favorite');


/* 
*
* End Save Favorites
------------------------------------
*/






/* 
*
* Begin Contact Form Processing
------------------------------------
*/

add_filter('wpcf7_form_hidden_fields', 'ffdl_custom_hidden_fields', 100);
function ffdl_custom_hidden_fields($hidden_fields=array()){

    if( is_singular( array('listing', 'property' ) ) ){
        global $post;
        if( $sf_id = get_post_meta($post->ID, 'ffd_salesforce_id', true) ){
            $hidden_fields['listing-id'] = $sf_id;
        }
    } 
    return $hidden_fields;
}


/* 
*
* End Contact Form Processing 
------------------------------------
*/



/* 
*
* Begin Save Search
------------------------------------
*/

function ajax_ffd_savesearch($ajax=true)
{
  
    if (isset($_REQUEST)) {
        $params = array();
        if(isset($_REQUEST['form_data']) ){
            parse_str($_REQUEST['form_data'], $params);
        }

        $params = apply_filters('ffdl_savesearch_params', $params, $_REQUEST['command']);

        $user_id = get_current_user_id();
        $user = wp_get_current_user();
        //Check if name already exists

        if ('save' == $_REQUEST['command']) {
            if (! trim($_REQUEST['name'])) {
                wp_send_json_error(array( 'message' => 'Name can not be empty' ));
            }

            $PB_params = array(
                'email' => $user->user_email,
                'first_name' => $user->user_firstname,
                'last_name'  => $user->user_lastname,
                //'property'   => $prop_id,
            );

            $data = array_merge($PB_params, $params);

            $dbData=FFDL_Searches::search_PBRequestId($_REQUEST['name']);
            $pbRID=null;
            $requestId=null;

            if (isset($dbData)) {
                $requestId=$dbData["requestId"];
            }

            if (!isset($requestId)  ) {
                $result = FFDL_PB_Request::send_sf_message($data);
                
                do_action('ffd_logger', array('send_sf_save_search' => $result, 'params' => $params));
                //var_dump($result);
                if( !is_wp_error($result) )
                    $requestId = $result["request"]["Id"];
            }

            $data=array();
            if(isset($_REQUEST['form_data']) ){
                parse_str($_REQUEST['form_data'], $data);
            }

            $data = array_filter($data);

            unset($data["ttlitems"]);

            if ($data["property-type"]=="Select") {
                unset($data["property-type"]);
            }

            if (isset($data["district"]) && $data["district"]=="Select") {
                unset($data["district"]);
            }

            FFDL_Searches::save_search($_REQUEST['name'], http_build_query($data), $requestId);
        } elseif ('del' == $_REQUEST['command']) {
            $_REQUEST['id'] = trim($_REQUEST['id']);

            //Remove the request from PB
            $dbData=FFDL_Searches::search_PBRequestIdFromId($_REQUEST['id']);
            $pbRID=null;
            $requestId=null;

            if (isset($dbData)) {
                $requestId=$dbData["requestId"];
            }

            if (isset($requestId)) {
                FFDL_PB_Request::query("services/apexrest/removerequest?id=" . $requestId, array(), "DELETE");
            }

            FFDL_Searches::del_search($_REQUEST['id']);
        } elseif ('get' == $_REQUEST['command']) {
            $ssearch = FFDL_Searches::get_search($_REQUEST['id']);
            if( $ajax )
                wp_send_json_success($ssearch);
            else
                return $ssearch;
        }
    }
    
    $saved_searches = FFDL_Searches::get_searches_for_user();
    if( $ajax ){
        wp_send_json_success($saved_searches);
        die();
    }

    return $saved_searches;
}
add_action('wp_ajax_ffd_savesearch', 'ajax_ffd_savesearch');


/* 
*
* End Save Search
------------------------------------
*/



/* 
*
* Begin User Registered
------------------------------------
*/

add_action( 'ffd_cf7_user_registered', 'ffd_on_user_registered', 9, 1 );
function ffd_on_user_registered( $user_id ) {

    $user = get_user_by('id', $user_id);

    $query = array (
        'first_name' => !empty($user->first_name) ? $user->first_name : $user->display_name,
        'last_name'  => !empty($user->last_name) ? $user->last_name : $user->display_name,
        'email'     => $user->user_email,
        'message' => 'User registered on site'
     );
		 
		 
		$r = FFDL_PB_Request::send_sf_message( $query);

		do_action('ffd_logger', array('pb_user_registeration'=>$query, 'response' => $r));
    //save sf user id to usermeta
    if( !is_wp_error($r) && isset($r["contact"]["Id"]) ){
        update_user_meta($user_id, 'PBID', $r["contact"]["Id"]);
    }

		
}



/* 
*
* End User Registered
------------------------------------
*/


/* 
*
* Begin User Login ( also after user is registered & login )
------------------------------------
*/
add_action( 'wp_login', 'ffd_on_user_logged_in', 9, 2 );
add_action( 'ffd_cf7_user_logged_in', 'ffd_on_user_logged_in', 9, 2 );
function ffd_on_user_logged_in( $user_login, $user ) {

    //$user = get_user_by('id', $user_id);
    $date_utc = new DateTime("now", new DateTimeZone("UTC"));
    $last_login = $date_utc->format("Y-m-d\TG:i:s"); 
    $query = array (
        'first_name' => !empty($user->first_name) ? $user->first_name : $user->display_name,
        'last_name'  => !empty($user->last_name) ? $user->last_name : $user->display_name,
        'email'     => $user->user_email,
        'last_login'  => $last_login
     );
		 
 

	$r = FFDL_PB_Request::send_sf_message( $query);
	
    //save sf user id to usermeta
    if( !is_wp_error($r) && isset($r["contact"]["Id"]) ){
        update_user_meta($user->ID, 'PBID', $r["contact"]["Id"]);
    } else {
        do_action('ffd_logger', array('pb_user_logged_in'=>$query, 'response' => $r));
    }

		
}

/* 
*
* Begin User Login ( also after user is registered & login )
------------------------------------
*/





/*  
*
* Save analytics
-----------------------------------------
*/

function ffdl_save_listing_view_analytics(){

    global $post;
    $listing_id = get_post_meta($post->ID, 'ffd_salesforce_id', true);
    FFDAnalytics::recordAnalytics($listing_id);

}
add_action('ffdl_listing_template_before', 'ffdl_save_listing_view_analytics');




add_action('init', 'ffdl_unsubscribe_emails', 21);
function ffdl_unsubscribe_emails(){

    if( isset($_GET['ffd_action']) && $_GET['ffd_action'] == 'unsubscribe' ){
        $pbid = isset($_GET["pbid"]) ? $_GET["pbid"] : null;
        $reqId= isset($_GET["rid"]) ? $_GET["rid"] : null;
        $PB_params=array();

        if(isset($pbid))
        {
            $args = array(
                'meta_query' => array(
                    array(
                        'key' => 'PBID',
                        'value' => $pbid,
                        'compare' => 'LIKE'
                    )
                )
            );


            $u= get_users($args);

            $user_id=$u[0]->ID;

            update_user_meta($user_id,'optout','true');

            $PB_params = array (
                    'id' => $pbid,
                    'optout' => "true"
                );

            $r = FFDL_PB_Request::send_sf_message( $PB_params );

            if( is_wp_error($r) ){
                do_action('ffd_logger', array('pb_unsubscribe'=>$r));
            }

            wp_die('<div class="all-mailing"><p><br<br>Successfully unsubscribed.</p></div>', 'Unsubscribe');

        }else{
            $rid=$_GET["rid"];

            //Remove the request from PB
            $dbData=FFDL_Searches::search_FromRequestId($rid);

            $name = '';
            if(isset($dbData)){
                $name =$dbData["name"];
            }
        
            $r = FFDL_PB_Request::query("services/apexrest/removerequest?id=" . $rid,array(),"DELETE");

            if( is_wp_error($r) ){
                do_action('ffd_logger', array('pb_unsubscribe'=>$r));
            }
            
            wp_die('<div class="saved-search saved-search-'.$name.'" ><p><br<br>Successfully unsubscribed.</p></div>', 'Unsubscribe');

        }

    }   


}




/*
*
*/

function ffdl_send_webtoprospect_contact($args_values){

    $agentId = null;

    $args=array(
        "first_name" => '',
        "last_name" => '',
        "phone" => '',
        "email" => '',
        "interest" => '',
        "property" => '',
        "message" => ''
    );

    $fields_mappings = get_option('ffd_form_fields_mappings');
    // split by line
    $fields_mappings = preg_split('/\r\n|\r|\n/', $fields_mappings);
    $fields_mappings = array_filter($fields_mappings, 'strlen');
    
    $data = array();
    $_test = array();
    foreach($fields_mappings as $format ){
        // formtype:sfkey:form_field_name
        $format = trim($format);
        $keys = explode(':', $format);
        $_test[] = $keys;

        if( count($keys) >= 3 ){
            $type = $keys[0];
            $sfkey = $keys[1];
            $name = $keys[2];

            if( isset($args_values[$name]) && in_array($type, array('cf7', 'gravity', 'ninja')) ){
                $data[$sfkey] = $args_values[$name];
            }
        }
    }
    
    

    //if no values for mapping
    //try to using default args to find value in form submitted data
    if( empty($data) ){
        $data = wp_parse_args($args, $args_values);
        if( empty($data) ){
            return;
        }
    }
    


    if( empty($data['first_name']) && !empty($data['name']) ){
        $peices = explode(' ', $data['name']);
        $data['first_name'] = $peices[0];
        $data['last_name'] = isset($peices[1]) ? $peices[1] : $peices[0];
        unset($data['name']);
    } 
    
    if( empty($data['last_name']) && !empty($data['first_name']) ){
        $peices = explode(' ', $data['first_name']);
        
        $data['first_name'] = $peices[0];
        $data['last_name'] = isset($peices[1]) ? $peices[1] : $peices[0];
        
    }

    if( empty($data['property'])  ){
        $post_type = ffd_get_listing_posttype();
        if(is_singular($post_type) ){
            global $post;
            $data['property'] = get_post_meta('ffd_salesforce_id', true);
        }
    }
    
    

    $r = FFDL_PB_Request::send_sf_message($data, $agentId);
    
    //do_action('ffd_logger', array('pb_process_all_forms'=>$data, 'response' => $r));
}



/* NINJA FORMS */
function ffd_nf_sub( $id = '', $form_id='' )
{
    if( class_exists('NF_Database_Models_Submission') )
        return new NF_Database_Models_Submission( $id, $form_id );

    return null;
}


function ffd_nf_get_form($id){
    global $wpdb;
    if( class_exists('NF_Abstracts_ModelFactory') )
        return new NF_Abstracts_ModelFactory( $wpdb, $id );

    return null;
        
}

function ffdl_process_ninja_form($sub_id){
    global $ninja_forms_processing;

    $sub_id = $ninja_forms_processing->get_form_setting( 'sub_id' );
		$form_id = $ninja_forms_processing->get_form_ID();
        $field_data = $ninja_forms_processing->get_all_fields();
        
    //try{
        // Returns an Submission Model for Submission ID 1
        //$sub = Ninja_Forms()->form($_POST['_form_id'])->sub($sub_id);
        $form_id = $_POST['_form_id'];

/*         $form = Ninja_Forms()->form( $form_id );
        $_field_values = array();
        foreach ($field_data as $field_id => $field_value ){
            $field = Ninja_Forms()->form()->get_field( $field_id );
            $key = $field->get_setting( 'key' );
            if( $key ) {
                $_field_values[ $key ] = implode(', ', $field_value);
            }
        }
 */
        
        //$sub = ffd_nf_sub($sub_id, $form_id);
        //$form = Ninja_Forms()->form($form_id);
        //$formdata = $sub->get_field_values();
        //print_r($form);
       // print_r($form);
        
        
        remove_action( 'nf_save_sub', 'ffdl_process_ninja_form', 11, 1);
        //return;
        //$data = array();
        //$field_value = $sub->get_field_value( $field_key );

        //$formdata = $sub->get_all_fields();
        

        ffdl_send_webtoprospect_contact($field_data);
    //}catch(Exception $e){
           
    //}

}
function ffdl_ninja_forms_after_submission(){

}
function ffd_nf_init(){
    add_action( 'nf_save_sub', 'ffdl_process_ninja_form', 11, 1);
    ///add_action( 'ninja_forms_after_submission', 'ffdl_ninja_forms_after_submission', 11, 1);
}
add_action('nf_init', 'ffd_nf_init');
/* CONTACT FORM 7 */
add_action("init", function () {
    $GLOBALS["wpcf7_user_id"] = wp_get_current_user()->ID;
});

function ffdl_process_cf7_form($cfdata, &$abort) {  
    
    $formdata = null;
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
    } 

    if( $formdata ){
        ffdl_send_webtoprospect_contact($formdata);
    }
   
    

    return $cfdata;
}
add_action('wpcf7_before_send_mail', 'ffdl_process_cf7_form', 20, 2);

/* GRAVITY FORM  */
add_action( 'gform_after_submission', 'ffdl_process_gravity_form', 10, 2 );
function ffdl_process_gravity_form( $entry, $form ) {

    $formdata = array();
    $form_id = $form['id'];
    foreach ( $form['fields'] as $field ) {
        $inputs = $field->get_entry_inputs();
        if ( is_array( $inputs ) ) {
            $values = array();
            foreach ( $inputs as $input ) {
                $values[] = rgar( $entry, (string) $input['id'] );
            }
            $formdata[$input['id']] = implode(', ', $values);
        } else {
            $value = rgar( $entry, (string) $field->id );
            $formdata[$input['id']] = $value;
        }
    }

    ffdl_send_webtoprospect_contact($formdata);
}