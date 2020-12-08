<?php

/**
 *
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
	 * The single instance of the class
	 *
	 * @var FFD_Integration
	 * @since 2.1
	*/
    protected static $_instance = null;
    protected $_form_instance = null;
    protected $version = null;
    protected $styles = array();
    protected $scripts = array();
    protected $tablename = '';
    protected $listing_fields = array(); // fields names from ffd integration mapping setttings
    protected $field_types = array(); // listing field types based on ffd integration mapping setttings

    protected $paginator = '';
    protected $number_properties = 0;
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
        $this->tablename = $wpdb->prefix . 'ffd_listings';
        if ( is_null( $this->_form_instance ) ) {
			$this->_form_instance = 0;
        }

        $this->version = '1.0.0';

        $this->init_hooks();
        $this->set_listing_fields();

    }


     /**
	 * Hook into actions and filters.
	 *
	 * @since 1.0
	 */
    public function init_hooks(){



        register_activation_hook( FFD_PLUGIN_FILE, array($this, 'activate_cronjob_ffd_searchdata') );
        register_deactivation_hook( FFD_PLUGIN_FILE, array($this, 'deactivate_cronjob_ffd_searchdata') );

        add_action('wp_head', array($this, 'print_js_in_head'), 5);
        add_action('wp_enqueue_scripts', array($this, 'register_scripts'), 20);

        add_action('ffd_sync_listing_deleted', array($this,  'delete_listing_in_custom_table'), 10, 1);
        add_action('ffd_sync_listing_created', array($this, 'update_listing_in_custom_table'),  10, 1);

        //cron event for load/refresh listing data to custom table
        add_action( 'cronjob_ffd_searchdata', array($this, 'generate_ffd_map_search_listing_db_data'));

        //Shortcodes
        add_shortcode( 'ffd-search-form', array($this, 'shortcode_ffd_search_form') );
        add_shortcode( 'ffd-script-tag', array($this, 'shortcode_ffd_script_tag') );
        add_shortcode( 'ffd-style-tag', array($this, 'shortcode_ffd_style_tag') );

        add_shortcode( 'ffd-savesearch-btn', array($this, 'shortcode_ffd_savesearch_button') );

        //franklin.espinoza
        add_shortcode( 'ffd-pager', array($this, 'shortcode_ffd_pager') );




        //Ajax search listing data
        /*add_action('wp_ajax_nopriv_ffd/integration/autocomplete', array($this, 'ajax_ffd_integration_autocomplete') );
        add_action('wp_ajax_ffd/integration/autocomplete', array($this, 'ajax_ffd_integration_autocomplete') );*/

        //franklin.espinoza
        add_action('wp_ajax_nopriv_ffd/integration/autocomplete', array($this, 'ajax_ffd_integration_autocomplete_trestle') );
        add_action('wp_ajax_ffd/integration/autocomplete', array($this, 'ajax_ffd_integration_autocomplete_trestle') );
        //franklin.espinoza

        /*add_action('wp_ajax_nopriv_ffd/integration/search', array($this, 'ajax_ffd_integration_search') );
        add_action('wp_ajax_ffd/integration/search', array($this, 'ajax_ffd_integration_search') );*/

        //franklin.espinoza
        add_action('wp_ajax_nopriv_ffd/integration/search', array($this, 'ajax_ffd_integration_search_trestle') );
        add_action('wp_ajax_ffd/integration/search', array($this, 'ajax_ffd_integration_search_trestle') );
        //franklin.espinoza

        add_action('wp_ajax_nopriv_ffd/integration/verify', array($this, 'ajax_ffd_integration_verify_backend') );
        add_action('wp_ajax_ffd/integration/verify', array($this, 'ajax_ffd_integration_verify_backend') );

        add_action('wp_ajax_ffd/integration/savesearch', array($this, 'ajax_ffd_integration_savesearch') );

        add_action('wp_ajax_ffd/integration/createdata', array($this, 'ajax_ffd_integration_createdata') );




    }

    function bootstrap_pagination( WP_Query $wp_query = null, $echo = true, $params = [] ) {
        if ( null === $wp_query ) {
            global $wp_query;
        }

        $add_args = [];

        $pages = paginate_links( array_merge( [
                'base'         => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
                'format'       => '?paged=%#%',
                'current'      => max( 1, get_query_var( 'paged' ) ),
                'total'        => $wp_query->max_num_pages,
                'type'         => 'array',
                'show_all'     => false,
                'end_size'     => 3,
                'mid_size'     => 1,
                'prev_next'    => true,
                'prev_text'    => __( '« Prev' ),
                'next_text'    => __( 'Next »' ),
                'add_args'     => $add_args,
                'add_fragment' => ''
            ], $params )
        );

        if ( is_array( $pages ) ) {
            $pagination = '<div class="pagination"><ul class="pagination">';

            foreach ( $pages as $page ) {
                $pagination .= '<li class="page-item '.(strpos($page, 'current') !== false ? 'active' : '').'"> ' . str_replace( 'page-numbers', 'page-link', $page ) . '</li>';
            }

            $pagination .= '</ul></div>';

            if ( $echo ) {
                echo $pagination;
            } else {
                return $pagination;
            }
        }
        //var_dump('$pagination', $pages);exit;
        return null;
    }

    function shortcode_ffd_pager(){ //var_dump('$_REQUEST', $_REQUEST);

        //echo $this->bootstrap_pagination();

        global $wpdb;

        $sql_total_reg = "SELECT *
                          FROM wp_posts
                          WHERE post_type = 'listing' AND post_status = 'publish';
                         ";
        $result_total_reg = $wpdb->get_results($sql_total_reg, OBJECT);
        $num_total_registros = count($result_total_reg);

        $size_page = 10;

        if ( isset($_GET['paged']) && $_GET['paged'] != '' ) {
            $offset = ($_GET['paged'] - 1) * $size_page;
        }else{
            $offset = 0;
        }

        $sql = "SELECT *
                FROM wp_posts
                WHERE post_type = 'listing' AND post_status = 'publish'
                LIMIT  ".$size_page."
                OFFSET ".$offset.";
               ";
        $result = $wpdb->get_results($sql, OBJECT);

        $page = $_GET['page'];
        if (!$page){
            $init = 0;
            $page = 1;
        }else{
            $init = ($page - 1) * $size_page;
        }

        $total_pages = round($num_total_registros/$size_page) + 1;

        if ( isset($_GET['paged']) && $_GET['paged'] != '' ) {
            $current = $_GET['paged'];
        }else{
            $current = 0;
        }

        $args = array(
                'base'         => '%_%',
                'format'       => '?paged=%#%',
                'current'      => $current,
                'total'        => $total_pages,
                'type'         => 'plain',
                'show_all'     => false,
                'end_size'     => 3,
                'mid_size'     => 1,
                'prev_next'    => true,
                'prev_text'    => __( '« Prev' ),
                'next_text'    => __( 'Next »' ),
                'add_args'     => false,
                'add_fragment' => '',
                'before_page_number' => '',
                'after_page_number' => ''
             );
        echo paginate_links($args);

    }

    public function ajax_ffd_integration_verify_backend(){

        $params = $_REQUEST;

        if(is_user_logged_in()){

            update_option('retries_counter', 1);

            global $wpdb;

            //User
            $current_user = wp_get_current_user();
            $user_id =  $current_user->data->ID;

            $user = $wpdb->get_results("
                select us.*
                from wp_usermeta us
                where us.user_id = $user_id
                ", ARRAY_A);

            $lead = array();

            foreach ($user as $row){

                switch ($row['meta_key']){

                    case 'first_name':
                        $lead['first_name'] = $row['meta_value'];
                        break;
                    case 'last_name':
                        $lead['last_name'] = $row['meta_value'];
                        break;
                    case 'mobile_number':
                        $lead['phone'] = $row['meta_value'];
                        break;
                    case 'codeCountry':
                        $lead['code_country'] = $row['meta_value'];
                        break;
                }
            }

            $lead['email'] =  $current_user->data->user_email;
            $lead['id_lead_live_modern'] =  $user_id;
            $lead['visits_lm'] =  get_user_meta($user_id, 'user_visits', true);
            //var_dump($lead);exit;
            //User
            /*==================================================*/
            //Property
            $listKey = $params['id'];
            $listKey = $wpdb->get_results("
                select distinct pm.post_id
                from wp_postmeta pm
                where pm.meta_key = 'ffd_id' and pm.meta_value = $listKey
                ", ARRAY_A);
            $listKey = $listKey[0]['post_id'];

            $property_meta = $wpdb->get_results("
                SELECT *
                FROM wp_postmeta pm
                WHERE pm.post_id = $listKey
                ", ARRAY_A);

            $property = array();
            foreach ($property_meta as $prop) {

                switch ($prop['meta_key']){

                    case 'ffd_address_pb':
                        $property['ffd_address_pb'] = $prop['meta_value'];
                        break;
                    case 'ffd_bedrooms_pb':
                        $property['ffd_bedrooms_pb'] = $prop['meta_value'];
                        break;
                    case 'ffd_fullbathrooms_pb':
                        $property['ffd_fullbathrooms_pb'] = $prop['meta_value'];
                        break;
                    case 'ffd_living_sq_ft':
                        $property['ffd_living_sq_ft'] = $prop['meta_value'];
                        break;
                    case 'ffd_longitude_pb':
                        $property['ffd_longitude_pb'] = $prop['meta_value'];
                        break;
                    case 'ffd_city_pb':
                        $property['ffd_city_pb'] = $prop['meta_value'];
                        break;
                    case 'ffd_listingprice_pb':
                        $property['ffd_listingprice_pb'] = $prop['meta_value'];
                        break;
                    case 'ffd_mls_id':
                        $property['ffd_mls_id'] = $prop['meta_value'];
                        break;
                    case 'ffd_featured_image':
                        $property['ffd_featured_image'] = $prop['meta_value'];
                        break;
                    case 'ffd_name':
                        $property['ffd_name'] = $prop['meta_value'];
                        break;
                    case 'ffd_subdivision':
                        $property['ffd_subdivision'] = $prop['meta_value'];
                        break;
                    case 'ffd_yearbuilt_pb':
                        $property['ffd_yearbuilt_pb'] = $prop['meta_value'];
                        break;
                    case 'ffd_taxes':
                        $property['ffd_taxes'] = $prop['meta_value'];
                        break;
                    case 'ffd_propertysubtype':
                        $property['ffd_propertysubtype'] = $prop['meta_value'];
                        break;
                    case 'ffd_guid':
                        $property['ffd_guid'] = $prop['meta_value'];
                        break;
                    case 'ffd_status':
                        $property['ffd_status'] = $prop['meta_value'];
                        break;
                    case 'ffd_postalcode_pb':
                        $property['ffd_postalcode_pb'] = $prop['meta_value'];
                        break;
                    case 'ffd_id':
                        $property['ffd_id'] = $prop['meta_value'];
                        break;
                    case 'ffd_source':
                        $property['ffd_source'] = $prop['meta_value'];
                        break;
                    case 'ffd_state_pb':
                        $property['ffd_state_pb'] = $prop['meta_value'];
                        break;
                    case 'ffd_listingtype':
                        $property['ffd_listingtype'] = $prop['meta_value'];
                        break;
                    case 'ffd_description_pb':
                        $property['ffd_description_pb'] = str_replace( "'","",$prop['meta_value']);
                        break;
                    case 'ffd_lotsize_pb':
                        $property['ffd_lotsize_pb'] = $prop['meta_value'];
                        break;

                }

            }



            /*$user_fup = $wpdb->get_results("
                SELECT uf.id_lead_fub
                FROM wp_user_lead_fub uf
                WHERE uf.id_lead_live_modern = $user_id
                ", ARRAY_A);*/
            //var_dump('enviar follupbase:',$property);exit;
            //$id_fub = 0;
            $user_fup = null;
            if( $user_fup == null ) {
                /*Breydi vasquez 14/07/2020*/
                $json_data = array(
                    "source" => 'Live Modern',
                    "system" => 'Live Modern',
                    "type" => $property['ffd_propertysubtype'],
                    "message" => '',//$property['ffd_description_pb']
                    "description" => '',//$property['ffd_description_pb']
                    "person" => array(
                        "firstName" => $lead['first_name'],
                        "lastName" => $lead['last_name'],
                        "emails" => array(array("value" => $lead['email'], "type" => "home")),
                        "phones" => array(array("value" => "(" . $lead['code_country'] . ") " . $lead['phone'], "type" => "home")),
                        "tags" => array("Lease option"),
                        "sourceUrl" => "",
                        "customBirthday" => ""
                    ),
                    "property" => array(
                        "street" => $property['ffd_address_pb'],
                        "city" => $property['ffd_city_pb'],
                        "state" => $property['ffd_state_pb'],
                        "code" => $property['ffd_postalcode_pb'],//"90068"
                        "mlsNumber" => $property['ffd_mls_id'],//"14729339"//ffd_mls_id //ffd_id

                        "forRent" => ($property['ffd_listingtype'] == 'Rent') ? '1' : '0',
                        "url" => $property['ffd_guid'],// ffd_guid //ffd_featured_image
                        "price" => $property['ffd_listingprice_pb'],
                        "bedrooms" => $property['ffd_bedrooms_pb'],
                        "bathrooms" => $property['ffd_fullbathrooms_pb'],
                        "type" => $property['ffd_propertysubtype'],
                        "lot" => $property['ffd_lotsize_pb'],
                        "area" => $property['ffd_living_sq_ft']
                    ),
                    "propertySearch" => array(
                        "type" => $property['ffd_propertysubtype'],
                        "neighborhood" => $property['ffd_city_pb'],
                        "city" => $property['ffd_city_pb'],
                        "state" => $property['ffd_state_pb'], //CA
                        "code" => $property['ffd_postalcode_pb'],//90068
                        "minPrice" => $property['ffd_listingprice_pb'],
                        "maxPrice" => $property['ffd_listingprice_pb']
                    ),
                    "campaign" => array(
                        "source" => "",
                        "medium" => "",
                        "term" => "",
                        "content" => "",
                        "campaign" => ""
                    )
                );

                $uri = "https://api.followupboss.com/v1/events";
                $data = json_encode($json_data);
                $method = "POST";
                $content_type = "application/json";

                //$id_fub = $this->callServiceFollowUpBoss($uri, $data, $method, $content_type, $user_id);
                $this->callServiceFollowUpBoss($uri, $data, $method, $content_type, $user_id);
            }

            /*******breydi vasquez fin*/

            $user_fup = $wpdb->get_results("
            SELECT uf.id_lead_fub, uf.id_agent
            FROM wp_user_lead_fub uf
            WHERE uf.id_lead_live_modern = $user_id
            ", ARRAY_A);

            $lead['id_lead_fub'] = $user_fup[0]["id_lead_fub"];
            $lead['id_agent'] = $user_fup[0]["id_agent"];

            //$lead['id_lead_fub'] = $id_fub;
            //var_export($property);
            //var_export($lead);exit;
            $data = json_encode(
                array(
                    'property' => $property,
                    'lead' => $lead
                )
            );
            //var_export(json_encode($data));exit;
            //service insert property
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "http://3.82.232.114/kerp/pxp/lib/rest/agent_portal/GreatSheet/insertDetailProperty",
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

            $response = curl_exec($curl);//var_export(curl_getinfo($curl));exit;
            $err = curl_error($curl);

            curl_close($curl);

            //var_dump('$response',$response);exit;
            /*if ($err) {
                echo "cURL Error #:" . $err;
            } else {
                echo $response;
            }*/

            //service insert property

            /*echo json_encode(array('status' => 'login'));
            die();*/
        }else{
            $retries = get_option('retries_counter');
            if ( $retries < 2){
                update_option('retries_counter', $retries+1, true);
            }else {
                update_option('retries_counter', 1);
                echo json_encode(array('status' => 'logout', 'href' => get_option('siteurl').'/login/'));//http://54.227.57.214/livemodern/register/
                die();
            }
            //header("Location: http://54.227.57.214/livemodern/register/");
        }

    }
/*Breydi vasquez 14/07/2020*/
    private function callServiceFollowUpBoss ($uri, $data, $method, $content_type, $user_id) {

        global $wpdb;

      $headers = array(
                  "Authorization: Basic NmYyZGY5MjFiNWRkMGM3NDQ0ZjMzNTlkZWMwYjRhNGQwMTFiNmM6",
                  "X-System: Modern-Living-Group",
                  "X-System-Key: 6f2df921b5dd0c7444f3359dec0b4a4d011b6c",
                  "Content-Type: $content_type"
                      );

      $curl = curl_init();

      curl_setopt_array($curl, array(
        CURLOPT_URL => $uri,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_HTTPHEADER => $headers
      ));

      $response = curl_exec($curl);
      $response = json_decode($response);
      curl_close($curl);

        $wpdb->insert('wp_user_lead_fub', array('id_lead_live_modern'=>$user_id,'id_lead_fub'=>$response->id,'id_agent'=>($response->collaborators)[0]->id,'name_agent'=>($response->collaborators)[0]->name,'role_agent'=>($response->collaborators)[0]->role));
      //echo $response;

        //return $response->id;
    }
    /*Breydi vasquez fin*/

    public function print_js_in_head(){

        $ffdsearchgmapinit = " function ffdsearchgmapinit(){
            jQuery(function($){ jQuery(document).trigger('ffd-gmap-api-ready'); });
        }";

        ffd_echo_js($ffdsearchgmapinit, false);


    }

    public function register_scripts(){

        $version = $this->version;
        $this->styles = array(
            'jquery-ui-style' => array('src'=>FFD()->plugin_url() . '/assets/css/jquery-ui.min.css', 'deps'=>array(), 'ver'=>$version, 'in_footer'=>false),
            'ffd-integration-search' => array('src'=>FFD()->plugin_url() . '/assets/css/ffd-integration-search.css', 'deps'=>array(), 'ver'=>$version, 'in_footer'=>false),
        );

        $this->scripts = array(
            'underscore' => '',
            'jquery' => '',
            'jquery-ui-autocomplete' => '',
            'cookiejs' => array('src'=>FFD()->plugin_url() . '/assets/js/js.cookie.js', 'deps'=>array('jquery'), 'ver'=>$version, 'in_footer'=>false),
            'historyjs' => array('src'=>FFD()->plugin_url() . '/assets/js/jquery.history.js', 'deps'=>array('jquery'), 'ver'=>$version, 'in_footer'=>false),
            'promise-polyfill' => array('src'=>FFD()->plugin_url() . '/assets/js/promise-polyfill.min.js', 'deps'=>array('jquery'), 'ver'=>$version, 'in_footer'=>false),
            'liquidjs' => array('src'=>FFD()->plugin_url() . '/assets/js/liquid.min.js', 'deps'=>array('jquery'), 'ver'=>$version, 'in_footer'=>false),
            'GoogleMap' => array('src'=>'https://maps.googleapis.com/maps/api/js?key=' . $this->get_settings('ffd_gmap_api_key') . '&callback=ffdsearchgmapinit&libraries=drawing', 'deps'=>array(), 'ver'=>$version, 'in_footer'=>false),
            'MarkerClusterer' => array('src'=>FFD()->plugin_url() . '/assets/js/markerclustererplus.js', 'deps'=>array('jquery'), 'ver'=>$version, 'in_footer'=>false),
            'MarkerWithLabel' => array('src'=>FFD()->plugin_url() . '/assets/js/markerwithlabel.js', 'deps'=>array('jquery'), 'ver'=>$version, 'in_footer'=>false),
            'jquery-serialize-object' => array('src' => FFD()->plugin_url() . '/assets/js/jquery.serialize-object.min.js', 'deps'=>array('jquery'), 'ver'=>$version, 'in_footer'=>false),
            'ffd-integration-search' => array('src'=>FFD()->plugin_url() . '/assets/js/ffd-integration-search.js', 'deps'=>array('jquery', 'underscore'), 'ver'=>$version, 'in_footer'=>false),
            'ffd-integration-map' => array('src'=>FFD()->plugin_url() . '/assets/js/ffd-integration-map.js', 'deps'=>array('jquery', 'underscore'), 'ver'=>$version, 'in_footer'=>false),
        );




        foreach($this->styles as $handle => $style){
            if( !empty($style) ){
                wp_register_style($handle, $style['src'], $style['deps'], $style['ver'], $style['in_footer']);
            }
        }

        foreach($this->scripts as $handle => $script){
            if( !empty($script) ){
                wp_register_script($handle, $script['src'], $script['deps'], $script['ver'], $script['in_footer']);
            }
        }

        $this->enqueue_scripts();

    }

    private function enqueue_scripts(){

        foreach($this->styles as $handle => $style){
            wp_enqueue_style($handle);
        }

        wp_enqueue_script('jquery');
        wp_enqueue_script('underscore');

        foreach($this->scripts as $handle => $script){
            wp_enqueue_script($handle);
        }
        //var_dump('$FFD_Search_Settings',$_SERVER['HTTP_HOST'],$_SERVER['REQUEST_URI']);exit;
        $FFD_Search_Settings =  array(
                                'ajaxurl' => admin_url('admin-ajax.php'),
                                'homeurl' => untrailingslashit(home_url()),
                                'templateurl' => get_template_directory_uri(),
                                'stylesheeturl' => get_stylesheet_directory_uri(),
                                'page_url' => get_permalink(),
                                'current_url' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}",
                                'is_user_logged_in' => is_user_logged_in(),
                                'cluster_img_path' => FFD()->plugin_url() . "/assets/images/gmap/m",
                                'where' => '',
                                'select' => '',
                                'page' => '1',
                                'offset' => '0',
                                'limit' => '12',
                                'image_placeholder' => FFD()->plugin_url() . "/assets/images/placeholder.jpg",
                                'keynames' => array_keys($this->listing_fields),
                            );
        wp_localize_script('ffd-integration-search', 'FFD_Search_Settings', $FFD_Search_Settings);

        //f.e.a
        //$this->trestle_ffd_integration_search();
        //f.e.a
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

    public function activate_cronjob_ffd_searchdata() {

        if (! wp_next_scheduled ( 'cronjob_ffd_searchdata')) {
            wp_schedule_event( time(), 'hourly', 'cronjob_ffd_searchdata');
        }
    }


    public function deactivate_cronjob_ffd_searchdata() {
        delete_transient('transient_ffd_searchdata');
        wp_clear_scheduled_hook( 'cronjob_ffd_searchdata' );
    }

    public function delete_listing_in_custom_table($postid){
        global $wpdb;

        $delete = "DELETE FROM `".$this->tablename."` WHERE type='listing' AND `postID` = '".$postid."' ";
        $wpdb->query($delete);

    }

    public function generate_ffd_map_search_listing_db_data(){

        global $wpdb;


        $post_type = $this->get_listing_post_type();
        $meta_keys = $this->get_listing_fields();

        $meta_sql = '';
        foreach($meta_keys as $meta_column => $meta_key ){

            $meta_sql .= "MAX(IF(PM.meta_key = '".$meta_column."', PM.meta_value, NULL)) AS ".$meta_column.",";

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


    public function replace_placeholders($string){

        $placeholders = array(
            'TEMPLATE_URL' => untrailingslashit(get_stylesheet_directory_uri()),
            'HOME_URL' => untrailingslashit(home_url())
        );

        foreach( $placeholders as $find => $replace ){
            $string = str_replace($find, $replace, $string);
        }

        return $string;
    }


    public function shortcode_ffd_savesearch_button( $atts, $content = null ) {

        $default = array(
            'btn_text' => 'Save Search',
            'view_url' => '/my-account/',
            'btn_class' => 'btn btn-primary ',
        );
        $atts = wp_parse_args($atts, $default);



        $btn = '<a class="'.$atts['btn_class'].' " type="button" data-toggle="collapse" data-target="#ffd-savesearch-container" ';
        $btn .=  ( is_user_logged_in() ? ' href="javascript:void(0)" >' : ' href="'.wp_login_url().'" >');
        $btn .= ( isset($_GET['ssearchid']) ? $atts['btn_text'] : $atts['btn_text']);
        $btn .= '</a>';

        ob_start();

        echo '<div class="ffd-savesearch-wrap" style="text-align:right;position:relative;">';

        echo $btn;
        if( is_user_logged_in() ):
    ?>


        <div class="collapse" id="ffd-savesearch-container" style="position: absolute;
            z-index: 999999;
            right: 0px;
            background-color: #f5f5f5;
            padding: 15px;
            top: 0;
            border: 1px solid #ccc;">
            <div class="well">
                <div class="text-right" > <span style="position:relative;top: -15px;right: -10px;cursor:pointer;" class="text-black" data-toggle="collapse" data-target="#ffd-savesearch-container">X</span></div>
                <div class="ffd-savesearch-heading"><h4 style="text-align: left;margin: 0 0 10px 0;">Save Search</h4></div>
                <div class="form-group">
                    <div class="text-left">
                        <label class="control-label"> Search Name </label>
                        <input type="text" value="<?php echo isset($_GET['ssearchname']) ? $_GET['ssearchname'] : ''; ?>" id="savesearch-name"  class="skip-serialize" autocomplete="off" style="background-color:#fff;" placholder="Search Name" />
                        <input type="hidden" value="<?php echo isset($_GET['ssearchid']) ? $_GET['ssearchid'] : ''; ?>" id="savesearch-id" class="skip-serialize" />
                        <div class="save-search-status"></div>
                    </div>
                    <div>
                    <a href="javascript:void(0);" class="btn  btn-default black ffd-savesearch-btn "><?php if( isset($_GET['ssearchid']) ): ?> Update <?php else: ?> Save <?php endif; ?></a>
                    <div class="text-left">
                        <!-- <a href="<?php echo $atts['url']; ?>">View Saved Searches</a>' -->
                    </div>
                    </div>
                </div>
            </div>
        </div>

    <?php
        endif;
        echo '</div>';
        return ob_get_clean();
    }

        public function shortcode_ffd_script_tag( $atts, $content = null ) {

        $js = "<!-- FFD Integration Script Tag -->\n<script type=\"text/javascript\" src=\"".$this->replace_placeholders($atts['src'])."\"></script>\n";

        return $js;
    }

    public function shortcode_ffd_style_tag( $atts, $content = null ) {

        $css = "<!-- FFD Integration Style Tag -->\n<link rel=\"stylesheet\"  href=\"".$this->replace_placeholders($atts['href'])."\" type=\"text/css\" media=\"all\" >\n";

        return $css;
    }
    public function shortcode_ffd_search_form( $atts, $content = null ) {

        $this->load_search_form_shortcodes();

        $form_atts = array(
            "html_class"    => false,
            "html_id"    => 'ffd-search-form-' . $this->_form_instance++,
            "data"      => false,
            'iw_template' => 'iw template',
            'listing_template' => 'listing card template',
            'gmap_selector' => '#ffd-search-gmap',
            'gmap_args' => '',
            'use_clusterer' => false,
            'cluster_condition' => '',
            'load_data' => 'no'
        );
        $atts = wp_parse_args( $atts, $form_atts );


		$class  = 'ffd-integration-search-form';
        $class .= ( $atts['html_class'] )       ? ' ' . $atts['html_class'] : '';
		$id =$atts['html_id'];;

		$data_props = $this->parse_data_attributes( $atts['data'] );

        $js_formready = ffd_echo_js("jQuery('#".$id."').trigger('form_ready');", true, true);

        $listing_template = $atts['listing_template'] ;
        $iw_template = $atts['iw_template'];
        $gmap_selector = $atts['gmap_selector'];
        $gmap_args = $atts['gmap_args'];
        $use_clusterer = $atts['use_clusterer'];
        $cluster_condition = $atts['cluster_condition'];
        $load_data = $atts['load_data'];

        if( !empty($listing_template) ){
            $listing_template = FFD_Template_Rendrer()->get_ui_template_html($listing_template);
        }

        if( !empty($iw_template) ){
            $iw_template = FFD_Template_Rendrer()->get_ui_template_html($iw_template);
        }

        $listing_template = isset($listing_template) ? $listing_template : '';

        $templates = '';
        $templates .='<div data-listingtemplate="" style="display:none;">{% raw %}'.($listing_template).'{% endraw %}</div>';
        $templates .='<div data-iwtemplate="" style="display:none;">{% raw %}'.($iw_template).'{% endraw %}</div>';



		$content = sprintf(
			'<form data-ffdsearch data-gmap_selector="%s" data-gmap_args="%s" data-use_clusterer="%s" data-cluster_condition="%s" data-load="%s" id="%s" class="%s"%s >%s %s</form>',
            $gmap_selector,
            $gmap_args,
            $use_clusterer,
            $cluster_condition,
            $load_data,
			$id,
			$class,
			( $data_props ) ? ' ' . $data_props : '',
            do_shortcode( $content ),
            $templates
        );

        $content = $content .  $js_formready;

        return $content;
    }

    private function load_search_form_shortcodes(){

        add_shortcode('search_field', array($this, 'form_shortcodes_search_field'));
    }

    function form_shortcodes_search_field($atts, $content = null ){

        $key = isset($atts['name']) ? $atts['name'] : '';

        $defaults =  array(
                    "return"    => true,
                    'type'      => 'text',
                    "name"    => '',
                    "value"    =>  isset($_REQUEST[$key]) ? $_REQUEST[$key] : null,
                    "class"    => '',
                    "label_class"    => '',
                    "input_class"    => '',
                    "validate"    => '',
                    "options"    => '',
                    "fieldcondition"    => '',
                    "fieldkeys"    => '',
                    "data"      => false
        );

        $atts = wp_parse_args( $atts, $defaults );




        $value = $atts['value'];

        //field attributes
        $atts['custom_attributes'] = array();
        $atts['custom_attributes']['data-fieldtype'] = 'ffdsearch';
        $atts['custom_attributes']['data-fieldcondition'] = $atts['fieldcondition'];
        $atts['custom_attributes']['data-fieldkeys'] = $atts['fieldkeys'];

        if( $atts['type'] === 'autocomplete') {
            $atts['type'] = 'text';
            $atts['custom_attributes']['data-fieldautocomplete'] = "yes";
            $atts['custom_attributes']['data-keywordsearch'] = "yes";
        }

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
        $atts['input_class'] = $atts['class'];
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


    public function ajax_ffd_integration_autocomplete_trestle(){

        global $wpdb;

        $params = $_REQUEST;
        //var_dump($params);exit;
        $term = $params['term'];
        $query = $params['query'];

        $columns = explode(',', $params['keys']);
        $columsn = array_map('trim', $columns);

        $response = array();
        $sql = '';
        $sqls = array();
        if( !empty($columns) ){
            foreach($columns as $column ){

                $sqls[]= "SELECT {$column} as value FROM {$wpdb->prefix}ffd_integration_search WHERE {$column} LIKE '%{$term}%' and ffd_system_source= 'trestle'";

            }



            if( !empty($sqls) ){
                $sql = implode(" UNION ", $sqls);
                $sql .= " ORDER BY 1 LIMIT 10";
            }


            if( !empty($sql) ){
                $results = $wpdb->get_results($sql, 'ARRAY_A');
                foreach($results as $result){
                    $response[] = array("value"=>$result['value'],"label"=>$result['value']);
                }

            }

        }

        echo json_encode($response);
        die();
    }

    public function ajax_ffd_integration_autocomplete(){

        global $wpdb;

        $params = $_REQUEST;

        $term = $params['term'];
        $query = $params['query'];

        $columns = explode(',', $params['keys']);
        $columsn = array_map('trim', $columns);

        $response = array();
        $sql = '';
        $sqls = array();
        if( !empty($columns) ){
            foreach($columns as $column ){

                $sqls[]= "SELECT {$column} as value FROM {$wpdb->prefix}ffd_integration_search WHERE {$column} LIKE '%{$term}%'";

            }



            if( !empty($sqls) ){
                $sql = implode(" UNION ", $sqls);
                $sql .= " ORDER BY 1 LIMIT 10";
            }


            if( !empty($sql) ){
                $results = $wpdb->get_results($sql, 'ARRAY_A');
                foreach($results as $result){
                    $response[] = array("value"=>$result['value'],"label"=>$result['value']);
                }

            }

        }




       echo json_encode($response);
       die();

    }

    public function parse_web2prospect_field_condition($condition, $pb_field){

        $build_condition = $this->build_where_condition($condition, 'nodb');


        $value = $build_condition['value'];

        if( $value == '' ) return '';

        $relation = $build_condition['relation'];
        $compare = (string) $build_condition['compare'];
        $type = $build_condition['type'];
        $type = $this->get_cast_for_type($type);


        $pb_valutype_fields = array(
            'pba__YearBuilt_pb_min__c' => '>pba__yearbuilt_pb__c',
            'pba__YearBuilt_pb_max__c' => '<pba__yearbuilt_pb__c',
            'pba__LotSize_pb_min__c' => '>pba__lotsize_pb__c',
            'pba__LotSize_pb_max__c' => '<pba__lotsize_pb__c',
            'pba__TotalArea_pb_min__c' => '>pba__totalarea_pb__c',
            'pba__TotalArea_pb_max__c' => '<pba__totalarea_pb__c',
            'pba__ListingPrice_pb_min__c' => '>pba__listingprice_pb__c',
            'pba__ListingPrice_pb_max__c' => '<pba__listingprice_pb__c',
            'pba__Bedrooms_pb_min__c' => '>pba__bedrooms_pb__c',
            'pba__Bedrooms_pb_max__c' => '<pba__bedrooms_pb__c',
            'pba__FullBathrooms_pb_min__c' => '>pba__fullbathrooms_pb__c',
            'pba__FullBathrooms_pb_max__c' => '<pba__fullbathrooms_pb__c',
            'pba__Keywords_pb__c' => '=keywords',

            '#pba__YearBuilt_pb_min__c' => '=pba__yearbuilt_pb__c',
            '#pba__LotSize_pb_min__c' => '=pba__lotsize_pb__c',
            '#pba__TotalArea_pb_min__c' => '=pba__totalarea_pb__c',
            '#pba__ListingPrice_pb_min__c' => '=pba__listingprice_pb__c',
            '#pba__Bedrooms_pb_min__c' => '=pba__bedrooms_pb__c',
            '#pba__FullBathrooms_pb_min__c' => '=pba__fullbathrooms_pb__c',
            '#pba__Keywords_pb__c' => '=search_txt',
        );

        if( in_array($compare, array('>', '<', '>=', '<=') ) ){
            $_compare = str_replace('=', '', $compare);
            $check = strtolower($_compare . $pb_field);
            $_pbfield = array_search ($check, $pb_valutype_fields);

            if( $_pbfield ){
                $_pbfield = str_replace('#', '', $_pbfield);
                $pb_field  = $_pbfield;
                $value = FFD_Organizer()->w2p_clean_value($value);
            }

        } else if($compare == '='){
            $check = strtolower($compare . $pb_field);
            $_pbfield = array_search($check, $pb_valutype_fields);

            if( $_pbfield ){
                $_pbfield = str_replace('#', '', $_pbfield);
                $pb_field  = $_pbfield;
                $value = FFD_Organizer()->w2p_clean_value($value);
            }
        }

        if( !empty($value) ){
            return array('field'=>$pb_field, 'value' => $value);
        }


        return '';
    }


    public function parse_web2prospect_fields($query_params, $fields_mapping){

        //parse_str($_POST['params'], $params);

        $w2p_params = array();
        if( !isset( $fields_mapping['keywords']) ){
            $fields_mapping['keywords'] = 'keywords';
        }

        if( !isset( $fields_mapping['search_txt']) ){
            $fields_mapping['search_txt'] = 'search_txt';
        }

        if( !isset( $fields_mapping['search_keywords']) ){
            $fields_mapping['Search_Keywords__c'] = 'search_keywords';
        }



        foreach($query_params as $column => $value){
            $pbfield = array_search($column, $fields_mapping);
            if( $pbfield ){

                if( is_array($value) ){

                    foreach($value as $condition ){

                       $param = $this->parse_web2prospect_field_condition($condition, $pbfield);

                       $pb_field = isset($param['field']) ? $param['field'] : '';
                       $pb_value = isset($param['value']) ? $param['value'] : '';
                       if( !empty($pb_value)  && isset($w2p_params[$pb_field]) ){

                           if( !is_array($w2p_params[$pb_field]) ){
                                $tmp = $w2p_params[$pb_field];
                                $w2p_params[$pb_field] = array();
                                $w2p_params[$pb_field][] = $tmp;
                            }

                            $w2p_params[$pb_field][] = $pb_value;

                       } else if( !empty($pb_value)  ) {
                            $w2p_params[$pb_field] = $pb_value;
                       }

                    }

                    if( is_array($w2p_params[$pb_field]) ){
                        $w2p_params[$pb_field] = implode(';', $w2p_params[$pb_field]);
                    }

                } else {

                    $param = $this->parse_web2prospect_field_condition($value, $pbfield);
                    $pb_field = isset($param['field']) ? $param['field'] : '';
                    $pb_value = isset($param['value']) ? $param['value'] : '';

                    if( !empty($pb_value) && isset($w2p_params[$pb_field]) ){

                        if( !is_array($w2p_params[$pb_field]) ){
                             $tmp = $w2p_params[$pb_field];
                             $w2p_params[$pb_field] = array();
                             $w2p_params[$pb_field][] = $tmp;
                         }

                         $w2p_params[$pb_field][] = $pb_value;

                    } else if( !empty($pb_value)  ) {
                         $w2p_params[$pb_field] = $pb_value;
                    }
                }
            }

        }

        return $w2p_params;
    }



    public function ajax_ffd_integration_savesearch(){

        //$this->show_errors();

        if( !is_user_logged_in() ){
            wp_send_json_error(array('message' => 'Invalid request.'));
            die();
        }

        $user = wp_get_current_user();
        $user_id = get_current_user_id();


        $save_search = get_user_meta( $user_id, '_saved_searches', true);
        if( empty($save_search) ){
            $save_search = array();
        }

        $org_savesearch = get_user_meta($user_id, '_organizer_save_searches', true);
        if( empty($org_savesearch) ){
            $org_savesearch = array();
        }

        /*Original para propertybase
        $fields_mapping = FFD_Listings_Sync::fields();
        */

        //Nuevo para Trestle
        $fields_mapping = FFD_Listings_Trestle_Sync::fields();
        $query_params = $_POST['query'];

        $existing_searchid = isset($_POST['searchid']) ? $_POST['searchid'] : '';
        $name = $_POST['name'];
        $command = $_POST['command'];
        $slug = sanitize_title($name);

        //update existing search
        if( isset($org_savesearch[$existing_searchid]) ){
            $existing_savesearch = $org_savesearch[$existing_searchid];
            $existing_name = $existing_savesearch['name'];
            $existing_slug = sanitize_title($existing_name);

            if( $slug !== $existing_slug && isset($save_search[ $existing_slug ]) ){
                $existing_savesearch['PB_ID'] = $existing_searchid;
                $save_search[ $slug ] = $existing_savesearch;
                unset($save_search[ $existing_slug ]);
            }
        }



        if( empty($name) ){
            wp_send_json_error(array('message' => 'Please enter search name.'));
            die();
        }

        if( in_array($command, array('update', 'delete')) && !isset($save_search[ $slug ]) ){

            wp_send_json_error(array('message' => 'Search with this name do not exist.'));
            die();
        }

        $w2p_params = $this->parse_web2prospect_fields($query_params, $fields_mapping);

        $save_search[$slug] = array(
                'created' => current_time('mysql'),
                'name' => $name,
                'url'=>$_POST['url'],
                'page_url'=>$_POST['page_url'],
                'params'=>$_POST['params']
        );

        $PB_params = array (
            'ssearch-name' => $name,
            'search-frequency' => 'Daily',
            'email' => $user->user_email,
            'first_name' => $user->first_name,
            'last_name'  => !empty($user->last_name) ? $user->last_name : $user->user_nicename,
        );

        $user_pbid = FFD_Organizer()->pb_get_user_id($user->ID);

        if( !empty($user_pbid) ){
            $PB_params['contactid'] = $user_pbid;
        }

        if( !empty($save_search[$slug]['PB_ID']) ){
            $PB_params['requestid'] = $save_search[$slug]['PB_ID'];
        }


        $search_id = null;
        if( 'delete' === $command ){
            //delete search


            if( !empty($save_search[$slug]['PB_ID']) ){
                $search_id = $save_search[$slug]['PB_ID'];
                $pbr = FFD_Organizer()->pb_delete_record($search_id, 'pba__Request__c');
                //if( is_wp_error($pbr) ){
                    //wp_send_json_error(array('message' => 'Error Deleting Save Search. Try again later.'));
                //}
                unset($org_savesearch[ $search_id ]);

            }

            unset($save_search[ $slug ]);
            $search_id = null;

        } else {

            if( !empty($save_search[$slug]['PB_ID']) ){

                //update save search by request id
                $search_id = $save_search[$slug]['PB_ID'];


                if( empty($org_savesearch) ){
                    $org_savesearch = array();
                }
                $org_savesearch[$search_id] = $save_search[$slug];

            }




            //perform new save search

            //$PB_params = array_merge($w2p_params, $PB_params);

            $pbquery = FFD_Organizer()->get_web2prospect_query($PB_params);
            $pbquery['prospect']['request'] = array_merge($w2p_params, $pbquery['prospect']['request']);

            $r = FFD_SalesForce()->rest->web_to_prospect($pbquery);



            if( !is_wp_error($r) ){

                $user_pbid = FFD_Organizer()->get_var($r["contact"], 'id' );
                $search_id = FFD_Organizer()->get_var($r["request"], 'id' );

                $save_search[$slug]['PB_ID'] = $search_id;
                if( !empty($user_pbid)) {
                    FFD_Organizer()->pb_update_user_id($user->ID, $user_pbid);
                }


                if( empty($org_savesearch) ){
                    $org_savesearch = array();
                }
                $org_savesearch[$search_id] = $save_search[$slug];


            } else {
                wp_send_json_error(array('message' => 'Unable to save search try again later.', 'PB_params' => $pbquery, 'r'=>$r));
		        die();
            }

        }



        update_user_meta( $user_id, '_organizer_save_searches', $org_savesearch);
        update_user_meta( $user_id, '_saved_searches', $save_search);

        $message = 'Success: Saved Search.';
        if( 'update' === $command ){
            $message = 'Success: Updated Search.';
        } else if('delete' === $command){
            $message = 'Success: Deleted Search.';
        }
        $response = array('message' => $message, 'PB_params' => $pbquery, 'pbr'=>$r);

        if( !empty($search_id) && 'delete' !== $command  ){
            $response['id'] = $search_id;
        }
        wp_send_json_success($response);
        die();


    }


    //f.e.a
    public function ajax_ffd_integration_search_trestle(){



        /*$ap_master_search = json_decode(get_option('ap_master_search'), true);
        foreach ($ap_master_search as $key => $search){
            var_dump( $key,'=>',$search);
        }exit;*/

        //var_dump('PROPERTY: ',FFD_Template_Rendrer()->get_post_data(21102));exit;
        //var_dump(get_bloginfo('name'));exit;
        //var_dump(wp_count_posts('listing'));exit;

        /*$data = array(
            'grant_type' => 'client_credentials',
            'scope'      => 'api',
            'client_id' => get_option('ffd_trestle_client_id'),
            'client_secret' => '6343a98b74ab4778917f24fe92a6cd4'
        );
        //$query = '';
        //$query .= '&' . http_build_query($data, null, '&');
        //$query = trim($query, '&');

        //$args['method'] = 'POST';

        $args['body'] = $data;

        $request = wp_remote_post('https://api-prod.corelogic.com/trestle/oidc/connect/token', $args);
        $response_body = wp_remote_retrieve_body( $request );
        var_export(json_decode($response_body));exit;*/
        //===============================================================
        /*$access_token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsIng1dCI6IlFCak1OaWk1TGpyQTdWTzhidXE4Z2o5QWtERSIsImtpZCI6IlFCak1OaWk1TGpyQTdWTzhidXE4Z2o5QWtERSJ9.eyJpc3MiOiJodHRwczovL3RyZXN0bGUuY29yZWxvZ2ljLmNvbS9vaWRjIiwiYXVkIjoiaHR0cHM6Ly90cmVzdGxlLmNvcmVsb2dpYy5jb20vb2lkYy9yZXNvdXJjZXMiLCJleHAiOjE2MDA5NTMwMjIsIm5iZiI6MTYwMDkyNDIyMiwiY2xpZW50X2lkIjoidHJlc3RsZV9NYXJrZXRGcmVzaFN0dWRpb3NMaXZlTW9kZXJuMjAyMDA1MjUwNTA5MzkiLCJjbGllbnRfcm9sZSI6ImFwaSIsImNsaWVudF9uYW1lIjoidHJlc3RsZV9NYXJrZXRGcmVzaFN0dWRpb3NMaXZlTW9kZXJuMjAyMDA1MjUwNTA5MzkiLCJjbGllbnRfY2xpZW50X2lkIjoidHJlc3RsZV9NYXJrZXRGcmVzaFN0dWRpb3NMaXZlTW9kZXJuMjAyMDA1MjUwNTA5MzkiLCJjbGllbnRfYnVzaW5lc3NfZW50aXR5X2lkIjoiNDIxMSIsImNsaWVudF9wcm9kdWN0X2lkIjoiMjkyNSIsImNsaWVudF9wcm9kdWN0X2RhdGFmZWVkX2lkIjoiMjAiLCJjbGllbnRfcHJvZHVjdF9kYXRhZmVlZF9tb2RlbF9pZCI6IjcxIiwiY2xpZW50X21sb19pZF9saXN0IjoiODIiLCJjbGllbnRfYnVzaW5lc3NfY29ubl9pZF9saXN0IjoiNzM2OCIsImNsaWVudF9tZiI6IjMiLCJjbGllbnRfcHJvZHVjdF9kYXRhZmVlZF90cmFuc3BvcnQiOiJBUEkiLCJjbGllbnRfcHJvZHVjdF9uYW1lIjoiTGl2ZSBNb2Rlcm4iLCJjbGllbnRfcHJvZHVjdF9kYXRhZmVlZF9uYW1lIjoiSURYIFBsdXMiLCJjbGllbnRfY29tcGFueV9uYW1lIjoiTWFya2V0IEZyZXNoIiwiY2xpZW50X2Jyb2tlcl9vZmZpY2Vfa2V5cyI6IiIsInNjb3BlIjoiYXBpIn0.D5I27zp14407LUJkyOqu00dsCZc4VPjIYdGBSr4wZG7SuVl-Oh_tQeut49JiI34AMQ8NjuWce0a01Zttszl3eugSQHoaBaWGUzvWeFG3KLv-O9P2rA7LeDvF2PQsm737r0-zBahXStEnCuXcy4j9Flu-ptninv46JFjbjskaOYrQLZNXSstS7tncRuNGuCRGwz_EQFtOtAFKuOGqLq0oIUjHN40J48tlHrn03XsXeyY88LO0Yspb-EtcTsdrhM5kulPmRsjBIkq4XBGTZet9MkuzyuKW4idLrdta3BhsF1hmnlVP2qTYDes3jQJgTM83QEbq5XbuHHPY-HimHLNKqA';

        $header = array(
            'Authorization' => 'Bearer '.$access_token
        );

        //$args['method'] = 'GET';

        $args['headers'] = $header;

        $end_point = 'https://api-prod.corelogic.com/trestle/odata/Property';
        $param = array();
        if(get_option('trestle_select')){$select='$select='.get_option('trestle_select'); $param[]=$select;}
        if(get_option('trestle_top')){$top='$top='.get_option('trestle_top'); $param[]=$top;}
        if(get_option('trestle_filter')){$filter = '$filter='.get_option('trestle_filter'); $param[]=$filter;}

        $query = '?';
        foreach ($param as $p){
            $query .= $p.'&';
        }
        $query = trim($query,'&');*/
        /*$query = '?' . http_build_query($param, null, '&');
        var_dump($query);exit;
        $query = '?$select='.get_option('trestle_select').'&$top='.get_option('trestle_top').'&$filter='.get_option('trestle_filter');
        $param = '$select=ListingId,MlsStatus,ParcelNumber,SubdivisionName&$top=1000&$filter=contains(ParcelNumber,%2774434322350%27)';*/
        /*$url = $end_point.$query;
        $request = wp_remote_get($url, $args);
        $response_body = wp_remote_retrieve_body( $request );

        var_export(json_decode($response_body));exit;*/
        //=================================================================================================================
        /*$curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://login.salesforce.com/services/oauth2/token",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "grant_type=password&client_id=3MVG9oNqAtcJCF.GQfbNoqDS3zV_ib3DI9Ikm1uf89xRghambzTh9.jrO6q.WPpSiqaAIXxqs_KD3kKJQQjWp&client_secret=CC0DE21E72CC80D31AC97F59ABC481FECBD43D7A57011923E8F0578B8904BE66",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/x-www-form-urlencoded",
                "Cache-Control: no-cache",
                "Accept-Encoding: gzip, deflate, br"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            echo $response;
        }exit;*/
        /*global $wpdb;
        $trestle = $wpdb->get_results("
                SELECT p.PostalCity, p.StateOrProvince, p.PostalCode, p.StandardStatus
                FROM Property p
                WHERE p.StandardStatus != 'Closed'
                ", ARRAY_A);
        var_dump('trestle',$trestle);*/

        //var_dump('$_REQUEST',$_REQUEST['queryObject']);exit;
        //var_dump('$_REQUEST');exit;
        if ( true || isset($_REQUEST['no_cache']) || false === ( $data = get_transient( 'transient_ffd_searchdata' ) ) ) {

            global $wpdb;
            //$limit = 50;

            // this code runs when there is no valid transient set
            $home = home_url();
            $params = $_REQUEST;
            $fields = 'ids';
            if( 'ids' === $fields ){
                $select= array($fields);
            }


//var_dump(is_user_logged_in());exit;
            $where=isset($params['where']) ? $params['where'] : array();
            $order=isset($params['order']) ? $params['order'] : '';
            $limit=isset($params['limit']) ? $params['limit'] : 12;
            $offset=isset($params['offset']) ? $params['offset'] : 0;
            $where['ffd_system_source'] = 'trestle|eq|text';

            $limit = 40;
            $size_where = count($params['where']);
            //comentado temporalmente franklin
            /*if($size_where > 1){
                $search_param = explode('|',$params['where']['ffd_city_pb']);


                $trestle = $wpdb->get_results("
                SELECT *
                FROM Property
                WHERE City LIKE '%$search_param[0]%'

                union

                SELECT *
                FROM Property
                WHERE PostalCode LIKE '%$search_param[0]%'

                union

                SELECT *
                FROM Property
                WHERE ListingId LIKE '%$search_param[0]%'

                union

                SELECT *
                FROM Property
                WHERE SubdivisionName LIKE '%$search_param[0]%'

                ", ARRAY_A);

            }else {*/
                $trestle = $wpdb->get_results("
                                                SELECT *
                                                FROM Property
                                                WHERE StandardStatus != 'Closed'
                                                ORDER BY ListingKey asc
                                                LIMIT 4500,1000
                ", ARRAY_A);

                /*$trestle = $wpdb->get_results("
                SELECT p.ListingKey, p.StateOrProvince, p.PostalCode, p.StandardStatus, p.MlsStatus, p.ListingKey, p.ListPrice, p.MlsStatus
                FROM Property p
                WHERE p.StandardStatus != 'Closed'
                ", ARRAY_A);*/

            //}

            $current_date = date('Y-m-d');
            /*foreach($trestle as $prop) {
                //verify exits into table listing
                $listing_id = $prop['ListingKey'];

                $ffd_listing = $wpdb->get_results("
                        SELECT pm.meta_value, p.ID
                        FROM wp_posts p
                        inner join wp_postmeta pm on pm.post_id = p.ID
                        WHERE pm.meta_key = 'ffd_id' and pm.meta_value = $listing_id
                        ", ARRAY_A);

                $ffd_image = $wpdb->get_results("
                    select med.MediaURL
                    from Media med
                    where med.ListingKey = $listing_id
                    order by med.Order asc
                    ", ARRAY_A  );

                $tam = count($ffd_image);


                if ($ffd_listing == null && $prop['MlsStatus'] != 'Closed') {

                    if ($tam > 0) {
                        $json_image = 'a:' . $tam . ':{';

                        foreach ($ffd_image as $key => $image) {
                            $tam_cad = strlen($image['MediaURL']);
                            if ($key == $tam - 1) {
                                $json_image .= 'i:' . $key . ';s:' . $tam_cad . ':"' . $image['MediaURL'] . '"';
                            } else {
                                $json_image .= 'i:' . $key . ';s:' . $tam_cad . ':"' . $image['MediaURL'] . '";';
                            }

                        }
                        $json_image .= ';}';
                    } else {
                        $json_image = null;
                    }

                    $addr = $prop['StreetNumber'] . ' ' . $prop['StreetDirPrefix'] . ' ' . $prop['StreetName'] . ' #' . $prop['UnitNumber'];
                    $address = ucwords($addr . ' ' . $prop['City'] . ', ' . $prop['StateOrProvince'] . ' ' . $prop['PostalCode']);

                    $city = isset($prop['City']) ? $prop['City'] . '-' : '-';
                    if ($city != '-')
                        $city = str_replace(' ', '-', $city);

                    $street = isset($prop['StreetName']) ? $prop['StreetName'] . '-' : '-';
                    if ($street != '-')
                        $street = str_replace(' ', '-', $city);

                    $addr_guid = (isset($prop['StreetNumber']) && $prop['StreetNumber'] != '' ? $prop['StreetNumber'] . '-' : '') .
                        (isset($prop['StreetDirPrefix']) && $prop['StreetDirPrefix'] != '' ? $prop['StreetDirPrefix'] . '-' : '') .
                        $street .
                        (isset($prop['UnitNumber']) && $prop['UnitNumber'] != '' ? $prop['UnitNumber'] . '-' : '') .
                        $city .
                        (isset($prop['StateOrProvince']) && $prop['StateOrProvince'] != '' ? $prop['StateOrProvince'] . '-' : '') .
                        (isset($prop['PostalCode']) ? $prop['PostalCode'] : '');
                    $addr_guid = $prop['ListingKey'].'-'.strtolower($addr_guid);


                    $protocol = isset($_SERVER['HTTP_X_FORWARDED_PROTO']) ? $_SERVER['HTTP_X_FORWARDED_PROTO'] : $_SERVER['REQUEST_SCHEME'];
                    $server_name = isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : $_SERVER['SERVER_NAME'];
                    $application = isset($_SERVER['CONTEXT_PREFIX']) ? $_SERVER['CONTEXT_PREFIX'] : 'livemodern';
                    $guid = $protocol . '://' . $server_name . '/' . $application . '/listing/' . $addr_guid . '/';
                    //insert into posts
                    $wpdb->insert(
                        'wp_posts',
                        array(
                            'post_author' => '1',
                            'post_date' => $current_date,
                            'post_date_gmt' => $current_date,
                            'post_content' => $prop['PublicRemarks'],
                            'post_title' => $address,
                            'post_status' => 'publish',
                            'comment_status' => 'closed',
                            'ping_status' => 'closed',
                            'post_name' => $addr_guid,//$prop['ListingId'],
                            'post_modified' => $current_date,
                            'post_modified_gmt' => $current_date,
                            'guid' => $guid,
                            'post_type' => 'listing'
                        )
                    );

                    $lastid = $wpdb->insert_id;
                    $image_default = $protocol . '://' . $server_name . $application . '/wp-content/plugins/ffd-integration/legacy-assets/images/placeholder.jpg';

                    $ff_address_short = $prop['PostalCity'].', '.$prop['StateOrProvince'].' '.$prop['PostalCode'];

                    //insert into wp_ffd_listings
                    $wpdb->insert(
                        'wp_ffd_listings',
                        array(
                            'ID' => $lastid,//'ID' => $prop['ListingKey'],
                            'post_title' => $address,
                            'post_type' => 'listing',
                            'ffd_address_pb' => $ff_address_short,
                            'ffd_fullbathrooms_pb' => 1 == $prop['BathroomsHalf'] ? $prop['BathroomsFull'] . '.5' : $prop['BathroomsFull'],
                            'ffd_bedrooms_pb' => $prop['BedroomsTotal'],
                            'ffd_city_pb' => $prop['City'],
                            'ffd_community_features' => $prop['CommunityFeatures'],
                            'ffd_createddate' => $prop['OnMarketDate'],
                            'ffd_description_pb' => $prop['PublicRemarks'],
                            'ffd_exterior_features' => $prop['ExteriorFeatures'],
                            'ffd_halfbathrooms_pb' => $prop['BathroomsHalf'],
                            'ffd_interior_features' => $prop['InteriorFeatures'],
                            'ffd_lastmodifieddate' => $prop['ContractStatusChangeDate'],
                            'ffd_latitude_pb' => $prop['Latitude'],
                            'ffd_listed_date' => $prop['ListingContractDate'],
                            'ffd_listing_agent_email' => $prop['ListAgentEmail'],
                            'ffd_listing_agent_firstname' => $prop['ListAgentFirstName'],
                            'ffd_listing_agent_lastname' => $prop['ListAgentLastName'],
                            'ffd_listing_agent_phone' => $prop['ListAgentPreferredPhone'],
                            'ffd_listingprice_pb' => $prop['ListPrice'],
                            'ffd_listingtype' => $prop['ListingTerms'],
                            'ffd_listingofficename' => $prop['ListOfficeName'],
                            'ffd_listingofficeshortid' => $prop['ListOfficeMlsId'],
                            'ffd_living_sq_ft' => $prop['LivingArea'],
                            'ffd_longitude_pb' => $prop['Longitude'],
                            'ffd_mls_id' => $prop['ListingId'],
                            'ffd_pets_allowed' => $prop['PetsAllowed'],
                            'ffd_private_pool' => $prop['PoolFeatures'],
                            'ffd_propertytype' => $prop['PropertyType'],
                            'ffd_id' => $prop['ListingKey'],
                            'ffd_state_pb' => $prop['StateOrProvince'],
                            'ffd_status' => $prop['StandardStatus'],
                            'ffd_subdivision' => $prop['SubdivisionName'],
                            'ffd_taxes' => $prop['TaxAnnualAmount'],
                            'ffd_name' => ($prop['SubdivisionName'] == null ? '' : $prop['SubdivisionName'] . ' / ') . $prop['City'] . ' ' . $prop['StateOrProvince'] . ' ' . $prop['PostalCode'],
                            'ffd_total_floors' => $prop['Flooring'],
                            'ffd_unbranded_virtual_tour' => $prop['VirtualTourURLUnbranded'],
                            'ffd_view' => $prop['View'],
                            'ffd_waterfront' => $prop['WaterfrontYN'] ? 'Yes' : 'No',
                            'ffd_waterfront_features' => $prop['WaterfrontFeatures'],
                            'ffd_yearbuilt_pb' => $prop['YearBuilt'],
                            'ffd_postalcode_pb' => $prop['PostalCode'],
                            'ffd_media' => isset($json_image) ? $json_image : $image_default,
                            'ffd_salesforce_id' => $prop['ListingKey'],
                            'ffd_days_on_market' => $prop['DaysOnMarket'],
                            'ffd_system_source' => 'trestle'
                        )
                    );


                    //insert into post meta
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_acres_calc', 'meta_value' => $prop['LotSizeAcres']));
                    //$wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_address_pb', 'meta_value' => $address));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_address_pb', 'meta_value' => $prop['PostalCity'].', '.$prop['StateOrProvince'].' '.$prop['PostalCode']));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_fullbathrooms_pb', 'meta_value' => 1 == $prop['BathroomsHalf'] ? $prop['BathroomsFull'] . '.5' : $prop['BathroomsFull']));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_bedrooms_pb', 'meta_value' => $prop['BedroomsTotal']));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_city_pb', 'meta_value' => $prop['City']));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_community_features', 'meta_value' => $prop['LotSizeAcres']));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_createddate', 'meta_value' => $prop['OnMarketDate']));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_description_pb', 'meta_value' => $prop['PublicRemarks']));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_exterior_features', 'meta_value' => $prop['ExteriorFeatures']));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_interior_features', 'meta_value' => $prop['InteriorFeatures']));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_lastmodifieddate', 'meta_value' => $prop['ContractStatusChangeDate']));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_latitude_pb', 'meta_value' => $prop['Latitude']));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_listed_date', 'meta_value' => $prop['ListingContractDate']));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_listing_agent_email', 'meta_value' => $prop['ListAgentEmail']));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_listing_agent_firstname', 'meta_value' => $prop['ListAgentFirstName']));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_listing_agent_lastname', 'meta_value' => $prop['ListAgentLastName']));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_listing_agent_phone', 'meta_value' => $prop['ListAgentPreferredPhone']));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_listingprice_pb', 'meta_value' => $prop['ListPrice']));

                    if (strrpos($prop['PropertyType'], 'Lease')) {
                        $type = 'Rent';
                    } else {
                        $type = 'Sale';
                    }

                    if (strpos($prop['ListingId'], 'R') === false) {
                        $mls = str_replace('F', 'FX-', $prop['ListingId']);
                    } else {
                        $mls = str_replace('R', 'RX-', $prop['ListingId']);
                    }

                    $mls = $prop['ListingId'];

                    $name = $prop['SubdivisionName'] . ' / ' . $prop['City'] . ' ' . $prop['StateOrProvince'] . ' ' . $prop['PostalCode'];

                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_listingtype', 'meta_value' => $type));//$prop['ListingTerms']
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_listingofficename', 'meta_value' => $prop['ListOfficeName']));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_listingofficeshortid', 'meta_value' => $prop['ListOfficeMlsId']));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_living_sq_ft', 'meta_value' => $prop['LivingArea']));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_longitude_pb', 'meta_value' => $prop['Longitude']));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_lotsize_pb', 'meta_value' => $prop['LotSizeArea']));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_mls_id', 'meta_value' => $mls));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_parkingspaces', 'meta_value' => isset($prop['GarageSpaces']) ? (integer)$prop['GarageSpaces'] : (isset($prop['ParkingTotal']) ? (integer)$prop['ParkingTotal'] : 0)));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_pets_allowed', 'meta_value' => $prop['PetsAllowed']));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_private_pool', 'meta_value' => $prop['PoolFeatures']));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_propertytype', 'meta_value' => isset($prop['PropertySubType']) ? $prop['PropertySubType'] : $prop['PropertySubTypeAdditional']));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_id', 'meta_value' => $prop['ListingKey']));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_state_pb', 'meta_value' => $prop['StateOrProvince']));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_status', 'meta_value' => $prop['StandardStatus']));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_subdivision', 'meta_value' => $prop['SubdivisionName']));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_taxes', 'meta_value' => $prop['TaxAnnualAmount']));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_name', 'meta_value' => $name));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_total_floors', 'meta_value' => 0));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_view', 'meta_value' => $prop['View']));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_waterfront', 'meta_value' => $prop['WaterfrontYN'] ? 'Yes' : 'No'));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_waterfront_features', 'meta_value' => $prop['WaterfrontFeatures']));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_yearbuilt_pb', 'meta_value' => $prop['YearBuilt']));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_postalcode_pb', 'meta_value' => $prop['PostalCode']));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_media', 'meta_value' => isset($json_image) ? $json_image : $image_default));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_featured_image', 'meta_value' => $ffd_image[0]['MediaURL']));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_listing_title', 'meta_value' => $name));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_salesforce_id', 'meta_value' => $prop['ListingKey']));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_record_is_deleted', 'meta_value' => ''));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_days_on_market', 'meta_value' => $prop['DaysOnMarket']));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_unbranded_virtual_tour', 'meta_value' => $prop['VirtualTourURLUnbranded']));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_system_source', 'meta_value' => 'trestle'));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_propertysubtype', 'meta_value' => $prop['PropertySubType']));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_guid', 'meta_value' => $guid));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_source', 'meta_value' => $prop['SourceSystemID']));
                    $wpdb->insert('wp_postmeta', array('post_id' => $lastid, 'meta_key' => 'ffd_parcel_number', 'meta_value' => $prop['ParcelNumber']));


                }
            }*/


//                else{
//                if ($prop['MlsStatus'] != 'Closed'){
//
//                    /*if ($tam > 0) {
//                        $json_image = 'a:' . $tam . ':{';
//
//                        foreach ($ffd_image as $key => $image) {
//                            $tam_cad = strlen($image['MediaURL']);
//                            if ($key == $tam - 1) {
//                                $json_image .= 'i:' . $key . ';s:' . $tam_cad . ':"' . $image['MediaURL'] . '"';
//                            } else {
//                                $json_image .= 'i:' . $key . ';s:' . $tam_cad . ':"' . $image['MediaURL'] . '";';
//                            }
//
//                        }
//                        $json_image .= ';}';
//                    } else {
//                        $json_image = null;
//                    }*/
//
//                    $post_id = $ffd_listing[0]['ID']; var_dump($ffd_listing, 'post_id', $post_id);exit;
//
//                    /*$ffd_media_listing = $wpdb->get_results("
//                        SELECT pm.meta_value as  media, p.ID
//                        FROM wp_posts p
//                        inner join wp_postmeta pm on pm.post_id = p.ID
//                        WHERE p.ID = $post_id AND pm.meta_key = 'ffd_media'
//                        ", ARRAY_A);
//                    $url_default = 'http://54.227.57.214/livemodern/wp-content/plugins/ffd-integration/legacy-assets/images/placeholder.jpg';
//
//                    if ( $url_default == $ffd_media_listing[0]['media'] || null == $ffd_media_listing[0]['media'] ){
//                        $wpdb->update('wp_postmeta', array('meta_value' => $json_image), array('post_id' => $ffd_listing[0]['ID'], 'meta_key' => 'ffd_media'));
//                    }*/
//
//
//                    //$formatted_address = $prop['StreetNumber'].' '.$prop['StreetName'].', '.$prop['PostalCity'].', '.$prop['StateOrProvince'].' '.$prop['PostalCode'].', EE. UU.';
//                    if ($ffd_listing != null) {
//                        /*$wpdb->insert('wp_postmeta', array('post_id'=>$post_id,'meta_key'=>'ffd_street_name_pb','meta_value' => $prop['StreetName']));
//                        $wpdb->insert('wp_postmeta', array('post_id'=>$post_id,'meta_key'=>'ffd_street_number_pb','meta_value' => $prop['StreetNumber']));
//                        $wpdb->insert('wp_postmeta', array('post_id'=>$post_id,'meta_key'=>'ffd_stories_pb','meta_value' => $prop['Stories']));
//                        $wpdb->insert('wp_postmeta', array('post_id'=>$post_id,'meta_key'=>'ffd_architectural_style_pb','meta_value' => $prop['ArchitecturalStyle']));
//                        $wpdb->insert('wp_postmeta', array('post_id'=>$post_id,'meta_key'=>'ffd_formatted_address_pb','meta_value' => $formatted_address));*/
//
//                        $wpdb->update('wp_ffd_listings', array('meta_value' => $prop['PostalCity'].', '.$prop['StateOrProvince'].' '.$prop['PostalCode']), array('ID' => $post_id, 'meta_key' => 'post_title'));
//
//                        //$wpdb->update('wp_postmeta', array('meta_value' => $prop['PostalCity'].', '.$prop['StateOrProvince'].' '.$prop['PostalCode']), array('post_id' => $post_id, 'meta_key' => 'ffd_address_pb'));
//                        //$wpdb->update('wp_postmeta', array('meta_value' => $prop['ListPrice']), array('post_id' => $post_id, 'meta_key' => 'ffd_listingprice_pb'));
//                        //$wpdb->update('wp_postmeta', array('meta_value' => $prop['MlsStatus']), array('post_id' => $post_id, 'meta_key' => 'ffd_status'));
//
//                    }
//                }
//            }



            $data = $this->query_ffd_search_data($select, $where, $order, $limit, $offset, false, $_REQUEST['queryObject']);
            //var_dump('$this->paginator',$this->paginator);exit;
            //var_dump('llega',!is_wp_error($data), 'ids' === $fields, !empty($data) );exit;
            if( !is_wp_error($data) ){
                if( 'ids' === $fields && !empty($data) ){

                    $args = array(
                        'post_type' => $this->get_listing_post_type(),
                        'post_status' => 'publish',
                        'post__in' => array_column($data, 'ID'),
                        'posts_per_page' => -1,
                        'orderby' => 'post__in',
                        'custom_query' => true,
                        'nopaging' => true
                    );

                    $custom_query = new WP_Query($args);//var_dump($custom_query->posts);exit;
                    $data = array();
                    if( isset($custom_query->posts) ){
                        foreach($custom_query->posts as $post){
                            $data[] = FFD_Template_Rendrer()->get_post_data($post->ID);

                        }//var_export($data);exit;
                    }

                } else {

                    foreach($data as $data_key => $item){

                        $permalink = ( !empty($item['ID']) ) ? get_permalink($item['ID']) : '';
                        if( empty($permalink) ){
                            unset($data[$data_key]);
                            continue;
                        }

                        if( !empty($item['ffd_media']) ){
                            $image = maybe_unserialize($item['ffd_media']);
                            if( !is_wp_error($image) && !empty($image) && is_array($image)){
                                $image = $image[0];
                                $item['image'] = $image;
                            } else {
                                $item['image'] = $image;
                            }
                        }
                        $item['link'] = str_replace($home, '', $permalink);
                        $data[$data_key] = apply_filters('ffd_integration_search_item', $item);
                    }

                }
            }
            echo json_encode(array('listings' => $data, 'meta' => array('limit' => $limit, 'offset' => $offset), 'paginator' => $this->paginator, 'number_properties' => $this->number_properties));
        } else {
            wp_send_json_error($data);
        }
        die();
    }
    //f.e.a

    /*
     * franklin.espinoza 19/06/2020
     * */
    public function ajax_ffd_integration_search(){

        //$this->show_errors();

        if ( true || isset($_REQUEST['no_cache']) || false === ( $data = get_transient( 'transient_ffd_searchdata' ) ) ) {

            // this code runs when there is no valid transient set

            $home = home_url();
            $params = $_REQUEST;

            $fields = 'ids';
            if( 'ids' === $fields ){
                $select= array($fields);
            }
            //$select=isset($params['select']) ? $params['select'] : array();

            $where=isset($params['where']) ? $params['where'] : array();
            $order=isset($params['order']) ? $params['order'] : '';
            $limit=isset($params['limit']) ? $params['limit'] : 12;
            $offset=isset($params['offset']) ? $params['offset'] : 0;

            //@Todo Testing
            //$limit = 50;


            $data = $this->query_ffd_search_data($select, $where, $order, $limit, $offset, false);
            if( !is_wp_error($data) ){
                if( 'ids' === $fields && !empty($data) ){

                    $args = array(
                        'post_type' => $this->get_listing_post_type(),
                        'post_status' => 'publish',
                        'post__in' => array_column($data, 'ID'),
                        'posts_per_page' => -1,
                        'orderby' => 'post__in',
                        'custom_query' => true
                    );

                    $custom_query = new WP_Query($args);
                    $data = array();
                    if( isset($custom_query->posts) ){
                        foreach($custom_query->posts as $post){

                            $data[] = FFD_Template_Rendrer()->get_post_data($post->ID);

                        }
                    }




                } else {

                    foreach($data as $data_key => $item){

                        $permalink = ( !empty($item['ID']) ) ? get_permalink($item['ID']) : '';
                        if( empty($permalink) ){
                            unset($data[$data_key]);
                            continue;
                        }

                        if( !empty($item['ffd_media']) ){
                            $image = maybe_unserialize($item['ffd_media']);
                            if( !is_wp_error($image) && !empty($image) && is_array($image)){
                                $image = $image[0];
                                $item['image'] = $image;
                            } else {
                                $item['image'] = $image;
                            }
                        }

                        $item['link'] = str_replace($home, '', $permalink);



                        /* $str = '';
                        $i=0;
                        foreach($item as $item_key => $item_value){
                            $str .= $i . "=" . $item_value.'&';
                            $i++;
                        }
                        $data[$data_key] = rtrim($str, '&'); */
                        $data[$data_key] = apply_filters('ffd_integration_search_item', $item);


                    }

                }

                //$data = array_values($data);
                //set_transient( 'transient_ffd_searchdata', $data, 29 * MINUTE_IN_SECONDS );
            }

            echo json_encode(array('listings' => $data, 'meta' => array('limit' => $limit, 'offset' => $offset)));
        } else {
            wp_send_json_error($data);
        }
        die();
    }


    public function ajax_ffd_integration_createdata(){

        $this->generate_ffd_map_search_listing_db_data();

        die('Done');
    }



    public function query_ffd_search_data( array $select=array(), array $where=array(), string $order, $limit=null, $offset=null, $return_json=true, $queryObject){

        global $wpdb;
        $destination_table = $this->tablename;
        $destination_alias = 'listing';

        $query = "";
        $meta_keys = $this->listing_fields;
        $fields ="";


        //$default =  array('lat', 'lng', 'image', 'size', 'status', 'openhouse', 'yearbuilt', 'listdate', 'proptype', 'beds', 'baths', 'listprice', 'saleprice', 'mls_id', 'city', 'address', 'post_title', 'ID');
        $keynames = array_keys($meta_keys);
        $default = array_merge($keynames, array('post_title', 'ID'));
        $ids_only = false;

        if( !empty($select) && in_array('ids', $select)){
            $ids_only = true;
            $select = array('ID');
        } else if( !empty($select) ){
            $select = array_merge($default, $select);
        } else {
            $select = $default;
        }

        $fields = implode(", ", $select);

        $where_clause = "";
        $select_clause = "";//var_dump('$where', $where);exit;
        if( !empty($where) ){
            $where_clause = " WHERE 1=1 ";
            $first_where_clause = true;
            $lastrelation = '';
            $startcombine = '';
            $endcombine = '';

            $whereIterator = new ArrayIterator($where);
            $whereIterator->rewind();
            $whereIterator->next(); // put the initial pointer to 2nd position

            foreach($where as $column => $value){
                $nextcolumn = $whereIterator->key();
                $nextvalue = $whereIterator->current();

                if($column == 'poly'){

                    $latlng_query = " SELECT lat, lng FROM " . $destination_table . " WHERE lat!='' AND lng!='';";
                    $latlng_data = $wpdb->get_results($latlng_query, "ARRAY_A");

                    $queryLat = $latlng_data['lat'];
                    $queryLon = $latlng_data['lng'];
                    $where_clause .= " AND st_within(point($queryLat,$queryLon),ST_GeomFromText('Polygon((" . $value . "))'))";

                } else if( isset($meta_keys[$column]) ){

                    if( is_array($value) ){
                        $first_sub_clause = true;
                        foreach($value as $_value ){

                            $build_condition = $this->build_where_condition($_value, 'db', $column);
                            $value = $build_condition['value'];
                            $relation = $build_condition['relation'];
                            $compare = $build_condition['compare'];
                            $type = $build_condition['type'];
                            $type = $this->get_cast_for_type($type);

                            if( $first_sub_clause ){
                                $relation = " AND ";
                                $first_sub_clause = false;

                            } else {
                                //$where_clause .= ' ( ';
                            }


                            if( $type !== 'CHAR' ){
                                $column = "CAST(".$column." AS {$type})";
                            }

                            $where_clause .= $relation . " ";
                            $where_clause .= $column . " " . $compare . " " .   $value . " ";

                        }
                        if( !empty($value) && !empty($where_clause) ){
                            //$where_clause .= ' ) ';
                        }
                    } else {


                        $next_condition = $this->build_where_condition($nextvalue, 'db', $column);

                        $build_condition = $this->build_where_condition($value, 'db', $column);
                        $value = $build_condition['value'];
                        $relation = $build_condition['relation'];
                        $compare = $build_condition['compare'];
                        $type = $build_condition['type'];
                        $type = $this->get_cast_for_type($type);

                        if( $first_where_clause ){
                            $relation = " AND ";
                            $first_where_clause = false;
                        }

                        if( $type !== 'CHAR' ){
                            $column = "CAST(".$column." AS {$type})";
                        }

                        $where_clause .= $relation . " ";

                        if( $next_condition['relation'] === "OR" && empty($startcombine) ){
                            $startcombine = " ( ";
                            $where_clause .= $startcombine;
                        }

                        $where_clause .= $column . " " . $compare . " " .   $value . " ";

                        if( ( empty($next_condition) || $next_condition['relation'] !== "OR" ) && !empty($startcombine) ){
                            $where_clause .= " ) ";
                            $startcombine = '';
                        }

                        $lastrelation = $relation;
                    }
                }


                $whereIterator->next();

            }
        }

        $order_clause = '';

        if( !empty($order) ){
            $peices1 = explode('|', $order);
            foreach($peices1 as $peice1){

                // orderby,order,type,value
                $peices2 = explode(',', $peice1);
                $column = $peices2[0];
                $order = $orderby = '';
                $type = isset($peices2[2]) ? $peices2[2] : '';
                $value = isset($peices2[3]) ? $peices2[3] : '';

                if( !empty($column) && !empty($meta_keys[$column]) ){
                    $type = $this->get_cast_for_type($type);
                    $orderby = "CAST(".$column." AS {$type})";
                    $order = isset($peices2[1]) ? ( ('ASC' !== strtoupper($peices2[1]) ) ? 'DESC' : $peices2[1] )  : 'DESC';

                    if( !empty($value) ){
                        $order_clause .= " CASE
                                            WHEN ".$orderby." = '".$value."' THEN 1
                                            WHEN ".$orderby." LIKE '".$value."%' THEN 2
                                            ELSE 3
                                        END " . $order;

                            break; // for now we only support one order by clause.

                    } else {
                        $order_clause .= ( !empty($orderby) ? ( !empty($order) ? $orderby . " ". $order . ',' : '' ) : '');
                    }


                }


            }

            if( !empty($order_clause) ){
                $order_clause = rtrim($order_clause, ',');
                $order_clause = " ORDER BY " . $order_clause . " ";
            }
        } else {
            $order_clause = ' ORDER BY ID DESC ';
        }

        /************************************ LIMIT ************************************/
        /*$offset = (int) $offset;
        $limit = (int) $limit;

        if( $limit > 0 ){
            $limit_clause = "LIMIT " . $offset . ", " . $limit . " ";
        } else if( $offset > 0 && $limit <= 0 ){
            $limit = 18446744073709551615;
            $limit_clause = "LIMIT " . $offset . ", " . $limit . " ";
        }*/
        /************************************ LIMIT ************************************/

        if( true === $ids_only ){
            $select_clause = " SELECT " . $fields;
        } else {
            $select_clause = " SELECT " . $fields;
        }

        /********************************************begin meta_query*******************************************/
        $query_where = '';
        $wp_query = '';

        if( isset($queryObject) && !is_null($queryObject) ) {
            //ffd_listingprice_pb
            if (isset($queryObject["ffd_listingprice_pb"]) && $queryObject["ffd_listingprice_pb"] != "") {
                $values = explode(' ', $queryObject["ffd_listingprice_pb"]);
                $meta_query[] = array(
                    'relation' => 'AND',
                    array(
                        'key' => 'ffd_listingprice_pb',
                        'value' => array($values[0], $values[1]),
                        'compare' => 'BETWEEN',
                        'type' => 'numeric'
                    )
                );
            }
            //ffd_propertytype
            if (isset($queryObject["ffd_propertytype"]) && $queryObject["ffd_propertytype"] != "") {
                $values = explode(';', $queryObject["ffd_propertytype"]);
                $meta_query[] = array(
                    'relation' => 'AND',
                    array(
                        'key' => 'ffd_propertytype',
                        'value' => $values,
                        'compare' => 'IN'
                    )
                );
            }
            //ffd_bedrooms_pb
            if (isset($queryObject["ffd_bedrooms_pb"]) && $queryObject["ffd_bedrooms_pb"] != "") {
                $values = explode(' ', $queryObject["ffd_bedrooms_pb"]);
                if ( $values[0] == 'any' && $values[1] == 'any' ){
                    $meta_query[] = array(
                        'relation' => 'AND',
                        array(
                            'key'     => 'ffd_bedrooms_pb',
                            'value'   => 6,
                            'compare' => '<=',
                            'type' => 'numeric'
                        )
                    );
                }else {
                    $meta_query[] = array(
                        'relation' => 'AND',
                        array(
                            'key' => 'ffd_bedrooms_pb',
                            'value' => array($values[0], $values[1]),
                            'compare' => 'BETWEEN',
                            'type' => 'numeric'
                        )
                    );
                }
            }
            //ffd_fullbathrooms_pb
            if (isset($queryObject["ffd_bathrooms_pb"]) && $queryObject["ffd_bathrooms_pb"] != "") {
                $values = explode(' ', $queryObject["ffd_bathrooms_pb"]);
                if ( $values[0] == 'any' && $values[1] == 'any' ){
                    $meta_query[] = array(
                        'relation' => 'AND',
                        array(
                            'key' => 'ffd_fullbathrooms_pb',
                            'value' => 6,
                            'compare' => '<=',
                            'type' => 'numeric'
                        )
                    );
                }else {
                    $meta_query[] = array(
                        'relation' => 'AND',
                        array(
                            'key' => 'ffd_fullbathrooms_pb',
                            'value' => array($values[0], $values[1]),
                            'compare' => 'BETWEEN',
                            'type' => 'numeric'
                        )
                    );
                }
            }

            //ffd_yearbuilt_pb
            if (isset($queryObject["ffd_yearbuilt_pb"]) && $queryObject["ffd_yearbuilt_pb"] != "") {
                $values = explode(' ', $queryObject["ffd_yearbuilt_pb"]);
                if ( $values[0] == 'any' && $values[1] == 'any' ){
                    $meta_query[] = array(
                        'relation' => 'AND',
                        array(
                            'key' => 'ffd_yearbuilt_pb',
                            'value' => 2019,
                            'compare' => '<=',
                            'type' => 'numeric'
                        )
                    );
                }else {
                    $meta_query[] = array(
                        'relation' => 'AND',
                        array(
                            'key' => 'ffd_yearbuilt_pb',
                            'value' => array($values[0], $values[1]),
                            'compare' => 'BETWEEN',
                            'type' => 'numeric'
                        )
                    );
                }
            }

            //ffd_acres_calc
            if (isset($queryObject["ffd_acres_calc"]) && $queryObject["ffd_acres_calc"] != "") {
                $values = explode(' ', $queryObject["ffd_acres_calc"]);
                if ( $values[0] == 'any' && $values[1] == 'any' ){
                    $meta_query[] = array(
                        'relation' => 'AND',
                        array(
                            'key' => 'ffd_acres_calc',
                            'value' => 100,
                            'compare' => '<=',
                            'type' => 'numeric'
                        )
                    );
                }else {
                    $meta_query[] = array(
                        'relation' => 'AND',
                        array(
                            'key' => 'ffd_acres_calc',
                            'value' => array($values[0], $values[1]),
                            'compare' => 'BETWEEN'
                        )
                    );
                }
            }

            //ffd_parkingspaces
            if (isset($queryObject["ffd_parkingspaces"]) && $queryObject["ffd_parkingspaces"] != "") {
                $values = explode(' ', $queryObject["ffd_parkingspaces"]);
                if ( $values[0] == 'any' && $values[1] == 'any' ){
                    $meta_query[] = array(
                        'relation' => 'AND',
                        array(
                            'key' => 'ffd_parkingspaces',
                            'value' => 10,
                            'compare' => '<=',
                            'type' => 'numeric'
                        )
                    );
                }else {
                    $meta_query[] = array(
                        'relation' => 'AND',
                        array(
                            'key' => 'ffd_parkingspaces',
                            'value' => array($values[0], $values[1]),
                            'compare' => 'BETWEEN'
                        )
                    );
                }
            }

            //ffd_days_on_market
            if (isset($queryObject["ffd_days_on_market"]) && $queryObject["ffd_days_on_market"] != "") {
                //$values = explode(' ', $queryObject["ffd_days_on_market"]);
                $meta_query[] = array(
                    'relation' => 'AND',
                    array(
                        'key' => 'ffd_days_on_market',
                        'value' => intval($queryObject['ffd_days_on_market']),
                        'compare' => '<',
                        'type' => 'numeric'
                    )
                );
            }

            //ffd_community_features
            if (isset($queryObject["ffd_community_features"]) && $queryObject["ffd_community_features"] != "") {
                $values = explode(';', $queryObject["ffd_community_features"]);
                $meta_query[] = array(
                    'relation' => 'AND',
                    array(
                        'key' => 'ffd_community_features',
                        'value' => $values,
                        'compare' => 'IN'
                    )
                );
            }

            //ffd_waterfront
            if (isset($queryObject["ffd_waterfront"]) && $queryObject["ffd_waterfront"] != "") {
                $values = explode(';', $queryObject["ffd_waterfront"]);
                $meta_query[] = array(
                    'relation' => 'AND',
                    array(
                        'key' => 'ffd_waterfront',
                        'value' => $values,
                        'compare' => 'IN'
                    )
                );
            }

            //ffd_view
            if (isset($queryObject["ffd_view"]) && $queryObject["ffd_view"] != "") {
                $values = explode(';', $queryObject["ffd_view"]);
                $meta_query[] = array(
                    'relation' => 'AND',
                    array(
                        'key' => 'ffd_view',
                        'value' => $values,
                        'compare' => 'IN'
                    )
                );
            }

            //ffd_exterior_features
            if (isset($queryObject["ffd_exterior_features"]) && $queryObject["ffd_exterior_features"] != "") {
                $values = explode(';', $queryObject["ffd_exterior_features"]);
                $meta_query[] = array(
                    'relation' => 'AND',
                    array(
                        'key' => 'ffd_exterior_features',
                        'value' => $values,
                        'compare' => 'IN'
                    )
                );
            }

            //ffd_interior_features
            if (isset($queryObject["ffd_interior_features"]) && $queryObject["ffd_interior_features"] != "") {
                $values = explode(';', $queryObject["ffd_interior_features"]);
                $meta_query[] = array(
                    'relation' => 'AND',
                    array(
                        'key' => 'ffd_interior_features',
                        'value' => $values,
                        'compare' => 'IN'
                    )
                );
            }

            //ffd_city_pb
            if (isset($queryObject["ffd_city_pb"]) && $queryObject["ffd_city_pb"] != "") {
                $values = explode(';', $queryObject["ffd_city_pb"]);
                $meta_query[] = array(
                    'relation' => 'AND',
                    array(
                        'key' => 'ffd_city_pb',
                        'value' => $values,
                        'compare' => 'IN'
                    )
                );
            }
            //ffd_postalcode_pb
            if (isset($queryObject["ffd_postalcode_pb"]) && $queryObject["ffd_postalcode_pb"] != "") {
                $values = explode(';', $queryObject["ffd_postalcode_pb"]);
                $meta_query[] = array(
                    'relation' => 'AND',
                    array(
                        'key' => 'ffd_postalcode_pb',
                        'value' => $values,
                        'compare' => 'IN'
                    )
                );
            }
            //ffd_state_pb
            if (isset($queryObject["ffd_state_pb"]) && $queryObject["ffd_state_pb"] != "") {
                $values = explode(';', $queryObject["ffd_state_pb"]);
                $meta_query[] = array(
                    'relation' => 'AND',
                    array(
                        'key' => 'ffd_state_pb',
                        'value' => $values,
                        'compare' => 'IN'
                    )
                );
            }

            //ffd_state_pb
            if (isset($queryObject["ffd_status"]) && $queryObject["ffd_status"] != "") {
                $values = explode(';', $queryObject["ffd_status"]);
                $meta_query[] = array(
                    'relation' => 'AND',
                    array(
                        'key' => 'ffd_status',
                        'value' => $values,
                        'compare' => 'IN'
                    )
                );
            }

            /*********************************************************************************************************************/
            //ffd_city_pb
            if (isset($queryObject["ffd_city_rb"]) && $queryObject["ffd_city_rb"]!=""){
                $meta_query[] = array(
                    'relation' => 'AND',
                    array(
                        'key'     => 'ffd_city_pb',
                        'value'   => (explode(',',$queryObject["ffd_city_rb"]))[0],
                        'compare' => '='
                    )
                );
            }
            //ffd_state_pb
            /*if (isset($queryObject["ffd_state_rb"]) && $queryObject["ffd_state_rb"]!=""){
                $meta_query[] = array(
                    'relation' => 'AND',
                    array(
                        'key'     => 'ffd_state_pb',
                        'value'   => $queryObject["ffd_state_rb"],
                        'compare' => '='
                    )
                );
            }*/
            //ffd_postalcode_pb
            if (isset($queryObject["ffd_postalcode_rb"]) && $queryObject["ffd_postalcode_rb"]!=""){
                $meta_query[] = array(
                    'relation' => 'AND',
                    array(
                        'key'     => 'ffd_postalcode_pb',
                        'value'   => $queryObject["ffd_postalcode_rb"],
                        'compare' => '='
                    )
                );
            }
            //ffd_subdivision
            if (isset($queryObject["ffd_subdivision"]) && $queryObject[""]!="ffd_subdivision"){
                $meta_query[] = array(
                    'relation' => 'AND',
                    array(
                        'key'     => 'ffd_subdivision',
                        'value'   => $queryObject["ffd_subdivision"],
                        'compare' => '='
                    )
                );
            }
            /*********************************************************************************************************************/

            /*end meta_query*/

            $meta_query[] = array(
                'relation' => 'AND',
                array(
                    'key'     => 'ffd_system_source',
                    'value'   => 'trestle',
                    'compare' => '='
                )
            );

            $args=array(
                'post_type' =>  'listing',
                'post_status' => 'publish',
                'meta_query' => $meta_query,
                'cache_results'=> false,
                'posts_per_page'=>100,
                'fields' => 'ids'
            );

            $wp_query = new WP_Query($args);

        }
        //var_dump('$query',$query);exit;

        $size_where = 0;
        foreach ($where as $w){
            if(is_array($w)){
                $size_where += count($w);
            }else{
                $size_where +=1;
            }

        }

        $lm_where = '';
        if($size_where == 2) {
            if (!isset($queryObject) && is_null($queryObject)) {//empty($query_where)
                $lm_master_search = json_decode(get_option('ap_master_search'), true);

                foreach ($lm_master_search as $key => $search) {
                    /*if ($key == 'ffd_architectural_style' and ($search != 'None' or $search != '')) {
                        $lm_where .= " AND ". $key . "='" . $search . "'";
                    } else */
                    if ($key == 'ffd_listingprice_pb' and $search != '') {
                        $lm_where .= " and " . $key . ">=" . $search;
                    }
                }
            }
        }

        $where_clause .=  $lm_where;
        $where_clause .=  $query_where;

        /************************************************* TOTAL LISTING *************************************************/

        $query = "SELECT count(ID) FROM " . $destination_table . " as ".$destination_alias." " . $where_clause;

        $page = (int)(!isset($_REQUEST['page'])) ? 1 : $_REQUEST['page'];

        $total_registration = $wpdb->get_var($query);

        $total_number_records = ceil(intval($total_registration) / $limit);

        /************************************ LIMIT ************************************/
        $offset = (int) (($page-1)*$limit);
        $limit = (int) $limit;

        $range = 5;
        $partition = ceil($range / 2);

        if( $limit > 0 ){
            $limit_clause = "LIMIT " . $offset . ", " . $limit . " ";
        } else if( $offset > 0 && $limit <= 0 ){
            $limit = 18446744073709551615;
            $limit_clause = "LIMIT " . $offset . ", " . $limit . " ";
        }
        /************************************ LIMIT ************************************/

        /****************************** Section Pagination *****************************/

        $IncrimentNum = (($page + 1) <= $total_number_records) ? ($page + 1) : 1;
        $DecrementNum = ($page - 1) < 1 ? 1 : ($page - 1);
        if ($page != 1) {
            $this->paginator .= '<ul><li data-paginator="ffdpaginator" data-ffdevent="click" class="btn" data-value="'.$DecrementNum.'">Previous</li>';
        }


        $from  = $page - (ceil($limit/2) - 1);
        $to = $page + (ceil($limit/2) - 1);

        //Validation
        $from = ($from < 1) ? 1 : $from;
        $to = ($to < $limit) ? $limit : $to;
        //var_dump('VARIABLES', $total_number_records, $from, $to, $limit, $offset, $page);exit;
        //Number Page
        for($i = $from; $i <= $to; $i++){

            if( $i <= $total_number_records ){

                /*if( $i == $page ){
                    $this->paginator .= '<li data-paginator="ffdpaginator" data-ffdevent="click" class="active" style="display: inline; padding: 0 5px; cursor: pointer;" data-value="'.$i.'">'.$i.'</li>';
                }else {
                    $this->paginator .= '<li data-paginator="ffdpaginator" data-ffdevent="click" style="display: inline; padding: 0 5px; cursor: pointer;" data-value="'.$i.'">'.$i.'</li>';
                }*/

                if( $page <= 3 && $page <= $range ){
                    if( $i <= $range ){
                        if($i == $page) {
                            $this->paginator .= '<li data-paginator="ffdpaginator" data-ffdevent="click" class="page-items active" data-value="'.$i.'">'.$i.'</li>';
                        } else {
                            $this->paginator .= '<li data-paginator="ffdpaginator" data-ffdevent="click" class="page-items" data-value="'.$i.'">'.$i.'</li>';
                        }

                    }
                }else{
                    if ($i < $page + $partition && $i > $page - $partition ){
                        if($i == $page) {
                            $this->paginator .= '<li data-paginator="ffdpaginator" data-ffdevent="click" class="page-items active" data-value="'.$i.'">'.$i.'</li>';
                        } else {
                            $this->paginator .= '<li data-paginator="ffdpaginator" data-ffdevent="click" class="page-items" data-value="'.$i.'">'.$i.'</li>';
                        }
                    }
                }

            }
        }
        if ($page <= $total_number_records - $partition) {
            $this->paginator .= '<li data-paginator="ffdpaginator" data-ffdevent="click" class="btn" data-value="' . $IncrimentNum . '">Next</li></ul>';
        }
        $this->number_properties = intval($total_registration);
        /****************************** Section Pagination *****************************/

        /************************************************* TOTAL LISTING *************************************************/

        $select_clause = apply_filters('ffd_search_query_select', $select_clause, $select);
        $where_clause  = apply_filters('ffd_search_query_where', $where_clause, $where);
        $order_clause  = apply_filters('ffd_search_query_order', $order_clause, $order);
        $limit_clause  = apply_filters('ffd_search_query_limit', $limit_clause, $limit, $offset);

        if( isset($queryObject) && !is_null($queryObject) ) {
           $query = $wp_query->request;
        }else{
            $query = $select_clause . " FROM " . $destination_table . " as ".$destination_alias." " . $where_clause . $order_clause . $limit_clause;
        }

        //var_dump($query);exit;
        $query = apply_filters('ffd_search_query_sql', $query);
        $data = $wpdb->get_results($query, "ARRAY_A");

        //echo $query;  exit;

        do_action('ffd_logger', array('[FFD SEARCH Query]' => $query, 'error'=>$wpdb->last_error));

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
        do_action('ffd_logger', array('[FFD Create Data Table Query]' => $wpdb->last_error, 'result'=>$wpdb->last_result));

        $insert_data = "INSERT INTO {$destination_table} " . $sql ;
        $wpdb->query($insert_data);

        do_action('ffd_logger', array('[FFD Insert Data to Table Query]' => $wpdb->last_error, 'result'=>$wpdb->last_result));
    }


    public function get_db_tablename(){



        return $this->tablename;
    }

    public function get_listing_post_type(){

        $post_type = apply_filters('ffd_listing_posttype_slug', 'listing');

        return $post_type;
    }


    public function get_listing_fields(){

        if( !isset($this->listing_fields) || empty($this->listing_fields) ){
            $this->set_listing_fields();
        }

        return $this->listing_fields;
    }
    public function set_listing_fields(){

        /*Original para Propertybase
        $field_mapping = FFD_Listings_Sync::fields('propertybase', 'meta');
        */

        //Nuevo para Trestle
        $field_mapping = FFD_Listings_Trestle_Sync::fields('trestle', 'meta');

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
            'halfbaths      =>  'pba__halfbathrooms_pb__c',
            'lotsize'          =>  'pba__lotsize_pb__c',
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
        $args = apply_filters( 'ffd_search_form_field_args', $args, $key, $value );



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

            $field_html .=  $field;
            //$field_html .= '<span class="ffd-input-wrapper">' . $field;

            if ( $args['description'] ) {
                $field_html .= '<span class="description" id="' . esc_attr( $args['id'] ) . '-description" aria-hidden="true">' . wp_kses_post( $args['description'] ) . '</span>';
            }

            //$field_html .= '</span>';

            $container_class = esc_attr( implode( ' ', $args['class'] ) );
            $container_id    = esc_attr( $args['id'] ) . '_field';
            $field           = $field_html;
            //$field           = sprintf( $field_container, $container_class, $container_id, $field_html );
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


    public function sql_escape($str){

        $find = array('*', '?', '+', '[', '(', ')', '{', '}', '^', '$', '|', '.');
        $replace = array_map(function($str){
            return '\\' . $str;
        }, $find);

        return str_replace($find, $replace, $str);
    }

    public function build_where_condition($value, $context="db", $column=''){

        $pieces = explode('|', $value);
        $value = $this->sql_escape($pieces[0]);

        // value|compare|type|relation
        $compare = isset($pieces[1]) ? ( '' === $pieces[1] ? '=' : $pieces[1] ) : '=';
        $type = isset($pieces[2]) ? ( '' === $pieces[2] ? 'CHAR' : $pieces[2] ) : 'CHAR';
        $relation = isset($pieces[3]) ? ( '' === $pieces[3] ? 'AND' : $pieces[3] ) : 'AND';

        $compare = $this->get_value_for_compare($compare);





        if( "db" == $context ){
            if( in_array($compare, array('IN', 'NOT IN')) ){
                $value = explode(',', $value);
                $value = array_map('trim', $value);
                $value = "('" . implode("', '", $value) . "')";
            } else if( in_array($compare, array('INCLUDES', 'NOT INCLUDES')) ){

                //INCLUDES is not really an sql operator
                $compare = str_replace('INCLUDES', 'REGEXP', $compare);

                $value = explode(',', $value);
                $value = array_map('trim', $value);

                $str = '';


                if( !empty($column) ){
                    $appnd = "AND " . $column . " " . $compare . " ";
                    $count = count($value);
                    $i = 1;
                    foreach( $value as $k => $v ){
                        $str .= "'" . $v . "' ";
                        if( $i < $count ){
                            $str .= $appnd;
                        }
                        //INSTR(column, $v) AND ';
                        $i++;
                    }
                    //$str = $this->rightTrim($str, $appnd);
                    $value = $str;
                }


            } else if( in_array($compare, array('INLIKE', 'NOT INLIKE')) ){

                //INLIKE is not really an sql operator
                $compare = str_replace('INLIKE', 'LIKE', $compare);

                $value = explode(',', $value);
                $value = array_map('trim', $value);

                $str = '';


                if( !empty($column) ){
                    $appnd = "AND " . $column . " " . $compare . " ";
                    $count = count($value);
                    $i = 1;
                    foreach( $value as $k => $v ){
                        $str .= "'%" . $v . "%' ";
                        if( $i < $count ){
                            $str .= $appnd;
                        }
                        //INSTR(column, $v) AND ';
                        $i++;
                    }
                    //$str = $this->rightTrim($str, $appnd);
                    $value = $str;
                }


            } else if( in_array($compare, array('REGEXPIN', 'NOT REGEXPIN')) ){
                //REGEXPIN is not really an sql operator
                $compare = str_replace('REGEXPIN', 'REGEXP', $compare);

                $value = explode(',', $value);
                $value = array_map(array($this, 'prepare_regexpin'), $value);
                $value = "'" . implode("|", $value) . "'";

            } else if( in_array($compare, array('LIKE', 'NOT LIKE')) ){
                $value = "'%" . $value . "%'";
            } else  {
                $value = "'" . $value . "'";
            }

            $value = str_replace(array('__EMPTY__', '_EMPTY_'), '', $value);
            $value = str_replace(array('__ZERO__', '_ZERO_'), '0', $value);

        } else {

            if( in_array($compare, array('IN', 'NOT IN', 'INLIKE', 'NOT INLIKE', 'INCLUDES', 'NOT INCLUDES', 'REGEXPIN', 'NOT REGEXPIN')) ){
                $value = explode(',', $value);
                $value = implode(';', $value);
                $compare = '=';
            } else if(in_array($compare, array('LIKE', 'NOT LIKE'))){
                $compare = '=';
            }

            $value = str_replace(array('__EMPTY__', '_EMPTY_'), '', $value);
            $value = str_replace(array('__ZERO__', '_ZERO_'), '0', $value);
        }




        return array('value'=>$value, 'compare'=>$compare, 'type'=>$type, 'relation'=>$relation);

    }

    public function prepare_regexpin($value){

        $value = '' . trim($value) . '';


        return $value;

    }


    /**
     * @param string    $str           Original string
     * @param string    $needle        String to trim from the end of $str
     * @param bool|true $caseSensitive Perform case sensitive matching, defaults to true
     * @return string Trimmed string
     */
    function rightTrim($str, $needle, $caseSensitive = true)
    {
        $strPosFunction = $caseSensitive ? "strpos" : "stripos";
        if ($strPosFunction($str, $needle, strlen($str) - strlen($needle)) !== false) {
            $str = substr($str, 0, -strlen($needle));
        }
        return $str;
    }

    /**
     * @param string    $str           Original string
     * @param string    $needle        String to trim from the beginning of $str
     * @param bool|true $caseSensitive Perform case sensitive matching, defaults to true
     * @return string Trimmed string
     */
    function leftTrim($str, $needle, $caseSensitive = true)
    {
        $strPosFunction = $caseSensitive ? "strpos" : "stripos";
        if ($strPosFunction($str, $needle) === 0) {
            $str = substr($str, strlen($needle));
        }
        return $str;
    }

    public function get_cast_for_type( $type = '' ) {
		if ( empty( $type ) ) {
			return 'CHAR';
		}

        $char_type = strtoupper( $type );

        if( in_array($char_type, array('DECIMAL', 'NUMERIC') ) ){
            $params = explode('-', $char_type);
            $char_type = $params[0];

            if( count($params) > 1 ){
                $precision = 10;
                $scale = 0;

                if( isset($params[1]) )
                    $precision = $params[1];

                if( isset($params[2]) )
                    $scale = $params[2];

                $char_type = $char_type . '(' . $precision . ',' . $scale . ')';
            }
        }


		if ( ! preg_match( '/^(?:BINARY|CHAR|DATE|DATETIME|SIGNED|UNSIGNED|TIME|NUMERIC(?:\(\d+(?:,\s?\d+)?\))?|DECIMAL(?:\(\d+(?:,\s?\d+)?\))?)$/', $char_type ) ) {
			return 'CHAR';
		}

		if ( 'NUMERIC' == $char_type ) {
			$char_type = 'SIGNED';
		}

		return $char_type;
    }

    public function get_value_for_compare($compare){

        $compare = strtoupper($compare);

        switch ($compare) {
            case 'GT': $compare = ">";break;
            case 'GTE': $compare = ">=";break;
            case 'LT': $compare = "<";break;
            case 'LTE': $compare = "<=";break;
            case 'EQ': $compare = "=";break;
            case 'NE': $compare = "!=";break;
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
                'INCLUDES',
                'NOT INCLUDES',
                'INLIKE',
                'NOT INLIKE',
                'REGEXPIN',
                'NOT REGEXPIN',
                'RLIKE',
            )
        ) ) {
            $compare = '=';
        }

        return $compare;
    }

    public function get_settings($name=''){


        $value  = get_option($name);
        if( $name == 'ffd_gmap_api_key' && empty($value) ){

            if ( strpos($_SERVER['HTTP_HOST'], "localhost") !== false  || strpos(home_url(), "frozenfishdev") !== false ) {
                $value = "AIzaSyDhe5CzOQ8VumIh5hggC0I1-OJCV0y0wm0";
            }

        }

        return $value;
    }


    public function show_errors(){
        ini_set('display_errors', 1);
        error_reporting(E_ALL);
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
