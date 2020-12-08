<?php

/**
 * Plugin setup
 *
 * @package FFD_Integration_Search
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main  Search Class.
 *
 * @class FFD_Integration_Search
 */
class FFD_Integration_Search {
    
    /**
	 * The single instance of the class.
	 *
	 * @var FFD_Integration
	 * @since 2.1
	*/
    protected static $_instance = null;
    protected $scripts = array();
    protected $tablename = '';
    protected $listing_fields = array(); // fields names from ffd integration mapping setttings
    protected $field_types = array(); // listing field types based on ffd integration mapping setttings
    
     /**
	 * Main FFD_Integration_Search Instance.
	 *
	 * Ensures only one instance of FFD_Integration_Search is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see FFD()
	 * @return FFD_Integration_Search - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
    }


    /**
	 * FFD_Integration_Search Constructor.
	 */
	public function __construct() {
        
        global $wpdb;
        $this->tablename = $wpdb->prefix . 'ffd_integration_search';

        $this->init_hooks();
        $this->set_listing_fields();
    
    }


     /**
	 * Hook into actions and filters.
	 *
	 * @since 1.0
	 */
    public function init_hooks(){

      

            register_activation_hook( FFD_PLUGIN_FILE, array($this, 'activate_cronjob_ffd_map_search') );
            register_deactivation_hook( FFD_PLUGIN_FILE, array($this, 'deactivate_cronjob_ffd_map_search') );

            add_action('wp_enqueue_scripts', array($this, 'register_scripts'), 20);

            add_action('ffd_sync_listing_deleted', array($this,  'delete_listing_in_custom_table'), 10, 1);
            add_action('ffd_sync_listing_created', 'update_listing_in_custom_table',  10, 1);

            //cron event for load/refresh listing data to custom table
            add_action( 'cronjob_ffd_map_search', array($this, 'generate_ffd_map_search_listing_db_data', 10, 2) );

            //Shortcodes
            add_shortcode( 'ffd-search-form', array($this, 'shortcode_ffd_search_form') );
            add_shortcode( 'map_search', array($this, 'shortcode_ffd_map_search') );
            add_shortcode( 'map_search_filters', array($this, 'shortcode_ffd_map_search_filters') );

            //Ajax search listing data
            add_action('wp_ajax_nopriv_ffd/integration/search', array($this, 'ajax_ffd_integration_search') );
            add_action('wp_ajax_ffd/integration/search', array($this, 'ajax_ffd_integration_search') );
        
    }

    public function register_scripts(){

        $this->scripts = array(
            'GoogleMap' => array('src'=>'https://maps.googleapis.com/maps/api/js?key=' . $this->get_settings('ffd_gmap_api_key') . '&callback=initMap&libraries=drawing', 'deps'=>array(), 'ver'=>'', 'in_footer'=>false),
            'MarkerClusterer' => array('src'=>FFD()->plugin_url() . '/js/markerclusterer.js', 'deps'=>array('jquery'), 'ver'=>'', 'in_footer'=>false),
            'MarkerWithLabel' => array('src'=>FFD()->plugin_url() . '/js/markerwithlabel.js', 'deps'=>array('jquery'), 'ver'=>'', 'in_footer'=>false),
            'ffd-integration-search' => array('src'=>FFD()->plugin_url() . '/includes/ffd-integration-search/ffd-integration-search.js', 'deps'=>array('jquery', 'underscore'), 'ver'=>'', 'in_footer'=>false),
        );
        foreach($this->scripts as $handle => $script){
            wp_register_script($handle, $script['src'], $script['deps'], $script['ver'], $script['in_footer']);
        }
      
        $this->enqueue_scripts();

    }

    private function enqueue_scripts(){
        
        wp_enqueue_script('jquery');
        wp_enqueue_script('underscore');

        foreach($this->scripts as $handle => $script){
            wp_enqueue_script($handle);
        }

        $FFD_Search_Settings =  array(
                                'ajaxurl' => admin_url('admin-ajax.php'),
                                'homeurl' => home_url(),
                                'templateurl' => get_template_directory_uri(),
                                'stylesheeturl' => get_stylesheet_directory_uri(),
                                'where' => '',
                                'select' => '',
                                'offset' => '',
                                'limit' => '',
                                'image_placeholder' => '',
                                'keynames' => $this->listing_fields,
                            );
        wp_localize_script('ffd-integration-search', 'FFD_Search_Settings', $FFD_Search_Settings);
    }


