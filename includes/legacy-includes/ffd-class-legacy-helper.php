<?php

class FFDL_Helper{

    public static $cache_wpdb_result = false;

    public static function get_community_id($page_title) {
        $page_title=str_replace('&','&amp;',$page_title);

        global $wpdb;
            $post = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_status='publish' AND post_type='community' AND post_parent=%d", $page_title, 0));
            if ( $post )
                return $post;
    
        return null;
    }
    public static function get_max($field)
    {
        global $wpdb;
        $query = $wpdb->prepare("SELECT MAX(cast(meta_value as UNSIGNED)) FROM {$wpdb->postmeta} WHERE meta_key='%s'", $field);
    
        $max=$wpdb->get_var($query); 

        return $max;
    }

    public static function custom_meta_fields($params){


        if( isset($_GET['debug']) ){
            ffd_debug($params, false);
        }

        $custom_metaquery = array();
        $prefix = '_meta_';
        foreach($params as $param => $param_value){

            if (substr($param, 0, strlen($prefix)) == $prefix) {

               

                $key = substr($param, strlen($prefix));
                $value = $param_value;
                $pieces = explode('|', $value);
                $value = $pieces[0];
                $compare = isset($pieces[1]) ? $pieces[1] : '=';
                $type = isset($pieces[2]) ? $pieces[2] : 'CHAR';
                $relation = isset($pieces[3]) ? $pieces[3] : 'AND';

                if( in_array($compare, array('IN', 'NOT IN')) ){
                    $value = explode(',', $value);
                    $value = array_map('trim', $value);
                }
        
                $custom_metaquery[] = array(
                    'relation' => $relation,
                    array(
                        'key' => $key,
                        'value' => $value,
                        'compare' => $compare,
                        'type' => $type
                    )
                );

                unset($params[$param]);

            } 

        }

        if( isset($_GET['debug']) ){
            ffd_debug($custom_metaquery, false);
        }

        return array($params, $custom_metaquery);

    }

