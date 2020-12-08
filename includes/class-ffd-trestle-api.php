<?php
/**
 * FFD Integration Admin
 *
 * @class    FFD_Trestle_API
 * @author   FrozenFish
 * @category Admin
 * @package  FFD Integration/Admin
 * @version  1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * FFD_Trestle_API class.
 */
class FFD_Trestle_API {


    protected static $start_time = 0;
    protected static $timeout_limit = 20;
    protected static $prevent_timeouts = false;

    protected static $platform='trestle';
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


    public static function is_platform_authorized($platform='trestle'){

        if( 'trestle' === $platform && isset($_POST['ffd_trestle_reset_auth']) && 1 == $_POST['ffd_trestle_reset_auth']){

            self::$authorized = false;
            self::trestle_reset_set_api_vars();
            update_option('ffd_trestle_reset_auth', 'no');
            $url = add_query_arg(array('page' => 'ffd-settings', 'tab'=>'platforms', 'section'=>'trestle'), admin_url('admin.php'));;
            wp_redirect($url); exit;

        } else if( 'trestle' === $platform ){

            $api_authorized = get_option('ffd_trestle_access_token');
            self::$authorized =  !empty($api_authorized) ? true : false;


        } else {
            self::$authorized = false;
        }

        return self::$authorized;
    }


    protected static function trestle_api_vars(){
        $api_vars =  array('client_id', 'client_secret', 'access_token', 'instance_url'/*, 'refresh_token'*/);
        return $api_vars;
    }


    protected static function trestle_reset_set_api_vars(){
        self::$api_vars =  self::trestle_api_vars();

        foreach( self::$api_vars as $api_var ){
            if( in_array($api_var, array('access_token')) ){
                update_option('ffd_trestle_' . $api_var, '');
            }
        }
    }

    protected static function trestle_set_api_vars(){

        self::$is_enabled = ( 'yes' === get_option('ffd_' . self::$platform . '_enabled', 'yes') );
        self::$is_sync_enabled = ( 'yes' === get_option('ffd_' . self::$platform . 'towp_sync', 'no') );
        //self::$is_sandbox = ( 'yes' === get_option('ffd_' . self::$platform . '_sandbox', 'no') );


        self::$api_vars =  self::trestle_api_vars();

        foreach( self::$api_vars as $api_var ){
            self::$api_settings[$api_var] = get_option('ffd_trestle_' . $api_var);
        }

        self::$api_settings['redirect_uri'] = add_query_arg(array('page' => 'ffd-settings', 'tab'=>'platforms', 'section'=>'trestle'), admin_url('admin.php'));
    }


    public static function trestle_get_api_vars(){
        self::trestle_set_api_vars();
        return self::$api_settings;
    }

    /*protected static function trestle_api_get_version(){
        //@Todo incomplete
        $url = self::$instance_url . '/trestle/odata/';
        $args = array();
        $args['headers'] = array(
            'Authorization' => 'Bearer ' . self::$api_settings['access_token'],
        );
    }*/

    protected static function trestle_api_get_token(){

        if( isset($_POST['ffd_trestle_reset_auth']) )
            return;

        //$response_code = $_REQUEST['code'];

        /*$data = array(
            'grant_type' => 'client_credentials',
            'scope'      => 'api'
            //'code' => $response_code,
        );*/
        //$url = self::trestle_api_oauth2_url('token', $data);

        //franklin.espinoza

        /*if( self::$is_sandbox ){
            $url="https://api-dev.corelogic.com/trestle/oidc/connect/token";
        } else {*/
        $url = get_option('ffd_trestle_instance_url')."/trestle/oidc/connect/token";
        //}

        //$args['method'] = 'POST';
        $args['body'] = array(
            'grant_type' => 'client_credentials',
            'scope'      => 'api',
            'client_id' => get_option('ffd_trestle_client_id'),
            'client_secret' => get_option('ffd_trestle_client_secret')
        );

        $json = self::remote_request($url, $args,'post');

        //refresh code if expired
        if(is_array($json) && isset($json->error) && 'invalid_grant' == $json->error ){
            self::trestle_api_get_response_code();
        } else {
            self::trestle_update_api_settings($json);
        }

    }

    /*protected static function trestle_api_get_response_code(){

        $data = array(
            'response_type'=>'code',
            'state'=>'trestle_updatetoken'
        );

        $url = self::trestle_api_oauth2_url('authorize', $data);

        wp_redirect($url);
        exit();
    }*/

