<?php
/**
 * FFD Integration Admin
 *
 * @class    FFD_PropertyBase_API
 * @author   FrozenFish
 * @category Admin
 * @package  FFD Integration/Admin
 * @version  1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * FFD_PropertyBase_API class.
 */
class FFD_PropertyBase_API {


    protected static $start_time = 0;
    protected static $timeout_limit = 20;
    protected static $prevent_timeouts = false;

    protected static $platform='propertybase';
    protected static $is_enabled= true; //is platform enabled
    protected static $is_sync_enabled=false; //is sync enabled
    protected static $is_sandbox=false; //sync mode

    protected static $defaults=array();
    protected static $api_vars=array();
    protected static $fields=array();
    protected static $db_map_type='post_meta';

    
    /* oAuth settings */
    protected static $api_settings=array();
    protected static $authorized=false;

    //property base instance URL
    protected static $instance_url;


    public static function is_platform_authorized($platform='propertybase'){

        if( 'propertybase' === $platform && isset($_POST['ffd_propertybase_reset_auth']) && 1 == $_POST['ffd_propertybase_reset_auth']){

            self::$authorized = false;
            self::propertybase_reset_set_api_vars();
            update_option('ffd_propertybase_reset_auth', 'no');
            $url = add_query_arg(array('page' => 'ffd-settings', 'tab'=>'platforms', 'section'=>'propertybase'), admin_url('admin.php'));;
            wp_redirect($url); exit;

        } else if( 'propertybase' === $platform ){

            $api_authorized = get_option('ffd_propertybase_access_token');
            self::$authorized =  !empty($api_authorized) ? true : false;

            
        } else {
            self::$authorized = false;
        }

        return self::$authorized;
    }

    
    protected static function propertybase_api_vars(){

        
        $api_vars =  array('client_id', 'client_secret', 'access_token', 'instance_url', 'refresh_token');
    
        return $api_vars;
    }

    
    protected static function propertybase_reset_set_api_vars(){


        self::$api_vars =  self::propertybase_api_vars();

        foreach( self::$api_vars as $api_var ){
            if( in_array($api_var, array('access_token')) ){
                update_option('ffd_propertybase_' . $api_var, '');
            }
        }
        
    
       
        
       
    }

    protected static function propertybase_set_api_vars(){

        self::$is_enabled = ( 'yes' === get_option('ffd_' . self::$platform . '_enabled', 'yes') );
        self::$is_sync_enabled = ( 'yes' === get_option('ffd_' . self::$platform . 'towp_sync', 'no') );
        self::$is_sandbox = ( 'yes' === get_option('ffd_' . self::$platform . '_sandbox', 'no') );
        

        self::$api_vars =  self::propertybase_api_vars();

        foreach( self::$api_vars as $api_var ){
            self::$api_settings[$api_var] = get_option('ffd_propertybase_' . $api_var);
        }
        
    
       
        self::$api_settings['redirect_uri'] = add_query_arg(array('page' => 'ffd-settings', 'tab'=>'platforms', 'section'=>'propertybase'), admin_url('admin.php'));
       
    }


    public static function propertybase_get_api_vars(){

        self::propertybase_set_api_vars();

        return self::$api_settings;
       
    }

    
    protected static function propertybase_api_get_version(){
        //@Todo incomplete
        $url = self::$instance_url . '/services/data/';
        $args = array();
        $args['headers'] = array(
            'Authorization' => 'OAuth ' . self::$api_settings['access_token'],
        );
    }


    protected static function propertybase_api_get_response_code(){

        $data = array(
                    'response_type'=>'code', 
                    'state'=>'propertybase_updatetoken'
                );

        $url = self::propertybase_api_oauth2_url('authorize', $data);
       
        wp_redirect($url);
        exit();
    }

    protected static function propertybase_api_get_token(){

        if( isset($_POST['ffd_propertybase_reset_auth']) )
            return;

        $response_code = $_REQUEST['code'];

        $data = array(
                    'grant_type' => 'authorization_code',
                    'code' => $response_code,
                );

        $url = self::propertybase_api_oauth2_url('token', $data);
        $json = self::remote_request($url);
       
        //refresh code if expired
        if(is_array($json) && isset($json->error) && 'invalid_grant' == $json->error ){
            self::propertybase_api_get_response_code();
        } else {
            self::propertybase_update_api_settings($json);
        }

    }


   