    public static function ffdl_query_listings($data=false, $pagination=true)
    {   
        do_action('ffdl_before_query_listings');
        
        global $post;

        $params=self::get_search_params($data);
       

        $meta_query=array();
        $orderby=array();
        $pageSize=1;
        $page=1;

       

        if( !is_admin()  ){
             $_SESSION['ffdl_search_page_id'] =  isset($params['_search_page_id']) ? $params['_search_page_id'] : $post->ID;
            $params = array_filter($params);
            $_SESSION['ffdl_user_last_search'] = urlencode('&' . http_build_query($params));
        }

        //ffd_debug($params);

        $custom_meta_fields = self::custom_meta_fields($params);
        if( !empty($custom_meta_fields[1]) ){
            $meta_query = $custom_meta_fields[1];
            $params = $custom_meta_fields[0];
        }
        

        if(isset($params["currentpage"]) && $params["currentpage"]!="")
            $page=intval($params["currentpage"]);

        if(isset($params["itemsperpage"]) && $params["itemsperpage"]!="")
            $pageSize=intval($params["itemsperpage"]);

        //if(isset($params["proptype"]) && is_array($params["proptype"]) && sizeof($params["proptype"])>0)
        if(isset($params["proptype"]) &&  !empty($params["proptype"]))
        {
            if( is_array($params["proptype"]) ){
                $meta_query[] = array(
                    'relation' => 'AND',
                    array(
                        'key'     => 'ffd_propertytype',
                        'value'   => $params["proptype"],
                        'compare' => 'IN'
                    )
                );
            } else {
                $meta_query[] = array(
                    'relation' => 'AND',
                    array(
                        'key'     => 'ffd_propertytype',
                        'value'   => $params["proptype"],
                        'compare' => '='
                    )
                );

            }
        }

        if(isset($params["favorites"]) && $params["favorites"]!="" && is_user_logged_in())
        {
            $likes = get_user_meta(get_current_user_id(), 'favorite_properties', false);

            $meta_query[] = array(
                'relation' => 'AND',
                array(
                    'key'     => 'ffd_salesforce_id',
                    'value'   => $likes,
                    'compare' => 'IN'
                )
            );
        }

       /*  if(isset($params["community"]) && $params["community"] != "")
        {
            $meta_query[] = array(
                'relation' => 'AND',
                array(
                    'key'     => 'community_name',
                    'value'   => $params["community"],
                    'compare' => '='
                )
            );
        } */

        /* this is neighborhood actually */

        if( !isset($params["district"]) && isset($params["neighborhood"]) && $params["neighborhood"] != ""){
            $params["district"]=$params["neighborhood"];
        }

        if(isset($params["district"]) && $params["district"] != "")
        {
            if( is_array($params['district'])){
                $meta_query[] = array(
                    'relation' => 'AND',
                    array(
                        //'key'     => 'community_name',
                        'key'     => 'subdist',
                        //'key'     => 'district',
                        'value'   => $params["district"],
                        //'value'   => $params["district"],
                        'compare' => 'IN'
                    )
                );
            } else {
                $meta_query[] = array(
                    'relation' => 'AND',
                    array(
                        //'key'     => 'community_name',
                        'key'     => 'subdist',
                        //'key'     => 'district',
                        'value'   => $params["district"],
                        //'value'   => $params["district"],
                        'compare' => '='
                    )
                );
            }
        }

        if(isset($params["beds"]) && $params["beds"]!="")
        {
            $meta_query[] = array(
                'relation' => 'AND',
                array(
                    'key'     => 'ffd_bedrooms_pb',
                    'value'   => intval($params["beds"]),
                    'compare' => '>=',
                    'type'    => 'numeric'
                )
            );
        }

        if(isset($params["baths"]) && $params["baths"]!="")
        {
            $meta_query[] = array(
                'relation' => 'AND',
                array(
                    'key'     => 'ffd_fullbathrooms_pb',
                    'value'   => intval($params["baths"]),
                    'compare' => '>=',
                    'type'    => 'numeric'
                )
            );
        }

        if(isset($params["maxprice"]) && $params["maxprice"]!=""  && !self::is_default_value($params["maxprice"], 'maxPrice') )
        {   
           
                $meta_query[] = array(
                    'relation' => 'AND',
                    array(
                        'key'     => 'ffd_listingprice_pb',
                        'value'   => intval($params["maxprice"]),
                        'compare' => '<=',
                        'type'    => 'numeric'
                    )
                );
            
        }

        if(isset($params["minprice"]) && $params["minprice"]!="")
        {
            $meta_query[] = array(
                'relation' => 'AND',
                array(
                    'key'     => 'ffd_listingprice_pb',
                    'value'   => intval($params["minprice"]),
                    'compare' => '>=',
                    'type'    => 'numeric'
                )
            );
        }

        if( isset($params["maxbeds"]) && $params['maxbeds'] !="" && $params['maxbeds'] == 0 ){
            //studios
            $meta_query[] = array(
                'relation' => 'AND',
                array(
                    'key'     => 'ffd_bedrooms_pb',
                    'value'   => 0,
                    'compare' => '=',
                    'type'    => 'numeric'
                )
            );

       } else {
           
                if(isset($params["minbeds"]) && $params["minbeds"]!="")
                {
                    $meta_query[] = array(
                        'relation' => 'AND',
                        array(
                            'key'     => 'ffd_bedrooms_pb',
                            'value'   => intval($params["minbeds"]),
                            'compare' => '>=',
                            'type'    => 'numeric'
                        )
                    );
                }

                if(isset($params["maxbeds"]) && $params["maxbeds"]!="")
                {
                    $meta_query[] = array(
                        'relation' => 'AND',
                        array(
                            'key'     => 'ffd_bedrooms_pb',
                            'value'   => intval($params["maxbeds"]),
                            'compare' => '<=',
                            'type'    => 'numeric'
                        )
                    );
                }

        }

        if( isset($params["maxbaths"]) && $params['maxbaths'] !="" && $params['maxbaths'] == 0 ){
            //studios
            $meta_query[] = array(
                'relation' => 'AND',
                array(
                    'key'     => 'ffd_fullbathrooms_pb',
                    'value'   => 0,
                    'compare' => '=',
                    'type'    => 'numeric'
                )
            );

       } else {
           
                if(isset($params["minbaths"]) && $params["minbaths"]!="")
                {
                    $meta_query[] = array(
                        'relation' => 'AND',
                        array(
                            'key'     => 'ffd_fullbathrooms_pb',
                            'value'   => intval($params["minbaths"]),
                            'compare' => '>=',
                            'type'    => 'numeric'
                        )
                    );
                }

                if(isset($params["maxbaths"]) && $params["maxbaths"]!="")
                {
                    $meta_query[] = array(
                        'relation' => 'AND',
                        array(
                            'key'     => 'ffd_fullbathrooms_pb',
                            'value'   => intval($params["maxbaths"]),
                            'compare' => '<=',
                            'type'    => 'numeric'
                        )
                    );
                }

        }
      
        if(isset($params["minsq"]) && $params["minsq"]!="")
        {
            $meta_query[] = array(
                'relation' => 'AND',
                array(
                    'key'     => 'ffd_totalarea_pb',
                    'value'   => intval($params["minsq"]),
                    'compare' => '>=',
                    'type'    => 'numeric'
                )
            );
        }
        if(isset($params["maxsq"]) && $params["maxsq"]!="")
        {
            $meta_query[] = array(
                'relation' => 'AND',
                array(
                    'key'     => 'ffd_totalarea_pb',
                    'value'   => intval($params["maxsq"]),
                    'compare' => '<=',
                    'type'    => 'numeric'
                )
            );
        }
        if(isset($params["minacr"]) && $params["minacr"]!="")
        {
            $meta_query[] = array(
                'relation' => 'AND',
                array(
                    'key'     => 'ffd_lotsize_pb',
                    'value'   => intval($params["minacr"]),
                    'compare' => '>=',
                    'type'    => 'numeric'
                )
            );
        }
        if(isset($params["maxacr"]) && $params["maxacr"]!="")
        {
            $meta_query[] = array(
                'relation' => 'AND',
                array(
                    'key'     => 'ffd_lotsize_pb',
                    'value'   => intval($params["maxacr"]),
                    'compare' => '<=',
                    'type'    => 'numeric'
                )
            );
        }

        if(isset($params["keywords"]) && $params["keywords"]!="" && $params["keywords"]!="Enter City, Suburb or Web Ref")
        {   
            $keyword_meta = array();
            $keyword_meta['relation'] = 'or';
            $check_kewword = strtolower($params["keywords"]);
            if( strpos($check_kewword, 'range') === false && strpos($check_kewword, '|') === false && strpos($check_kewword, '^') === false ){
                $keyword_meta[] =  array(
                    'key'     => 'ffd_city_pb',
                    'value'   => str_replace(array('range', '|', '^'), '', strtolower($params["keywords"])),
                    'compare' => 'LIKE',
                );

                $keyword_meta[] =  array(
                    'key'     => 'mls_id',
                    'value'   => str_replace(array('range', '|', '^'), '', strtolower($params["keywords"])),
                    'compare' => 'LIKE',
                );
            }


            $keywords = explode('|', $params["keywords"]);
            foreach ($keywords as $key => $keyword) {
                
                $range_check = strtolower($keyword);
                if( strpos($range_check, 'range') !== false ){
                    
                    $keyword = str_replace(array('range', 'street', 'st'), '', $range_check);
                    $keyword = trim($keyword);
                

                    $keywords = preg_match("#[0-9]+-[0-9]+\s{1}#", $keyword, $matches);
                    $range = $matches[0];
                    $string = str_replace($range, '', $keyword);
                    $pieces = explode('-', $range);
                    $regex = self::regex_range($pieces[0], $pieces[1]);
                    $regex = '('.trim($regex) . ') ' . trim($string);

                    $keyword_meta[] = array(
                        'key'     => 'ffd_address_pb',
                        'value'   => '^'.$regex,
                        'compare' => 'REGEXP',
                    );

                    
                    
                } else {
                    $search_keyword = str_replace(array('St', 'ST', 'st', 'street', 'street'), '', trim($keyword));
                    if( strpos($search_keyword, '^') !== false ){
                            $regex = trim($search_keyword, '^');
                            $regex = trim($regex);
                            $keyword_meta[] = array(
                                'key'     => 'ffd_address_pb',
                                'value'   => '^(' . $regex . ')',
                                'compare' => 'REGEXP',
                            );
                    } else {
                        $keyword_meta[] = array(
                            'key'     => 'ffd_address_pb',
                            'value'   => $search_keyword,
                            'compare' => 'LIKE',
                        );
                    }
                   
                }
            }
            
            $meta_query[] = $keyword_meta;
        }


        if(isset($params["agent"]) && $params["agent"]!="")
        {
            $meta_query[] = array(
                'relation' => 'or',
                array(
                    'key'     => 'listing_agent_mls_id',
                    'value'   => $params["agent"],
                    'compare' => '=',
                ),
                array(
                    'key'     => 'selling_agent_mls_id',
                    'value'   => $params["agent"],
                    'compare' => '=',
                ),
            );
        }

        if(isset($params["parking"]) && $params["parking"]!="")
        {
            $meta_query[] = array(
                'relation' => 'AND',
                array(
                    'key'     => 'parkingspaces',
                    'value'   => $params["parking"],
                    'compare' => '>='
                )
            );
        }


        if(isset($params["hoa_dues_max"]) && $params["hoa_dues_max"]!="")
        {
            $meta_query[] = array(
                'relation' => 'AND',
                array(
                    'key'     => 'hoa_dues',
                    'value'   => $params["hoa_dues_max"],
                    'compare' => '<='
                )
            );
        }


        if(isset($params["orderby"]) && $params["orderby"]!="")
        {
            $parts=explode(":",$params["orderby"]);

           
            
            if($parts[0]==="listing_price"){
                
                $meta_query[] = array(
                                //'relation' => 'OR',
                                'listing_price' => array(
                                    'key'     => 'ffd_listingprice_pb',
                                    'type'    => 'numeric',
                                    'compare' => 'EXISTS'
                                )
                ); 
                
                $orderby["orderby"]=array( 
                                    'listing_price' => isset($parts[1]) ? $parts[1] : 'DESC'
                                );

            } else if($parts[0]==="listed_date") {

                $meta_query[] = array(
                                //'relation' => 'OR',
                                'listed_date' => array(
                                    'key'     => 'ffd_listed_date',
                                    'type'    => 'DATE',
                                    'compare' => 'EXISTS'
                                ),
                                
                ); 
                 
                 $orderby["orderby"]=array( 
                                    'listed_date' => isset($parts[1]) ? $parts[1] : 'DESC'
                                );
               
                 
             }  else if($parts[0]==="days_on_market"){

                $meta_query[]= array(
                              //'relation' => 'OR',
                              'days_on_market'=>array(
                                  'key'     => 'ffd_days_on_market',
                                  'compare' => '>=',
                                  'value' => '0',
                                  'type' => 'NUMERIC'
                              )
                              
                          
              );
               $orderby["orderby"] = array( 
                                  'days_on_market' => isset($parts[1]) ? $parts[1] : 'ASC'
                              );

             } else{

                $default_sort =  self::get_default_sort_meta_query();

               
               
                if( $default_sort !== null ){
                    

                    $meta_query[] = $default_sort['meta_query'];
                    $orderby = $default_sort['orderby'];

               

                }

            }

           
        } else {
            
            
             $default_sort =  self::get_default_sort_meta_query();
               
            if( $default_sort !== null ){
                

                $meta_query[] = $default_sort['meta_query'];
                $orderby = $default_sort['orderby'];


            }

        }

        if (isset($params["status"]) && $params["status"]!=""){
            
            $statuses = !is_array($params['status']) ? explode(',', $params["status"]) : $params['status'];
            $statuses = array_map('trim',$statuses);

            $meta_query[] = array(
                'relation' => 'AND',
                array(
                    'key'     => 'ffd_status',
                    'value'   => $statuses,
                    'compare' => 'IN'
                )
            );
        } else {
            $meta_query[] = array(
                'relation' => 'AND',
                array(
                    'key'     => 'ffd_status',
                    'value'   => array('Active', 'Contingent'),
                    'compare' => 'IN'
                )
            );
        }
        
        if(isset($params["frontage"]) && $params["frontage"]!=""){
            $localquery=array();
            $selfrontage = explode(",",$params["frontage"]);
            foreach($selfrontage as $propview)
            {
                $localquery[] = array(
                    'relation' => 'OR',
                    array(
                        'key'     => 'ffd_frontage',
                        'value'   => $propview,
                        'compare' => 'LIKE'
                    )
                );
            }
            //$meta_query[] = array('relation' => 'AND',$localquery);
            $meta_query[] = $localquery;

        }

        if(isset($params["view"]) && $params["view"]!=""){
            $localquery=array();
            $selfrontage = explode(",",$params["view"]);
           
            foreach($selfrontage as $propview)
            {
                $propview = str_replace('__', '/', $propview);
                $localquery[] = array(
                    'relation' => 'OR',
                    array(
                        'key'     => 'ffd_view',
                        'value'   => $propview,
                        'compare' => 'LIKE'
                    )
                );
            }
            //$meta_query[] = array('relation' => 'AND',$localquery);
            $meta_query[] = $localquery;

        }

        if(isset($params["pool"]) && $params["pool"]!=""){
            $meta_query[] = array(
                'relation' => 'AND',
                array(
                    'key'     => 'pool',
                    'value'   => 1,
                    'compare' => '='
                )
            );
        }
        if(isset($params["tenure"]) && $params["tenure"]!="" && $params["tenure"]!='All'){
            $meta_query[] = array(
                'relation' => 'AND',
                array(
                    'key'     => 'ffd_land_tenure',
                    'value'   => $params["tenure"],
                    'compare' => '='
                )
            );
        }
        if(isset($params["changed"]) && $params["changed"]!=""){
            $days = str_replace(" day or less","",str_replace(" days or less","",$params["changed"]));
            $afterDate = date('Y-m-d', strtotime("-".$days." day".(($days>1)?'s':'')));
            $afterDate.='T00:00:00.000+0000';
            $meta_query[] = array(
                'relation' => 'AND',
                array(
                    'key'     => 'ffd_price_changed',
                    'value'   => $afterDate,
                    'compare' => '>='
                )
            );
        }
        if(isset($params["daysm"]) && $params["daysm"]!=""){
            $days = str_replace(" day or less","",str_replace(" days or less","",$params["daysm"]));
           /*  $afterDate = date('Y-m-d', strtotime("-".$days." day".(($days>1)?'s':'')));
            $afterDate.='T00:00:00.000+0000'; */
            $afterDate = $days;
            $meta_query[] = array(
                'relation' => 'AND',
                array(
                    'key'     => 'ffd_days_on_market',
                    'value'   => $afterDate,
                    'compare' => '<=',
                    'type'    => 'numeric'
                )
            );
        }
        if (isset($params["featured"]) && $params["featured"]!="") {
            $meta_query[] = array(
                'relation' => 'AND',
                array(
                    'key'     => 'ffd_listing_office_id',
                    'value'   => 22343,
                    'compare' => '='
                )
            );
        }
        if (isset($params["projectname"]) && $params["projectname"]!="") {
            $meta_query[] = array(
                'relation' => 'AND',
                array(
                    'key'     => 'project',
                    'value'   => $params["projectname"],
                    'compare' => '='
                )
            );
        }
        if(isset($params["subdivision"]) && $params["subdivision"]!=""){
            
            $subdivision_meta = array(
                'key'     => 'subdivision',
                'value'   => $params["subdivision"],
                'compare' => '='
            );
            $_subdivision_meta = apply_filters( 'ffdl_subdivision_meta_query', $subdivision_meta);
            if( $_subdivision_meta )
                $subdivision_meta = $_subdivision_meta;

            $meta_query[] = array(
                'relation' => 'AND',
                $subdivision_meta
            );
        }
        if(isset($params["community"]) && $params["community"]!=""){
            $meta_query[] = array(
                'relation' => 'AND',
                array(
                    /*  'key'     => 'community_name', */
                    'key'     => 'subdist',
                    'value'   => str_replace("&amp;", "&", $params["community"]),
                    'compare' => '='
                )
            );
        }

        if(isset($params["streetname"]) && $params["streetname"]!=""){
            $meta_query[] = array(
                'relation' => 'AND',
                array(
                    'key'     => 'ffd_address_pb',
                    'value'   => $params["streetname"],
                    'compare' => 'LIKE'
                )
            );
        }
        if(isset($params["city"]) && $params["city"]!=""){
            $meta_query[] = array(
                'relation' => 'AND',
                array(
                    'key'     => 'ffd_city_pb',
                    'value'   => $params["city"],
                    'compare' => '='
                )
            );
        }
        if(isset($params["postalcode"]) && $params["postalcode"]!=""){

           
            if( strpos($params["postalcode"], '-') !== false ){

                $pieces = explode('-', $params["postalcode"]);
                $regex = self::regex_range($pieces[0], $pieces[1]);
                $regex = '('.trim($regex) . ') ' . trim($string);

                $meta_query[] = array(
                    'key'     => 'ffd_postalcode_pb',
                    'value'   => ''.$pieces[1].'-'.$pieces[0].'|'.$pieces[1].'|' . $pieces[0],
                    'compare' => 'REGEXP',
                );

            } else {

                $meta_query[] = array(
                    'relation' => 'AND',
                    array(
                        'key'     => 'ffd_postalcode_pb',
                        'value'   => $params["postalcode"],
                        'compare' => 'LIKE'
                    )
                );

            }
           
        }
        
        
        
        $args=array(
			'post_type' =>  'listing',
			'posts_per_page' => $pageSize,
			'post_status' => 'publish',
			'paged' => $page,
            'meta_query' => $meta_query,
            'cache_results'=> false,
        );

        //if no pagination needed turn of sql calc rows
        if( !$pagination || isset($params['no_found_rows']) ){
            $args['no_found_rows'] = true;
        }
       
       
        

        $_args = apply_filters('ffdl_query_listings_args', $args, $params);
        if( $_args && is_array($_args) )
            $args = $_args;

        $_orderby = apply_filters('ffdl_query_listings_orderby', $orderby);

        if( $_orderby && is_array($_orderby) )
            $orderby = $_orderby;
            
        $args=array_merge($args,$orderby);
       
        $query = new WP_Query($args);
        
        /*if( wp_doing_ajax() ){
             ffd_debug(count($query->posts), false);
            ffd_debug($args, false);
            ffd_debug($query->request, false);
            exit;
        }*/
        
        if( isset($_GET['debug']) ){
            ffd_debug($args, false);
            ffd_debug($query->request, false);
        }
        

        return $query;
    }