    protected static function trestle_api_oauth2_url($endpoint='', $data=array()){

        /*if( self::$is_sandbox ){
            $url="https://api-dev.corelogic.com/trestle/oidc/connect/" . $endpoint;
        } else {*/
        $url="https://api-prod.corelogic.com/trestle/oidc/connect/" . $endpoint;
        //}

        self::$api_settings['redirect_uri'] = add_query_arg(array('page' => 'ffd-settings', 'tab'=>'platforms', 'section'=>'trestle'), admin_url('admin.php'));

        $request_body = array(
            'client_id' => self::$api_settings['client_id'],
            'client_secret' => self::$api_settings['client_secret'],
            //'redirect_uri' => self::$api_settings['redirect_uri']
        );

        $request_body = array_merge($request_body, $data);

        $url = self::format_get($url, $request_body);

        return $url;

    }


    protected static function trestle_api_refresh_token(){

        $url =  self::$api_settings['instance_url'] . "/trestle/oidc/connect/token";
        do_action('ffd_logger', array('[FFD Trestle Api Refresh Token]' =>  $url));
        //$args['method'] = 'POST';

        $args['body'] = array(
            'grant_type' => 'client_credentials',
            'scope' => 'api',
            'client_id' => self::$api_settings['client_id'],
            'client_secret' => self::$api_settings['client_secret']
        );

        $json = self::remote_request($url, $args, 'post');
        do_action('ffd_logger', array('[FFD Trestle Remote Request Refresh Token]' =>  $json));
        if( false !== $json  && !isset($json->error) ){
            self::trestle_update_api_settings($json);
        }

        return $json;
    }

    /*protected static function trestle_api_get_fields(){

        self::trestle_set_api_vars();

        $url = self::$api_settings['instance_url'] . '/services/data/v20.0/sobjects/pba__listing__c/describe';
        $args = array();
        $args['headers'] = array(
            'Authorization' => 'Bearer ' . self::$api_settings['access_token'],
        );

        $json = self::trestle_api_request($url, $args);


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


    public static function trestle_api_post_analytics($args = array()){

        self::trestle_set_api_vars();

        $url = self::$api_settings['instance_url'] . '/services/data/v36.0/composite/tree/analytic__c';

        $args['headers']['Authorization'] =  'OAuth ' . self::$api_settings['access_token'];

        //Content-Type' => 'application/json; charset=utf-8'
        $json = self::trestle_api_request($url, $args);

        return $json;
    }*/

    protected static function trestle_api_request($url, $args){

        $json = self::remote_request($url, $args, 'get');
        do_action('ffd_logger', array('[FFD Trestle API Request]' =>  'FFD Trestle Refresh Token'));
        do_action('ffd_logger', array('[FFD Trestle API Request VALUES]' =>  $json->fault->detail->errorcode.'>-----<'.self::$api_settings['access_token']));

        //do_action('ffd_logger', array('[FFD Trestle API Request JSON]' =>  $json ));
        //do_action('ffd_logger', array('[FFD Trestle API Request IS ARRAY]' =>  is_array($json)));
        //do_action('ffd_logger', array('[FFD Trestle API Request IS SET]' =>  isset($json->fault)));
        //do_action('ffd_logger', array('[FFD Trestle API Request EQUALS]' =>  $json->fault->detail->errorcode == "steps.jwt.FailedToDecode"));

        //refresh token if expired
        if ( is_null($json) || (isset($json->fault) && $json->fault->detail->errorcode == "steps.jwt.FailedToDecode")) {

            $json = self::trestle_api_refresh_token();
            do_action('ffd_logger', array('[FFD Request Token]' =>  $json));
            if( $json && isset($json->access_token) ){

                //update headers with new token
                $args['headers']['Authorization'] =  'Bearer ' . self::$api_settings['access_token'];

                return self::trestle_api_request($url, $args);
            } else {
                $error = isset($json->error) ? $json->error : '401';
                $errorMessage = isset($json->error_description) ? $json->error_description : 'Unknown error ocurred';
                return new WP_Error($error, $errorMessage, array('url'=>$url, 'args' => $args, 'response'=>$json));
            }

        } else if (isset($json->fault->detail->errorcode)){

            $error_code = $json->fault->detail->errorcode;

            $error_message = isset($json[0]->message) ? $json[0]->message : 'No message found for this error.';
            $error_data = array('status' => '');

            return new WP_Error( $error_code, $error_message, $error_data);

        } else {

            return (!empty($json) ? $json : new WP_Error('empty_response', 'empty response returned.') );
        }

    }