    public function update_listing_in_custom_table($postid){

        global $wpdb;
    
        $post = get_post( $postid );
     
        if ( ! $post ) {
            return $post;
        }
        $table_fields = $this->get_table_fields();
        $fields = $this->get_listing_fields();
        $columns = array_keys($fields);
        $data = array();
        foreach($columns as $column){
            if( in_array($column, $table_fields) && $meta_value = get_post_meta($post->ID, $column, true) ){
                $data[$columns] = $meta_value;
            }
        }

        $where = array('ID'=>$post->ID);
    
        $result = $wpdb->update( 
            $this->tablename, 
            $data, // array( column_name=>value ) 
            $where
        );

        if( $result !== false && $result === 0 ){

            $result = $wpdb->insert( 
                $this->tablename, 
                $data
            );
        }
        
    }

    public function activate_cronjob_ffd_map_search() {
    
        if (! wp_next_scheduled ( 'cronjob_ffd_map_search')) {
            wp_schedule_event( time(), 'hourly', 'cronjob_ffd_map_search');
        }
    }
    
    
    public function deactivate_cronjob_ffd_map_search() {
        delete_transient('ffdl_listings_mapsearch_data');
        wp_clear_scheduled_hook( 'cronjob_ffd_map_search' );
    }

    public function delete_listing_in_custom_table($postid){
        global $wpdb;
    
        $delete = "DELETE FROM `".$this->tablename."` WHERE type='listing' AND `postID` = '".$postid."' ";
        $wpdb->query($delete);
    
    }
    
    public function generate_ffd_map_search_listing_db_data(){

        global $wpdb;
    
        
        $post_type = $this->get_listing_post_type();
        $meta_keys = $this->listing_fields;
    
        $meta_sql = '';
        foreach($meta_keys as $meta_column => $meta_key ){
    
            $meta_sql .= "MAX(IF(PM.meta_key = '".$meta_key."', PM.meta_value, NULL)) AS ".$meta_column.",";
    
        }
        $meta_sql = rtrim($meta_sql, ',');
    
        $sql = "SELECT P.ID, P.post_title, P.post_type, ";
        $sql .= $meta_sql;
        $sql .= " FROM {$wpdb->posts} AS P
                INNER JOIN {$wpdb->postmeta} AS PM on PM.post_id = P.ID
                WHERE P.post_type = '".$post_type."' and P.post_status = 'publish' 
                GROUP BY PM.post_id";
    
        
        
        $this->add_ffd_map_search_listing_data_to_table($sql);
    
    }


    public function shortcode_ffd_search_form( $atts, $content = null ) {

        $this->load_search_form_shortcodes();

		$atts = shortcode_atts( array(
					"html_class"    => false,
					"html_id"    => false,
					"data"      => false
		), $atts );

		$class  = 'ffd-integration-search-form';
        $class .= ( $atts['html_class'] )       ? ' ' . $atts['html_class'] : '';
        
		$id = ( $atts['html_id'] )       ? ' ' . $atts['html_id'] : 'ffd-search-forms';

		$data_props = $this->parse_data_attributes( $atts['data'] );

		return sprintf(
			'<form id="%s" class="%s"%s>%s</form>',
			$id,
			$class,
			( $data_props ) ? ' ' . $data_props : '',
			do_shortcode( $content )
		);
    }
    
    private function load_search_form_shortcodes(){

        add_shortcode('search_field', array($this, 'form_shortcodes_search_field'));
    }