    protected static function propertybase_api_oauth2_url($endpoint='', $data=array()){

        if( self::$is_sandbox ){
            $url="https://test.salesforce.com/services/oauth2/" . $endpoint;
        } else {
            $url="https://login.salesforce.com/services/oauth2/" . $endpoint;
        }

        self::$api_settings['redirect_uri'] = add_query_arg(array('page' => 'ffd-settings', 'tab'=>'platforms', 'section'=>'propertybase'), admin_url('admin.php'));

        $request_body = array(
            'client_id' => self::$api_settings['client_id'],
            'client_secret' => self::$api_settings['client_secret'],
            'redirect_uri' => self::$api_settings['redirect_uri']
        );

        $request_body = array_merge($request_body, $data);
        
        $url = self::format_get($url, $request_body);

        return $url;

    }


    protected static function propertybase_api_refresh_token(){

        $url =  self::$api_settings['instance_url'] . "/services/oauth2/token";
            
        $args['method'] = 'POST';

        $args['body'] = array(
            'grant_type' => 'refresh_token',
            'client_id' => self::$api_settings['client_id'],
            'client_secret' => self::$api_settings['client_secret'],
            'refresh_token' => self::$api_settings['refresh_token'],
        );

        $json = self::remote_request($url, $args);

        if( false !== $json  && !isset($json->error) ){
            self::propertybase_update_api_settings($json);
        }
        

        return $json;

    }


    protected static function propertybase_api_get_fields(){

        self::propertybase_set_api_vars();

        $url = self::$api_settings['instance_url'] . '/services/data/v20.0/sobjects/pba__listing__c/describe';
        $args = array();
        $args['headers'] = array(
            'Authorization' => 'OAuth ' . self::$api_settings['access_token'],
        );

        $json = self::propertybase_api_request($url, $args);
       

        if( is_wp_error( $json ) ) {
            
            //ffd_debug($json, true);

        } else if( false !== $json )  {

            foreach($json->fields as $field){
                self::$fields[$field->name] = $field->label;
            }
        } else {
           
        }

        return self::$fields;
    }


    public static function propertybase_api_post_analytics($args = array()){

        self::propertybase_set_api_vars();

        $url = self::$api_settings['instance_url'] . '/services/data/v36.0/composite/tree/analytic__c';
       
        $args['headers']['Authorization'] =  'OAuth ' . self::$api_settings['access_token'];

        //Content-Type' => 'application/json; charset=utf-8'
        $json = self::propertybase_api_request($url, $args);
       

       

        return $json;
    }

    protected static function propertybase_api_request($url, $args){

        $json = self::remote_request($url, $args);
       

        //refresh token if expired
        if ( $json && is_array($json) && isset($json[0]->errorCode) && $json[0]->errorCode == "INVALID_SESSION_ID") {

            $json = self::propertybase_api_refresh_token();
            
            if( $json && isset($json->access_token) ){
                
                //update headers with new token
                $args['headers']['Authorization'] =  'OAuth ' . self::$api_settings['access_token'];

                return self::propertybase_api_request($url, $args);
            } else {
                $error = isset($json->error) ? $json->error : '401';
                $errorMessage = isset($json->error_description) ? $json->error_description : 'Unknown error ocurred';
                return new WP_Error($error, $errorMessage, array('url'=>$url, 'args' => $args, 'response'=>$json));
            }

        } else if (is_array($json) && isset($json[0]->errorCode)){
            
            $error_code =$json[0]->errorCode;

            $error_message = isset($json[0]->message) ? $json[0]->message : 'No message found for this error.';
            $error_data = array('status' => '');

            return new WP_Error( $error_code, $error_message, $error_data);

        } else {
           
            return (!empty($json) ? $json : new WP_Error('empty_response', 'empty response returned.') );
        }

    }


    protected static function remote_request($url, $args=array()){

        //overide wp curl settings
        add_action('http_api_curl', array('FFD_PropertyBase_API','http_api_curl_overrides'), 10);

        if( !isset($args['method'] ) ){
            $args['method'] = 'GET';
        }
        $request = wp_remote_get($url, $args);
       
        if( is_wp_error( $request )){
           
            $request->add('error_trace', self::generateCallTrace() );
            $request->add('request_url', $url);
            $request->add('request_args', http_build_query($args));
            return $request;
        }

        $response_body = wp_remote_retrieve_body( $request );
       
        $json = json_decode( $response_body );

        return $json;
    }


    protected static function propertybase_update_api_settings($json){

        self::$api_vars =  self::propertybase_api_vars();

        foreach(self::$api_vars as $api_var){

            //update api setting
            if( isset( self::$api_settings[$api_var]) && isset($json->$api_var) ){
                self::$api_settings[$api_var] = $json->$api_var;
            }

            //update api setting in db
            if( isset($json->$api_var) ){
                update_option('ffd_propertybase_' . $api_var, $json->$api_var);
            }
        }

        do_action('ffd_propertybasetowp_credentials', self::$api_settings);

    }

    


