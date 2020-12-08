<?php

class FFD_SalesForce_Rest{

  protected static $_instance = null;
  
  
  public $instance_url;
  public $base;
  public $resource;
  public $endpoint;
  public $version;
  public $version_endpoint;

  public $token_refresh_try=null;
  
  public $access_token;
 
  public $credentials_names =  array();
  public $credentials=array();

  public static function instance() {
    if ( is_null( self::$_instance ) ) {
      self::$_instance = new self();
    }
    return self::$_instance;
  }
  
  public function __construct() {
    
    $this->credentials_names = array(
        'client_id', 
        'client_secret', 
        'access_token', 
        'instance_url', 
        'refresh_token'
    );


    $this->set_credentials();
    $this->init_hooks();
    
  }


  
  function __call($name, $args){
      
    /* if( method_exists($this, $name) ){

    } */
     return new WP_Error('inaccessible', 'Method <b>' . $name . '</b> does not exits', $args);
  }


  
  public function init_hooks(){
      add_action('ffd_propertybasetowp_credentials', array($this, 'credentials_updated'));

      
    
    }


    
  public function set_credentials(){

  
    foreach( $this->credentials_names as $name ){
        $option_name = 'ffd_propertybase_' . $name;
        if( $value = get_option($option_name) ){
            $this->credentials[$name] = $value;
        }
    }

    if( !empty( $this->credentials) ){
      $this->access_token = isset($this->credentials['access_token']) ? $this->credentials['access_token'] : '';
      $this->instance_url = isset($this->credentials['instance_url']) ? $this->credentials['instance_url'] : '';
    }
    
   //ffd_debug($this->credentials);
   //ffd_debug(FFD_PropertyBase_API::propertybase_get_api_vars(), true);


    $this->token_refresh_try = null;



    $this->base = '/services/data'; 
    $this->version = '/v46.0';
    $this->resource = '/sobjects';

  }


  public  function update_credentials($data){

    if( is_object($data) )
    $data = (array) $data;

    $updated = 0;
    foreach( $this->credentials_names as $name ){
        if( isset($data[$name]) ){
            update_option('ffd_propertybase_' . $name, $data[$name]);
            $this->credentials[$name] = $data[$name];
            $updated++;
        }
    }
 

    if( $updated ){

      $this->instance_url = $this->credentials['instance_url'];
      $this->access_token = $this->credentials['access_token'];

      
      do_action('ffd_salesforce_rest_credentials', $this->credentials);

    }

   

    return $updated;

  }


  /* 
    * When credentials are updated in 
    * propertybase to wp api calls ( specificaly tokens )
    */
    public function credentials_updated($data){

      if( is_object($data) )
          $data = (array) $data;

      $updated = 0;
      foreach( $this->credentials_names as $name ){
          if( isset($data[$name]) ){
              $this->credentials[$name] = $data[$name];
          }
      }

      $this->base_url = $this->credentials['instance_url'];
      $this->access_token = $this->credentials['access_token'];

      do_action('ffd_logger', array('wptopropertybase_credentials_set' => 'by propertybasetowp'));
  }


    public function wp_curl_settings($handle){

        //Don't verify SSL certs
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);