    function form_shortcodes_search_field($atts, $content = null ){

        $key = isset($atts['name']) ? $atts['name'] : '';

        $atts = shortcode_atts( array(
                    "return"    => true,
                    "name"    => '',
                    "value"    =>  isset($_REQUEST[$key]) ? $_REQUEST[$key] : null,
                    "class"    => '',
                    "label_class"    => '',
                    "input_class"    => '',
                    "validate"    => '',
                    "options"    => '',
                    "filtercondition"    => '',
                    "data"      => false
        ), $atts );

        
        
        $value = $atts['value'];
        
        //field attributes
        $atts['custom_attributes'] = array();
        $atts['custom_attributes']['data-fieldtype'] = 'ffdsearch';
        $atts['custom_attributes']['data-filtercondition'] = $atts['filtercondition'];

        //extract custom attributes from  data value passed in shortcode.
		if( isset($atts['data'])) {
			$data = explode( '|', $atts['data'] );
			if( !empty($data) ){
                foreach( $data as $d ) {
                    $d = explode( ',', $d );
                    if( !empty($d) && count($d) == 2 ){
                        $atts['custom_attributes'][$d[0]] = trim( $d[1] );
                    }
                }
            }
            unset( $atts['data']);
        }

        
        
        //array as value require by __CLASS__::form_field
        $atts['class'] = explode(' ', $atts['class']);
        $atts['label_class'] = explode(' ', $atts['label_class']);
        $atts['input_class'] = explode(' ', $atts['input_class']);
        $atts['validate'] = explode(' ', $atts['validate']);

        //field options
        $options = $atts['options'];
        $atts['options'] = array();

		if( isset($options)) {
			$data = explode( '|', $options );
			if( !empty($data) ){
                foreach( $data as $d ) {
                    $d = explode( ',', $d );
                    if( !empty($d) && count($d) == 2 ){
                        $atts['options'][$d[0]] = trim( $d[1] );
                    }
                }
            }
        }
        

        
        return $this->form_field($key, $atts, $value);

    }

    public function shortcode_ffd_map_search($atts=array(), $content=''){
	
        $select = array();
        $where = array();
        if( isset($atts['fileds']) ){
            $select = explode(',', $atts['fields']);
            unset($atts['fields']);
        }
    
        
        $data_keys = $this->listing_fields;
        foreach($data_keys as $field => $meta_key ){
            if( isset($atts[$field]) ){
                $where[$field] = $atts[$field];
            }
            
        }
        
        
        
    
        if( !empty($atts) ){
            $atts = array_merge($atts, array('select'=>$select, 'where'=>$where));
        }
    
        ob_start();
        $template_path = "/parts/map-search";
        //tk_get_template( $template_path, $atts);
        return ob_get_clean();
    }


    public function shortcode_ffd_map_search_filters($atts=array(), $content=''){
        ob_start();
        
    
        //get_template_part( '/parts/map-search-filters');
    
        return ob_get_clean();
    }




    public function ajax_ffd_integration_search(){

        if ( isset($_REQUEST['no_cache']) || false === ( $data = get_transient( 'ffdl_listings_mapsearch_data' ) ) ) {
    
            // this code runs when there is no valid transient set
    
            $home = home_url();
            $params = $_REQUEST;
    
            $select=isset($params['select']) ? $params['select'] : array(); 
            $where=isset($params['where']) ? $params['where'] : array();  
            $order=isset($params['order']) ? $params['order'] : '';
            $limit=isset($params['limit']) ? $params['limit'] : null;  
            $offset=isset($params['offset']) ? $params['offset'] : null;  
    
            //@Todo Testing
            //$limit = 50;
    
            
            $data = $this->query_ffd_map_search_data($select, $where, $order, $limit, $offset, false);
            foreach($data as $data_key => $item){
    
                
                $permalink = get_permalink($item['ID']);
                if( empty($permalink) ){
                    unset($data[$data_key]);
                    continue;
                }
    
                if( !empty($item['image']) ){
                    $image = maybe_unserialize($item['image']);
                    if( !empty($image) && is_array($image)){
                        $image = $image[0];
                        $item['image'] = $image;
                    }
                }
                
                $item['link'] = str_replace($home, '', $permalink);
                
            
    
                $str = '';
                $i=0;
                foreach($item as $item_key => $item_value){
                    $str .= $i . "=" . $item_value.'&';
                    $i++;
                }
                $data[$data_key] = rtrim($str, '&');
            }
    
            $data = array_values($data);
            set_transient( 'ffdl_listings_mapsearch_data', $data, 29 * MINUTE_IN_SECONDS );
        }
       
        echo json_encode($data);
        die();
    }