    public static function get_search_params( $data = false ) {
        if (!is_array($data)) { 
            unset($_GET['action']);
            $data = $_GET;
            array_walk($data, function(&$v, $k) { 
                if (is_array($v)) {
                    array_walk($v,function(&$vi, $ki){ $vi = urldecode($vi); });
                }
                else{$v = urldecode(str_replace("\'","'",$v));} 
            }); 
            

            // Don't allow the itemsperpage to be overriden through $_GET
            unset( $data['itemsperpage'] );
            if(isset($data['property-type']) && $data['property-type']!=''){ 
                $data['proptype'] = $data['property-type']; 
            }
            unset( $data['property-type'] );
        }

        $defaults = array(
            'currentpage'  => 1,
            'itemsperpage' => 12,
            'status'       => array('Active','Contingent'),
            'orderby'      => 'listed_date:DESC',
            'parking'      => '',
            'hoa_dues_max'      => '',
            'frontage'     => '',
            'community'    => '',
            'favorites'    => '',
            'district'     => '',
            'type'         => '',
            'bedrooms'     => '',
            'beds'         => '',
            'agent'        => '',
            //'bathrooms'    => '',
            'baths'        => '',
            'proptype'     => array(),
            'statcecity'   => '',
            'price'        => '',
            'minprice'     => '',
            'maxprice'     => '',
            'minbeds'     => '',
            'maxbeds'     => '',
            'minbaths'     => '',
            'maxbaths'     => '',
            'minsq'        => '',
            'maxsq'        => '',
            'minacr'       => '',
            'maxacr'       => '',
            'sq'           => '',
            'daysm'        => '',
            'changed'      => '',
            'keywords'     => '',
            'tenure'       => '',
            'pool'         => '',
            'featured'     => '',
            'projectname'  => '',
            'subdivision'  => '',
            'streetname'   => '',
            'city'         => '',
            'view'         => '',
            'postalcode'     => '',
        );

        $defaults = apply_filters('ffdl_search_params_defaults', $defaults);

        if(isset($data["maxprice-ipad"]) && $data["maxprice-ipad"]!="")
            $data["maxprice"]=$data["maxprice-ipad"];
        else if(isset($data["maxprice-mobile"]) && $data["maxprice-mobile"]!="")
            $data["maxprice"]=$data["maxprice-mobile"];

        if(isset($data["minprice-ipad"]) && $data["minprice-ipad"]!="")
            $data["minprice"]=$data["minprice-ipad"];
        else if(isset($data["minprice-mobile"]) && $data["minprice-mobile"]!="")
            $data["minprice"]=$data["minprice-mobile"];

        if(isset($data["maxbeds-ipad"]) && $data["maxbeds-ipad"]!="")
            $data["maxbeds"]=$data["maxbeds-ipad"];
        else if(isset($data["maxbeds-mobile"]) && $data["maxbeds-mobile"]!="")
            $data["maxbeds"]=$data["maxbeds-mobile"];

        if(isset($data["minbeds-ipad"]) && $data["minbeds-ipad"]!="")
            $data["minbeds"]=$data["minbeds-ipad"];
        else if(isset($data["minbeds-mobile"]) && $data["minbeds-mobile"]!="")
            $data["minbeds"]=$data["minbeds-mobile"];

        if(isset($data["beds-ipad"]) && $data["beds-ipad"]!="")
            $data["beds"]=$data["beds-ipad"];
        else if(isset($data["beds-mobile"]) && $data["beds-mobile"]!="")
            $data["beds"]=$data["beds-mobile"];

        if(isset($data["baths-ipad"]) && $data["baths-ipad"]!="")
            $data["baths"]=$data["baths-ipad"];
        else if(isset($data["baths-mobile"]) && $data["baths-mobile"]!="")
            $data["baths"]=$data["baths-mobile"];

        if(isset($data["keywords-mobile"]) && $data["keywords-mobile"]!="")
            $data["keywords"]=$data["keywords-mobile"];

        if(isset($data["parking-mobile"]) && $data["parking-mobile"]!="")
            $data["parking"]=$data["parking-mobile"];

        if(isset($data["minsq-mobile"]) && $data["minsq-mobile"]!="")
            $data["minsq"]=$data["minsq-mobile"];

        $args = wp_parse_args( $data, $defaults );
        
        $ignore_zero = apply_filters('ffdl_ignore_zero_params', array('minprice', 'minsq', 'minacr'));

        foreach($ignore_zero as $key => $key_name ){
            if( isset($args[$key_name]) && $args[$key_name] == 0 ) unset($args[$key_name]);
        }

        $args = apply_filters('ffdl_get_search_params', $args, $data);

        return $args;
        //return array_filter( $args, 'strlen' );
       
    }

