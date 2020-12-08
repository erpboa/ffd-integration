<?php
/**
 *
 *
 * @package FFD_Propertybase_Sync
 * @since   1.0.0
 */
defined( 'ABSPATH' ) || exit;

/**
 *
 * @class FFD_Propertybase_Sync
 */
class FFD_Propertybase_Sync {

    /**
	 * The single instance of the class.
	 *
	 * @var FFD_Propertybase_Sync
	 * @since 2.1
	*/
    protected static $_instance = null;

    
    public $base_url=null;
    public $endpoint=null;
    public $access_token=null;
    public $version_endpoint=null;
    public $listing_endpoint=null;

    public $credentials_names =  array(
                                    'client_id', 
                                    'client_secret', 
                                    'access_token', 
                                    'instance_url', 
                                    'refresh_token'
                                );
    public $credentials=array();

    public $token_refresh_try=0;
    public $timeout_request_try=0;

    public $post_id = null;
    public $last_request=array();
    public $last_error=null;
    public $response_code=null;
    public $response_headers=null;


    //save pattern values ( multi key format PBKEY+PBKEY )
    // to use later for filling in pbkey value
    // when fetching post data
    public $mapping_pattern_values = array();
    
    //save pb key => format ( value format ) for mapped fields if available
    public $mapping_fields_format = array();

    //save currently in loop post terms values
    public $current_post_terms = array();

    /**
	 * Main FFD_Propertybase_Sync Instance.
	 *
	 * Ensures only one instance of FFD_Propertybase_Sync is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see FFD()
	 * @return FFD_Propertybase_Sync - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
    }




    /**
	 * FFD_Propertybase_Sync Constructor.
	 */
	public function __construct() {
        
        $this->init_hooks();
       
    
    }


     /**
	 * Hook into actions and filters.
	 *
	 * @since 1.0
	 */
    public function init_hooks(){

        register_activation_hook( FFD_PLUGIN_FILE, array( $this, 'plugin_activated' ) );
        register_deactivation_hook( FFD_PLUGIN_FILE, array( $this, 'plugin_deactivated' ) );

        add_action('ffd_propertybasetowp_credentials', array($this, 'credentials_updated'), 10, 1);
       
        $wptopb = get_option('ffd_wptopropertybase_sync', 'no');
        $pbtowp = get_option('ffd_propertybasetowp_sync', 'no');

        if( 'yes' === $wptopb &&  $pbtowp !== 'yes' ){
            add_action('ffd_wptopropertybase_sync', array($this, 'sync_to_pb'));
        }

        add_action('init', function(){
            if(isset($_GET['test_pb_sync']) ){
                $status = $this->sync_to_pb();
                ffd_debug($status, true);
                exit;
            }
        });

       

       

    }



    public function plugin_activated(){

        $timestamp = wp_next_scheduled( 'ffd_main_sync');
        if( !$timestamp ){
            $timestamp = wp_schedule_event(time(), 'hourly', 'ffd_wptopropertybase_sync');
        }

    }