    public function query_ffd_map_search_data( array $select=array(), array $where=array(), string $order, $limit=null, $offset=null, $return_json=true){

        global $wpdb;
        $destination_table = $this->tablename;
        $destination_alias = 'listing';
    
        $query = "";
        $meta_keys = $this->listing_fields;
        $fields ="";
    
       
        //$default =  array('lat', 'lng', 'image', 'size', 'status', 'openhouse', 'yearbuilt', 'listdate', 'proptype', 'beds', 'baths', 'listprice', 'saleprice', 'mls_id', 'city', 'address', 'post_title', 'ID');
        $keynames = array_keys($meta_keys);
        $default = array_merge($keynames, array('post_title', 'ID'));
        if( !empty($select) ){
            $select = array_merge($default, $select);
        } else {
            $select = $default;
        }
    
        $fields = implode(", ", $select);
    
        if( !empty($where) ){
            $where_clause = " WHERE 1=1 ";
            foreach($where as $column => $value){
    
                if($column == 'poly'){
    
                    $latlng_query = " SELECT lat, lng FROM " . $destination_table . " WHERE lat!='' AND lng!='';";
                    $latlng_data = $wpdb->get_results($query, "ARRAY_A");
    
                    $queryLat = $latlng_data['lat'];
                    $queryLon = $latlng_data['lng'];
                    $where_clause .= " AND st_within(point($queryLat,$queryLon),ST_GeomFromText('Polygon((" . $value . "))'))";
    
                } else if( isset($meta_keys[$column]) ){
    
                    $pieces = explode('|', $value);
                    $value = $pieces[0];
                    // value|compare|type|relation
                    $compare = isset($pieces[1]) ? ( '' === $pieces[1] ? 'CHAR' : $pieces[2] ) : '=';
                    $type = isset($pieces[2]) ? ( '' === $pieces[2] ? 'CHAR' : $pieces[2] ) : 'CHAR';
                    $relation = isset($pieces[3]) ? ( '' === $pieces[3] ? 'AND' : $pieces[2] ) : 'AND';
                    
                    switch ($compare) {
                        case 'gt': $compare = ">";break;
                        case 'gte': $compare = ">=";break;
                        case 'lt': $compare = "<";break;
                        case 'lte': $compare = "<=";break;
                        case 'eq': $compare = "=";break;
                        case 'ne': $compare = "!=";break;
                    }

                    if ( ! in_array(
                       $compare,
                        array(
                            '=',
                            '!=',
                            '>',
                            '>=',
                            '<',
                            '<=',
                            'LIKE',
                            'NOT LIKE',
                            'IN',
                            'NOT IN',
                            'BETWEEN',
                            'NOT BETWEEN',
                            'EXISTS',
                            'NOT EXISTS',
                            'REGEXP',
                            'NOT REGEXP',
                            'RLIKE',
                        )
                    ) ) {
                        $compare = '=';
                    }

                    
                    $type = $this->get_cast_for_type($type);

                    //$column = "CAST(".column." AS {$type})";
    
                    if( in_array($compare, array('IN', 'NOT IN')) ){
                        $value = explode(',', $value);
                        $value = array_map('trim', $value);
                        $value = "('" . implode("', '", $value) . "')";
                    } else if( in_array($compare, array('LIKE', 'NOT LIKE')) ){
                       
                        $value = "'%" . $value . "%'";
                    } else  {
                        $value = "'" . $value . "'";
                    }
                    
                    $where_clause .= $relation . " " . $column . " " . $compare . " " .   $value . " ";
                }
    
            }
        }
        
        $order_clause = '';
        if( !empty($order) ){
            $peices1 = explode('|', $order);
            foreach($peices1 as $peice1){
                $peices2 = explode(',', $peice1);
                $label = $peices2[0];
                if( !empty($meta_keys[$label]) ){
                    $orderby = $meta_keys[$label]; 
                    $order = isset($peices2[1]) ? $peices2[1] : 'DESC';
                }
    
                $order_clause .= $orderby . " ". $order . ',';
                $order_clause = rtrim($order_clause, ',');
            }
    
            if( !empty($order_clause) ){
                $order_clause = " ORDER BY " . $order_clause . " ";
            } 
        } else {
            $order_clause = ' ORDER BY listprice DESC ';
        }
    
    
        $limit_clause = '';
        $offset = (int) $offset;
        $limit = (int) $limit;
        
        if( $limit > 0 ){
            $limit_clause = "LIMIT " . $offset . ", " . $limit . " "; 
        } else if( $offset > 0 && $limit <= 0 ){
            $limit = 18446744073709551615;
            $limit_clause = "LIMIT " . $offset . ", " . $limit . " "; 
        }
        
        
    
       $query = " SELECT " . $fields . " FROM " . $destination_table . " as ".$destination_alias." " . $where_clause . $order_clause . $limit_clause;
        
        //echo $query;  exit;
    
        $data = $wpdb->get_results($query, "ARRAY_A");
        if( !empty($wpdb->last_error) ) {
            $error = new WP_Error('DB_ERROR', $wpdb->last_error);
            $error->add("DB_ERROR", $query);
            return $error;
        } 
        
        $data = array_map('maybe_unserialize', $data);
    
    
        if( $return_json )
            $data = json_encode($data);
        
        
    
        return $data;
    }