        //set connecting timeout to 10 seconds.
        curl_setopt( $handle, CURLOPT_CONNECTTIMEOUT, 30 ); 
        //set execution and retrieving data timeout.
        curl_setopt( $handle, CURLOPT_TIMEOUT, 30 ); 
    
        
    }

    public function http_request_args($r, $url){
        //set timeout to 10 seconds.
        $r['timeout'] = 30; /// @todos
        return $r;
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

  }


  function unset_var($obj, $var){
	

    foreach($obj as $key => $value) {
  
      if(strtolower($var) == strtolower($key)) {
        
        if(isset($obj[$key]) ){
          unset($obj[$key]);
        } else if( isset($obj->$key) ){
          unset($obj->$key);
        }
        break;

      }

    }

    return $obj;

  }
    
  
  public function web_to_prospect($prospect_query){

    
    $compositeRequest = array();

    $ContactId = '';
    $requestId = '';

    foreach($prospect_query['prospect'] as $name => $data){

        if( empty($data) )
          continue;

        $name = strtolower($name);
        $method = 'POST'; //method for subrequest;
        $objectName = ''; 
        $objectId = ''; 
        $request = array();
        $objectId = $this->get_var($data, 'id');
    
        

        

        switch ($name) {

          case 'contact':
              $objectName = 'Contact';
              $ContactId = $objectId;

              if( empty($objectId) && !empty($data['Email']) ){
                $existing_contact = $this->get_object_by_field('Email', $data['Email'], $objectName);
                if( !is_wp_error($existing_contact) && isset($existing_contact->Id)){
                  $objectId = $existing_contact->Id;
                  $ContactId = $objectId;
                }
              }

              if( empty($data['LastName']) ){
                $data['FirstName'] = $data['LastName'];
              }

              if( empty($data['LastName']) ){
                $data['LastName'] = $data['FirstName'];
              }

              /* if(empty($data['Name']) ){
                $data['Name'] = $data['FirstName'] . ' ' . $data['LastName'];
              } */

              
            break; 

          case 'request':

              $objectName = 'pba__Request__c';
              $requestId = $objectId;

              if( !empty($ContactId) ){
                $data['pba__Contact__c'] = $ContactId;
              } else {
                $data['pba__Contact__c'] = '@{refContact.id}';
              }

            break;

          case 'favorite':
            
            $objectName = 'pba__Favorite__c';

            if( !empty($ContactId) ){
              $data['pba__Contact__c'] = $ContactId;
            } else {
              $data['pba__Contact__c'] = '@{refContact.id}';
            }

            if( !empty($requestId) ){
              $data['pba__Request__c'] = $requestId;
            } else {
              $data['pba__Request__c'] = '@{refRequest.id}';
            }

          break;

        }

              

        if(!empty($objectName) ){

          $endpoint = $this->get_endpoint('/sobjects/'.$objectName.'/'); // endpoint for subrequest
          if( !empty($objectId) ){
            $method = 'PATCH';
            $endpoint = $endpoint . $objectId; // passing id will update object data
           
          }  

          //at this point "id" (object id ) param be set for contact/request
          $data = $this->unset_var($data, 'id');

          $request = array(
            'method' => $method,
            'url' => $endpoint,
            'referenceId' => 'ref'.ucwords($name),
            'body' => $data
          );

          $compositeRequest[] = $request;

        }


    }

    
    $compositeResource = array(
      'allOrNone' => true, // if any of the sub request failed revert the whole composite request.
      'compositeRequest' => $compositeRequest
    );

    $method = 'POST'; // method for main composite request
    $endpoint = $this->get_endpoint('/composite/'); // endpoint for main composite request
    
    $response = $this->rest_request($compositeResource, $endpoint, $method);

      //if response is still not an error
      // clean the response;
      if( !is_wp_error($response) ){

          //$request = $compositeResource['compositeRequest'];
          //$response = $response->compositeResponse;
          $compositeResponse = array();

          foreach($response->compositeResponse as $response ){
              $name = strtolower($response->referenceId);
              $name = str_replace('ref', '', $name);
              $compositeResponse[$name] = (array) $response->body;
              $location = isset($response->httpHeaders) ? $response->httpHeaders : null;
              $location = isset($location->Location) ? $location->Location : null;
              $compositeResponse[$name]['url'] = $location;

          }
          
          $response = $compositeResponse;
      }

    return $response;

  }

  public function rest_request($data=array(), $endpoint, $method='POST'){

    $this->endpoint = $endpoint;

    $args['headers'] = array(
        'Content-Type' => 'application/json'
    );

    if( !empty($data) ){
        $args['body'] = wp_json_encode($data);
    }

    $args['method'] = $method;
    
    $response = $this->request($args);

    
    //handle composite response ( if exists );
    $response = $this->validate_composite_response($response, $data);

     

    return $response;
  }




  public function request($args = null, $endpoint=null, $base_url=null, $access_token=null){

    if( empty(get_option('ffd_propertybase_access_token') )){
      return new WP_Error('401', 'API Not Authorized. Try Re-Authorizing using API Settings in Admin.');
    }


    if( !$args )
      $args = array();

    if( !$base_url )
      $base_url = $this->instance_url;

    if( !$endpoint )
      $endpoint = $this->endpoint;

    if( !$access_token )
      $access_token = $this->access_token;

    
    

    $url = $base_url . $endpoint;
    $headers = array(
        'Authorization' => 'OAuth ' . $access_token
    );
    if( !empty($args['headers']) ){ 
        $headers = wp_parse_args($args['headers'], $headers);
    } 

    if( $args['headers'] === false ){
        unset($args['headers']);
    } else {
        $args['headers'] = $headers;
    }


    //wp http 
    //overide http request settings
    add_action('http_api_curl', array($this,'wp_curl_settings'), 20, 1);
    //add_filter('http_request_args', array($this, 'http_request_args'), 10, 2);
      
    $request = wp_remote_get($url, $args);

    //check for errors
    if( is_wp_error( $request )){
        if( isset($request->error_data) && empty($request->error_data) ){
          
          return new Wp_Error($request->get_error_code(), $request->get_error_message(), array('url'=>$url, 'args' => $args) );
        }
      return $request;
    }

    //get reponse body
    $response_body = wp_remote_retrieve_body( $request );
    $response = json_decode( $response_body );

    

    //token is not valid
    if (  isset($response->error) && '' !== $response->error ) {

      if( $response->error == "unsupported_grant_type" ){

          $error = new WP_Error($response->error, 'API Not Authorized. May be due to invalid refresh/access token.', array('url'=>$url, 'args' => $args, 'response'=>$response));
          
          $error->add('error_trace', $this->generateCallTrace() );

          return $error;

          //if( $this->token_refresh_try > 1 )
              //return $error;

          //$refresh = $this->refresh_token();
          //if( !is_wp_error($refresh) && isset($refresh->access_token)  ){
            //re process the last request after refreshing token
            //return $this->request($args, $endpoint, $base_url, null);

          //} else {
            //return $error;
          //}
          
      } else  if( $response->error == "invalid_grant" ){
          return new WP_Error('401', $response->error_description, array('url'=>$url, 'args' => $args));
      } else{
          return new WP_Error('401', 'Unknown error ocurred.', array('url'=>$url, 'args' => $args, 'response'=>$response));
      }
    }


    //token expired
    if ( is_array($response) && isset($response[0]) && isset($response[0]->errorCode) && $response[0]->errorCode == "INVALID_SESSION_ID") {

      $refresh = $this->refresh_token();

      if( !is_wp_error($refresh) && isset($refresh->access_token)  ){

            //re process the last request after refreshing token
            return $this->request($args, $endpoint, $base_url, null);

        } else {
          return new WP_Error('401', 'Session expired, Failed to refresh the token.', array('url'=>$url, 'args' => $args, 'response'=>$refresh));
        }

    }

    

    //check for general error in response body
    if ( is_array($response) && isset($response[0]) && isset($response[0]->errorCode)){
          
        $error_code = $response[0]->errorCode;
        $error_message = isset($response[0]->message) ? $response[0]->message : 'No error message found for this error.';
        $error_data = array('args' => $args, 'response' => $response);

        return new WP_Error( $error_code, $error_message, $error_data);

    }
    $response_code = wp_remote_retrieve_response_code($request);
    $method = strtolower($args['method']);

    // delete and patch can have empty response on success
    if(  $response_code == 204 && in_array($method, array('delete', 'patch')) ){
      return $response;
    }

    //empty reponse check
    if( empty($response) ){
        
        return new WP_Error('empty_response', 'Empty Response', array('args'=>$args, 'url' => $url, 'response_code'=>$response_code));
    }



    //finally if all OK return response
    return $response;

  }




  public function validate_composite_response($response, $args){

      //check composite response for error.
      if( !is_wp_error($response) && isset($response->compositeResponse)){
          
       
        $errorCode = null;
        foreach($response->compositeResponse as $compositeResponse){
            $statusCode = intval($compositeResponse->httpStatusCode);
            if( in_array($statusCode, array(200, 201, 203) ) ) {
                //response is good no need to look for errors.
                // because we are halting all if one fail
                break;
            } else if( $statusCode >= 400 ){

                $response = new WP_Error('request_halted', 'Composite request error.', $response);
                break;
                $body = $compositeResponse->body;
                $errorCode = $body[0]->errorCode;
                $errorMessage = $body[0]->message;
                $refrenceid = (isset($compositeResponse->referenceId) ? $compositeResponse->referenceId : '');
                if( strpos(strtolower($errorMessage), 'the transaction') === false ){
                    $response = new WP_Error($errorCode, $errorMessage, array('refrenceid'=>$refrenceid, 'args'=>$args, 'response'=>$body));
                    break;
                }
            }
        }
        
       
    } 

   

    return $response;

  }



 


  public function refresh_token(){

    $this->endpoint = "/services/oauth2/token";

    if( $this->token_refresh_try === null ){
        $this->token_refresh_try = 0;
    }

    if( $this->token_refresh_try > 1 ){
        return new WP_Error('token_refresh_limit', 'Too many calls to refresh token. Check request method');
    }

    $this->token_refresh_try = $this->token_refresh_try + 1;

    //$url =  $this->api_base ;
        
    $args['method'] = 'POST';

    $args['body'] = array(
        'grant_type' => 'refresh_token',
        'client_id' => $this->credentials['client_id'],
        'client_secret' => $this->credentials['client_secret'],
        'refresh_token' => $this->credentials['refresh_token']
    );

    //no headers needed;
    $args['headers'] = false;

    $response = $this->request($args);

  
    if( !is_wp_error($response) ){
        $this->token_refresh_try = null;
        $this->update_credentials($response);
    }

    return $response;
    
  }


  public function query($query){

    $resource = '/query/' . '?q=' . urlencode($query);
    $endpoint =  $this->get_endpoint($resource);

  
    $response = $this->rest_request(null, $endpoint, 'GET');

    if( is_wp_error($response) ){
        return $response;
    }


    return $response;

  }
  
  public function get_object_by_field($field, $value, $object_name){

      $method = 'GET';
      $endpoint = $this->get_endpoint('/sobjects/'.$object_name.'/');
      $endpoint = $endpoint . $field . '/' . $value . '?fields=id';
      $response = $this->rest_request(null, $endpoint, $method);
      
      return $response;

  }

    public function get_fields($object_key){
      
    $fields = array();

    $resource = '/sobjects/' . $object_key . '/describe';
    $endpoint =  $this->get_endpoint($resource);

    //describe request don't need args
    $args = null;
    $response = $this->rest_request($args, $endpoint, 'GET');

    if( is_wp_error($response) ){
        return $response;
    }
    
    if( isset($response->fields) && !empty($response->fields) ){
      foreach($response->fields as $field){
        $fields[$field->name] = array(
          'label'=>$field->label, 
          'type'=>$field->type, 
          'length'=>$field->length
        );
      }
    }

    return $fields;
  }



  public function format_fields_value($data, $object_fields){

    foreach($data as $pb_field => $value ){

        if( isset($object_fields[$pb_field]) ){

            $field  = $object_fields[$pb_field];
            $type   = $field['type'];
            $length = $field['length'];

            
            switch($type){
                case 'boolean':
                        //incase it is already in proper format.
                        if( $value !== 'true' && $value !== 'false' ){
                            $data[$pb_field] = ($value) ? 'true' : 'false';
                        }
                    break;

                case 'double':
                    $data[$pb_field] =  preg_replace('#[^0-9\.,]#', '', $value);
                break;

                case 'string':
                    
                    $data[$pb_field] = ( (strlen($value) > $length) ?  substr($value, 0, $length) :   $value);
                    break;

                case 'picklist':
                    
                
                    break;

                case 'multipicklist':
                    
                
                    break;
            }

        }

    }

    return $data;
  }


  public function get_endpoint($resource=null, $base=null, $version=null){
    
    if( !$resource )
      $resource = $this->resource;

    if( !$base )
      $base = $this->base;

    if( !$version )
      $version = $this->version;

    //build endpoint url
    $endpoint = $base . $version . $resource;

    return $endpoint;
  }



  protected  function generateCallTrace(){
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

}




class FFD_SalesForce_Integration{

  protected static $_instance = null;
  
  public $rest;
  public $request = null;
  public $error = null;



  public static function instance() {
    if ( is_null( self::$_instance ) ) {
      self::$_instance = new self();
    }
    return self::$_instance;
  }

  public function __construct() {
   
    //rest api instance
    $this->rest = FFD_SalesForce_Rest::instance();

    $this->init_hooks();
     
  }

  
  public function init_hooks(){
      
  }





}



function FFD_SalesForce() {
	return FFD_SalesForce_Integration::instance();
}
FFD_SalesForce();
