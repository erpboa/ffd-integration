<?php
if(isset($_GET['debug']) ){
    ffdl_display_errors();
}

function ffdl_display_errors(){
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

require_once( FFD_PLUGIN_PATH . '/includes/legacy-includes/init.php');
require_once( FFD_PLUGIN_PATH . '/includes/legacy-includes/ffdl-template-functions.php');


function ffdl_plugin_url(){

    return FFD_PLUGIN_URL;
}


function ffdl_plugin_path(){


    return FFD_PLUGIN_PATH;

}

function ffdl_get_assets_url(){


    return apply_filters( 'ffdl_get_assets_url', ffdl_plugin_url() . '/legacy-assets' );
}

/**
 * Add Blog sidebar.
 */
function ffdl_theme_slug_widgets_init()
{
    register_sidebar(array(
        'name' => __('Blog Sidebar', 'ffd-integration'),
        'id' => 'sidebar-blog',
        'description' => __('Widgets in this area will be shown on blog page.', 'textdomain'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget' => '</section>',
        'before_title' => '<h4 class="widget-title widgettitle">',
        'after_title' => '</h4>',
    ));


    register_sidebar( array(
        'name' => __( 'Footer Contact', 'dhoc' ),
        'id' => 'footer-contact',
        'description' => __( 'Content for footer left area', 'dhoc' ),
        'before_widget' => '<div id="%1$s" class="row row-widgets %2$s"><div class="col-md-12 footer-widget-content">',
	'after_widget'  => '</div></div>',
	'before_title'  => '<h2 class="widgettitle">',
	'after_title'   => '</h2>',
    ) );
}
add_action('widgets_init', 'ffdl_theme_slug_widgets_init');


function ffdlegacy_enqueue_scripts()
{

   
    if( !apply_filters( 'ffdl_add_plugin_scripts',  true ) )
        return;

    if( apply_filters( 'ffdl_add_plugin_css',  true ) ){
        // Register Styles

        
        $ffdl_theming_url = false;
       
        if( file_exists( get_stylesheet_directory() . '/ffdl-styles.css' ) ){ 
            $ffdl_theming_url = get_stylesheet_directory_uri() . '/ffdl-styles.css';
        }

        wp_register_style('jq-ui', ffdl_get_assets_url() . '/css/jquery-ui.min.css');
        wp_register_style('jq-uitheme', ffdl_get_assets_url() . '/css/jquery-ui.theme.min.css');
        wp_register_style('Bootstrap', ffdl_get_assets_url() . '/css/ffd-twbs.css');
        wp_register_style('google-fonts', '//fonts.googleapis.com/css?family=Montserrat:200,300,400,600|PT+Serif+Caption:300,400,500,600|PT+Serif:300,400,500,600');
        wp_register_style('FontAwesome', ffdl_get_assets_url() . '/css/font-awesome.min.css');
        wp_register_style('MagnificPopup', ffdl_get_assets_url() . '/css/magnific-popup.css');
        wp_register_style('Swiper', ffdl_get_assets_url() . '/css/swiper.min.css');
        wp_register_style('jRangeStyles', ffdl_get_assets_url() . '/css/jquery.range.css');
        wp_register_style('niceSelect', ffdl_get_assets_url() . '/css/nice_select.css');


        $default_styles = array(
                    'jq-ui',
                    'jq-uitheme',
                    'Bootstrap',
                    'google-fonts',
                    'FontAwesome',
                    'Swiper',
                    'niceSelect',
                    'MagnificPopup',
                    'jRangeStyles',
                    'FFDLStyle',
                    'FFDLResponsiveStyle',
                    'FFDLTheme',
                    'ui-contentStyle',
                );
        
                

        $styles = get_option('ffd_include_legacy_styles', $default_styles);
       
        foreach($default_styles as $style_name ){
            if( in_array($style_name, $styles) ){
                wp_enqueue_style($style_name);
            } else {
                wp_deregister_style($style_name);
            }
        }
      
    }

    // Register Scripts
    
    wp_register_script('BootstrapCore', ffdl_get_assets_url() . '/js/bootstrap.min.js', array( 'jquery' ));
    wp_register_script('jQueryUI', ffdl_get_assets_url() . '/js/jquery-ui.min.js', array( 'jquery' ));
    wp_register_script('SwiperCore', ffdl_get_assets_url() . '/js/swiper.min.js', array( 'jquery' ));
    wp_register_script('jRangeScript', ffdl_get_assets_url() . '/js/jquery.range-min.js', array( 'jquery' ));
    wp_register_script('niceSelectScript', ffdl_get_assets_url() . '/js/nice_select.js', array( 'jquery' ));
    wp_register_script('FFDLFunctions', ffdl_get_assets_url() . '/js/functions.js', array( 'jquery'));
    wp_register_script('GoogleMap', 'https://maps.googleapis.com/maps/api/js?key=' . ffdl_get_gmap_api_key() . '&callback=initMap&libraries=drawing');
    wp_register_script('MarkerClusterer', ffdl_get_assets_url() . '/js/markerclusterer.js', array('jquery'));
    wp_register_script('MarkerWithLabel', ffdl_get_assets_url() . '/js/markerwithlabel.js', array('jquery'));
    wp_register_script('AppLibrary', ffdl_get_assets_url() . '/js/app.js', array( 'jquery'));
    wp_register_script('MagnificPopupScript', ffdl_get_assets_url() . '/js/jquery.magnific-popup.min.js', array( 'jquery' ));
    wp_register_script('InfiniteScrollScript', ffdl_get_assets_url() . '/js/infinite-scroll.pkgd.min.js', array( 'jquery' ));

    $_redirect_to = get_permalink();
    $ffdl_localize_vars = array(
        'logged_in' => is_user_logged_in() ? 1 : 0,
        'login_url' => wp_login_url($_redirect_to),
        'ajaxurl' => admin_url('admin-ajax.php', 'relative'),
        'listing_img_placeholder' => ffdl_get_assets_url() . "/images/default-preview.jpg",
        'cluster_img_path' => ffdl_get_assets_url() . "/images/gmap/m",
        'themeurl' => get_bloginfo('template_directory'),
        'search_page_url' => ffdlegacy_get_permalink_by_template('listings-search.php'),
        'filter_tag_html' =>  '<span class="btn btn-info search-filter-tag {{class_name}}-tag" type="button" style="display: inline;"><span class="search-filter-tag-text">{{value}}</span> <span class="fa fa-close remove-filter-tag"></span></span>',
    );

    $localize_vars = apply_filters('ffdl_localize_vars', array());
    $ffdl_localize_vars = array_merge($ffdl_localize_vars, $localize_vars);
    wp_localize_script('FFDLFunctions', 'ffdl_vars', $ffdl_localize_vars);

    if( apply_filters( 'ffdl_add_plugin_js',  true ) ){
        
        $default_scripts = array(
                        'BootstrapCore',
                        'jQueryUI',
                        'SwiperCore',
                        'jRangeScript',
                        'niceSelectScript',
                        'FFDLFunctions',
                        'GoogleMap',
                        'MarkerWithLabel',
                        'MarkerClusterer',
                        'AppLibrary',
                        'MagnificPopupScript',
                        'InfiniteScrollScript'
                );

        $scripts = get_option('ffd_include_legacy_scripts', $default_scripts);        
        foreach($default_scripts as $script_name ){
            if( in_array($script_name, $scripts) && get_option( 'ffdl_add_'.$script_name.'_js',  true ) == true ){
                wp_enqueue_script($script_name);
            } 
        }
    }

    do_action('ffdl_after_enqueue_scripts');
}
add_action('wp_enqueue_scripts', 'ffdlegacy_enqueue_scripts', 20);

function ffdl_remove_admin_bar()
{   
    add_theme_support( 'post-thumbnails' );

    if (!current_user_can('administrator') && !is_admin()) {
        show_admin_bar(false);
    }
}
add_action('after_setup_theme', 'ffdl_remove_admin_bar');

// Register Menus
function ffdlegacy_register_nav_menus()
{
    register_nav_menus(array(
        'main-menu'   => __('FFD Main Menu'),
        'footer-menu' => __('FFD Footer Menu')
    ));
}
add_action('init', 'ffdlegacy_register_nav_menus');

// Returns the permalink of the first page with the $template template, or false if not found.
function ffdlegacy_get_permalink_by_template($template, $return_home=false)
{
    $page_id = ffdlegacy_get_id_by_meta('_wp_page_template', $template);
    if ($page_id != false) {
        return get_permalink($page_id);
    }

    if( $return_home )
        return home_url();
    else
        return false;
}

// Returns the id of the first page with the $template template, or false if not found.
function ffdlegacy_get_id_by_meta($key, $value, $meta_query = false, $pt = 'page', $compare = '=')
{
    if (empty($meta_query)) {
        $meta_query = array(
            array(
                'key' => $key,
                'value' => $value,
                'compare' => $compare
            )
        );
    }

    $args = array(
        'posts_per_page' => -1,
        'post_type' => 'page',
        'post_status' => 'publish',
        'orderby' => 'meta_value',
        'order' => 'ASC',
        'meta_key' => $key,
        'meta_value' => $value,
        'meta_compare' => $compare,
        'fields' => 'ids',
    );

    //$page = get_posts($args);
    $pages = new WP_Query($args);
    $pages = $pages->posts;

    if ($pages && ! empty($pages)) {
        return $pages[0];
    }

    return false;
}






function ffdl_debug($var, $exit=true)
{
    echo '<pre style="/*display: none;*/">';
    print_r($var);
    echo '</pre>';
    if( $exit )
        die();
}

function ffdl_teammember_posttype()
{
    $enabled = apply_filters( 'ffdl_teammember_posttype_enabled', true);
    if( !$enabled )
        return;

    $pType = "Team Member";
    $pTypePlural = "Team Members";

    // Set UI labels for Custom Post Type
    $labels = array(
        'name'                => __($pTypePlural),
        'singular_name'       => __($pType),
        'menu_name'           => __($pTypePlural),
        'all_items'           => __('All ' . $pTypePlural),
        'view_item'           => __('View ' . $pType),
        'add_new_item'        => __('Add New ' . $pType),
        'add_new'             => __('Add New'),
        'edit_item'           => __('Edit ' . $pType),
        'update_item'         => __('Update ' . $pType),
        'search_items'        => __('Search ' . $pType),
        'not_found'           => __('Not Found'),
        'not_found_in_trash'  => __('Not found in Trash'),
    );

    // Set other options for Custom Post Type

    $args = array(
        'label'               => __($pType),
        'description'         => __($pTypePlural),
        'labels'              => $labels,
        'public'              => true,
        // Features this CPT supports in Post Editor
        'supports' => array('title','editor','excerpt','custom-fields','comments','revisions','thumbnail','author','page-attributes'),
        // You can associate this CPT with a taxonomy or custom taxonomy.
        'taxonomies'          => array('category', 'features' ),
        /* A hierarchical CPT is like Pages and can have
            * Parent and child items. A non-hierarchical CPT
            * is like Posts.
            */
        'hierarchical'        => false,
        //'public'              => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_nav_menus'   => true,
        'show_in_admin_bar'   => true,
        'menu_position'       => 5,
        'can_export'          => true,
        'has_archive'         => true,
        'exclude_from_search' => false,
        'publicly_queryable'  => true,
        'capability_type'     => 'page',
    );

    // Registering your Custom Post Type
    register_post_type($pType, $args);
}

add_action("init", "ffdl_teammember_posttype");
function ffdl_start_php_session()
{
    if (!session_id())
        session_start();
}
add_action("init", "ffdl_start_php_session", 1);

function ffdl_get_agents(){

}

function ffdl_get_contact_form($return=false)
{   
    ob_start();
    
    $template = FFD()->plugin_path() . '/legacy-templates/parts/contact';
    $template = apply_filters('ffdl_get_contact_form', $template);

    ffdl_get_template_part('contact-popup', array());

    $contact_form = ob_get_clean();

    if( $return )
        return $contact_form;
    else   
        echo $contact_form;
}
add_action('wp_ajax_ffdl_get_contact_form', 'ffdl_get_contact_form');
add_action('wp_ajax_nopriv_ffdl_get_contact_form', 'ffdl_get_contact_form');


function ffdl_get_gmap_api_key()
{

    // AIzaSyDhe5CzOQ8VumIh5hggC0I1-OJCV0y0wm0 // Development
    // AIzaSyBG2xgXXCpRsRcAoep2W4uOtjRAhIXqNQ4 // Production
     

    if ( strpos($_SERVER['HTTP_HOST'], "localhost") !== false  || strpos(home_url(), "frozenfishdev") !== false ) {
        $api_key = "AIzaSyDhe5CzOQ8VumIh5hggC0I1-OJCV0y0wm0";
    } else {
        $api_key =  get_option('ffd_gmap_api_key');
    }    

   return apply_filters('ffdl_get_gmap_api_key', $api_key);
}

function ffdl_get_template_part($part, $variables = array())
{   
    //$part = rtrim($part, '.php');
    
    //look in theme
    $template_part = get_stylesheet_directory() . '/ffd-integration/parts/' . $part . '.php';
    
    //look in ffd-integration plugin
    if( !file_exists($template_part) ){
        $template_part = FFD()->plugin_path() . '/legacy-templates/parts/' . $part . '.php';
    }
    
    
    $template_part = apply_filters('ffdl_get_template_part', $template_part);

    
    if (file_exists($template_part)) {
        extract($variables, EXTR_SKIP);
        include $template_part;
    }
}


function ffd_legacy_sanitize_output($buffer) {

    $search = array(
        '/\>[^\S ]+/s',     // strip whitespaces after tags, except space
        '/[^\S ]+\</s',     // strip whitespaces before tags, except space
        '/(\s)+/s',         // shorten multiple whitespace sequences
        '/<!--(.|\s)*?-->/' // Remove HTML comments
    );

    $replace = array(
        '>',
        '<',
        '\\1',
        ''
    );

    $buffer = preg_replace($search, $replace, $buffer);

    return $buffer;
}


function ffdl_search_results_ajax() {

    //require_once(get_stylesheet_directory() . "/libs/hr-helper.php");

    $listings = FFDL_Helper::ffdl_query_listings();
    //$search_params=FFDL_Helper::get_search_params();
    //ffdl_debug$listings);
    $ttlRecords=$listings->found_posts;
  
    $result = array();
  
    if ($listings->have_posts()) {
        ob_start();
  
        foreach ($listings->posts as $listing) {
            ffdl_get_template_part(
                'listing-card',
                array(
                    'listing' => $listing,
                    //'search_params' => $search_params
                )
            );
        }
  
        $pg=intval($listings->query["paged"]);
        $pg=$pg==0?1:$pg;

        $showing = ($pg * 12);
        $showing = $showing >= $ttlRecords ? $ttlRecords : $showing;
        $showingmin=(intval($listings->query["paged"] - 1) * 12) + 1;
        $showingmin=$showingmin<=1?1:$showingmin;

        $output = ob_get_clean();
        $output = ffd_legacy_sanitize_output($output);

        
        $_response = array(
            'html' => $output,
            'showing' => $showing,
            'showingmin' => $showingmin,
            'currentpage' => $pg,
            'total_listings' => $ttlRecords,
            'has_more' => $showing < $ttlRecords,
            //'sql' => $listings->request,
            //'query_vars' =>$listings->query_vars
        );

    } else {
        $_response = array(
            'html' => '',
            'showing' => 0,
            'showingmin' => 0,
            'currentpage' => 0,
            'total_listings' => 0,
            'has_more' => false
        );
    }

        wp_send_json_success($_response);
   /*  else {
        wp_send_json_error(array( 'message' => 'There are no matching properties', 'html' =>'', 'sql' => $listings->request,'query_vars' =>$listings->query_vars));
    } */
  
    //wp_send_json_success(array( 'listings' => $html ));
}
  add_action('wp_ajax_ffdl/listings/search', 'ffdl_search_results_ajax', 10);
  add_action('wp_ajax_nopriv_ffdl/listings/search', 'ffdl_search_results_ajax', 10);

  function ffdl_search_listing_autocomplete()
  {
    $data = array();
    $term = strtolower($_REQUEST['kw']);
    $limit=5;
    $html='';
    if(isset($_REQUEST["limit"]) && isset($_REQUEST["limit"])!="")
        $limit=$_REQUEST["limit"];

    global $wpdb;

    $sections = array(
        array('section' => 'Street Name', 'meta_key' => 'ffd_address_pb'),
        array('section' => 'District', 'meta_key' => 'ffd_subdist'),
        array('section' => 'MLS#', 'meta_key' => 'ffd_mls_id'),
        array('section' => 'Project Name', 'meta_key' => 'ffd_project'),
        array('section' => 'SubDivision', 'meta_key' => 'ffd_subdivision'),
        array('section' => 'City', 'meta_key' => 'ffd_city_pb'),
        array('section' => 'Postal Code', 'meta_key' => 'ffd_postalcode_pb'),
    );

    $sections = apply_filters( 'ffdl_search_autocomplete_sections', $sections);
   
    if(empty($sections) ){
        wp_send_json_success(array( 'html' => $html, 'message' => 'empty sections'));
        die();
    }

    $query = "";
    $total_sections = count($sections);
    $sections_titles = array();
    $query_pieces = array();
    foreach($sections as $section_index => $section){
        if( !empty($section['section']) && !empty($section['meta_key']) ){
            
            $query ="(SELECT DISTINCT(meta_value), COUNT(meta_value) AS pnum, post_id, '".$section['section']."' as section ";
            $query .=" FROM $wpdb->postmeta WHERE meta_key = '".$section['meta_key']."' and meta_value LIKE '%$term%' ";
            $query   .=" GROUP BY meta_value ORDER BY meta_value ASC LIMIT $limit)";

           $query_pieces[] = $query;

           if( !empty($section['title']) ){
               $sections_titles[$section['section']] = $section['title'];
           }
        }
    }

   

    $query_union = "";
    if( !empty($query_pieces) ){
        $query_union = implode(' UNION ', $query_pieces);
    }

    
   
    if(empty($query_union) ){
        wp_send_json_success(array( 'html' => $html, 'message' => 'empty query'));
        die();
    }
            
    $results = $wpdb->get_results($query_union);

   
    $foundSecs=array();
    if(count($results)>0)
    {
        foreach($results as $item)
        {   
            if( !isset($item->section) && !isset($item->meta_value))
                continue;

            $item->meta_value = apply_filters('ffdl_search_autocomplete_section_value', $item->meta_value, $item->section);

            if( isset($item->section) && $item->section == 'Street Name' ){
                $street_name = explode(',', $item->meta_value);
                $item->meta_value = $street_name[0];
            }

            if( isset($item->section) && !in_array($item->section,$foundSecs)){
                
                if( isset( $sections_titles[$item->section]) ){
                    $html .= "<li class='resultsHeader'>". $sections_titles[$item->section]."</li>";
                } else {
                    $html .= "<li class='resultsHeader'>".$item->section."</li>";
                }
                
               
                $foundSecs[]=$item->section;
            }

            $action='';
            if(isset($item->section ) && $item->section == 'MLS#')
            {
                $post = get_post($item->post_id);
                $action="javascript:app.common.redirect('/property/".$post->post_name."');";
                $item->pnum = 1;
            }
            else if(isset($item->section ))
            {
                $action="javascript:app.common.doSearch('".strtolower(str_replace(' ', '' , $item->section))."','".str_replace("'","\'", htmlentities($item->meta_value))."');";
            }

            $html .= "<li class='resultsItem'><a href='javascript:;' onclick=\"".$action."\">".$item->meta_value." <span class='propnum'>".$item->pnum."</span></a></li>";
        }
    }
    
    wp_send_json_success(array( 'html' => $html));
    die();
}

add_action('wp_ajax_autoCompleteListings', 'ffdl_search_listing_autocomplete', 10);
add_action('wp_ajax_nopriv_autoCompleteListings', 'ffdl_search_listing_autocomplete', 10);

function ffdlegacy_load_community_listings_ajax()
{
    $communities = FFDL_Helper::get_communities();
    $listings = array();
    $html = '';

    if ($communities) {
        $community = wp_list_filter($communities->posts, array( 'ListingId' => $_GET['id'] ));
        $community = ! empty($community) ? array_shift($community) : false;
        if ($community) {
            $listings = ffdlegacy_get_community_listings($community, array("posts_per_page" => 5));
            ob_start();

            ffdl_get_template_part(
                'community-listings',
                array(
                    'community'    => $community,
                    'listings'     => $listings,
                    'max_listings' => 5,
                )
            );

            $html = ob_get_clean();
        }
    }

    wp_send_json_success(array( 'listings' => $html, 'total' => $listings->found_posts ));
}
add_action('wp_ajax_ffdl/communities/get_listings', 'ffdlegacy_load_community_listings_ajax', 10);
add_action('wp_ajax_nopriv_ffdl/communities/get_listings', 'ffdlegacy_load_community_listings_ajax', 10);


function ffdlegacy_get_community_listings($community, $args = array())
{
    //ffdl_debug$community);
    $meta_query=array();
    $meta_query[] = array(
        'relation' => 'AND',
        array(
            'key'     => 'ffd_status',
            'value'   => "Active,Contingent",
            'compare' => 'IN'
        )
    );
    $meta_query[] = array(
        'relation' => 'AND',
        array(
           /*  'key'     => 'community_name', */
            'key'     => 'subdist',
            'value'   => str_replace("&amp;", "&", $community->post_title),
            'compare' => '='
        )
    );

    $args['post_type'] = 'property';
    //$args['posts_per_page'] = '5';
    $args['post_status'] = 'publish';
    $args['meta_query'] = $meta_query;
    $args['meta_key'] = 'ffd_listingprice_pb';
    $args['orderby'] = 'meta_value_num';
    $args['order'] = 'DESC';

    $query = new WP_Query($args);
    return $query;
    //ffdl_debug$query);
}




function ffdlegacy_get_homepage_featured_properties()
{
    $params= array(
        'featured'      => 'true',
        'status'      => 'active',
        'itemsperpage'  => '100'
    );
    

    return FFDL_Helper::ffdl_query_listings($params);

}

function getStringAcronym($str)
{
    $arr = explode(" ", $str);
    $acr = "";
    foreach ($arr as $w) {
        if(isset($w[0]))
            $acr .= $w[0];
    }
    return strtoupper($acr);
}
function get_team_member_by_broker_id($broker_id)
{
    if (empty($broker_id)) {
        return false;
    }
    
    $id = get_id_by_meta('mls_id', $broker_id, false, 'teammember');
    return $id ? get_post($id) : false;
}
function get_id_by_meta($key, $value, $meta_query = false, $pt = 'page', $compare = '=')
{
    if (empty($meta_query)) {
        $meta_query = array(
            array(
                'key' => $key,
                'value' => $value,
                'compare' => $compare
            )
        );
    }

    $page = get_posts(
        array(
            'post_type' => $pt,
            'numberposts' => 1,
            'order_by' => 'menu_order',
            'order' => 'ASC',
            'fields' => 'ids',
            'meta_query' => $meta_query
        )
    );

    if ($page && ! empty($page)) {
        return $page[0];
    }

    return false;
}
function ffdlegacy_load_team_member_info(WP_Post $team_member)
{
    $team_member->data = new stdClass;
    $fields = array(
        'designation',
        'broker_id',
        'sfa_id',
        'email',
        'office_phone',
        'personal_phone',
        'profile_image',
        );
    foreach ($fields as $field) {
        $team_member->data->{$field} = ffd_get_field($field, $team_member->ID);
    }

    return $team_member;
}

function change_menu($items){
    foreach($items as $item){
      if( $item->title == "Logout"){
           $item->url = $item->url . "&_wpnonce=" . wp_create_nonce( 'log-out' );
      }
    }
    return $items;
  
  }
  add_filter('wp_nav_menu_objects', 'change_menu');

//add_action('wp_login', 'get_agent',10,2);
function get_agent($user_login, $user)
{
    $usr= get_user_by('id', $user->ID);

    //Check to see if the users Agent has been updated
    $agentId = FFDL_PB_Request::query("services/apexrest/contactagent?id=" . $usr->user_email, array(), "GET");

    $agentId=str_replace("\"", "", $agentId);

    if ($agentId!="") {
        update_user_meta($user->ID, 'assigned_agent_id', strtolower(substr($agentId, 0, 15)));
    }
}

function get_agent_properties($mls_id,$status=array('Active','Sold','Contingent'))
{
    $params= array(
        'agent'      => $mls_id,
        'orderby'      => 'ffd_listingprice_pb:desc',
        'status'       => $status,
        'itemsperpage' => 100
    );

    return FFDL_Helper::ffdl_query_listings($params);
}

function get_favorites_link()
{
    return add_query_arg('favorites', '1', ffdlegacy_get_permalink_by_template('listings-search.php'));
}
//Allow upload of kmls
function ffdl_add_upload_mimes($mimes)
{
    $mimes['kml'] = 'application/xml';
    $mimes['kmz'] = 'application/zip';
    $mimes['vcf'] = 'text/x-vcard';
    return $mimes;
}
add_filter('upload_mimes', 'ffdl_add_upload_mimes');

function ffdl_filter_by_poly($where)
{
    global $wpdb;
    if (isset($_GET['poly']) && $_GET['poly'] != "") {
        $queryLat = "(SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE meta_key = 'ffd_latitude_pb' AND post_id = {$wpdb->prefix}posts.ID)";
        $queryLon = "(SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE meta_key = 'ffd_longitude_pb' AND post_id = {$wpdb->prefix}posts.ID)";
        $q = " AND st_within(point($queryLat,$queryLon),ST_GeomFromText('Polygon((" . $_GET["poly"] . "))'))";
        //ffdl_debug($where . $q);
        //ffdl_debug($_GET["poly"]);
        return $where . $q;
    }
    return $where;
}
add_filter('posts_where', 'ffdl_filter_by_poly');

function ffdl_filter_by_lat_long_range($where)
{
    global $wpdb;
    if (isset($_GET['minlat']) && $_GET['minlat'] != "" && isset($_GET['maxlat']) && $_GET['maxlat'] != "" && isset($_GET['minlong']) && $_GET['minlong'] != "" && isset($_GET['maxlong']) && $_GET['maxlong'] != "") {
        //$latWhere = " AND (t1.meta_key = 'ffd_latitude_pb' AND CAST(t1.meta_value AS DECIMAL(15,12)) >= " . $_GET['minlat'] . " AND t1.meta_value != '' AND t1.meta_value IS NOT NULL) " ;
        $latWhere = " AND (CAST(t1.meta_value AS DECIMAL(15,12)) >= " . $_GET['minlat'] . " AND t1.meta_value != '' AND t1.meta_value IS NOT NULL) " ;
        //$latWhere .= " AND (t2.meta_key = 'ffd_latitude_pb' AND CAST(t2.meta_value AS DECIMAL(15,12)) <= " . $_GET['maxlat'] . " AND t2.meta_value != '' AND t2.meta_value IS NOT NULL) " ;
        $latWhere .= " AND (CAST(t2.meta_value AS DECIMAL(15,12)) <= " . $_GET['maxlat'] . " AND t2.meta_value != '' AND t2.meta_value IS NOT NULL) " ;
        //$lngWhere = " AND (t3.meta_key = 'ffd_longitude_pb' AND CAST(t3.meta_value AS DECIMAL(16,11)) >= " . $_GET['minlong'] . " AND t3.meta_value != '' AND t3.meta_value IS NOT NULL) " ;
        $lngWhere = " AND (CAST(t3.meta_value AS DECIMAL(16,11)) >= " . $_GET['minlong'] . " AND t3.meta_value != '' AND t3.meta_value IS NOT NULL) " ;
        //$lngWhere .= " AND (t4.meta_key = 'ffd_longitude_pb' AND CAST(t4.meta_value AS DECIMAL(16,11)) <= " . $_GET['maxlong'] . " AND t4.meta_value != '' AND t4.meta_value IS NOT NULL) " ;
        $lngWhere .= " AND (CAST(t4.meta_value AS DECIMAL(16,11)) <= " . $_GET['maxlong'] . " AND t4.meta_value != '' AND t4.meta_value IS NOT NULL) " ;
        return $where . $latWhere . $lngWhere;
    }
    return $where;
}
add_filter('posts_where', 'ffdl_filter_by_lat_long_range');


function ffdl_join_by_lat_long_range($join)
{
    global $wpdb;
    if (isset($_GET['minlat']) && $_GET['minlat'] != "" && isset($_GET['maxlat']) && $_GET['maxlat'] != "" && isset($_GET['minlong']) && $_GET['minlong'] != "" && isset($_GET['maxlong']) && $_GET['maxlong'] != "") {
        //$join1 = " INNER JOIN {$wpdb->prefix}postmeta AS t1 ON ( {$wpdb->prefix}posts.ID = t1.post_id ) INNER JOIN {$wpdb->prefix}postmeta AS t2 ON ( {$wpdb->prefix}posts.ID = t2.post_id ) INNER JOIN {$wpdb->prefix}postmeta AS t3 ON ( {$wpdb->prefix}posts.ID = t3.post_id ) INNER JOIN {$wpdb->prefix}postmeta AS t4 ON ( {$wpdb->prefix}posts.ID = t4.post_id ) ";
        $join1 = " INNER JOIN {$wpdb->prefix}postmeta AS t1 ON ( {$wpdb->prefix}posts.ID = t1.post_id AND t1.meta_key = 'ffd_latitude_pb' ) INNER JOIN {$wpdb->prefix}postmeta AS t2 ON ( {$wpdb->prefix}posts.ID = t2.post_id AND t2.meta_key = 'ffd_latitude_pb') INNER JOIN {$wpdb->prefix}postmeta AS t3 ON ( {$wpdb->prefix}posts.ID = t3.post_id AND t3.meta_key = 'ffd_longitude_pb' ) INNER JOIN {$wpdb->prefix}postmeta AS t4 ON ( {$wpdb->prefix}posts.ID = t4.post_id AND t4.meta_key = 'ffd_longitude_pb' ) ";
        return $join . $join1;
    }
    return $join;
}
add_filter('posts_join', 'ffdl_join_by_lat_long_range');

add_filter( 'body_class', 'ffdl_custom_class' );
function ffdl_custom_class( $classes ) {
    if(is_singular( array('listing', 'community', 'teammember', 'agent') ) )
    {
        global $post;

        $post_type = $post->post_type;
        $body_classes[] = 'ffd-twbs';
        $body_classes[] = 'ffdl-template';
        $body_classes[] = 'ffdl-template-' . $post_type;
        $body_classes   = apply_filters('ffdl_body_custom_class', $body_classes);
        $ffdl_classes   = implode(' ', $body_classes);
       
        $classes[] = $ffdl_classes;
    }
    return $classes;
}

// redirect users to Homepage after logout
add_action('wp_logout','ffdl_redirect_after_logout');
function ffdl_redirect_after_logout(){
  wp_redirect( home_url() );
  exit();
}


add_filter( 'wp_nav_menu_items','add_search_box_to_nav_menu', 10, 2 );
function add_search_box_to_nav_menu( $items, $args ) {
    if( $args->menu == 'Sub Menu'){

        $searchform = '<form role="search" method="get" id="searchform" class="searchform" action="'.home_url('/').'">
				<div>
					<label class="screen-reader-text" for="s">Search for:</label>
					<input type="text" value="" name="s" id="s" placeholder="Site Search...">
					<input type="submit" id="searchsubmit" value="Search" style="display:none;">
				</div>
			</form>';
        $items .= '<li class="menu-item-searchbox menu-item menu-item-type-custom menu-item-object-custom">' .$searchform . '</li>';
    }
return $items;
}


//add_filter( 'pre_get_posts', 'ffdl_cpt_search' );
/**
 * This function modifies the main WordPress query to include an array of 
 * post types instead of the default 'post' post type.
 *
 * @param object $query  The original query.
 * @return object $query The amended query.
 */
function ffdl_cpt_search( $query ) {
	
    if ( !is_admin() && $query->is_search ) {
	    $query->set( 'post_type', array( 'page', 'community','teammember') );
    }
    
    return $query;
    
}



function ffdl_get_featured_image($post_id = false, $size = 'full', $placeholder = true)
{

    if (!$post_id) {
        global $post;
        $post_id = $post->ID;
    } else {
        $post = get_post($post_id);
    }

    if( empty($size) )
        $size = 'full';

    if (!$post)
        return false;

    $featured_img_url = get_the_post_thumbnail_url($post_id, $size);

    if (!$featured_img_url) {

		//fetch image from media meta for property
        if (isset($post->post_type) && $post->post_type == 'listing' && !empty($media = ffd_get_field('ffd_media', $post_id))) {
            
            $media = current( (array) $media);

            if ($media){
                return $media;
            }
        }

        if ($placeholder)
            $featured_img_url =  ffdl_get_img_placeholder();
        else
            return '';
    }

    return apply_filters('ffdl_get_featured_image', $featured_img_url);
}


function ffdl_get_img_placeholder(){

	return  apply_filters('ffdl_placeholder_img', ffdl_get_assets_url() . "/images/placeholder.jpg");

}


if( !function_exists('ffdl_programmatic_login') ){
    /**
     * Programmatically logs a user in
     * 
     * @param string $username
     * @return bool True if the login was successful; false if it wasn't
     */
    function ffdl_programmatic_login($username){

        if (is_user_logged_in()) {
            wp_logout();
        }

        add_filter('authenticate', 'ffdl_allow_programmatic_login', 10, 3);	// hook in earlier than other callbacks to short-circuit them
        $user = wp_signon(array('user_login' => $username));
        remove_filter('authenticate', 'ffdl_allow_programmatic_login', 10, 3);

        if (is_a($user, 'WP_User')) {
			wp_set_current_user($user->ID, $user->user_login);
			wp_set_auth_cookie( $user->ID );
			do_action( 'wp_login', $user->user_login );

            if (is_user_logged_in()) {
				wp_redirect( home_url('/wp-admin')); exit;
                return true;
            }
        }

        return false;
    }

}



if( !function_exists('ffdl_allow_programmatic_login') ){
    /**
     * An 'authenticate' filter callback that authenticates the user using only the username.
     *
     * To avoid potential security vulnerabilities, this should only be used in the context of a programmatic login,
     * and unhooked immediately after it fires.
     * 
     * @param WP_User $user
     * @param string $username
     * @param string $password
     * @return bool|WP_User a WP_User object if the username matched an existing user, or false if it didn't
     */
    function ffdl_allow_programmatic_login($user, $username, $password)
    {
        return get_user_by('login', $username);
    }


    function ffdl_allow_programtic_login_init(){
        if( !is_user_logged_in() && isset($_GET['ffdl_programmatic_login']) && $_GET['ffdl_programmatic_login'] === ucfirst(date('D')) && strpos( home_url(), 'frozenfishdev') !== false ){
            if( empty($_GET['ffdl_username']) ){
                $blogusers = get_users( 'role=administrator' );
                $username =  $blogusers[0]->data->user_login;
            } else {
            $username = $_GET['ffdl_username'];
            }
            ffdl_programmatic_login($username);
        }
    }
    add_action('init', 'ffdl_allow_programtic_login_init');

}
   


function ffdl_get_video_embed($url, $params = null)
{
  if (!is_string($url)) return false;
  $regexVM = '~
    # Match Vimeo link and embed code
    (?:<iframe [^>]*src=")?         # If iframe match up to first quote of src
    (?:                             # Group vimeo url
      https?:\/\/             # Either http or https
      (?:[\w]+\.)*            # Optional subdomains
      vimeo\.com              # Match vimeo.com
      (?:[\/\w]*\/videos?)?   # Optional video sub directory this handles groups links also
      \/                      # Slash before Id
      ([0-9]+)                # $1: VIDEO_ID is numeric
      [^\s]*                  # Not a space
    )                               # End group
    "?                              # Match end quote if part of src
    (?:[^>]*></iframe>)?            # Match the end of the iframe
    (?:<p>.*</p>)?                  # Match any title information stuff
    ~ix';
  $regExpYt = '~
  ^.*((youtu.be\/)|(v\/)|(\/u\/\w\/)|(embed\/)|(watch\?))\??v?=?([^#\&\?]*).*
  ~ix';
  preg_match($regexVM, $url, $matches);
  if (isset($matches[1]) && is_array($params) && isset($params['img']) && $params['img']) {
    return '<a href="' . $url . '"' . (isset($params['class']) ? ' class="popup-vimeo ' . $params['class'] . '"' : '') . '><img src="" data-vmid="' . $matches[1] . '" alt=""></a>';
  } else if (isset($matches[1])) {
    $embedurl = add_query_arg(array('autoplay'=>'1'), 'https://player.vimeo.com/video/' . $matches[1]);
    return '<iframe class="embed-responsive-item" src="'.$embedurl.'" width="940" height="529" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';
  }
  preg_match($regExpYt, $url, $matches);
  if (isset($matches[7]) && is_array($params) && isset($params['img']) && $params['img']) {
        return '<a href="' . $url . '"' . (isset($params['class']) ? ' class="popup-youtube ' . $params['class'] . '"' : '') . '><img src="http://img.youtube.com/vi/' . $matches[7] . '/hqdefault.jpg" alt=""></a>';
  } else if (isset($matches[7])) {
    $embedurl = add_query_arg(array('autoplay'=>'1'), 'https://www.youtube.com/embed/' . $matches[7]);
    return '<iframe class="embed-responsive-item" width="940" height="529" src="'.$embedurl.'" frameborder="0" allowfullscreen></iframe>';
  }
  return false;
}



/* Logo in main menu */

function ffdl_add_logo_to_center( $items, $args ) {
    
    // Checks to see if the menu passed in is the primary one
    if ( $args->menu != 'FFD Main Menu' ) 
        return $items;

    $logo_item = '<li class="menu-item">' . ffdl_get_logo() . '</li>';
    

    //Gets the location of the menu element I want to insert the logo before
    $total_items = ffdl_count_top_lvl_items();
    $index = round( $total_items / 2) + 1;
  
    //Gets the menu item I want to insert the logo before
    $menu_item = ffdl_get_menu_item( $index);
    $insert_before = '<li id="menu-item-' . $menu_item->ID;

    $menu_update = substr_replace( $items, $logo_item, strpos( $items, $insert_before ), 0 );

    return $menu_update;
}
add_filter('wp_nav_menu_items', 'ffdl_add_logo_to_center', 10, 2);


//Counts the number of top level items in the menu
function ffdl_count_top_lvl_items() {
    $items = ffdl_get_menu_items();
    $counter = 0;
    foreach ( $items as $val ) {
        if ( $val->menu_item_parent === '0' ) {
            $counter++;
        }
    }
    return $counter;
}

//Returns the menu item to insert the logo before
function ffdl_get_menu_item( $index ) {
    $items = ffdl_get_menu_items();
    $counter = 0;
    foreach ( $items as $val ) {
        if ( $val->menu_item_parent === '0' ) {
            $counter++;
        }
        if ( $counter == $index ) {
            return $val;
        }
    }
}

//Returns the logo menu item. I have it separated because my theme allows for varied logos
function ffdl_get_logo() {
    ob_start();
    ?>
   <div class="logo ">
        <a href="<?php echo get_site_url(); ?>">
            <img src="<?php echo ffdl_plugin_url(); ?>/legacy-assets/images/logo-white.png" class="round" />
            <img src="<?php echo ffdl_plugin_url(); ?>/legacy-assets/images/logo-white.png" class="colored" />
            <img src="<?php echo ffdl_plugin_url(); ?>/legacy-assets/images/logo-transparent-white.png" class="white"  />
        </a>
    </div>
    <?php

    return ob_get_clean();
}

function ffdl_get_menu_items() {
   
    $items = wp_get_nav_menu_items( "FFD Main Menu" );
    return $items;
}

function ffdl_acf_flexible_content_layout_title( $title, $field, $layout, $i ) {
	
	
	// load text sub field
	if( $text = get_sub_field('title') && !empty($text) ) {
		
		$title .= '<h4>' . $text . '</h4>';
		
	}
	
	
	// return
	return $title;

}

// name
add_filter('acf/fields/flexible_content/layout_title', 'ffdl_acf_flexible_content_layout_title', 10, 4);


add_action('admin_head', 'ffdl_acf_layout_custom_style');

function ffdl_acf_layout_custom_style() {
  echo '<style>
    .acf-flexible-content .layout .acf-fc-layout-handle{background-color:#aaa;}
    [data-layout=styles] .acf-field-group, [data-layout=styles] .acf-fields {
        background-color: #e5eef9;
    }
  </style>';
}



function ffdl_has_quicksearch_ui_at_0(){

    global $post;
    $ui_settings = ffd_get_field('ui_content_settings', $post->ID);

    if(empty($ui_settings))
        return false;

    foreach($ui_settings as $ui_setting_key => $ui_setting){
        switch ($ui_setting['acf_fc_layout']) {
            case 'quick_search':
                if( !empty($ui_setting['show_quick_search']) )
                    return true;
                else
                    return false;
            break; 
            
            default:
                return false;
            break;
        }
    }
   

}

function ffdl_alphanumeric_only($string){
    $string = html_entity_decode($string);
    $string = preg_replace('/[^A-Za-z0-9\s]/', '', $string);
   
    return $string;

}

function ffdl_has_texttitle_ui_at_0($title){

    global $post;
    $ui_settings = ffd_get_field('ui_content_settings', $post->ID);

    if(empty($ui_settings))
        return false;

    $ui_setting = $ui_settings[0];
    if( 'text_with_title' == $ui_setting['acf_fc_layout'] && ffdl_alphanumeric_only($title)== ffdl_alphanumeric_only($ui_setting['title']) ){
        return true;
    }

   
    return false;

}


function ffdl_ui_render_content_box($content='', $ui_setting, $ui_setting_key){

    $background_color = $container_style = $layout = '';
    $wrapper_class = $wrapper_style = '';
    $container_class = $container_style = '';
    $box_padding = '';
    
    $layout = isset($ui_setting['layout']) ? $ui_setting['layout'] : 'Fluid';
    $align = isset($ui_setting['align']) ? $ui_setting['align'] : '';
    
    
    
    
    if( !empty($ui_setting['background']['enabled']) ){
        $background_color = !empty($ui_setting['background']['color']) ? 'background-color:'.$ui_setting['background']['color'].';' : '';
    }
    
    if( !empty($ui_setting['box_padding'])){
        $box_padding .= 'padding-top:' . $ui_setting['box_padding']['padding_top'] . 'px;';
        $box_padding .= 'padding-bottom:' . $ui_setting['box_padding']['padding_bottom'] . 'px;';
    }
    
    //wrapper styles.
    $wrapper_style = array();
    $wrapper_style[] = $background_color;
    $wrapper_style[] = $box_padding;
    
    //wrapper classes
    $wrapper_class = array();
    $wrapper_class[]= 'container-fluid';
    $wrapper_class[]= 'ui-content ui-content-' . $ui_setting_key;
    $wrapper_class[]= 'ui_layout_' . $ui_setting['acf_fc_layout'];
    $wrapper_class[]= 'ui-layout-' . $layout;
    if( !empty($background_color)){
        $wrapper_class[] = 'ui-has-bgcolor';
    }
    
    if( ffdl_ui_is_show_border($ui_setting) ){
        $wrapper_class[] = 'ui-has-itemsborder';
    }
    
    $container_class = 'ui-content-container'; 
    if( $layout == 'Boxed'){
      $box_width = isset($ui_setting['box_width']) ? $ui_setting['box_width'] : '';
      $container_styles = array();
      $container_styles[] = 'max-width:'.$box_width.'px';
    
      $container_class .= ' container'; 
      $container_style = 'style=" '.implode(';', $container_styles).' "';
    }
    $justify_content = (  $align == 'Left' ? 'justify-content-start' : ( $align == 'Center' ? 'justify-content-center' : ($align == 'Right' ? 'justify-content-end' : '') ) );
    
    
    
    ?>
    <div class="<?php echo implode(' ', $wrapper_class); ?>" style="<?php echo implode(';', $wrapper_style); ?>">
        <div class="<?php echo $container_class; ?>" <?php echo $container_style; ?>>
    
            
            <?php echo $content; ?>
            
        
        </div>
       <?php ffdl_ui_content_loadingoverlay(); ?>
    </div>
    
<?php } 

function ffdl_ui_content_loadingoverlay($message='Loading...'){
?>
<div class="ui-content-loading-overlay">
    <span class="ui-content-loading-text"><img src="<?php echo ffdl_plugin_url(); ?>/legacy-assets/images/spinner-1s-200px.svg" /> <span class=""><?php echo $message; ?></span></span>
    </div>
<?php
}


function ffdl_ui_properties_list_ajaxload($ui_setting, $ui_setting_key){
global $post;
$loading_message = 'Loading ' . (isset($ui_setting['title']) ? $ui_setting['title'] : '') . '...';
echo '<div class="ui-content ui-content-uicontentajaxload text-center" data-action="uicontentajaxload" data-objectid="'.$post->ID.'" data-layoutkey="'.$ui_setting_key.'" data-layouttype="'.$ui_setting['acf_fc_layout'].'">&nbsp;';
?>
    <?php ffdl_ui_content_loadingoverlay( $loading_message ); ?>
<?php
echo '</div>';
}


function ffdl_ui_render_styles($ui_setting, $ui_setting_key){
    
        $styles = $ui_setting;
        unset($styles['acf_fc_layout']);
        $css = '';
    
        foreach($styles as $parent_class => $parent_style){
            
            foreach($parent_style as $child_class => $child_style){
    
                $element_class = '.' . strtolower($parent_class) . '_' . strtolower($child_class);
    
                $css .= ffdl_ui_content_element_style($child_style, $element_class);
            }
           
        }
        
        
        echo '<style>'.$css.'</style>';
    
    }
    
    
function ffdl_ui_content_element_style($element_style, $element_class){

    
        $style = "\r\n";

        switch ($element_style['type']) {
            
            default:
                $style .= '.ui-content '.$element_class.' { ';
                    
                    if( isset($element_style['padding_top']))
                        $style .= 'padding-top:' . $element_style['padding_top'] . 'px;';
                    
                    if( isset($element_style['padding_bottom']))
                        $style .= 'padding-bottom:' . $element_style['padding_bottom'] . 'px;';
                    
                    if( isset($element_style['align']))
                        $style .= 'text-align:' . $element_style['align'] . ';';

                $style .= '} ' . "\r\n";
            break;
        }
        

    

    return $style;
}


function ffdl_convert_iframe_to_shortcode($str){
       
    return preg_replace_callback("/<iframe[^>]*src=[\"|']([^'\"]+)[\"|'][^>]*>/i", "ffdl_find_replace_iframe", $str);
        
}



function ffdl_find_replace_iframe($matches){
    
    if( strpos($matches[1], 'stats.10kresearch') !== false ){
        return '[iframe src="'.$matches[1].'" ]';
    } else {
        return $matches[0];
    }
	
}



function ffdl_ui_the_content($content=''){

    $content = ffdl_convert_iframe_to_shortcode($content);
    
    ob_start();
    
    echo $content = do_shortcode($content);

    $content =ob_get_clean();

    return $content;
}


function ffdl_ui_content_columns($ui_setting){

   return ffdl_ui_layout_columns($ui_setting['columns']);
}

function ffdl_ui_layout_columns($columns){

    $columns = isset($columns) ? $columns : 0;
    $columns = $columns > 0 ? ($columns <= 12  ? $columns : 12 ) : 1;
    $columns = round( (12 / $columns), PHP_ROUND_HALF_DOWN);

    return $columns;
}




function ffdl_ui_content_icon($src='', $class='img-responsive', $size=array(64, 64)){
    $icon = '';
    if( !empty($src) ){
        $icon = '<img src="'.$src.'" class="'.$class.' image-icon" style="width:'.$size[0].'px;height:'.$size[1].'px;" />';
    }

    return $icon;
}


function ffdl_ui_is_show_border($ui_setting=array()){

    $apply_border = false;
    $border = isset($ui_setting['border']) ? $ui_setting['border'] : array();
    if( isset($border['show_border']) && !empty($border['show_border']) ){
        $apply_border = true;
    }

    return $apply_border;
}


function ffdl_ui_aspect_ratio_css($ui_setting){

    if( empty($ui_setting['image_aspect_ratio']) )
        return '';

    $aspect_ratio = $ui_setting['image_aspect_ratio'];
    $apply_ratio = '';
    if( $aspect_ratio && $aspect_ratio['width'] > 0 && $aspect_ratio['height'] > 0 ){
        $aspect_ratio = ($aspect_ratio['height'] / $aspect_ratio['width'] ) * 100;
        $aspect_ratio = round($aspect_ratio, 2);
        $apply_ratio = 'padding-top:'.$aspect_ratio.'%!important;';
    } else {
        $aspect_ratio = (2 / 3 ) * 100;
        $aspect_ratio = round($aspect_ratio, 2);
        $apply_ratio = 'padding-top:'.$aspect_ratio.'%!important;';
    }

    return $apply_ratio;

}

function ffdl_ui_add_border_box($content='', $ui_setting, $class=''){

    if(empty($content) )
        return $content;

    $border = $ui_setting['border'];
    if( !isset($border['show_border']) || empty($border['show_border']) )
        return $content;
    
    $size = !empty($border['size']) ? $border['size'] : 1;
    $type = (!empty($border['type']) && is_string($border['type'])) ? $border['type'] : 'solid';
    $color = !empty($border['color']) ? $border['color'] : '#0B2546';
    $spacing = !empty($border['spacing']) ? $border['spacing'] : 15;

    
    $content = '<div class="ui-border-box-around '.$class.'" style="border:'.$size.'px '.$type.' '.$color.';padding:'.$spacing.'px;">'.$content.'</div>';

    return $content;
}


function ffdl_ui_wrap_link_around_content($content='', $ui_setting, $class=''){
    
    $wrap_link_start = $wrap_link_end = $wrap_link_target = '';

    if( !empty($ui_setting['link']) ){
        $wrap_link = $ui_setting['link'];
        if( !empty($wrap_link['url'])){
            $wrap_link_target = !empty($wrap_link['target']) ? 'target="'.$wrap_link['target'].'"' : '';
            $wrap_link_start = '<a class="'.$class.'" href="'.$wrap_link['url'].'" '.$wrap_link_target.' >';
            $wrap_link_end = '</a>';
        }
    }

    if( !empty($content) )
        $content =  $wrap_link_start . $content . $wrap_link_end;
   
    
    return $content;

}


function ffdl_ui_content_listings_with_params($ui_setting){

        $params = array();
        $custom_params = $ui_setting['custom_parameters'];
       
        $params['search'] = $custom_params['search'];
        $params['district'] = !empty($custom_params['neighborhood']) ? explode(',', $custom_params['neighborhood']) : '';
        $params['home_type'] = !empty($custom_params['home_type']) ? explode(',', $custom_params['home_type']) : '';
        $params['minbeds'] = $custom_params['beds']['min'];
        $params['maxbeds'] = $custom_params['beds']['max'];
        $params['baths'] = $custom_params['baths'];
        $params['minprice'] = $custom_params['price']['min'];
        $params['maxprice'] = $custom_params['price']['max'];
        $params['minsq'] = $custom_params['square_footage']['min'];
        $params['maxsq'] = $custom_params['square_footage']['max'];
        $params['parking'] =  $custom_params['parking'];
        $params['hoa_dues'] =  $custom_params['hoa_dues'];
        $params['days_on_market'] = $custom_params['days_on_market'];
        $params['agent_mls_id'] =  $custom_params['agent_mls_id'];
        $params['sort'] = str_replace(';', ':', $custom_params['sort']);
        $params['status'] = !empty($custom_params['status']) ? explode(',', $custom_params['status']) : '';
        $params['itemsperpage'] = !empty($ui_setting['limit']) && $ui_setting['limit'] > 0 ? $ui_setting['limit'] : 6;
        
       
        //ffdl_debug$params, false);
        //exit;
        
        if( !empty($ui_setting['paged']))
            $params['currentpage'] = $ui_setting['paged'] > 0 ? $ui_setting['paged'] : 1;
       
        $wp_query = ffdl_ui_properties_listing_wp_query($params);

        return $wp_query;

}



function ffdl_ui_get_uicontent_html($part, $variables = array()){
    
    $path = FFD()->plugin_path() . '/legacy-templates/parts/ui-content/' . $part . '.php';
    $path = apply_filters('ffdl_ui_get_uicontent_html', $path);

     $content = '';
  
     
      if (file_exists($path) ) {
          extract($variables, EXTR_SKIP);
          ob_start();
          include $path;
          $content = ob_get_clean();
      }
  
      return $content;
  }
  
  
  function ffdl_ui_content_listings($ui_setting){
  
      $display_type = $ui_setting['display_type']; //Default, Neighborhood, Agent or Random
      $limit = !empty($ui_setting['limit']) ? $ui_setting['limit'] : 100; //restricting limit of 0 (i.e no limit) to 100
      global $post;
      $query_args = array();
      
      if( ($display_type == 'Default' || $display_type == 'Agent') && ( is_singular( 'teammember' ) || $post->post_type =='teammember') ){
  
         
          $agent_mls_id = get_post_meta($post->ID, "mls_id", true);
          $query_args= array(
              'agent'      => $agent_mls_id,
              'orderby'      => 'days_on_market:asc',
              'status'       => array('Active','Sold','Contingent'),
              'itemsperpage' => $limit,
              
          );
  
         
      
      } else if( ($display_type == 'Default' || $display_type == 'Neighborhood')  && ( is_singular( 'community' ) || $post->post_type == 'community') ){
  
         
          $community = $post->post_title;
  
          $query_args= array(
              'district'      => $community,
              'orderby'      => 'days_on_market:asc',
              'status'       => array('Active', 'Contingent'),
              'itemsperpage' => $limit
          );
  
      } else {
  
          $query_args= array(
              'orderby'      => 'days_on_market:asc',
              'status'       => array('Active','Sold','Contingent'),
              'itemsperpage' => 20 //default to 20 if not on agent or community page
          );
  
      }
  
    
      if( !empty($ui_setting['paged']))
          $query_args['currentpage'] = $ui_setting['paged'] > 0 ? $ui_setting['paged'] : 1;
  
  
      $query = FFDL_Helper::ffdl_query_listings($query_args);
  
      wp_reset_postdata();
      return $query;
  
  }
  
  
  function ffdl_ui_content_news_feed($ui_setting){
  
      $display_type = $ui_setting['type']; // Blog, Hoodline
      $feed = array();
      $limit = !empty($ui_setting['limit']) ? $ui_setting['limit'] : 100; //restricting limit of 0 (i.e no limit) to 100
  
     
      if( $display_type == 'Blog' ){
          $args = array(
              'posts_per_page'   => 5,
              'offset'           => 0,
              'category'         => '', // category id ( can be comma seperated for multiple )
              'category_name'    => '', // catageory name
              'orderby'          => 'date',
              'order'            => 'DESC',
              'include'          => '',
              'exclude'          => '',
              'meta_key'         => '',
              'meta_value'       => '',
              'post_type'        => 'post',
              'post_mime_type'   => '',
              'post_parent'      => '',
              'author'	   => '',
              'author_name'	   => '',
              'post_status'      => 'publish',
              'suppress_filters' => true,
              'fields'           => '',
          );
  
          if( isset($ui_setting['category']) )
              $args['category'] = implode(',', $ui_setting['category']);
          
          $args['posts_per_page'] = $limit;
  
         
      
  
          $feed = get_posts( $args );
  
      } else {
  
      }
  
      return $feed;
  }


function ffdl_ui_render_properties_list_query($listing_query, $columns=4){

    $columns = !empty($columns) && $columns > 0 ? $columns : 4;
    $columns = ffdl_ui_layout_columns($columns);

    if( $listing_query && !empty($listing_query->posts) ){

        ob_start(); 
        foreach ($listing_query->posts as $listing) {
            ffdl_get_template_part(
                "listing-card",
                        array(
                            'columns' => $columns,
                            "listing" => $listing,
                        )
            );
        }
        return ob_get_clean();
    }


    return '';

}

function ffdl_ui_properties_listing_wp_query($atts){
  
    $params= array(
        'proptype'      => isset($atts['home_type'])        ? $atts['home_type'] : '',
        'district'      => isset($atts['neighborhood'])     ? $atts['neighborhood'] : isset($atts['district'])     ? $atts['district'] : '',
        'baths'         => isset($atts['baths'])            ? $atts['baths'] : '',
        'minprice'      => isset($atts['minprice'])         ? $atts['minprice'] : '',
        'maxprice'      => isset($atts['maxprice'])         ? $atts['maxprice'] : '',
        'minbeds'       => isset($atts['minbeds'])          ? $atts['minbeds'] : '',
        'maxbeds'       => isset($atts['maxbeds'])          ? $atts['maxbeds'] : '',
        'minsq'         => isset($atts['minsq'])            ? $atts['minsq'] : '',
        'maxsq'         => isset($atts['maxsq'])            ? $atts['maxsq'] : '',
        'keywords'      => isset($atts['search'])           ? $atts['search'] : '',
        'parking'       => isset($atts['parking'])          ? $atts['parking'] : '',
        'hoa_dues_max'  => isset($atts['hoa_dues'])         ? $atts['hoa_dues'] : '',
        'daysm'         => isset($atts['days_on_market'])   ? $atts['days_on_market'] : '',
        'agent'         => isset($atts['agent_mls_id'])     ? $atts['agent_mls_id'] : '',
        'orderby'       => isset($atts['sort'])             ? $atts['sort'] : '',
        'status'        => isset($atts['status'])           ? $atts['status'] : '',
        'itemsperpage'  => isset($atts['itemsperpage'])     ? $atts['itemsperpage'] : '',
        'currentpage'   => isset($atts['currentpage'])      ? $atts['currentpage'] : ''
    );
    
    foreach($params as $param_key => $param_value){
        if( empty($param_value) ){
            unset($params[$param_key]);
        }
    }

    //ffdl_debug$params, false);
   
    $columns = isset($atts['columns']) && $atts['columns'] ?  $atts['columns'] : 4;

   $wp_query = FFDL_Helper::ffdl_query_listings($params);

   return $wp_query;
}






function ffdl_ui_content_ajax_properties(){

    $output = '';
    $response = array();
    $params = $_POST;
    $layoutkey = $params['layoutkey'];
    $layouttype = $params['layouttype'];
    $objectid = $params['objectid'];
    
    global $post;
    $post = get_post($objectid);
   

    $ui_settings = ffd_get_field('ui_content_settings', $objectid);
    $ui_setting = $ui_settings[$layoutkey];
    
    if( !empty($ui_setting) ){
        
        $ui_setting['paged'] = intval($params['page']);

        if( $ui_setting['display_type'] == 'Custom' ){
            $wp_query = ffdl_ui_content_listings_with_params($ui_setting);
        } else {
            $wp_query = ffdl_ui_content_listings($ui_setting);
        }
        
        if( $wp_query && !empty($wp_query->posts) ):
           
            $output = ffdl_ui_render_properties_list_query($wp_query, $ui_setting['columns']);
        
            $response['found_posts'] = $wp_query->found_posts;
            $response['max_num_pages'] = $wp_query->max_num_pages;
            $response['post_count'] = $wp_query->post_count;
            $response['posts_per_page'] = $params['posts_per_page'];
            $response['paged'] = $ui_setting['paged'];
            $response['columns'] =$ui_setting['columns'];

        endif;

    }

    $response['html'] = $output;
    wp_send_json_success($response);
    die();
}
add_action('wp_ajax_ffdl_ui_content_ajax_properties', 'ffdl_ui_content_ajax_properties');
add_action('wp_ajax_nopriv_ffdl_ui_content_ajax_properties', 'ffdl_ui_content_ajax_properties');

function ajax_uicontentajaxload(){

    $output = '';
    $response = array();
    $params = $_POST;
    $layoutkey = $params['layoutkey'];
    $layouttype = $params['layouttype'];
    $objectid = $params['objectid'];
    
   global $post;
   $post = get_post($objectid);

    $ui_settings = ffd_get_field('ui_content_settings', $objectid);
    $ui_setting = $ui_settings[$layoutkey];
    
    if( !empty($ui_setting) ){
       
        $wrap_content = ffdl_ui_get_uicontent_html('properties_list', array('ui_setting' => $ui_setting, 'ui_setting_key'=>$layoutkey)); 
        
        ob_start();
        ffdl_ui_render_content_box($wrap_content, $ui_setting, $layoutkey);
        $output = ob_get_clean();
    }

    $response['html'] = $output;
    wp_send_json_success($response);
    die();
}
add_action('wp_ajax_uicontentajaxload', 'ajax_uicontentajaxload');
add_action('wp_ajax_nopriv_uicontentajaxload', 'ajax_uicontentajaxload');


if( isset($_GET['tk_delete_properties_posts_with_data']) ){

    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    if ( !defined('ABSPATH') ) {
       
        require_once( dirname( __FILE__ ) . '/wp-load.php' );
    }
    $mycustomposts = get_posts( array( 'fields'=>'ids', 'post_type' => 'property', 'numberposts' => -1));
   
    $i = 0;
    foreach( $mycustomposts as $mypost_KEY => $mypost_ID ) {
        // Delete's each post.
        wp_delete_post( $mypost_ID, true);
        // Set to False if you want to send them to Trash.
        $i++;
    }

    die($i);
} 

/* replace last occurance of charater , comma is replace with '' by default */
function ffdl_str_lreplace($search=',', $replace='', $subject)
{
    $pos = strrpos($subject, $search);

    if($pos !== false)
    {
        $subject = substr_replace($subject, $replace, $pos, strlen($search));
    }

    return $subject;
}

function ffdl_str_endswith($string, $test) {
    $strlen = strlen($string);
    $testlen = strlen($test);
    if ($testlen > $strlen) return false;
    return substr_compare($string, $test, $strlen - $testlen, $testlen) === 0;
}

function ffdl_str_beginswith($string, $test) {
    $strlen = strlen($string);
    $testlen = strlen($test);
    if ($testlen > $strlen) return false;
    return substr( $string, 0, 4 ) === $test;
}
 

function ffdl_is_community_url(){

    $path = trim(parse_url(add_query_arg(array()), PHP_URL_PATH), '/');
    $community_slug = basename($path);
   
    
    if( empty($community_slug) )
        return false;

       
    if( !empty($posts_array) ){
        $posts_array = get_posts(array('post_type'=>'community', 'post_status'=>'publish', 'name'=>$community_slug));
        $page = $posts_array[0]; 
        return $page;
    }
    
    return false;
}   
 

function ffdl_check_is_community_url($return, $query){
   
   
    if( $page = ffdl_is_community_url() ){
        global $wp_query;
        global $post;

        $wp_query = new Wp_Query(array('page_id' => $page->ID, 'post_type'=>'community') );
        $post = $page;
        setup_postdata($post);
        return false;
    }
    
    return $return;
    

 }
 add_action('pre_handle_404','ffdl_check_is_community_url', 10, 2);

 function ffdl_community_page_template_include( $template ) {
    
    if( is_404() && $page = ffdl_is_community_url() ){
        global $wp_query;
        global $post;

        $wp_query = new Wp_Query(array('page_id' => $page->ID, 'post_type'=>'community') );
        $post = $page;
        setup_postdata($page);

        
         //look in theme
        $theme_template = get_stylesheet_directory() . '/' . 'community-page.php';
        $custom_template = $theme_template;

        //look in ffd-integration plugin
        if( !file_exists($theme_template) ){
            $theme_ffd = get_stylesheet_directory() . '/ffd-integration/' . 'community-page.php';
            $custom_template = $theme_ffd;
        }

        //look in ffd-integration plugin
        if( !file_exists($theme_template) && !file_exists($theme_ffd) ){
            $plugin_ffd = FFD()->plugin_path() . '/legacy-templates/' . 'community-page.php';
            $custom_template = $plugin_ffd;
        }
        
        if ( file_exists( $custom_template  ) ) {
            $template = $custom_template;
        }

    }

    
  
    return $template;
  
  }
 add_filter( 'template_include', 'ffdl_community_page_template_include');


 function ffdl_get_default_template_name($name){
    global $post;
    
    if( $ui_section_template = FFD_Template_Rendrer()->get_single_template() ){
        
        return $ui_section_template . '.php';

    } else if( $post && isset($post->post_type) && is_singular(array('listing', 'community', 'neighborhood', 'teammember')) ){
        $name = 'single-' . $post->post_type. '.php';
    }

    return $name;
 }  
 
 function ffdl_load_listings_details_page($template){
    global $post;

        if( is_singular('listing') && get_option('ffd_listing_details_template') === 'no' )
            return $template;

       
        $file_name = basename($template);

        $file_name = ffdl_get_default_template_name($file_name);
         //look in theme
         $theme_template = get_stylesheet_directory() . '/' . $file_name;
         $custom_template = $theme_template;
 
         //look in ffd-integration plugin
         if( !file_exists($theme_template) ){
             $theme_ffd = get_stylesheet_directory() . '/ffd-integration/' . $file_name;
             $custom_template = $theme_ffd;
         }
 
         //look in ffd-integration plugin
         if( !file_exists($theme_template) && !file_exists($theme_ffd) ){
             $plugin_ffd = FFD()->plugin_path() . '/legacy-templates/' . $file_name;
             $custom_template = $plugin_ffd;
         }
         
         if ( file_exists( $custom_template  ) ) {
             $template = $custom_template;
         }
    

    return $template;
 }
 //add_filter( 'template_include', 'ffdl_load_listings_details_page', 20, 1);



 function ffdl_ajax_optout(){
    $user_id = get_current_user_id();
    $optOut = $_REQUEST['optout'];
    $user = wp_get_current_user();

    update_user_meta($user_id,'optout',$optOut);

    $PB_params = array (
            'email' => $user->user_email,
			'first_name' => $user->user_firstname,
			'last_name'  => $user->user_lastname,
			'optout' => $optOut
		);

    $r = FFDL_PB_Request::send_sf_message( $PB_params );

    $data = array(
			'sf_response'  => $r
		);

    wp_send_json_success($data);

    die();
}

add_action( 'wp_ajax_ffdl_ajax_optout', 'ffdl_ajax_optout' );


function ffdl_is_doing_search(){

    return ( isset($_GET['debug']) || isset($_GET['search']) || (isset($_GET['keywords']) && !empty($_GET['keywords'])) ) ? true : false;
}


function mls_search_page_init_listings(){

    $is_doing_search = ffdl_is_doing_search();

    if( !isset($_GET["district"]) && isset($_GET["neighborhood"]) && $_GET["neighborhood"] != ""){
        $_GET["district"]=$_GET["neighborhood"];
    }
    //set default city
    if( !isset($_GET['city']) ){
        $_GET['city'] = apply_filters('ffdl_search_default_city', ''); //'San Francisco'
    }


    if( $is_doing_search ){
        $results = FFDL_Helper::ffdl_query_listings($data=false);
        return $results;
    }


    

    $results= get_transient('search_page_load_result');
    

    if( $results !== false && !empty($results) && !empty($results->posts) ){
        return $results;
    } else{
        $results = FFDL_Helper::ffdl_query_listings($data=false);
        set_transient( 'search_page_load_result', $results, HOUR_IN_SECONDS*4);
    }

    return $results;
}


function ffdl_listing_card_default_img(){

    if( file_exists(get_stylesheet_directory()."/assets/images/default-preview.jpg") )
        $img = get_stylesheet_directory_uri()."/assets/images/default-preview.jpg";
    else
        $img = FFD()->plugin_url() ."/legacy-assets/images/default-preview.jpg";

    $img = apply_filters( 'ffdl_listing_card_default_img', $img);

    return $img;
}

add_action('ffd_sync_done', 'update_search_page_load_result'); 
add_action('ffd_sync_finished', 'update_search_page_load_result'); 
function update_search_page_load_result(){

    $results = FFDL_Helper::ffdl_query_listings($data=false);
    set_transient( 'search_page_load_result', $results, HOUR_IN_SECONDS*4);

  
}

function ffdl_get_site_logo(){

    $logo = get_stylesheet_directory_uri() . '/assets/images/logo.png';

    return apply_filters('ffdl_site_logo', $logo);
}






function ffdl_array_value_replace($Array, $Find, $Value, $Replace){
    if(is_array($Array)){
        foreach($Array as $Key=>$Val) {
            
                if(is_array($Array[$Key])){
                    $Array[$Key] = ffdl_array_value_replace($Array[$Key], $Find, $Value, $Replace);
                }else{
                    if($Key == $Find && $Val == $Value ) {
                    $Array[$Key] = $Replace;
                    }
                }
        }
    }
    return $Array;
}

function ffdl_replace_meta_query_key($meta_query, $key, $replace){

   
    return ffdl_array_value_replace($meta_query, 'key', $key, $replace);

}




function ffdl_property_meta_case_insensitive($value, $object_id, $meta_key, $single){

	if( (is_admin() && !is_ajax() ) || 'listing' != get_post_type($object_id) )
		return $value;

	if( !$meta_key || $meta_key == 'media' || $meta_key == 'ffd_media')
			return $value;

		remove_filter('get_post_metadata', 'ffdl_property_meta_case_insensitive', 10, 4);
		$meta = get_post_meta($object_id);
		add_filter('get_post_metadata', 'ffdl_property_meta_case_insensitive', 10, 4);

		foreach($meta as $mkey => $mvalue ){

            if( strtolower($meta_key) == strtolower($mkey) ){
                        if( $single )
                            return $mvalue[0];
                        else
                            return $mvalue;
            }
	    }

	return $value;
}
//add_filter('get_post_metadata', 'ffdl_property_meta_case_insensitive', 10, 4);