    public function add_ffd_map_search_listing_data_to_table($sql){
        global $wpdb;
    
        $destination_table = $this->tablename;
    
        $drop_table = "DROP TABLE IF EXISTS {$destination_table}";
        $wpdb->query($drop_table);
    
        $create_table = "CREATE TABLE  {$destination_table} " 
        . $sql
        . ' limit 0' // we need to create table columns, data will be insert later
        ;
        
        $wpdb->query($create_table);
        $insert_data = "INSERT INTO {$destination_table} " . $sql ;
        $wpdb->query($insert_data); 
    
    
    }


    public function get_db_tablename(){

        
    
        return $this->tablename;
    }

    public function get_listing_post_type(){

        $post_type = apply_filters('ffd_listing_posttype', 'listing');
    
        return $post_type;
    }


    public function get_listing_fields(){

        if( !isset($this->listing_fields) || empty($this->listing_fields) ){
            $this->set_listing_fields();
        }

        return $this->listing_fields;
    }
    public function set_listing_fields(){

        /* Llamada original a PropertyBase
        $field_mapping = FFD_Listings_Sync::fields(null, 'meta');
        */

        //Nueva llamada a Trestle
        $field_mapping = FFD_Listings_Trestle_Sync::fields(null, 'meta');

        $fields = array();
        $field_types = array();
        foreach($field_mapping as $pb_key => $db_key){
            $sanitize_field = $this->sanitize_field_key($db_key);
            $key = $sanitize_field['key'];
            $type = $sanitize_field['type'];
            if( !empty($key) ){
                $field_types[$key] = $type;
                $fields[$key] = $pb_key;
            }
        }
        /* $fields = array(
            'mls_id'        =>  'mls_id__c',
            'listdate'      =>  'listed_date__c',
            'saledate'      =>  'sale_date__c',
            'listprice'     =>  'pba__listingprice_pb__c',
            'saleprice'     =>  'sale_price__c',
            'sqftprice'     =>  'price_per_sqft__c',
            'status'        =>  'pba__status__c',
            'listtype'      =>  'pba__listingtype__c',
            'proptype'      =>  'propertytypenormailzed__c',
            'dom'           =>  'dom__c',
            'lat'           =>  'pba__latitude_pb__c',
            'lng'           =>  'pba__longitude_pb__c',
            'beds'          =>  'pba__bedrooms_pb__c',
            'baths'         =>  'pba__fullbathrooms_pb__c',
            'size'          =>  'pba__lotsize_pb__c',
            'totalarea'     =>  'pba__totalarea_pb__c',
            'city'          =>  'pba__city_pb__c',
            'state'         =>  'pba__state_pb__c',
            'neighborhood'  =>  'area_text__c',
            'address'       =>  'pba__address_pb__c',
            'postalcode'    =>  'pba__postalcode_pb__c',
            'image'         =>  'media',
            'openhouse'     =>  'open_house_date_time__c',
            'yearbuilt'     =>  'pba__yearbuilt_pb__c',
            'parking'       =>  'parkingspaces__c',
            'view'          =>  'view__c'
        ); */
    
        $_fields = apply_filters( 'ffd_listing_fields', $fields);
    
        if( !empty($_fields) ){
           foreach($fields as $name => $value ){
                if( isset($_fields[$name]) ){
                    $fields[$name] = $_fields[$name];
                }
            } 
        }

        $this->listing_fields = $fields;
        $this->field_types = $field_types;
    
       
    }