    public static function get_property_types($key='ffd_propertytype'){
        global $wpdb;
        $property_types = array();
        if(empty($key) ){
            $key = 'ffd_propertytype';
        }

        $query = "SELECT DISTINCT meta_value, COUNT(meta_value) AS COUNT FROM $wpdb->postmeta WHERE meta_key = '".$key."' GROUP BY meta_value ORDER BY meta_value ASC;";
        $query = apply_filters('ffdl_get_property_types_query', $query);

        $results = $wpdb->get_results($query);
        $results = self::cache_wpdb_result('get_property_types', 'get_results', $query, '');
        //ffd_debug($results, false);
        if( !empty($results) ){
            foreach($results as $property_type){
                if(strpos($property_type->meta_value, ","))
                    continue;
                $property_types[] = $property_type->meta_value;
            }
        }
      
        return $property_types;
    }

    public static function get_property_type($post_id, $key='ffd_propertytype'){
        global $wpdb;
        $property_types = array();
        
        if(empty($key) ){
            $key = 'ffd_propertytype';
        }

        $query = "SELECT DISTINCT meta_value FROM $wpdb->postmeta WHERE meta_key = '".$key."' AND post_id = $post_id;";
        $results = $wpdb->get_results($query);
        if($results)
            return $results[0]->meta_value;
        return "";
    }

