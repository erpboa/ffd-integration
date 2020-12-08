<?php

/**
 *
 *
 * @package FFD_Organizer
 * @since   1.0.0
 */
defined( 'ABSPATH' ) || exit;

/**
 *
 * @class FFD_Organizer
 */
class FFD_Organizer {

    protected static $_instance = null;

    public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
    }

    public function __construct() {
        
        $this->init_hooks();
       
    
    }


     /**
	 * Hook into actions and filters.
	 *
	 * @since 1.0
	 */
    public function init_hooks(){

        //register_activation_hook(FFD_PLUGIN_FILE, array($this, 'activate_cronjob'));
        //register_deactivation_hook(FFD_PLUGIN_FILE, array($this, 'deactivate_cronjob'));
        //add_action('cronjob_ffd_organizer', array($this, 'run_cronjob'));

    }


    function activate_cronjob() {
        
        if (! wp_next_scheduled ( 'cronjob_ffd_organizer')) {
            wp_schedule_event( time(), 'hourly', 'cronjob_ffd_organizer');
        }
    }


    function deactivate_cronjob() {
        wp_clear_scheduled_hook( 'cronjob_ffd_organizer' );
    }


    
    function run_cronjob(){


    }

    function pb_get_user_inquiries($user_pbid=null){

        
        if( !$user_pbid )
            return new WP_Error('invalid_id', 'Please provide a valid Contact Id.');

        $object = 'pba__Request__c';
        $field_names = $this->get_search_fields('pb');
        $field_names = array_unique($field_names);
        
        $pbquery = "SELECT " . implode(',' , $field_names) . " FROM pba__Request__c WHERE pba__Contact__c='".$user_pbid."'";
        $response = FFD_SalesForce()->rest->query($pbquery);
        

        return $response;
    }


    function debug($test, $exit=false){

        if( !is_bool($test) && !is_scalar($test) )
            echo $test = '<pre>' . print_r($test, true) .  '</pre>'; 
        else
            var_dump($test);

        if( $exit === true ) exit;
    }



    function get_web2prospect_query($body){

        $user_id=0;

        if(is_user_logged_in())
            $user_id=get_current_user_id();

        
        $query = array (
            'prospect' => array(
                'contact' => array (
                    'LeadSource' => 'Website',
                    'Email'      => $body['email'],
                ),
                'contactFields' => array(
                    'Email',
                    'LastName',
                    'Phone'
                ),
                'request' => array(),
                'favorite'	=> array(),
            )
        );

        if ( ! empty( $body['user_id'] ) ) {
            $user_id=$body['user_id'];
        }

        if ( ! empty( $body['first_name'] ) ) {
            $query ['prospect']['contact']['FirstName'] =  $body['first_name'];
        }

        if ( ! empty( $body['optout'] ) ) {
            $query['prospect']['contact']['HasOptedOutOfEmail']=$body['optout'];
        }

        if ( ! empty( $body['last_name'] ) ) {
            $query ['prospect']['contact']['LastName'] =  $body['last_name'];
        }

        if ( ! empty( $body['name'] ) ) {
            $query ['prospect']['contact']['FirstName'] =  $body['name'];
        }

        if ( ! empty( $body['phone'] ) ) {
            $query ['prospect']['contact']['Phone'] =  $body['phone'];
        }

        if ( ! empty( $body['contactid'] ) ) {
            $query ['prospect']['contact']['Id'] =  $body['contactid'];
        }

        if ( ! empty( $body['listing_email_alerts'] ) ) {
            $query ['prospect']['contact']['Listing_Email_Updates__c'] =  true;
        }

        if ( ! empty( $body['comparable_home_updates'] ) ) {
            $query ['prospect']['contact']['Comparable_Home_Updates__c'] =  true;
        }

        if ( ! empty( $body['website_updates'] ) ) {
            $query ['prospect']['contact']['Website_Updates__c'] =  true;
        }

        if ( ! empty( $body['ownerid'] ) ) {
            $query ['prospect']['contact']['OwnerID'] = $body['ownerid'];
        }

        

        if ( ! empty( $body['ssearch-name'] ) ) {
            $query['prospect']['request']['Search_Name__c'] = $body['ssearch-name'];
        }

        if ( ! empty( $body['search-frequency'] ) ) {
            $query['prospect']['request']['Email_Frequency__c'] = $body['search-frequency'];
        }
        
        if ( ! empty( $body['email-sendtime'] ) ) {
            $query['prospect']['request']['Email_SendTime__c'] = $body['email-sendtime'];
        }

        if ( ! empty( $body['subject'] ) ) {
            $query['prospect']['request']['Subject__c'] = $body['subject'];
        }
        
        if ( ! empty( $body['type'] ) ) {
            $query['prospect']['request']['Request_Type__c'] = $body['type'];
        }

        if ( ! empty( $body['daysm'] ) ) {
            $query['prospect']['request']['Days_on_Market__c'] = $body['daysm'];
        }

        if ( ! empty( $body['requestId'] ) ) {
            $query['prospect']['request']['Id'] = $body['requestid'];
        }

        if ( ! empty( $body['changed'] ) ) {
            $query['prospect']['request']['Price_Changed_Days_Max__c'] = $body['changed'];
        }

        if ( ! empty( $body['brokerage'] ) ) {
            $query['prospect']['request']['Only__c'] = "true";
        }

        if ( ! empty( $body['pool'] ) ) {
            $query['prospect']['request']['Pool__c'] = $body['pool'];
        }

        if ( ! empty( $body['minprice'] ) ) {
            $query['prospect']['request']['pba__ListingPrice_pb_min__c'] = $body['minprice'];
        }

        if ( ! empty( $body['maxprice'] ) ) {
            $query['prospect']['request']['pba__ListingPrice_pb_max__c'] = $body['maxprice'];
        }

        if ( ! empty( $body['baths'] ) ) {
            $query['prospect']['request']['pba__FullBathrooms_pb_min__c'] = $body['baths'];
        }

        if ( ! empty( $body['tenure'] ) ) {
            $query['prospect']['request']['Tenure__c'] = $body['tenure'];
        }

        if ( ! empty( $body['minsq'] ) ) {
            $query['prospect']['request']['pba__TotalArea_pb_min__c'] = $body['minsq'];
        }

        if ( ! empty( $body['maxsq'] ) ) {
            $query['prospect']['request']['pba__TotalArea_pb_max__c'] = $body['maxsq'];
        }

        if ( ! empty( $body['minacr'] ) ) {
            $query['prospect']['request']['pba__LotSize_pb_min__c'] = $body['minacr'];
        }

        if ( ! empty( $body['maxacr'] ) ) {
            $query['prospect']['request']['pba__LotSize_pb_max__c'] = $body['maxacr'];
        }

        if ( ! empty( $body['proptype'] ) ) {
            $query['prospect']['request']['pba__PropertyType__c'] = implode(";",$body['proptype']);
        }

        if ( ! empty( $body['propset'] ) ) {
            $query['prospect']['request']['Property_Settings__c'] = implode(";",$body['propset']);
        }

        if ( ! empty( $body['district'] ) ) {
            $query['prospect']['request']['District__c'] = implode(";",$body['district']);
        }

        if ( ! empty( $body['status'] ) ) {
            $query['prospect']['request']['Statuses__c'] = implode(";",$body['status']);
        }


        /* Based on julie search form */

        if ( ! empty( $body['parking'] ) ) {
            $query['prospect']['request']['parkingspaces__c'] = $body['parking'];
        }

        if ( ! empty( $body['view'] ) ) {
            $query['prospect']['request']['view__c'] = $body['view'];
        }

        if ( ! empty( $body['complex'] ) ) {
            $query['prospect']['request']['complex_name__c'] = $body['complex'];
        }
        
        if ( ! empty( $body['yearbuilt-min'] ) ) {
            $query['prospect']['request']['pba__yearbuilt_pb__c'] = $body['yearbuilt-min'];
        }

        if ( ! empty( $body['yearbuilt-max'] ) ) {
            $query['prospect']['request']['pba__yearbuilt_pb__c'] = $body['yearbuilt-max'];
        }

        if ( ! empty( $body['lotsize-min'] ) ) {
            $query['prospect']['request']['pba__LotSize_pb_min__c'] = $body['lotsize-min'];
        }

        if ( ! empty( $body['lotsize-max'] ) ) {
            $query['prospect']['request']['pba__LotSize_pb_max__c'] = $body['lotsize-max'];
        }
        

        if ( ! empty( $body['sqft-min'] ) ) {
            $query['prospect']['request']['pba__TotalArea_pb_min__c'] = $body['sqft-min'];
        }

        if ( ! empty( $body['sqft-max'] ) ) {
            $query['prospect']['request']['pba__TotalArea_pb_max__c'] = $body['sqft-max'];
        }

        if ( ! empty( $body['min_price'] ) ) {
            $query['prospect']['request']['pba__ListingPrice_pb_min__c'] = $body['min_price'];
        }

        if ( ! empty( $body['max_price'] ) ) {
            $query['prospect']['request']['pba__ListingPrice_pb_max__c'] = $body['max_price'];
        }
        
        if ( ! empty( $body['property_type_filter'] ) ) {
            $query['prospect']['request']['pba__PropertyType__c'] = implode(";",$body['property_type_filter']);
        }
        
        if ( ! empty( $body['city_type_filter'] ) ) {
            $query['prospect']['request']['pba__City_pb__c'] = implode(";",$body['city_type_filter']);
        }
        
        if( isset($body['area_type_filter']) && !isset($body['neighborhood'])){
            $body['neighborhood'] = $body['area_type_filter'];
        }

        if(isset($body['neighborhood']) && !empty($body['neighborhood'])){
            $query['prospect']['request']['area_text__c'] = implode(";",$body['neighborhood']);
        }
        
        

        if ( ! empty( $body['beds'] ) ) {
            
            $query['prospect']['request']['pba__Bedrooms_pb_min__c'] = str_replace("+","",$body['beds'][0]);
        }

        if ( ! empty( $body['community_name'] ) ) {
            $query['prospect']['request']['Message__c'] = $body['community_name'] . ': Tour Request';
        }

        if ( ! empty( $body['message'] ) || ! empty( $body['community_name'] ) ) {
            if ( ! empty( $body['message'] ) ) {
                $query['prospect']['request']['pba__Comments__c'] = $body['message'];
            }

            if ( ! empty( $body['community_name'] ) ) {
                $query['prospect']['request']['Message__c'] = $body['community_name'] . ': Tour Request';
            }
        }

        if( !empty($query['prospect']['request']) ){
            // $query['prospect']['request']['Is_API__c'] = 'true';
        }

        if( !empty($query['prospect']['contact']) ){
           // $query['prospect']['contact']['Is_API__c'] = 'true';
        }

        
        if ( ! empty( $body['favorite'] ) ) {
            $query ['prospect']['favorite']['pba__Listing__c'] = $body['favorite'];
        }
        



        return $query;
        
    }



    function get_contact_fields($context=null){

        $fields = array(
            'email' => 'Email',
            'first_name' => 'FirstName',
            'last_name' => 'LastName',
            'last_name' => 'LastName',
            'name' => 'FirstName',
            'phone' => 'Phone',
            'ownerid' => 'OwnerID',
            'listing_email_alerts' => 'Listing_Email_Updates__c',
            'comparable_home_updates' => 'Comparable_Home_Updates__c',
            'website_updates' => 'Website_Updates__c',
        );

        $fields = array_map('strtolower', $fields);

        if( $context === 'pb' )
            return array_values($fields);
        if( $context === 'db' )
            return array_keys($fields);
        else
            return $fields;
    }


    function get_linkedlisting_fields($context=null){

        $fields = array(
            'AccountId'	=> 'pba__Account__c', 	
            'ContactId'	=> 'pba__Contact__c',
            'ContactMobile' => 'Contact_Mobile__c',
            'ContactPhone' => 'Contact_Phone__c',	
            'Inquiry' => 'pba__Request__c',
            'ListingStatus' => 'Listing_Status__c', 
            'Price' => 'Price_from_Listing__c'
        );

        $fields = array_map('strtolower', $fields);

        if( $context === 'pb' )
            return array_values($fields);
        if( $context === 'db' )
            return array_keys($fields);
        else
            return $fields;
    }



    function get_search_fields($context=null){

        $fields = array(
            'ssearch-name' => 'Search_Name__c',
            'search-frequency' => 'Email_Frequency__c',
            'email-sendtime' => 'Email_SendTime__c',
            'daysm' => 'Days_on_Market__c',
            'minprice' => 'pba__ListingPrice_pb_min__c',
            'maxprice' => 'pba__ListingPrice_pb_max__c',
            'baths' => 'pba__FullBathrooms_pb_min__c',
            'minsq' => 'pba__TotalArea_pb_min__c',
            'maxsq' => 'pba__TotalArea_pb_max__c',
            'minacr' => 'pba__LotSize_pb_max__c',
            'maxacr' => 'pba__LotSize_pb_min__c',
            'proptype' => 'pba__propertytype__c',
            'district' => 'District__c',
            'status' => 'Statuses__c',
            'parking' => 'parkingspaces__c',
            //'complex' => 'complex_name__c', //not exists
            'yearbuilt-min' => 'pba__YearBuilt_pb_max__c',
            'yearbuilt-max' => 'pba__YearBuilt_pb_min__c',
            'lotsize-min' => 'pba__LotSize_pb_min__c',
            'lotsize-max' => 'pba__LotSize_pb_max__c',
            'sqft-min' => 'pba__TotalArea_pb_min__c',
            'sqft-max' => 'pba__TotalArea_pb_max__c',
            'min_price' => 'pba__ListingPrice_pb_min__c',
            'max_price' => 'pba__ListingPrice_pb_max__c',
            'beds-min' => 'pba__Bedrooms_pb_min__c',
            'beds-max' => 'pba__Bedrooms_pb_max__c',
            'property_type_filter' => 'pba__PropertyType__c',
            'city_type_filter' => 'pba__City_pb__c',
            //'neighborhood' => 'area_text__c', // not exits;
            'search_txt'	=> 'pba__Keywords_pb__c',
            
            'message' => 'pba__Comments__c',
            'community_name' => 'Message__c',
        );

        $fields = array_map('strtolower', $fields);

        if( $context === 'pb' )
            return array_values($fields);
        if( $context === 'db' )
            return array_keys($fields);
        else
            return $fields;
    }


    function get_myproperty_fields($context=null){

        $fields = array(
            'address' => 'Address__c',
            'city' => 'City__c',
            'state' => 'State__c',
            'zipcode' => 'ZipCode__c',
        );

        if( $context === 'pb' )
            return array_values($fields);
        if( $context === 'db' )
            return array_keys($fields);
        else
            return $fields;

    }




    function get_userdata($user_id=null){

        if( !$user_id )
            $user_id = get_current_user_id();

        $user = get_user_by('id', $user_id);
        
        if( is_wp_error($user) || !$user )
            return array();

        return array (
            'email' => $user->user_email,
            'first_name' => $user->first_name,
            'last_name'  => !empty($user->last_name) ? $user->last_name : $user->display_name
        );

    }



    function savedetails($data){

        $current_user = wp_get_current_user();
        $user_params = array (
            'email' => $current_user->user_email,
            'first_name' => $current_user->first_name,
            'last_name'  => !empty($current_user->last_name) ? $current_user->last_name : $current_user->display_name
        );


        //required fields
        $user_data = array(
            'ID' => $current_user->ID,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'user_email' => $data['email'],
        );

        $user_data = array_filter($user_data, 'strlen');

        $added = false;
        $message = '';

        if( !empty($user_data) ){

            wp_update_user($user_data);

            $current_pass = $data['current_pass'];
            $new_pass = $data['new_pass'];
            $repeat_pass = $data['repeat_pass'];

        
            
            if( !empty($current_pass) ){

                if ( $current_user && wp_check_password( $current_pass, $current_user->data->user_pass, $current_user->ID )) {

                    if( !empty($new_pass) && $new_pass === $repeat_pass  ){
                        wp_set_password( $new_pass, $current_user->ID);
                        $message = 'Details and Password updated.';
                        $added = true;
                    } else {
                        $message = 'New and Old Password do not match.';
                    }
                    
                } else {
                    $message = 'Invalid current password.';
                }

            } else {
                $message = 'Details updated.';
                $added = true;
            }

        } else {
            $message = 'Error: Missing required field(s) for My Info.';
        }


        return array('added' => $added, 'message' => $message );

    }



    function save_myproperty($data){
        
        $current_user = wp_get_current_user();
        $user_params = array (
            'email' => $current_user->user_email,
            'first_name' => $current_user->first_name,
            'last_name'  => !empty($current_user->last_name) ? $current_user->last_name : $current_user->display_name
        );

        $properties = get_user_meta( $current_user->ID, '_organizer_my_properties', true);
        if(empty($properties) ){
            $properties = array();
        }



        //add required fields here
        $property_data = array(
            'address' => $data['address'],
            'city' => $data['city'],
            'zipcode' => $data['zipcode'],
            'state' => $data['state'],
            
            'created' => current_time('mysql')
        );
        $required_count = count($property_data);
        
        $property_data['pbid'] = $data['pbid'];

        $address = sanitize_title($property_data['address']);
        
        
        $property_data = array_filter($property_data, 'strlen');
        $posted_count = count($property_data);

        $non_required = array(); //added not required fields here
        $property_data = array_merge($non_required, $property_data);

        $added = false;
        $message = '';
        if( $required_count !== $posted_count ){
            $message = "Error: Missing required field(s) for My Properties.";
        } else if( empty($address) ){
            $message = "Error: Address is required for adding property.";
        } else if(isset($properties[$address])){
            $message = "Error: Property already exist with this address.";
        } else {

            $response = $this->pb_save_myproperty($current_user, $property_data);

            if( !is_wp_error($response) ){
                $property_data['pbid'] = $response;
                $properties[$address]=$property_data;
                update_user_meta($current_user->ID, '_organizer_my_properties', $properties);
                $message = 'Added New "My Property".';
                $added = true;
            } else {
                $message =  'Error: Could not Save Property. <br> ' . $response->get_error_message();
            }

            

        }


        return array('added' => $added, 'message' => $message );

    }



    function save_emailprefrences($data){

        $user_id = get_current_user_id();
        $prefrences_data = array( 
            'listing_email_alerts' => $data['listing_updates'],
            'comparable_home_updates' => $data['myproperty_updates'],
            'website_updates' => $data['website_updates']

        );
        
        $prefrences_data = array_filter($prefrences_data, 'strlen');
        $response = $this->pb_save_emailprefrences($prefrences_data, $user_id);

        $added = false;
        $message = '';
        if( !is_wp_error($response)  ){

            update_user_meta($user_id, '_organizer_email_prefrences', $prefrences_data);
            $message = 'Updated Email Prefrences.';
            $added = true;

        } else {
            $message =  'Error: Could not Update Email Prefrences. <br> ' . $response->get_error_message();
        }


        return array('added' => $added, 'message' => $message );
    }



    function array_to_atts($data){

        $str = '';
        
        if( !empty($data) ){
            foreach($data as $name => $value ){ 
                if( is_scalar($value) ){
                    $str .= ' data-' . $name . '="' . $value . '" '; 
                }
            }
        }
        return $str;
    }


    function reset_data(){
        $user_id = get_current_user_id();

        if( $_GET['reset'] == '_organizer_my_properties' ) 
            update_user_meta($user_id, '_organizer_my_properties', '');

        if( $_GET['reset'] == '_organizer_email_prefrences' )
            update_user_meta($user_id, '_organizer_email_prefrences', '');

        if( $_GET['reset'] == '_organizer_save_searches' ){
            update_user_meta($user_id, '_organizer_save_searches', '');
            update_user_meta($user_id, '_saved_searches', '');
        } 
    }




    function get_pbid_by_email($user_id=null){

        if( !$user_id )
            $user_id = get_current_user_id();

        $pbid = null;
        $user = get_user_by('id', $user_id);
        if( !is_wp_error($user) && $user ){
            //$field_names = array('id');
            //$pbquery = "SELECT " . implode(',' , $field_names) . " FROM Contact WHERE Email='".$user->user_email."' LIMIT 1";
            //$response = FFD_SalesForce()->rest->query($pbquery);
            $response = $this->pb_get_record('Email', $user->user_email, 'Contact');
            
            if( is_wp_error($response) ){
                return $response;
            }

            $pbid = $this->get_object_var($response, 'id'); 

        }

        return $pbid;
    }


    function pb_get_user_id($user_id){

        if( !$user_id )
            return null;

        return get_user_meta($user_id, 'rest_pbid', true);
        
    }


    function pb_update_user_id($user_id, $value){

        if( !$value )
            return;

    return update_user_meta($user_id, 'rest_pbid', $value);

    }



    function not_empty($value){
        
        $return =  !( empty($value) && !is_numeric($value) && !is_bool($value) );
        return $return;
    }



    function w2p_clean_value($data, $pbfield=null){

        if( is_array($data) ){
            $value = array();
            foreach($data as $k => $v ){
                if( $v !== '_0_' && $v != '0' ){
                    $value[$k] = $v;
                }
            }
        } else {
            $value = '';
            if( $data !== '_0_' && $data != '0' ){
                $value = $data;
            }
        }

        return $value;
    }

    function get_var($obj, $var, $default=null){
	
        if( !$obj || !is_array($obj) )
            return $default;
        
        
        foreach($obj as $key => $value) {
    
            if(strtolower($var) == strtolower($key)) {
                return $value;
                break;
            }
        }
        
        
        return $default;
    }

    function pb_save_myproperty($current_user, $property_data){


        $user_pbid = $this->pb_get_user_id($current_user->ID);
        $prop_pbid = $property_data['pbid'];
        
        $data = array(
            'Address__c' => $property_data['address'],
            'City__c' => $property_data['city'],
            'State__c' => $property_data['state'],
            'ZipCode__c' => $property_data['zipcode'],
            'Contact__c' => $user_pbid
        );

        if( !empty($prop_pbid) ){
            $response = $this->pb_update_record($data, $prop_pbid, 'My_Property__c');
        } else {
            $response = $this->pb_insert_record($data, 'My_Property__c');
        }


        return $response;

    }


    function pb_get_record($field, $value, $object_name){

        $method = 'GET';
        $endpoint = FFD_SalesForce()->rest->get_endpoint('/sobjects/'.$object_name.'/');
        $endpoint = $endpoint . $field . '/' . $value . '?fields=id';
        $response = FFD_SalesForce()->rest->rest_request(null, $endpoint, $method);
        
        return $response;

    }


    function pb_insert_record($data, $object_name){

        $method = 'POST';
        $endpoint = FFD_SalesForce()->rest->get_endpoint('/sobjects/'.$object_name.'/');
        $response = FFD_SalesForce()->rest->rest_request($data, $endpoint, $method);
        
        if( !is_wp_error($response)  ){
            return $this->get_object_var($response, 'Id');
        } 

        return $response;

    }


    function pb_update_record($data, $id, $object_name){

        $method = 'PATCH';
        $endpoint = FFD_SalesForce()->rest->get_endpoint('/sobjects/'.$object_name.'/');
        $endpoint = $endpoint . $id;

        $response = FFD_SalesForce()->rest->rest_request($data, $endpoint, $method);
        //echo $test = '<pre>' . print_r($response, true) .  '</pre>'; exit;
        if( !is_wp_error($response)  ){
            return $this->get_object_var($response, 'id', $id);
        } 

        return $response;

    }


    function pb_delete_record($id, $object_name){

        if( empty($id) )
            return new WP_Error('invalid_pb_id', 'Please provide a valid id for deleting record.');

        if( empty($object_name) )
            return new WP_Error('invalid_pb_id', 'Please provide a API Name for object.');

        $method = 'DELETE';
        $endpoint = FFD_SalesForce()->rest->get_endpoint('/sobjects/'.$object_name.'/');
        $endpoint = $endpoint . $id;
        $response = FFD_SalesForce()->rest->rest_request(null, $endpoint, $method);

        
        if( !is_wp_error($response)  ){
            return $this->get_object_var($response, 'id', $id);
        } 

        return $response;

    }


    function pb_save_emailprefrences($prefrences_data, $user_id){

        $user_params = $this->get_userdata($user_id);

        $user_pbid = $this->pb_get_user_id($user_id);
        $method = 'POST';
        $endpoint = FFD_SalesForce()->rest->get_endpoint('/sobjects/Contact/');

        $request_arg = array();
        $request_arg['FirstName'] = $user_params['first_name'];
        $request_arg['LastName'] = $user_params['last_name'];
        $request_arg['Email'] = $user_params['email'];
        $request_arg['Phone'] = $user_params['phone'];
        $request_arg['Listing_Email_Updates__c'] =  false;
        $request_arg['Comparable_Home_Updates__c'] =  false;
        $request_arg['Website_Updates__c'] =  false;


        if ( ! empty( $prefrences_data['listing_email_alerts'] ) ) {
            $request_arg['Listing_Email_Updates__c'] =  true;
        }

        if ( ! empty( $prefrences_data['comparable_home_updates'] ) ) {
            $request_arg['Comparable_Home_Updates__c'] =  true;
        }

        if ( ! empty( $prefrences_data['website_updates'] ) ) {
            $request_arg['Website_Updates__c'] =  true;
        }

        if( !empty($user_pbid) ){
            $method = 'PATCH';
            $endpoint = $endpoint . $user_pbid;
        }

        $request_arg = array_filter($request_arg, array($this, 'not_empty'));
        $response = FFD_SalesForce()->rest->rest_request($request_arg, $endpoint, $method);

        if( !is_wp_error($response) ){
            $id = $this->get_object_var($response, 'id');
            $this->pb_update_user_id($user_id, $id);
        } 

        return $response;
    }


    function get_properties(){
    
        $current_user = wp_get_current_user();
        $userid = get_current_user_id();

        if( !$userid )
            return null;
        

        $user_pbid = get_user_meta($userid, 'PB_ID', true);
        if(empty($user_pbid) ){

            $PB_params = array (
                'email' => $current_user->user_email,
                'first_name' => $current_user->first_name,
                'last_name'  => !empty($current_user->last_name) ? $current_user->last_name : $current_user->display_name,
            );

            $r = pb_integration::send_sf_message( $PB_params );
            if( !is_wp_error($r) && isset($r['contact']['Id']) ){
                $user_pbid = $r['contact']['Id'];
                update_user_meta($userid, 'PB_ID', $user_pbid);
            }
        }

        if( !empty($user_pbid) ){

            $field_names = $this->get_myproperty_fields();
            $pbquery = "SELECT " . implode(',' , $field_names) . " FROM My_Property__c WHERE Contact__c='".$user_pbid."' ";
            $user_pbproperties = FFD_SalesForce()->rest->query($pbquery);


        } else {
            
            $user_pbproperties = array();
        }

    }






    function form_data_posted($organizer_action, $data){
        
        if( !is_user_logged_in() )
            return null;


        $current_user = wp_get_current_user();
        $organizer_update_message = '';

        if( !empty($organizer_action) && !empty($data) ){

            $user_params = array (
                'email' => $current_user->user_email,
                'first_name' => $current_user->first_name,
                'last_name'  => !empty($current_user->last_name) ? $current_user->last_name : $current_user->display_name
            );

            switch($organizer_action){
                case 'save_details':

                        $save_details = $this->save_myproperty($data);
                        $organizer_update_message = $save_details['message'];
                    

                    break;

                case 'save_my_property':
                    
                    $save_my_property = $this->save_myproperty($data);
                    $organizer_update_message = $save_my_property['message'];

                    break;

                case 'save_email_prefrences':

                    $save_email_prefrences = $this->save_emailprefrences($data);
                    $organizer_update_message = $save_email_prefrences['message'];


                        
                    

                    break;

                case 'save_searches':
                    
                        $save_searches = get_user_meta( $current_user->ID, '_organizer_save_searches', true);

                    

                        $posted_searches = $data['save_search'];
                        foreach($posted_searches as $search_id => $posted_data ){

                            $save_search = $save_searches[$search_id];
                            foreach($posted_data as $key => $value ){
                                $save_search[$key] = $value;
                            }

                            $save_searches[$search_id] = $save_search;
                        } 

                    

                    update_user_meta($current_user->ID, '_organizer_save_searches', $save_searches);
                    $organizer_update_message = 'Updated Save Searches.';

                break;

                default:

                    break;
            }

        }

        return $organizer_update_message;
	
}
}



function FFD_Organizer() {
	return FFD_Organizer::instance();
}
FFD_Organizer();
