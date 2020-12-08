<?php
register_activation_hook( FFD_PLUGIN_FILE, 'activate_cronjob_ffd_map_search' );

register_deactivation_hook( FFD_PLUGIN_FILE, 'deactivate_cronjob_ffd_map_search' );


//cron event for load/refresh listing data to custom table
add_action( 'cronjob_ffd_map_search', 'generate_ffd_map_search_listing_db_data', 10, 2 );

//Shortcodes
add_shortcode( 'map_search','shortcode_ffd_map_search');
add_shortcode( 'map_search_filters','shortcode_ffd_map_search_filters');

//Ajax search listing data
add_action('wp_ajax_nopriv_ffdl/listings/mapsearch', 'ajax_ffdl_listings_mapsearch');
add_action('wp_ajax_ffdl/listings/mapsearch', 'ajax_ffdl_listings_mapsearch');



function activate_cronjob_ffd_map_search() {
    
    if (! wp_next_scheduled ( 'cronjob_ffd_map_search')) {
        wp_schedule_event( time(), 'hourly', 'cronjob_ffd_map_search');
    }
}


function deactivate_cronjob_ffd_map_search() {
    delete_transient('ffdl_listings_mapsearch_data');
    wp_clear_scheduled_hook( 'cronjob_ffd_map_search' );
}



function shortcode_ffd_map_search($atts=array(), $content=''){
	
	$select = array();
	$where = array();
	if( isset($atts['fileds']) ){
		$select = explode(',', $atts['fields']);
		unset($atts['fields']);
	}

	if( function_exists('metakey_names_ffd_map_search') ){
		$data_keys = metakey_names_ffd_map_search();
		foreach($data_keys as $field => $meta_key ){
			if( isset($atts[$field]) ){
				$where[$field] = $atts[$field];
			}
			
		}
	}
	
	

	if( !empty($atts) ){
		$atts = array_merge($atts, array('select'=>$select, 'where'=>$where));
	}

	ob_start();
	$template_path = "re-branding/parts/map-search";
	//include($located);
	tk_get_template( $template_path, $atts);
	return ob_get_clean();
}



function shortcode_ffd_map_search_filters($atts=array(), $content=''){
	ob_start();
	

	get_template_part( 're-branding/parts/map-search-filters');

	return ob_get_clean();
}