    public static function get_districts($key='subdist'){
        global $wpdb;

        $districts = array();

        if(empty($key) ){
            $key = 'subdist';
        }

        $query = "SELECT DISTINCT meta_value, COUNT(meta_value) AS COUNT FROM $wpdb->postmeta WHERE meta_key = '".$key."' GROUP BY meta_value ORDER BY meta_value ASC;";
        $query = apply_filters('ffdl_get_districts_query', $query);

        //$results = $wpdb->get_results($query);
        $results = self::cache_wpdb_result('get_districts', 'get_results', $query);
        if( !empty($results) ){
            foreach($results as $district){
                if(strpos($district->meta_value, ","))
                    continue;
                $districts[] = $district->meta_value;
            }
        }
        return $districts;
    }

    public static function get_districts_by_city($city='San Francisco', $city_key='ffd_city_pb', $subdist_key='subdist'){
        global $wpdb;

        if(empty($city_key) ){
            $city_key = 'ffd_city_pb';
        }

        if(empty($subdist_key) ){
            $subdist_key = 'ffd_city_pb';
        }
        

        $districts = array();
        $query = "SELECT DISTINCT m1.meta_value as district, m2.meta_value as city FROM $wpdb->postmeta as m1 
        LEFT JOIN $wpdb->postmeta as m2 ON m1.post_id = m2.post_id AND m2.meta_key='".$city_key."'  AND m2.meta_value='".$city."' 
        where m1.meta_key='".$subdist_key."' AND m2.meta_value IS NOT NULL GROUP BY m1.meta_value ORDER BY m1.meta_value ASC";

        $query = apply_filters('ffdl_get_districts_by_city_query', $query);

        //$results = $wpdb->get_results($query);
        $results = self::cache_wpdb_result('get_districts_by_city', 'get_results', $query);
        if( !empty($results) ){
            foreach($results as $district){
                if(strpos($district->district, ","))
                    continue;
                $districts[] = $district->district;
            }
        }
        return $districts;
    }