    public function plugin_deactivated(){

        $timestamp = wp_next_scheduled( 'ffd_main_sync');
        if( $timestamp ){
            wp_unschedule_event( $timestamp, 'ffd_wptopropertybase_sync');
        }

        update_option('_wptopropertybase_synced', '');
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


    public function sync_to_pb(){

        $wptopb = get_option('ffd_wptopropertybase_sync', 'no');
        $pbtowp = get_option('ffd_propertybasetowp_sync', 'no');

        if( 'yes' !== $wptopb ||  $pbtowp === 'yes' ){
            return;
        } 

        do_action('ffd_logger', array('WP_TO_PB_SYNC' => 'STARTED'));

        $upload_dir = wp_upload_dir();
        $log_file = $upload_dir['basedir'] . '/wptopropertybase_' . date('Y-m-d') . '.log';

        //open file to write
        // clear content to 0 bits
        $fh = fopen( $log_file, 'w' );
        fclose($fh);

        $this->set_credentials();
        
        $synced = get_option('_wptopropertybase_synced', array());
        if(empty($synced) )
            $synced = array();

        $posts = $this->get_posts_to_sync();

        $status = array(
            'processed_now' => 0,
            'synced_now' => 0,
            'synced_total' => 0,
        );
        
        if( $posts ){
            foreach($posts as $post ){

                if( isset($post->ID) )
                    $post_id = $post->ID;
                else
                    $post_id = $post;

                

                $compositeRequest = $this->create_post_composite($post);
                $compositeResource = array(
                    'allOrNone' => true, // if any of the sub request failed revert thw whole composite request.
                    'compositeRequest' => $compositeRequest
                );

                $compositeResource['compositeRequest'] = array_slice($compositeResource['compositeRequest'], 0, 50);
                $result = $this->process_composite_request($compositeResource);
            
                $status['processed_now']++;
                $listing_id = $this->parse_composite_response($result);
                
                if( $listing_id ){
                    $status['synced_now']++;
                    $synced[$listing_id] = $post_id;
                    update_option('_wptopropertybase_synced', $synced);
                    update_post_meta($post_id, 'ffd_salesforce_id', $listing_id);
                } else {
                    error_log($this->last_error . "\n\r" . "\n\r", 3, $log_file);
                }

                //@Todo only when testing
                if( isset($_GET['test_pb_sync']) ){ 
                    ffd_debug($this->last_error, false); 
                    ffd_debug($this->listing_fields); 
                    ffd_debug($this->last_request); 
                    exit; 
                }
            }
        }
        
        $status['synced_total'] = count($synced);
        
        do_action('ffd_logger', array('WP_TO_PB_SYNC' => $status));

        return $status;
    }


    public function parse_composite_response($result){

        $listing_id = false;
     
       
        if( !is_wp_error($result) && isset($result->compositeResponse)){
            foreach($result->compositeResponse as $compositeResponse){
                //fetch listing id from first response;
                if(!$listing_id){
                    $listing_id = $compositeResponse->body->id;
                }
            }
        }

        return $listing_id;

    }


    public function process_composite_request($compositeResource){

        $response = null;
        $requset_count = count($compositeResource['compositeRequest']); //should not exceed 25 operations.
       
        if( $requset_count > 25 ){

            //extract the first 2 request ( related to listing upsert and get updated/created listing data )
            $listing_part = array_slice($compositeResource['compositeRequest'], 0, 2);
            $exceeding_part = array_slice($compositeResource['compositeRequest'], 25);

            //execute first request
            $compositeResource['compositeRequest'] = array_slice($compositeResource['compositeRequest'], 0, 25);
           
            $response = $this->rest_api($this->composite_endpoint, 'POST', $compositeResource);

            if( $this->parse_composite_response($response) ){
                //process remaining subrequest
                $parts_combined = array_merge($listing_part, $exceeding_part);
                $compositeResource['compositeRequest'] = $parts_combined;
                if( $requset_count > 25 ){
                    return $this->process_composite_request($compositeResource);
                } else {
                    $response = $this->rest_api($this->composite_endpoint, 'POST', $compositeResource);
                }
            }
        
        } else {

            $response = $this->rest_api($this->composite_endpoint, 'POST', $compositeResource);
           
        }

        return $response;
    }


    public function create_post_composite($post){
       
            if( isset($post->ID) )
                $post_id = $post->ID;
            else
                $post_id = $post;

            //@_TODO_ for testing.
            if( isset($_GET['test_pb_sync']) ){$post_id = $_GET['test_pb_sync'];}

            
            $this->post_id = $post_id;
            

            $postData = $this->get_post_data($post_id);
            $mediaData = $this->get_media_data($post_id);

            $compositeRequest = array();

            //Warning: Don't change,  The first two request for compositeRequest are expected 
            //to be the following ( Listing & ListingInfo ) two for later use

            //add/update listing
            $compositeRequest[] = array(
                'method' => 'PATCH',
                'url' => $this->listing_endpoint . '/Site_Post_Id__c/' . $post_id,
                'referenceId' => 'Listing',
                'body' => $postData
            );

            //get created listing info to be used in media request
           $compositeRequest[] = array(
                'method' => 'GET',
                'url' => $this->listing_endpoint . '/@{Listing.id}',
                'referenceId' => 'ListingInfo'
            ); 





            if( !empty($mediaData) ){
                foreach($mediaData as $mediaIndex => $media  ){

                    $media_id = $media['pba__SystemExternalId__c'];
                    unset($media['pba__SystemExternalId__c']);
                    $compositeRequest[] = array(
                        'method' => 'PATCH',
                        'url' => $this->media_endpoint . '/pba__SystemExternalId__c/'.$media_id,
                        'referenceId' => 'Media_' . $media_id,
                        'body' => $media
                    );
                    
                }    
            } 

            

        return $compositeRequest;
    }

    public function get_posts_to_sync($args=array()){

        $post_type = ffd_get_listing_posttype();
        $synced = get_option('_wptopropertybase_synced', array());

        $defaults = array(
            'post_type' => $post_type,
            'posts_per_page' => -1,
            'post_status' => array('publish'),

            'sync_to_pb' => 1,
            'fields' => 'ids',
            'ffd_wpquery_label' => 'wptopropertybase_posts'
            /* 'meta_query' => array(
                'compare' => 'AND',
                array(
                    'key' => '_sync_to_pb',
                    'value' => 'yes',
                    'compare' => '='
                )
            ), */
            
            /* 
            'cache_results' => false,
            'update_post_term_cache' => false,
            'update_post_meta_cache' => false, */
        );

        if( !empty($synced) ){
           $args['post__not_in'] = array_values($synced);
        }

        $args = wp_parse_args($args, $defaults);

        $args = apply_filters('ffd_wptopropertybase_postargs', $args);

        do_action('ffd_logger', $args);

        $wpquery = new WP_Query($args);
        $posts = isset($wpquery->posts) ? $wpquery->posts: false;
        return $posts;
    }


    public function get_post_data($post_id){

        global $post;

        $post = get_post($post_id);
        setup_postdata($post);
    
        $mappings = $this->get_mappings();
        $post_fields = $this->get_mappings('post_fields');
        
       
        
       
        $data = array();
       
        
        $this->mapping_fields_format = array();
        $this->mapping_pattern_values = array();


        foreach($mappings as $pb_key => $format ){
            if( strpos($format, '=') !== false ){
                $this->get_multikey_field_values($format, $pb_key, $post_id, $mappings);
            }
        }
    
        foreach($mappings as $pb_key => $format ){
            if( !empty( $this->mapping_pattern_values[$pb_key]) ){
                $value = $this->mapping_pattern_values[$pb_key];
            } else {
                $value = $this->get_maped_field_value($format, $pb_key, $post_id, $mappings);
            }

            $value = trim($value);
            if(preg_match("/^[0-9,]+$/", $value)) 
                $value = str_replace(',', '', $value);

            $data[$pb_key] = $value;
        }

        $data = array_filter($data, 'strlen');
        
        //unset some default sf fields that API may complains about.
        //specially Id as api complain about it if present
        $unset_fields = array('Id', 'CreatedDate', 'LastModifiedDate');
        foreach($unset_fields as $unset_field){
            if( isset($data[$unset_field]))  unset($data[$unset_field]);
        }
        
        
        $formatted_data = array();
        foreach($this->mapping_fields_format as $pb_key => $field ){

            if( isset($data[$pb_key]) ){
                $value  = $this->format_maped_field_value($data[$pb_key], $field['format'], $field['type'], $pb_key);
                if($field['type'] === 'category' || $field['type'] === 'tag'){
                    $value = str_replace(',', ';', $value);
                }
                $formatted_data[$pb_key] = $value;
            }    
        }
        


        //fill in values for post fields.
        foreach( $post_fields as $pb_field => $post_field){
            if( isset($post->$post_field) && '' !== $post->$post_field ){
                $formatted_data[$pb_field] = $post->$post_field;
            }
        }

       

        //cleanup empty values
        $formatted_data = array_filter($formatted_data, 'strlen');
      


        //set other custom fields
        $formatted_data['Is_API__c'] = 'true';
        $formatted_data['Name'] = get_the_title($post_id);
        //$content = apply_filters('the_content', get_the_content(null, false, $post_id));
        //$formatted_data['Website_Description__c'] = $content;
         

        $formatted_data = $this->convert_values_types($formatted_data);

        //$meta_values = get_post_meta($post_id);

        //ffd_debug($post_id, false);
        //ffd_debug($this->current_post_terms);
        //ffd_debug($data, false);
        //ffd_debug($formatted_data, false);
        //ffd_debug($this->mapping_fields_format, true); 
        //exit;

        wp_reset_postdata();
        return apply_filters('ffd_wptopropertybase_postdata', $formatted_data, $this);        
        
        
    }


    public function get_media_data($post){

        if( isset($post->ID) )
            $post_id = $post->ID;
        else
            $post_id = $post;

        $media = get_attached_media( 'image' , $post_id);

        $data = array();
        $imgCount = 0;

        $testUrls = array();
        $post_thumbnail_id = get_post_thumbnail_id( $post );

        if( !empty($post_thumbnail_id) ){
            $image = get_post($post_thumbnail_id);
            
            $image_attributes = wp_get_attachment_image_src($image->ID, 'full');
            $src = $image_attributes[0];

            //if( file_exists($src) ){
                //$testUrls[basename($src)] = $src;
                $data[] =  array(
                    'pba__Title__c' => $image->post_title,
                    'Name' => $image->post_title,
                    'pba__Property__c'=> '@{ListingInfo.pba__Property__c}',
                    'pba__ExternalThumbnailUrl__c' => wp_get_attachment_thumb_url( $image->ID ),
                    'pba__SystemExternalId__c' => $image->ID,
                    'pba__ExternalLink__c' => $src,
                    'pba__IsExternalLink__c' => true,
                    'pba__Category__c' => 'Images',
                    'pba__IsOnExpose__c' => true,
                    'pba__SortOnWebsite__c' => $imgCount,
                    'pba__SortOnExpose__c' => $imgCount,
                    'pba__MimeType__c' => 'image/jpeg',
                    'pba__IsOnWebsite__c' => true,
                );
                $imgCount++;
            //}
        }


        foreach($media as $image ){

            if( $image->ID == $post_thumbnail_id )
                continue;
           
            $image_attributes = wp_get_attachment_image_src($image->ID, 'full');
            $src = $image_attributes[0];
            //if( file_exists($src) ){
                //$testUrls[basename($src)] = $src;
                $data[] =  array(
                    'pba__Title__c' => $image->post_title,
                    'Name' => $image->post_title,
                    'pba__Property__c'=> '@{ListingInfo.pba__Property__c}',
                    'pba__ExternalThumbnailUrl__c' => wp_get_attachment_thumb_url( $image->ID ),
                    'pba__SystemExternalId__c' => $image->ID,
                    'pba__ExternalLink__c' => $src,
                    'pba__IsExternalLink__c' => true,
                    'pba__Category__c' => 'Images',
                    'pba__IsOnExpose__c' => true,
                    'pba__SortOnWebsite__c' => $imgCount,
                    'pba__SortOnExpose__c' => $imgCount,
                    'pba__MimeType__c' => 'image/jpeg',
                    'pba__IsOnWebsite__c' => true,
                );
                $imgCount++;
            //}
        }


        $data = apply_filters('ffd_wptopropertybase_mediadata', $data, $this);
        
        

        return $data;
       
    }



   public function rest_api($endpoint, $method='POST', $data=array()){

        
        $this->last_request = $data;
        $this->token_refresh_try = 0;
        $this->endpoint = $endpoint;

        $args['headers'] = array(
            'Content-Type' => 'application/json'
        );

        if( !empty($data) ){
            $args['body'] = wp_json_encode($data);
        }

        $args['method'] = $method;
        
        $response = $this->send_request($args);

        
        //handle error for composite response;
        if( !is_wp_error($response) && isset($response->compositeResponse)){
            
            $this->last_error = null;
            $errorCode = null;
            foreach($response->compositeResponse as $compositeResponse){
                $statusCode = intval($compositeResponse->httpStatusCode);
                if( in_array($statusCode, array(200, 201, 203) ) ) {
                    //response is good no need to look for errors.
                    break;
                } else if( $statusCode >= 400 ){
                    $body = $compositeResponse->body;
                    $errorCode = $body[0]->errorCode;
                    $errorMessage = $body[0]->message;
                    $refrenceid = (isset($compositeResponse->referenceId) ? $compositeResponse->referenceId : '');
                    
                    if( !isset($this->last_error) && strpos(strtolower($errorMessage), 'the transaction') === false ){
                        $this->last_error = $errorMessage .' (postid: '.$this->post_id.')';
                        $this->response_code = $errorCode;
                        $response = new WP_Error($this->response_code, $this->last_error);
                        break;
                    }
                }
            }
             
        } 

          
        if( is_wp_error($response) ){
            $this->last_error = $response->get_error_message();
        }

        return $response;
    }

   

    public function get_multikey_field_values($format, $pb_key='', $post_id, $mappings){

        //meta_key=PB_KEY+,+PB_KEY ( pasting two pb key values together i.e when using latlng together seperated by comma)

        $value = $format;
        $_value = trim($value);

        $value = explode('=', $value);
        $value = array_map('trim', $value);
        $db_key = !empty($value[0]) ? $value[0] : $pb_key;
        $key_format = !empty($value[1]) ? $value[1] : $db_key;
        
        $db_value = '';
        $meta_values = get_post_meta($post_id);
        foreach($meta_values as $_meta_key => $_meta_value ){
            if( $_meta_key == $db_key ){
                $db_value = $_meta_value[0];
            }
        }

        $pieces = explode('+', $key_format);
        $pieces = array_map('trim', $pieces);
        
        $pattern_pbkeys = array();
        $pb_index = 0;
        foreach($pieces as $piece ){
            if( $piece !== $pb_key ){
                if( "," !== $piece && " " !== $piece && isset($mappings[$piece]) ){
                    $pattern_pbkeys[$piece] = $pb_index++;
                    $piece_db_key = $mappings[$piece];
                    $piece_value = $this->get_maped_field_value($piece_db_key, $piece, $post_id, $mappings);
                    
                } else {
                    $piece_value = $piece;
                }
                //$db_value = str_replace($piece_value, '', $db_value);
            } else {
                $pattern_pbkeys[$piece] = $pb_index++;
            }
        }

        $value_pattern = str_replace('+', '', $key_format);
        $pattern = $value_pattern;
        foreach($pattern_pbkeys as $key_name => $key_index ){
            $pattern = str_replace($key_name, '(.*)', $pattern);
        }
        preg_match('#'.$pattern.'#', $db_value, $matches);
       
        foreach($pattern_pbkeys as $key_name => $key_index ){
            $value = $matches[ $pattern_pbkeys[$key_name] + 1 ];
            $this->get_maped_field_value($value, $pb_key, $post_id, $mappings);
            $this->mapping_pattern_values[$key_name] = $value;
            
        }



    }


    public function get_maped_field_value($format, $pb_key='', $post_id, $mappings){

        $value = $format;
        $_value = trim($value);

        /* if( strpos($value, '=') !== false ){
            //meta_key=PB_KEY+,+PB_KEY ( pasting two pb key values together i.e when using latlng together seperated by comma)


        } else  */
        if( strpos($value, ':') !== false ){

            $value = explode(':', $value);
            $value = array_map('trim', $value);

            $type = !empty($value[0]) ? $value[0] : 'meta';
            $db_key = !empty($value[1]) ? $value[1] : $_value;
            $format = !empty($value[2]) ? $value[2] : 'text';

        } else {

            $type = 'meta';
            $db_key = $_value;
            $format = 'text';

        }

        $db_value = '';
        if( $type === 'meta' ){
            
            //if field key has "/" then multiple meta value will be check
            //
            $field_metakeys = explode('/', $db_key);
            
           
            $meta_values = get_post_meta($post_id);
            foreach($meta_values as $_meta_key => $_meta_value ){

                foreach($field_metakeys as $field_metakey ){
                    if( $_meta_key == $field_metakey && '' !== $_meta_value[0] ){
                        $db_value = $_meta_value[0];
                        break;
                    }
                }

                
            }
        } else if( $type === 'category' ||  $type === 'tag' ){
           
            $terms_str = '';
            $this->current_post_terms[$db_key] = $this->get_the_terms($post_id, $db_key, true, $terms_str);
            $db_value = rtrim($terms_str, ',');
           
        }

        $this->mapping_fields_format[$pb_key]['db_key'] = $db_key;
        $this->mapping_fields_format[$pb_key]['format'] = $format;
        $this->mapping_fields_format[$pb_key]['type'] = $type;
        $this->mapping_fields_format[$pb_key]['value'] = $db_value;

        return $db_value;

    }


    public function convert_values_types($data){

        foreach($data as $pb_field => $value ){

            if( isset($this->listing_fields[$pb_field]) ){

                $field  = $this->listing_fields[$pb_field];
                $type   = $field['type'];
                $length = $field['length'];

                //Fix value for lat long if no in proper format.
                switch($field){
                    case 'pba__Latitude_pb__c':
                    case 'pba__Longitude_pb__c':
                            $latlng = explode(',', trim($value));
                            if(  $field === 'pba__Latitude_pb__c' ){
                                $value = $latlng[0];
                            } else {
                                $value = isset($value[1]) ? $value[1] : $value[0];
                            }
                        break;
                }
                
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

    
    
    protected function format_category_field_value($value, $format='', $type='', $pb_key='') {
        
        if( strpos($format, 'is_selected ') !== false ){
            
            $format_str = explode('is_selected', $format);
            $format_pieces = array_map('trim', $format_str);
            $index = (int) $format_pieces[0];
            $term_title = $format_pieces[1];

            //$array = explode(',', $value);

            $db_key = $this->mapping_fields_format[$pb_key]['db_key'];

            if( isset($this->current_post_terms[$db_key]) ){
               
                $result = $this->search_children_of($term_title, $this->current_post_terms[$db_key]);
                if( $result !== false ){
                    return 'true';
                } else {
                    return 'false';
                }
            } else {
                return 'false';
            }

        } else if( strpos($format, 'children_of') !== false ){

            $format_str = explode('children_of', $format);
            $format_pieces = array_map('trim', $format_str);
            $index = (int) $format_pieces[0];
            $term_title = $format_pieces[1];

            //$array = explode(',', $value);

            $db_key = $this->mapping_fields_format[$pb_key]['db_key'];
            $value  = '';
           
        
            if( isset($this->current_post_terms[$db_key]) ){
               
                    $children = $this->search_children_of($term_title, $this->current_post_terms[$db_key]);
                   
                    // if( strtolower($term_title) == 'lots' ){
                    //     ffd_debug($children);
                    //     exit;
                    // }

                    if( is_array($children) &&  !empty($children) ){
                        $names = array_column($children, 'name', 'term_id');
                       
                        $value = implode(',', $names);

                        //ffd_debug($children);
                    

                        /* foreach ($array as $key => $str) {
                            if( strpos($str, $term_title) === 0 ){
                                $value = $str;
                            }
                        }  */

                        $value = str_replace($term_title, '', $value);
                        $value = trim($value, '>');
                    }

            }

            
            return $value;

        } else if( strpos($format, 'child_of') !== false ){
            //reads "1_child_of PB_KEY" first child of the PB_KEY value
            
            $format_str = explode('_child_of', $format);
            $format_pieces = array_map('trim', $format_str);
            $index = (int) $format_pieces[0];
            $parent_key = $format_pieces[1];
           
         

            if( !empty($parent_key) && isset($this->mapping_fields_format[$parent_key]) ){
                //$parent_key = trim( str_replace('child_of', '', $format) );
                $parent_value = $this->mapping_fields_format[$parent_key]['value'];
                $parents = explode(',', $parent_value);
                if( count($parents) > 0 ){
                    //@todo need to improve this
                    $parent_value = $parents[0];
                    /* foreach ($parents as $key => $parent) {
                        if( strpos($parent, 'child_of') === 0 ){

                        }
                    } */
                }
                return explode('>', $parent_value)[$index];
            } else {
                return '';
            }


        } else if( strpos($format, 'parent') !== false ){
            //reads "child_of PB_KEY"
            $format = trim( str_replace('parent', '', $format) );
            
            return explode('>', $value)[0];

        } else {

            return $this->format_maped_field_value( $value, $format, null);
        }

    }

    public function search_children_of($needle, $haystack, $depth=0){

        foreach($haystack as $element){

                if($element['name'] == $needle ){
                   
                    return isset($element['children']) ? $element['children'] : array();
                } else if(is_array($element['children'])){

                    $children = $this->search_children_of($needle, $element['children']);
                    if ($children !== false){
                        return $children;
                    }
                }

               
        }
        
        return false; 
    }

    protected function format_maped_field_value($value, $format='', $type='', $pb_key='') {

        if( $type === 'category' ){
            
            return $this->format_category_field_value($value, $format, $type, $pb_key);

        } else if( 'comma_seperated' === $format && is_array($value) ){
            $value = implode(',', $value);
        } else if( 'semicolon_seperated' === $format && is_array($value) ){
            $value = implode(';', $value);
        } else if( 'colon_seperated' === $format && is_array($value) ){
            $value = implode(':', $value);
        } else if( 'array' === $format && !is_array($value) && is_string($value) ){
            $value = explode(',', $value);
        } else if( strpos($format, 'split') !== false && !is_array($value) && is_string($value)){
            
            $original = $value;
            $peices =   explode('_', $format);
            $sep    =   isset($peices[1]) ? $peices[1] : 'comma';
            $index  =   isset($peices[2]) ? $peices[2] : '';

            /* $delimeters = array(
                'comma' => ',',
                'space' => ' ',
                'line' => '/\r\n|\r|\n/',
            );

            if( !is_array($value) ){
                $value = implode($delimeters[$sep]);
            } */

            switch ($sep) {
                case 'space':
                    $value = explode(' ', $value);
                    break;
                case 'line':
                    $value = preg_split('/\r\n|\r|\n/', $value);
                    break;
                
                case 'comma':
                        $value = explode(',', $value);
                    break;
                default:
                    $value = $original;
                    break;
            }

            if( $index != '' ){
                $value = isset($value[$index]) ? trim($value[$index]) : $original;
            }

        } else if( 'boolean' === $format ){

            $value = ( !empty($value) ) ? 1 : 0;

        } else if( 'number' === $format ){

            $value = preg_replace('/\D/', '', $value);

        } else if( 'currency' === $format ){

            $value = preg_replace('#[^0-9\.,]#', '', $value);

        }

        

        return $value;
    }
    
    
    public function send_request( array $args=array()){

        if( empty(get_option('ffd_propertybase_access_token') )){
            return new WP_Error('401', 'API Not Authorized. Try Re-Authorizing using API Settings in Admin.');
        }

       
       

        $url = $this->base_url . $this->endpoint;
        $headers = array(
            'Authorization' => 'OAuth ' . $this->access_token
        );
        if( !empty($args['headers']) ){ 
            $headers = wp_parse_args($args['headers'], $headers);
        }
        $args['headers'] = $headers;


         //wp http 
        //overide http request settings
        add_action('http_api_curl', array($this,'wp_curl_settings'), 10, 1);

        $request = wp_remote_get($url, $args);

       

        if( is_wp_error( $request )){
            if( isset($request->errors) ){

                $errors = $request->errors;
                $error = isset($errors['http_request_failed'][0]) ? $errors['http_request_failed'][0] : 'Request failed.';
                //if( strpos($error, 'cURL error 28') ){
                
                    //try resending request 1 time if we are timingout;
                    if( 1 > $this->timeout_request_try  ){
                        $this->timeout_request_try++;
                        return $this->send_request($args);
                    } else {
                        $args['url'] = $url; 
                        return new WP_Error('request_error', 'Request failed', array('response_errors' => $errors, 'request_args' => $args));
                    }

                //}
            }
            return $request;
        }

        $response_body = wp_remote_retrieve_body( $request );
        $response = json_decode( $response_body );

        //incase of composite request
        if (  isset($response->error) && $response->error == "unsupported_grant_type") {

            do_action('ffd_logger', array('unsupported_grant_type' =>  $this->token_refresh_try) );

            //try refresh token request 1  time
            if( 1 > $this->token_refresh_try  && $this->refresh_token() ){
                
                //re process the last request

                $this->token_refresh_try++;
                return $this->send_request($args);
            } else {

                if( 1 > $this->token_refresh_try ){
                    do_action('ffd_logger', array('unsupported_grant_type' => 'token_refresh_failed') );
                    update_option('ffd_propertybase_access_token', '');
                }

                return new WP_Error('401', 'API Not Authorized. Try Re-Authorizing using API Settings in Admin.');
            }

        } else if ( is_array($response) && isset($response[0]) && isset($response[0]->errorCode) && $response[0]->errorCode == "INVALID_SESSION_ID") {

            do_action('ffd_logger', array('INVALID_SESSION_ID' =>  $this->token_refresh_try) );

            //try refresh token request 1  time
            if( 1 > $this->token_refresh_try && $this->refresh_token() ){
               
               
                $this->token_refresh_try++;

                //re process the last request
                return $this->send_request($args);
            } else {
                
                if( 1 > $this->token_refresh_try ){
                    do_action('ffd_logger', array('INVALID_SESSION_ID' => 'token_refresh_failed') );
                    update_option('ffd_propertybase_access_token', '');
                }

                return new WP_Error('401', 'API Not Authorized. Try Re-Authorizing using API Settings in Admin.');

            }
        } else if ( is_array($response) && isset($response[0]) && isset($response[0]->errorCode)){
            
            $error_code = $response[0]->errorCode;
            $error_message = isset($response[0]->message) ? $response[0]->message : 'No error message found for this error.';
            $error_data = array('args' => $args, 'response' => $response);

           $response = new WP_Error( $error_code, $error_message, $error_data);

        } else {
           
            $response = (!empty($response) ? $response : new WP_Error('empty_response', 'Empty Response', array('args'=>$args) ));
        }

        $response = (!empty($response) ? $response : new WP_Error('empty_response', 'Empty Response', array('args'=>$args) ));
        
            if( !is_wp_error($response) ){
            $this->token_refresh_try = 0;
        }

        return $response;

    }


    

    public function refresh_token(){

        $this->endpoint = "/services/oauth2/token";

        //$url =  $this->api_base ;
            
        $args['method'] = 'POST';

        $args['body'] = array(
            'grant_type' => 'refresh_token',
            'client_id' => $this->credentials['client_id'],
            'client_secret' => $this->credentials['client_secret'],
            'refresh_token' => $this->credentials['refresh_token']
        );

        $response = $this->send_request($args);

        if( !is_wp_error($response) ){
            $this->update_credentials($response);
            return true;
        }

        return false;
       
    }


    public  function set_credentials(){

        foreach( $this->credentials_names as $name ){
            $option_name = 'ffd_propertybase_' . $name;
            if( $value = get_option($option_name) ){
                $this->credentials[$name] = $value;
            }
        }
        
        
        $this->base_url = $this->credentials['instance_url'];
        $this->access_token = $this->credentials['access_token'];
        //set version url
        $this->set_version_endpoint();

       
        $this->composite_endpoint = $this->version_endpoint.'/composite/';
        $this->property_endpoint = $this->version_endpoint . '/sobjects/pba__Property__c';
        $this->listing_endpoint = $this->version_endpoint . '/sobjects/pba__Listing__c';
        $this->media_endpoint = $this->version_endpoint . '/sobjects/pba__PropertyMedia__c';
        
        //listing allowable fields.
        $this->listing_fields = $this->get_fields();
        

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

        $this->base_url = $this->credentials['instance_url'];
        $this->access_token = $this->credentials['access_token'];

        do_action('ffd_salesforce_rest_credentials', $this->credentials);

        return $updated;

    }


    public function get_fields(){

        //$this->listing_fields = get_option('propertybase_api_fields');

        //if( empty($this->listing_fields) ){
            
            $this->listing_fields = array();

            $this->endpoint =  $this->listing_endpoint . '/describe';
            $response = $this->send_request(array());

            if( !is_wp_error($response) ){
                foreach($response->fields as $field){
                    $this->listing_fields[$field->name] = array('label'=>$field->label, 'type'=>$field->type, 'length'=>$field->length);
                }
            }
        //}

        return $this->listing_fields;
    }


    public function get_mappings($context="default"){
        
       
        $fields_mapping = get_option( 'propertybase'. '_fields_mapping', array());
       
        $post_fields = array();
        $post_table = array(
            'ID',
            'post_author',
            'post_date', 
            'post_date_gmt', 
            'post_content', 
            'post_title', 
            'post_excerpt', 
            'post_status',
            'post_password', 
            'post_name',
            'post_modified', 
            'post_modified_gmt',
            'post_parent', 
            'guid', 
            'menu_order',
        );

        $mappings = array();
        if ( !empty($fields_mapping) ){

            foreach($fields_mapping as $field_name => $field_settings ){
                if( $field_settings['enabled'] == 1){
                    
                    if( isset($this->listing_fields[$field_name]) ){

                        $key = trim($field_settings['key']);
                        $key_pieces = explode(':', $key);
                        if( in_array($key_pieces[0], $post_table) ){
                            $post_fields[$field_name] = $key_pieces[0];
                        } else {
                            $mappings[$field_name] = $key;
                        }
                    
                    }
                }

            }

        } 

        if( $context == 'post_fields' ){
            return $post_fields;
        }

        return $mappings;

    }




    public function set_version_endpoint(){

        //default version url
        // this don't need base/instance urls
        $this->version_endpoint = '/services/data/v46.0';

        // $versions = $this->get_versions();
        // if( $versions && !is_wp_error($versions) ){
            
        //     $count = count($versions);
        //     if( $count >= 2 )
        //         $version = $versions[$count - 2 ];
        //     else
        //         $version = reset($versions);

        //     $version = (array) $version;
        //     $this->version_endpoint = $version['url'];
        // }

    }



    public function get_version_endpoint(){

        if( !isset($this->version_endpoint) ){
           $this->set_version_endpoint();
        }

        return $this->version_endpoint;
    }



    public function get_versions(){

        $this->endpoint = "/services/data";
        $response = $this->send_request();

        return $response;
    }



    public function get_resources(){

        $this->endpoint = $this->version_endpoint;
        $response = $this->send_request();

        return $response;
    }


    public function get_object($type='all', $name=''){

        if( empty($type) || empty($name) || 'all' === 'type' )
            $this->endpoint = $this->version_endpoint . '/sobjects';
        else
            $this->endpoint = $this->version_endpoint . '/sobjects/' . $name; // i.e  name = pba__Listing__c

        $response = $this->send_request();

        return $response;
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


    


    public function get_the_terms($postid, $taxonomy, $sort=false, &$terms_str){
        
        global $wpdb;
    
        $sql = "    SELECT t.name, t.term_id, tt.parent FROM {$wpdb->prefix}terms AS t 
        INNER JOIN {$wpdb->prefix}term_taxonomy AS tt ON (tt.term_id = t.term_id) 
        INNER JOIN {$wpdb->prefix}term_relationships AS tr ON (tr.term_taxonomy_id = tt.term_taxonomy_id) 
        WHERE tt.taxonomy IN ('{$taxonomy}') AND tr.object_id IN ($postid) 
        ORDER BY t.name ASC;";
    
        $results = $wpdb->get_results($sql, ARRAY_A);
    
        $terms = array();
        if( ! empty( $results ) ) {
           
            if ( ! is_wp_error( $results )  ) {
                if( $sort == true ){
                    
                    //$this->sort_terms_hierarchicaly($results, $terms);

                    //foreach( $results as $term ) {
                       /*  $_id = $term->parent != 0  ? $term->parent : $term->term_id;
                        $terms[$_id][] = $term; */

                        
                    //}
                    
                    

                    $terms = $this->buildTree($results, 0, $terms_str);
                   
                   

                } else {
                    $terms = $results;
                }

            } else {
                //ffd_debug($results, true);
            }
        }

    
    
        return $terms;
    
    }


    
    function buildTree(array $elements, $parentId = 0, &$terms_str, $sep='') {
        $branch = array();
        
        if( $parentId === 0 )
            $sep = ',';
        else 
            $sep = '>';


        foreach ($elements as $element) {
            
            
            if ($element['parent'] == $parentId) {
                
                $terms_str .= $element['name'] . '>';
                $children = $this->buildTree($elements, $element['term_id'], $terms_str);
                if ($children) {
                    $element['children'] = $children;
                } else {
                    $terms_str .= ',';
                }
                
              
                $branch[] = $element;
            }
           
        }
        
        $terms_str = rtrim($terms_str, '>');

        return $branch;
    }


    function sort_terms_hierarchicaly(array &$cats, array &$into, $parentId = 0){
        foreach ($cats as $i => $cat) {
            if ($cat->parent == $parentId) {
                $into[] = $cat;
                unset($cats[$i]);
            }
        }

        foreach ($into as $topCat) {
            $topCat->children = array();
            $this->sort_terms_hierarchicaly($cats, $topCat->children, $topCat->term_id);
        }
    }


    function terms_hierarchical_string($terms, $str='', $sep=''){

        $terms_strn = '';
        foreach($terms as $term ){
            $str .= $term->name;
            if( $term->children){
                $str .='>';
                return $this->terms_hierarchical_string($term->children, $str, '');
            } else {
                $str .=',';
            }
            
        }

        return $str;

    }

    


    public function get_error_description($code){

        $errors = array(
            '200' => "OK success code, for GET or HEAD request.",
            '201' => "Created success code, for POST request.",
            '204' => "No Content success code, for DELETE request.",
            '300' => "The value returned when an external ID exists in more than one record. The response body contains the list of matching records.",
            '304' => "The request content has not changed since a specified date and time. The date and time is provided in a If-Modified-Since header. See Get Object Metadata Changes for an example.",
            '400' => "The request couldn’t be understood, usually because the JSON or XML body contains an error.",
            '401' => "The session ID or OAuth token used has expired or is invalid. The response body contains the message and errorCode.",
            '403' => "The request has been refused. Verify that the logged-in user has appropriate permissions. If the error code is REQUEST_LIMIT_EXCEEDED, you’ve exceeded API request limits in your org.",
            '404' => "The requested resource couldn’t be found. Check the URI for errors, and verify that there are no sharing issues.",
            '405' => "The method specified in the Request-Line isn’t allowed for the resource specified in the URI.",
            '415' => "The entity in the request is in a format that’s not supported by the specified method.",
            '500' => "An error has occurred within Lightning Platform, so the request couldn’t be completed. Contact Salesforce Customer Support."
        );
    }


}


/**
 * Main instance of FFD_Propertybase_Sync.
 *
 * Returns the main instance of FFD_UI to prevent the need to use globals.
 *
 * @since  1.0
 * @return FFD_Propertybase_Sync
 */
function FFD_Propertybase() {
	return FFD_Propertybase_Sync::instance();
}
FFD_Propertybase();