function ajax_ffdl_listings_mapsearch(){

    if ( false === ( $data = get_transient( 'ffdl_listings_mapsearch_data' ) ) ) {

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

        
        $data = query_ffd_map_search_data($select, $where, $order, $limit, $offset, false);
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


function ffd_map_search_get_listing_data(){

    //update properties cache data
    $data = generate_ffd_map_search_listing_db_data();
    $FILE_CACHE = new FFD_File_Cache_Filesystem();
    $FILE_CACHE->store('data_ffd_map_search', $data, HOUR_IN_SECONDS);

    return $data;
}

/* 
// @Todo not used
// fetch json data from file
*/
function get_data_ffd_map_search_cache(){

    $FILE_CACHE = new FFD_File_Cache_Filesystem();
    //fetch from cache
    $data = $FILE_CACHE->fetch('data_ffd_map_search');

    if( !$data ) {
        //re-generate data and return data //also save it to cache
        $data = ffd_map_search_get_listing_data();
    }
    return $data;
}




function metakey_names_ffd_map_search(){

    $meta_keys = array(
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
    );

    $_meta_keys = apply_filters( 'metakey_names_ffd_map_search', $meta_keys);

    if( !empty($_meta_keys) ){
       foreach($meta_keys as $name => $value ){
            if( isset($_meta_keys[$name]) ){
                $meta_keys[$name] = $_meta_keys[$name];
            }
        } 
    }

    return $meta_keys;
}


function ffd_map_search_data_post_type(){

    $post_type = 'property';

    return $post_type;
}

function ffd_map_search_db_tablename(){

    global $wpdb;
    $post_type = ffd_map_search_data_post_type();
    $table = $wpdb->prefix . $post_type .'_data';

    return $table;
}

function generate_ffd_map_search_listing_db_data(){

    global $wpdb;

    
    $post_type = ffd_map_search_data_post_type();
    $meta_keys = metakey_names_ffd_map_search();

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

    
    
    add_ffd_map_search_listing_data_to_table($sql);

}


//@Todo for testing only;
if( isset($_GET['generate_listing_db_data']) ){
    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    $msc = microtime(true);
    generate_ffd_map_search_listing_db_data();
    exit;
}


function add_ffd_map_search_listing_data_to_table($sql){
    global $wpdb;

    $destination_table = ffd_map_search_db_tablename();

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



function ffd_map_get_listing_data_from_db(){

    global $wpdb;
    $destination_table = ffd_map_search_db_tablename();

    $sql = "SELECT * FROM " . $destination_table;

    $results = $wpdb->get_results( $sql, 'ARRAY_A');
    if( empty($results) ){

        generate_ffd_map_search_listing_db_data();
        $results = $wpdb->get_results( $sql, 'ARRAY_A');
    }

    return $results;
}

function ffd_meta_query_to_sql($meta_query_args){
   

    $meta_query = new FFD_WP_Meta_Query();
    $meta_keys = metakey_names_ffd_map_search();
    $db_keys = array_flip($meta_keys);
    $meta_query->parse_query_vars( $meta_query_args, $db_keys);
    
    // First let's clear some variables
    $distinct         = '';
    $whichauthor      = '';
    $whichmimetype    = '';
    $where            = '';
    $limits           = '';
    $join             = '';
    $search           = '';
    $groupby          = '';
    $post_status_join = false;
    $page             = 1;
    
    $clauses = $meta_query->get_sql( 'post', 'wp_posts', 'ID');
    $join   .= $clauses['join'];
    $where  .= $clauses['where'];
    
    return $clauses['where'];
    
}


function meta_query_sql_ffd_map_search_ids($where){

    global $wpdb;
    $destination_table = ffd_map_search_db_tablename();
    $destination_alias = 'listing';
    $query = "";
    $order_clause = '';
    $limit_clause = '';
    $meta_keys = metakey_names_ffd_map_search();

    foreach($meta_keys as $db_key => $meta_key ){

        $where = str_replace($meta_key, $db_key, $where);
    }

    $query = " SELECT ID FROM " . $destination_table . " as ".$destination_alias." WHERE 1=1 " . $where . $order_clause . $limit_clause;
    
    //echo $query;  exit;

    $data = $wpdb->get_results($query, "ARRAY_N");
    if( !empty($wpdb->last_error) ) {
        $error = new WP_Error('DB_ERROR', $wpdb->last_error);
        $error->add("DB_ERROR", $query);
        return $error;
    } 
    
    
    return $data;
}

function query_ffd_map_search_data( array $select=array(), array $where=array(), string $order, $limit=null, $offset=null, $return_json=true){

    global $wpdb;
    $destination_table = ffd_map_search_db_tablename();
    $destination_alias = 'listing';

    $query = "";
    $meta_keys = metakey_names_ffd_map_search();
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
                $compare = isset($pieces[1]) ? $pieces[1] : '=';
                $type = isset($pieces[2]) ? $pieces[2] : 'CHAR';
                $relation = isset($pieces[3]) ? $pieces[3] : 'AND';

                switch ($compare) {
                    case 'gt': $compare = ">";break;
                    case 'gte': $compare = ">=";break;
                    case 'lt': $compare = "<";break;
                    case 'lte': $compare = "<=";break;
                    case 'eq': $compare = "=";break;
                    case 'ne': $compare = "!=";break;
                }

                if( in_array($compare, array('IN', 'NOT IN')) ){
                    $value = explode(',', $value);
                    $value = array_map('trim', $value);
                    $value = "('" . implode("', '", $value) . "')";
                } else if( in_array($compare, array('LIKE', 'NOT LIKE')) ){
                   
                    $value = "'%" . $value . "%'";
                } else  {
                    $value = "'" . $value . "'";
                }

                //$column = "CAST(".column." AS {$type})";
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