    public static function get_property_views($key='view')
    {
        global $wpdb;

        $property_views = array();

        if(empty($key) ){
            $key = 'view';
        }

        $query = "SELECT DISTINCT meta_value FROM $wpdb->postmeta WHERE meta_key = '".$key."' AND meta_value != '' GROUP BY meta_value ORDER BY meta_value ASC;";
        $query = apply_filters('ffdl_get_property_views_query', $query);

        //$results = $wpdb->get_results($query);
        $results = self::cache_wpdb_result('get_districts_by_city', 'get_results', $query);
        if( !empty($results) ){
            foreach ($results as $property_view) {
                
                $peices = explode(',', $property_view->meta_value);

                $property_views = array_merge($property_views, $peices);
                //$property_views = $property_views + $peices;
            }
        }
        if( !empty($property_views) )
            $property_views = array_unique($property_views);
        return $property_views;
    }

    ////
    public static function get_prop_type( $field ) {
		$types =  array_map('strtolower', explode(',', $field) );
		if ( in_array( 'residential', $types ) ) {
			$return  = 'Residential';
		} else if ( in_array( 'condo', $types ) ) {
			$return =  'Condo';
		} else if ( in_array( 'land', $types ) ) {
			$return = 'Land';
		} else {
            $return = 'Land';
        }

        $return = apply_filters('ffdl_get_prop_type', $return, $field);

        return $return;
        
	}