    public static function http_api_curl_overrides($handle){

        if( 'propertybase'  === self::$platform){
            //Don't verify SSL certs
            curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);

             //set connecting timeout to 10 seconds.
            curl_setopt( $handle, CURLOPT_CONNECTTIMEOUT, 30 ); 
            //set execution and retrieving data timeout.
            curl_setopt( $handle, CURLOPT_TIMEOUT, 30 ); 
        }    

    }
   
	protected static function format_get($url, $data) {
		if (!empty($data)) {
			$url_parts = parse_url($url);
			if (empty($url_parts['query'])) {
				$query = $url_parts['query'] = '';
			}
			else {
				$query = $url_parts['query'];
			}

			$query .= '&' . http_build_query($data, null, '&');
			$query = trim($query, '&');

			if (empty($url_parts['query'])) {
				$url .= '?' . $query;
			}
			else {
				$url = str_replace($url_parts['query'], $query, $url);
			}
		}
		return $url;
    }
    

    
	

	
	


    protected static function generateCallTrace(){
        $e = new Exception();
        $trace = explode("\n", $e->getTraceAsString());
        // reverse array to make steps line up chronologically
        $trace = array_reverse($trace);
        array_shift($trace); // remove {main}
        array_pop($trace); // remove call to this method
        $length = count($trace);
        $result = array();
        
        for ($i = 0; $i < $length; $i++)
        {
            $result[] = ($i + 1)  . ')' . substr($trace[$i], strpos($trace[$i], ' ')); // replace '#someNum' with '$i)', set the right ordering
        }
        
        return "\t" . implode("\n\t", $result);
    }


    public static function propertybase_api_get_api_fields(){
        
        self::$fields = get_option('propertybase_api_fields');

        if( empty( self::$fields) || isset($_REQUEST['propertybase_refresh_fields']) ){
            self::$fields = FFD_PropertyBase_API::propertybase_api_get_fields();

            asort(self::$fields);
            //self::$fields = array_change_key_case(self::$fields);

            update_option('propertybase_api_fields', self::$fields);
            if( isset($_GET['_ffd_redirect']) ){
                wp_redirect($_GET['_ffd_redirect']); exit;
            }
            return self::$fields;
        }

        
        return self::$fields;
    }

    public static function propertybase_field_mapping(){

        $field_mapping = get_option('property_fields_mapping', array());

        return $field_mapping;
    }

    public static function propertybase_api_default_fields(){
        
        $default_fields = array(
                    'id'=>'required', 
                    'name'=>'required',
                    'lastmodifieddate'=>'required',
                    'createddate'=>'required',

                    'pba__description_pb__c'=>'',
                    'mls_id__c'=>'',

                    'pba__listingprice_pb__c'=>'',
                    'listed_date__c'=>'',
                    'sale_date__c'=>'',
                    'sale_price__c'=>'',
                    'price_per_sqft__c'=>'',

                    
                    'pba__solddate__c'=>'',
                    'pba__soldprice__c'=>'', 

                    'pba__propertytype__c'=>'',
                    'pba__listingtype__c'=>'',

                    'pba__fullbathrooms_pb__c'=>'',
                    'pba__halfbathrooms_pb__c'=>'',
                    'pba__bedrooms_pb__c'=>'',

                    'pba__postalcode_pb__c'=>'',
                    'pba__latitude_pb__c'=>'',
                    'pba__longitude_pb__c'=>'',
                    'pba__state_pb__c'=>'',
                    'pba__city_pb__c'=>'',
                    'pba__subdivision__c'=>'',
                    'pba__address_pb__c'=>'',
                    'pba__displayaddress__c'=>'',

                    'dom__c'=>'',
                    'days_on_market__c'=>'', 

                    'pba__yearbuilt_pb__c'=>'', 
                    'pba__totalarea_pb__c'=>'',
                    'pba__lotsize_pb__c'=>'',

                    'pba__listing_agent_email__c'=>'',
                    'pba__listing_agent_firstname__c'=>'',
                    'pba__listing_agent_lastname__c'=>'',

                    'pba__listofficemlsid__c'=>'',
                    'pba__listofficename__c'=>'',
                    'pba__listingagentmlsid__c'=>'',
                    'pba__listingagentname__c'=>'',

                    'pba__colistingagentmlsid__c'=>'',
                    'pba__colistingagentname__c'=>'',
                    'pba__colistingofficemlsid__c'=>'',
                    'pba__colistingofficename__c'=>'',
                );

        return $default_fields;
    }

}