    public function sanitize_field_key($field_key){

        if( strpos($field_key, '=') !== false ){

            $key_pieces = explode('=', $field_key);
            $key_pieces = array_map('trim', $key_pieces);
            $key = isset($key_pieces[0]) && !empty($key_pieces[0]) ? $key_pieces[0] : '';

        } else {

            if( strpos($field_key, ':') !== false ){

                $key_pieces = explode(':', $field_key);
                $key_pieces = array_map('trim', $key_pieces);

                $type =     isset($key_pieces[0]) ? $key_pieces[0] : 'meta';
                $key =      isset($key_pieces[1]) ? $key_pieces[1] : '';
                $format =   isset($key_pieces[2]) ? $key_pieces[2] : 'text';

            } else {

                $type = 'meta';
                $key = trim($field_key);
                $format = 'text';

            }

        }

        $return = array(
            'type' => $type, 
            'key' => $key, 
            'format' => $format
        );

        return $return;
    
    }


    public  function get_table_fields()
    {
        global $wpdb;

        $sql="show columns from " . $this->tablename;
        $fields=array();

        $records = $wpdb->get_results($sql);

        foreach($records as $record)
        {
            $fields[]= $record->Field; //str_replace("unsigned","",$record->Type);
        }

        return $fields;
    }


    /**
	 * return/output field values for a key
	 *
	 * @param bool $echo Should echo?.
	 * @return string
	 */
	function field_values_by_name($name, $echo=false, $custom_table=true) {

		if( !isset($post_id) || empty($post_id) ){
			global $post;
			$post_id = $post->ID;
		}
		
		global $wpdb;
        $field = array();
        if($custom_table){
            
            $sql = "SELECT $name FROM $this->tablename GROUP BY $name;";
            $results = $wpdb->get_results($sql, 'ARRAY_A');
            if( !empty($results) ){
                foreach($results[$name] as $result ){
                    $field[$result] = $result;
                }
            }

        }else{

            $sql = "SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE  meta_key = '{$name}' AND meta_value !='' GROUP BY meta_value;";
            $results = $wpdb->get_results($sql, 'ARRAY_A');
            if( !empty($results) ){
                foreach($results as $result ){
                    $field[$result['meta_value']] = $result['meta_value'];
                }
            }

        }

        


		$field = apply_filters( 'ffd_listing_field_values_by_name', $field);

		if ( $echo ) {
			echo ( is_array($field) ? implode(',', $field) : $field ); // WPCS: XSS ok.
		} else {
			return $field;
		}

	}