	public static function format_date( $date, $format='Y-m-d') {
        if(empty($format) ){
            $format = 'Y-m-d';
        }

		$date_extracted = DateTime::createFromFormat($format, substr( $date, 0, 10 ) );
		if ( false == $date_extracted ) {
			$date =  $date;
		} else {
			$date = $date_extracted->format('m-d-Y');;
		}

        $date = apply_filters('ffdl_get_prop_type', $date, $format);

        return $date;
	}


	public static function format_price( $price, $key_info = array() ) {
		$decimals = isset( $key_info['decimals'] ) ? $key_info['decimals'] : 0;

        $price = number_format( floatval($price), $decimals, '.', ',' );
        
        $price = apply_filters('ffdl_get_prop_type', $price, $price);

		return $price;
	}

	public static function separate_thousands( $value, $key_info = array() ) {
		$decimals = isset( $key_info['decimals'] ) ? $key_info['decimals'] : 0;
		$separator = isset( $key_info['separator'] ) ? $key_info['separator'] : ',';

        $thousands = number_format( floatval($value), $decimals, '.', $separator );
        $price = apply_filters('ffdl_get_prop_type', $thousands, $value);

		return $thousands;
	}

	public static function shorten_string( $value, $key_info = array() ) {
		$length = isset( $key_info['length'] ) ? $key_info['length'] : 27;
		$suffix = isset( $key_info['suffix'] ) ? $key_info['suffix'] : '...';

		return substr($value, 0, $length) . $suffix;
	}

    public static function get_tax_formatted( $value, $key_info ) {
		if ( 0 == preg_match('/(\d)(\d)(\d)(\d\d\d)(\d\d\d)(\d+)?/', $value, $tax_elements ) )
			return $value;
		array_shift($tax_elements);
		array_walk($tax_elements, function(&$element, $key) {
			$element = intval($element);
		});
		return implode( '-', $tax_elements );
	}

	public static function separate_suffixed_thousands( $value, $key_info = array() ) {
		preg_match('/(.*\d)(\D*)$/', $value, $n);
		if ( isset( $n[1] ) ) {
			return self::separate_thousands( $n[1], $key_info ) . $n[2];
		} else {
			return self::separate_thousands( $value, $key_info );
		}
	}

	public static function get_tenure_short( $value, $key_info = array() ) {
		$keywords = array(
			'FS' => 'simple',
			'LH' => 'hold',
		);
		foreach ( $keywords as $key => $word) {
			if (strpos( strtolower( $value ), $word ) !== false )
				return $key;
		}
		return '';
	}

	public static function add_wildcards( $val ) {
		//return "%25$val%25";
		return "%$val%";
	}

    public static function get_communities($id = 0){
        $args=array(
			'post_type' =>  'community',
            'post_status' => 'publish',
            'posts_per_page' => 100,
            'orderby' => 'menu_order',
            'order' => 'ASC'
        );

        $args = apply_filters('ffdl_get_communities_args', $args);

        if($id > 0)
            $args['p'] = $id;
        $query = new WP_Query($args);
        if($query){
            foreach($query->posts as $post){
                $fields = get_post_meta($post->ID);
                $post->ffd_salesforce_id = isset($fields['ffd_salesforce_id'][0]) ? $fields['ffd_salesforce_id'][0] : false;
                $post->ListingImage = isset($fields['ffd_media'][0]) ? $fields['ffd_media'][0] : false;
                $kml_file = ffd_get_field("kml_file", $post->ID);
                $post->kml = apply_filters('ffdl_kml_file_url', $kml_file['url']);
                $post->excerpt = ffd_get_field("ffd_excerpt",$post->ID);
            }
        }
        if($id > 0 && $query->found_posts == 1){
            return $query->posts[0];
        }
        return $query;
    }