    protected static function remote_request($url, $args=array(), $method){

        //overide wp curl settings
        //add_action('http_api_curl', array('FFD_Trestle_API','http_api_curl_overrides'), 10);

        /*if( !isset($args['method'] ) ){
            $args['method'] = 'GET';
        }*/
        //do_action('ffd_logger', array('[FFD Remote Request Url]' => $url ));
        //do_action('ffd_logger', array('[FFD Remote Request Arguments]' => $args ));
        //do_action('ffd_logger', array('[FFD Remote Request Method]' => $method ));
        if( $method == 'get' ) {
            $request = wp_remote_get($url, $args);
        }else if( $method == 'post' ){
            $request = wp_remote_post($url, $args);
        }

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


    protected static function trestle_update_api_settings($json){

        self::$api_vars =  self::trestle_api_vars();

        foreach(self::$api_vars as $api_var){

            //update api setting
            if( isset( self::$api_settings[$api_var]) && isset($json->$api_var) ){
                self::$api_settings[$api_var] = $json->$api_var;
            }

            //update api setting in db
            if( isset($json->$api_var) ){
                update_option('ffd_trestle_' . $api_var, $json->$api_var);
            }
        }

        do_action('ffd_trestletowp_credentials', self::$api_settings);

    }

    /*public static function http_api_curl_overrides($handle){

        if( 'trestle'  === self::$platform){
            //Don't verify SSL certs
            curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);

            //set connecting timeout to 10 seconds.
            curl_setopt( $handle, CURLOPT_CONNECTTIMEOUT, 30 );
            //set execution and retrieving data timeout.
            curl_setopt( $handle, CURLOPT_TIMEOUT, 30 );
        }

    }*/

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
            }else {
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


    public static function trestle_api_get_api_fields(){

        self::$fields = get_option('trestle_api_fields');

        if( empty( self::$fields) || isset($_REQUEST['trestle_refresh_fields']) ){
            self::$fields = FFD_Trestle_API::trestle_api_get_fields();

            asort(self::$fields);
            //self::$fields = array_change_key_case(self::$fields);

            update_option('trestle_api_fields', self::$fields);
            if( isset($_GET['_ffd_redirect']) ){
                wp_redirect($_GET['_ffd_redirect']); exit;
            }
            return self::$fields;
        }

        
        return self::$fields;
    }

    public static function trestle_field_mapping(){

        $field_mapping = get_option('trestle_fields_mapping', array());

        return $field_mapping;
    }

    public static function trestle_api_default_fields(){

        $default_fields = array(
            'id'=>'required',
            'name'=>'required',
            'ffd_lastmodifieddate'=>'required',
            'ffd_createddate'=>'required',
            'ffd_description_pb'=>'',
            'ffd_mls_id'=>'',
            'ffd_id'=>'', //
            'ffd_salesforce_id'=>'', //
            'ffd_status'=>'', //
            'ffd_acres_calc'=>'',//
            'ffd_listingprice_pb'=>'',
            'ffd_listed_date'=>'',
            'ffd_community_features'=>'',
            'ffd_interior_features'=>'',
            'ffd_exterior_features'=>'',
            'ffd_living_sq_ft'=>'',//
            'ffd_propertytype'=>'',//
            'ffd_propertysubtype'=>'',//
            'ffd_listingtype'=>'',//
            'ffd_fullbathrooms_pb'=>'',
            'ffd_bathrooms_half'=>'',
            'ffd_bedrooms_pb'=>'',
            'ffd_postalcode_pb'=>'',
            'ffd_latitude_pb'=>'',
            'ffd_longitude_pb'=>'',
            'ffd_state_pb'=>'',
            'ffd_city_pb'=>'',
            'ffd_subdivision'=>'',
            'ffd_address_pb'=>'',
            'ffd_unbranded_virtual_tour'=>'',
            'ffd_taxes'=>'',
            'ffd_view'=>'',
            'ffd_waterfront'=>'',
            'ffd_waterfront_features'=>'',
            'ffd_days_on_market'=>'',
            'ffd_yearbuilt_pb'=>'',
            'ffd_lotsize_pb'=>'',
            'ffd_listing_agent_email'=>'',
            'ffd_listing_agent_firstname'=>'',
            'ffd_listing_agent_lastname'=>'',
            'ffd_listingofficeshortid'=>'',
            'ffd_listingofficename'=>'',
            'ffd_listing_agent_phone'=>'',
            'ffd_pets_allowed'=>'',
            'ffd_private_pool'=>'',
            'ffd_parkingspaces'=>'',
            'ffd_media'=>''
        );

        return $default_fields;
    }

}