    /**
     * Outputs form field.
     *
     * @param string $key Key.
     * @param mixed  $args Arguments.
     * @param string $value (default: null).
     * @return string
     */
    function form_field( $key, $args, $value = null ) {
        $defaults = array(
            'type'              => 'text',
            'label'             => '',
            'description'       => '',
            'placeholder'       => '',
            'maxlength'         => false,
            'required'          => false,
            'autocomplete'      => false,
            'id'                => $key,
            'class'             => array(),
            'label_class'       => array(),
            'input_class'       => array(),
            'return'            => false,
            'options'           => array(),
            'custom_attributes' => array(),
            'validate'          => array(),
            'default'           => '',
            'autofocus'         => '',
            'priority'          => '',
        );

        $args = wp_parse_args( $args, $defaults );
        $args = apply_filters( 'ffd_form_field_args', $args, $key, $value );

        if( empty($args['options']) && isset($args['db_key'])){
            $args['options'] = $this->field_values_by_name($args['db_key']);
        }

        if ( $args['required'] ) {
            $args['class'][] = 'validate-required';
            $required        = '&nbsp;<abbr class="required" title="' . esc_attr__( 'required', 'ffd-integration' ) . '">*</abbr>';
        } else {
            $required = '&nbsp;<span class="optional">(' . esc_html__( 'optional', 'ffd-integration' ) . ')</span>';
        }

        if ( is_string( $args['label_class'] ) ) {
            $args['label_class'] = array( $args['label_class'] );
        }

        if ( is_null( $value ) ) {
            $value = $args['default'];
        }

        // Custom attribute handling.
        $custom_attributes         = array();
        $args['custom_attributes'] = array_filter( (array) $args['custom_attributes'], 'strlen' );

        if ( $args['maxlength'] ) {
            $args['custom_attributes']['maxlength'] = absint( $args['maxlength'] );
        }

        if ( ! empty( $args['autocomplete'] ) ) {
            $args['custom_attributes']['autocomplete'] = $args['autocomplete'];
        }

        if ( true === $args['autofocus'] ) {
            $args['custom_attributes']['autofocus'] = 'autofocus';
        }

        if ( $args['description'] ) {
            $args['custom_attributes']['aria-describedby'] = $args['id'] . '-description';
        }

        if ( ! empty( $args['custom_attributes'] ) && is_array( $args['custom_attributes'] ) ) {
            foreach ( $args['custom_attributes'] as $attribute => $attribute_value ) {
                $custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
            }
        }

        if ( ! empty( $args['validate'] ) ) {
            foreach ( $args['validate'] as $validate ) {
                $args['class'][] = 'validate-' . $validate;
            }
        }

        $field           = '';
        $label_id        = $args['id'];
        $sort            = $args['priority'] ? $args['priority'] : '';
        $field_container = '<p class="form-row %1$s" id="%2$s" data-priority="' . esc_attr( $sort ) . '">%3$s</p>';

        switch ( $args['type'] ) {
            case 'textarea':
                $field .= '<textarea name="' . esc_attr( $key ) . '" class="input-text ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" id="' . esc_attr( $args['id'] ) . '" placeholder="' . esc_attr( $args['placeholder'] ) . '" ' . ( empty( $args['custom_attributes']['rows'] ) ? ' rows="2"' : '' ) . ( empty( $args['custom_attributes']['cols'] ) ? ' cols="5"' : '' ) . implode( ' ', $custom_attributes ) . '>' . esc_textarea( $value ) . '</textarea>';

                break;
            case 'checkbox':
                $field = '<label class="checkbox ' . implode( ' ', $args['label_class'] ) . '" ' . implode( ' ', $custom_attributes ) . '>
                        <input type="' . esc_attr( $args['type'] ) . '" class="input-checkbox ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" value="1" ' . checked( $value, 1, false ) . ' /> ' . $args['label'] . $required . '</label>';

                break;
            case 'text':
            case 'password':
            case 'datetime':
            case 'datetime-local':
            case 'date':
            case 'month':
            case 'time':
            case 'week':
            case 'number':
            case 'email':
            case 'url':
            case 'tel':
                $field .= '<input type="' . esc_attr( $args['type'] ) . '" class="input-text ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" placeholder="' . esc_attr( $args['placeholder'] ) . '"  value="' . esc_attr( $value ) . '" ' . implode( ' ', $custom_attributes ) . ' />';

                break;
            case 'select':
                $field   = '';
                $options = '';

                if ( ! empty( $args['options'] ) ) {
                    foreach ( $args['options'] as $option_key => $option_text ) {
                        if ( '' === $option_key ) {
                            // If we have a blank option, select2 needs a placeholder.
                            if ( empty( $args['placeholder'] ) ) {
                                $args['placeholder'] = $option_text ? $option_text : __( 'Choose an option', 'ffd-integration' );
                            }
                            $custom_attributes[] = 'data-allow_clear="true"';
                        }
                        $options .= '<option value="' . esc_attr( $option_key ) . '" ' . selected( $value, $option_key, false ) . '>' . esc_attr( $option_text ) . '</option>';
                    }

                    $field .= '<select name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" class="select ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" ' . implode( ' ', $custom_attributes ) . ' data-placeholder="' . esc_attr( $args['placeholder'] ) . '">
                            ' . $options . '
                        </select>';
                }

                break;
            case 'radio':
                $label_id .= '_' . current( array_keys( $args['options'] ) );

                if ( ! empty( $args['options'] ) ) {
                    foreach ( $args['options'] as $option_key => $option_text ) {
                        $field .= '<input type="radio" class="input-radio ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" value="' . esc_attr( $option_key ) . '" name="' . esc_attr( $key ) . '" ' . implode( ' ', $custom_attributes ) . ' id="' . esc_attr( $args['id'] ) . '_' . esc_attr( $option_key ) . '"' . checked( $value, $option_key, false ) . ' />';
                        $field .= '<label for="' . esc_attr( $args['id'] ) . '_' . esc_attr( $option_key ) . '" class="radio ' . implode( ' ', $args['label_class'] ) . '">' . $option_text . '</label>';
                    }
                }

                break;
        }

        if ( ! empty( $field ) ) {
            $field_html = '';

            if ( $args['label'] && 'checkbox' !== $args['type'] ) {
                $field_html .= '<label for="' . esc_attr( $label_id ) . '" class="' . esc_attr( implode( ' ', $args['label_class'] ) ) . '">' . $args['label'] . $required . '</label>';
            }

            $field_html .= '<span class="ffd-input-wrapper">' . $field;

            if ( $args['description'] ) {
                $field_html .= '<span class="description" id="' . esc_attr( $args['id'] ) . '-description" aria-hidden="true">' . wp_kses_post( $args['description'] ) . '</span>';
            }

            $field_html .= '</span>';

            $container_class = esc_attr( implode( ' ', $args['class'] ) );
            $container_id    = esc_attr( $args['id'] ) . '_field';
            $field           = sprintf( $field_container, $container_class, $container_id, $field_html );
        }

        /**
         * Filter by type.
         */
        $field = apply_filters( 'ffd_form_field_' . $args['type'], $field, $key, $args, $value );

        /**
         * General filter on form fields.
         *
         * @since 3.4.0
         */
        $field = apply_filters( 'ffd_form_field', $field, $key, $args, $value );

        if ( $args['return'] ) {
            return $field;
        } else {
            echo $field; // WPCS: XSS ok.
        }
    }