    public static function regex_range($from, $to) {

        if($from < 0 || $to < 0) {
          return '';
        }
      
        if($from > $to) {
          return '';
        }
      
        $ranges = array($from);
        $increment = 1;
        $next = $from;
        $higher = true;
      
        while(true) {
      
          $next += $increment;
      
          if($next + $increment > $to) {
            if($next <= $to) {
              $ranges[] = $next;
            }
            $increment /= 10;
            $higher = false;
          }
          else if($next % ($increment*10) === 0) {
            $ranges[] = $next;
            $increment = $higher ? $increment*10 : $increment/10;
          }
      
          if(!$higher && $increment < 10) {
            break;
          }
        }
      
        $ranges[] = $to + 1;
      
        $regex = '';
      
        for($i = 0; $i < sizeof($ranges) - 1; $i++) {
          $str_from = (string)($ranges[$i]);
          $str_to = (string)($ranges[$i + 1] - 1);
      
          for($j = 0; $j < strlen($str_from); $j++) {
            if($str_from[$j] == $str_to[$j]) {
              $regex .= $str_from[$j];
            }
            else {
              $regex .= "[" . $str_from[$j] . "-" . $str_to[$j] . "]";
            }
          }
          $regex .= "|";
        }
      
        return substr($regex, 0, strlen($regex)-1) . '';
      }


      private static function set_transient($name, $value, $time=60*60*6){
        set_transient($name, $value, $time);
      }

      /* 
      * @transient @string key where to store result
      * @wpdb_func @string wpdb method 
      * @wpdb_func_args array|string args to pass to wpdb method
      * @duration @number (seconds) timestamp  cache duration
      * @refresh @bool force update cache result
       */
      //cache_wpdb_result( 'transient_name', 'get_results', array($status_value_query, ARRAY_N));
      private static function cache_wpdb_result($transient, $wpdb_func, $wpdb_func_args, $duration='', $refresh=false){
        global $wpdb;

        if( self::$cache_wpdb_result ){

            if( empty($duration) ){
                $duration = HOUR_IN_SECONDS * 4;
            }

            $transient = 'ffdl_cache_' . $transient;
            $result= get_transient($transient);
            if( $refresh !== true && $result !== false && !empty($result) )
                return $result;
            
            if( is_array($wpdb_func_args) )
                $result = call_user_func_array(array($wpdb, $wpdb_func), $wpdb_func_args);
            else
                $result = call_user_func(array($wpdb, $wpdb_func), $wpdb_func_args);

            set_transient( $transient, $result, $duration);

        } else {

            if( is_array($wpdb_func_args) )
                $result = call_user_func_array(array($wpdb, $wpdb_func), $wpdb_func_args);
            else
                $result = call_user_func(array($wpdb, $wpdb_func), $wpdb_func_args);
        }
    
        return $result;
    }


    public static function get_default_sort_meta_query(){


          $default_sort =  apply_filters('ffdl_search_default_sort', array( 'name' => 'days_on_market', 'key' => 'ffd_days_on_market', 'value' => '0', 'type' => 'numeric', 'compare' => '>=', 'order' =>'ASC'));
               
            if( isset($default_sort['key']) && !empty($default_sort['key']) ){
                
                $default_sort_meta = array();
                //$default_sort_meta['relation'] = 'OR';
                $default_sort_meta['default_sort_meta']['key'] =  $default_sort['key'];
                
                if( isset($default_sort['compare']) && !empty($default_sort['compare']) )
                    $default_sort_meta['default_sort_meta']['compare']   =  $default_sort['compare'];

                if( isset($default_sort['value']) && $default_sort['value'] !== ''  && strpos($default_sort['compare'], 'EXISTS') === false )
                    $default_sort_meta['default_sort_meta']['value']     =  $default_sort['value'];

                if( isset($default_sort['type']) && !empty($default_sort['type']) )
                    $default_sort_meta['default_sort_meta']['type']      =  $default_sort['type'];

                $orderby = $exist_meta_key = array();

               
               //$default_sort_meta['default_sort_meta_notexists'] =  array('key' => 'ffd_listingprice_pb', 'type' => 'numeric', 'compare' => 'EXISTS', 'order' =>'DESC');

                $orderby['orderby'] = array( 
                        'default_sort_meta' => $default_sort['order'],
                        'default_sort_meta_notexists' => $default_sort['order'],
                    );

                return array('meta_query' => $default_sort_meta, 'orderby' => $orderby, 'params' => $default_sort, 'name' => $default_sort['name'], 'order' => $default_sort['order']);
            } else {

                return null;
            }

            return null;
    }


    public static function get_filters_default_values(){

        $default_values = apply_filters(
        'listing_search_default_values', 
        array(
            'maxPrice' => self::get_max("ffd_listingprice_pb"),
            'maxParking' => '200',
            'maxHOADues' => '15000',
            'maxBeds' => '15',
            'maxBaths' => '15',
            )
        );

        return $default_values;
    }


    public static function is_default_value($value, $key){

        $default_values = self::get_filters_default_values();

         if( isset($default_values[$key]) && !empty($default_values[$key]) && $default_values[$key] == $value ){

                return true;
        } else {

            return false;
        }
    }

    
}