    /*--------------------------------------------------------------------------------------
		*
		* Parse data-attributes for shortcodes
		*
		*-------------------------------------------------------------------------------------*/
	public function parse_data_attributes( $data ) {

		$data_props = '';

		if( $data ) {
			$data = explode( '|', $data );

			foreach( $data as $d ) {
				$d = explode( ',', $d );
				$data_props .= sprintf( 'data-%s="%s" ', esc_html( $d[0] ), esc_attr( trim( $d[1] ) ) );
			}
		}
		else {
			$data_props = false;
		}
		return $data_props;
    }
    

    public function get_cast_for_type( $type = '' ) {
		if ( empty( $type ) ) {
			return 'CHAR';
		}

		$char_type = strtoupper( $type );

		if ( ! preg_match( '/^(?:BINARY|CHAR|DATE|DATETIME|SIGNED|UNSIGNED|TIME|NUMERIC(?:\(\d+(?:,\s?\d+)?\))?|DECIMAL(?:\(\d+(?:,\s?\d+)?\))?)$/', $char_type ) ) {
			return 'CHAR';
		}

		if ( 'NUMERIC' == $char_type ) {
			$char_type = 'SIGNED';
		}

		return $char_type;
	}

    public function get_settings($name=''){


        return get_option($name);
    }
    
}


/**
 * Main instance of FFD_Integration_Search.
 *
 * Returns the main instance of FFD_Search to prevent the need to use globals.
 *
 * @since  1.0
 * @return FFD_Integration_Search
 */
function FFD_Search() {
	return FFD_Integration_Search::instance();
}
FFD_Search();
