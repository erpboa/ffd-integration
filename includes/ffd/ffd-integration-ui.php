<?php

/**
 *
 *
 * @package FFD_Integration_UI
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main  UI Class.
 *
 * @class FFD_Integration_UI
 */
class FFD_Integration_UI {

    /**
	 * The single instance of the class.
	 *
	 * @var FFD_Integration
	 * @since 2.1
	*/
    protected static $_instance = null;



    /**
	 * Main FFD_Integration_UI Instance.
	 *
	 * Ensures only one instance of FFD_Integration_UI is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see FFD()
	 * @return FFD_Integration_UI - Main instance.
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

        $this->init_hooks();


    }


     /**
	 * Hook into actions and filters.
	 *
	 * @since 1.0
	 */
    public function init_hooks(){


		add_action('wp_head', array($this, 'wp_head'));


        add_action('wp_footer', array($this, 'wp_footer'), 101);
        add_filter('template_include', array($this, 'load_templates'), 21, 1);
        add_action('ffd_ui_template_before', array($this, 'ui_template_before'));

        add_action('ffd_ui_template_content', array($this, 'ui_template_content'));
		add_shortcode('ffd_get_content', array($this, 'shortcode_get_content'));
		add_shortcode('ffd_render_shortcode', array($this, 'render_shortcode'));

		add_shortcode('ffd_ui_queryposts', array($this, 'query_posts'));
    }

	public function query_posts($atts, $content=null, $tag=null){

		global $post;
		$_current_post = $post;

		if( !empty($content) ){
			$card_template = $content;
		} else {
			$template_title = isset($atts['template']) ? $atts['template'] : '';
			$template_id = $this->get_ui_post_by('post_title', $template_title);

			if(!$template_id)
				return '';

			$card_template = get_post($template_id);
			$card_template = $card_template->post_content;
		}

		if(empty($card_template))
				return '';

		//replace value for current page/post
		$_curr_args = array(
			'parent', 'child_of'
		);

		foreach($_curr_args as $_curr_arg){
			if( isset($atts[$_curr_arg]) ) {

				$case = $atts[$_curr_arg];
				switch ($case) {
					case 'current_page_id':
						$atts[$_curr_arg] = $_current_post->ID;
						break;

					default:

						break;
				}

			}
		}


		$args = $this->query_args($atts);

		if(isset($_GET['debug_ffd_ui_queryposts']) ){
			echo '<pre>'.print_r($args, true).'</pre>';
			exit;
		}

		$query = new WP_Query($args);
		global $post;
		$html = $no_posts_found = '';

		if( isset($query->posts) &&  count($query->posts) > 0 ){

			//foreach(array_chunk($query->posts, 3, true) as $posts):

				foreach($query->posts as $post){
					setup_postdata( $post );
					$data = $this->get_post_data($post->ID);
					$html .= FFD()->liquid->render($card_template, $data);
				}

			//endforeach;

		} else {
			$no_posts_found = isset($args['no_posts_message']) ? $args['no_posts_message'] : 'No Data Found.';
			$html .= '<div class="ffd_ui_queryposts ffd_no_posts '.( isset($args['post_type']) ? 'ffd_ui_queryposts_' . $args['post_type'] : '') .'"><p>'.$no_posts_found.'</p></div>';
		}
		wp_reset_postdata();

		return do_shortcode($html);

	}

	function rb_display_posts($atts, $content=null){


	}

    public function ui_template_before(){

        global $post;
        $listing_id = get_post_meta($post->ID, 'ffd_salesforce_id', true);
        if( !empty($listing_id) && class_exists('FFDAnalytics') ){
            FFDAnalytics::recordAnalytics($listing_id);
        }

    }

    public function shortcode_get_content($attr=array(), $content=null){

        $attr = wp_parse_args($attr, array('id'=>'', 'post_type'=>'page', 'title'=>''));
        $post = null;
        if( !empty($attr['id']) ){
            $post_id = $attr['id'];
            $post=get_post($post_id);
        } else if( !empty($attr['title']) ){
            $post = get_page_by_title($attr['title'], OBJECT, $attr['post_type']);
        }


        if( $post && !is_wp_error($post) ){
            ob_start();
            setup_postdata($post);
            the_content();
            wp_reset_postdata();
            $content = ob_get_clean();
        }

        return $content;
	}

	public function render_shortcode($atts=array(), $content=null){
		global $post;

		$tag = trim($atts['tag']);
		unset($atts['tag']);

		$data = $this->get_post_data($post->ID);

		$attr_str = '';
		foreach($atts as $key => $value ){
			if( strpos($value, '}}') !== false )
				$attr_str .= ' ' . $key . '=' . '"' . FFD()->liquid->render($value, $data) . '"';
			else
				$attr_str .= ' ' . $key . '=' . '"' . $value . '"';
		}
		$shortcode = '[' . $tag . ' ' . $attr_str . ' ]';

		if( !empty($content) ){
			$shortcode = $shortcode . $content . '[/' . $tag . ']';
		}

		return do_shortcode($shortcode) . '<!-- ' . $shortcode . ' -->';
	}

    public function ui_template_content(){

        global $post;
        global $ffd_ui_post;

        // remove_filter('the_content', 'wpautop');
        // remove_filter( 'the_excerpt', 'wpautop' );
            ob_start();
            //echo $content = apply_filters('the_content', $ffd_ui_post->post_content);
            echo $content = do_shortcode($ffd_ui_post->post_content);
            $content = ob_get_clean();
        // add_filter('the_content', 'wpautop');
        // add_filter( 'the_excerpt', 'wpautop' );

        $data = $this->get_post_data($post->ID);
        $content = FFD()->liquid->render($content, $data);


        echo $content;


    }



    /**
	 * Load templates if found as UI Content Post
	 *
	 * @since 1.0
	 */
    public function load_templates($template){
        global $post;

        if( is_singular('listing') && get_option('ffd_listing_details_template') === 'no' ){
            return $template;
        }

        $_template = $template;
        $template_name = basename($template);

        if( is_singular($post->post_type) ){

            //look in ui content posts
            $ui_template = $this->get_ui_template( 'single-' . $post->post_type );
            if( $ui_template ){
                return $ui_template;
            }

        } else {
             //look in ui content posts
            $ui_template = $this->get_ui_template($template_name);
            if( $ui_template ){
                return $ui_template;
            }
        }




        //look in ffd-integration template inside theme
        $ffd_template = get_stylesheet_directory() . '/ffd-integration/' . $template_name;
        if( file_exists($ffd_template) ){
            return $ffd_template;
        }

        //look in ffd-integration template inside theme
        $ffd_template = get_stylesheet_directory() . '/ffd-integration/' . $template_name;
        if( file_exists($ffd_template) ){
            return $ffd_template;
        }

        //look in ffd-integration plugin tempaltes
        $ffd_template = FFD()->plugin_path() . '/templates/' . $template_name;
        if( file_exists($ffd_template) ){
            return $ffd_template;
        }

        //look in ffd-integration plugin old templates
        $ffd_template = FFD()->plugin_path() . '/legacy-templates/' . $template_name;
        if( file_exists($ffd_template) ){
            return $ffd_template;
        }





        return $_template;
    }


    function get_ui_template($template){
		global $post;
		global $ffd_ui_post;

        $template = pathinfo($template, PATHINFO_FILENAME);
		$ui_post = $this->get_ui_post_by('post_title', $template);
		if( empty($ui_post) ){
			return false;
		} else{
            $ffd_ui_post = get_post($ui_post);
            if( has_shortcode( $ffd_ui_post->post_content, 'ffd_get_content') ){
                // Look within passed path within the theme - this is priority.
                return $template = locate_template(array('page.php', 'single.php'));
            }
            add_filter('body_class', array($this, 'body_ui_template_class'));
			return FFD()->plugin_path() . '/templates/' . 'render-ui-template.php';
		}

    }


    function body_ui_template_class( $classes ) {

        $classes[] = 'ffd-ui-template';
        return $classes;
    }



    function get_ui_post_by($field_name, $field_value){


		if( empty($field_name) || empty($field_value) )
			return null;

			$field_name = trim($field_name);
			$field_value = trim($field_value);


		global $wpdb;
		$query = "SELECT ID FROM {$wpdb->prefix}posts WHERE {$field_name}='{$field_value}' AND post_type='ui-section' AND post_status='publish';";
		$post_id = $wpdb->get_var($query);

		return $post_id;

    }



    function get_post_data($post_id, $context='single'){

		global $post;
		global $ffd_post_data;


		$post = get_post($post_id);
		setup_postdata($post);
		$values = get_post($post_id, 'ARRAY_A');
		$data = array();

		foreach($values as $key => $value){
			if( !empty($value) && !is_array($value) && !is_object($value) ){
				if( apply_filters('ffd_get_post_data', true, $key, $value) ){
                    if( !in_array($key, array('post_content', 'post_title')) ){
                        $data['post'][$key] = $value;
                    }
				}
			}
		}

		$meta = get_post_meta($post_id);

		foreach($meta as $key => $value){

			$value = isset($value[0]) ? $value[0] : $value;
			if( !empty($value) && is_string($value) ){
				if( apply_filters('ffd_get_post_data', true, $key, $value) ){
					$data['meta'][$key] = ffd_get_field($key, $post_id);
				}
			}
		}

		if( $post->post_type === 'listing' ){
			$pb_fields = array(
				'mlsid'        	=>  'ffd_mls_id',
				'listdate'      =>  'ffd_listed_date',
				'saledate'      =>  'ffd_sale_date',
				'listprice'     =>  'ffd_listingprice_pb',
				'saleprice'     =>  'ffd_sale_price',
				'sqftprice'     =>  'ffd_price_per_sqft',
				'status'        =>  'ffd_status',
				'listtype'      =>  'ffd_listingtype',
				'proptype'      =>  'ffd_propertytype',
				'dom'           =>  'ffd_dom',
				'lat'           =>  'ffd_latitude_pb',
				'lng'           =>  'ffd_longitude_pb',
				'beds'          =>  'ffd_bedrooms_pb',
				'baths'         =>  'ffd_fullbathrooms_pb',
				'halfbaths'		=>  'ffd_halfbathrooms_pb',
				'lotsize'       =>  'ffd_lotsize_pb',
				'totalarea'     =>  'ffd_totalarea_pb',
				'city'          =>  'ffd_city_pb',
				'state'         =>  'ffd_state_pb',
				'neighborhood'  =>  'ffd_area_text',
				'subdivision'  	=>  'ffd_subdivision',
				'address'       =>  'ffd_address_pb',
				'postalcode'    =>  'ffd_postalcode_pb',
				'image'         =>  'ffd_media',
				'openhouse'     =>  'ffd_open_house_date_time',
				'yearbuilt'     =>  'ffd_yearbuilt_pb',
				'parking'       =>  'ffd_parkingspaces',
				'view'          =>  'ffd_view',
				'description'	=> 	'ffd_description_pb'
			);

			foreach($pb_fields as $name => $key ){
				if( isset($data['meta'][$key]) && !empty($data['meta'][$key]) ){
					if( $name === 'image' ){

						$ffd_media = $data['meta'][$key];
						if( is_array($data['meta'][$key]) ){
							$data['listing'][$name] = reset($ffd_media);
						} else {
							$data['listing'][$name] = $ffd_media;
						}

					} else {

						$data['listing'][$name] = $data['meta'][$key];
					}
				}
			}

			if( !empty($data['listing']['baths']) && !empty($data['listing']['halfbaths'])){
				$haltbaths = floatval($data['listing']['halfbaths'] / 2);
				$data['listing']['totalbaths'] = intval($data['listing']['baths']) + (number_format($haltbaths, 1, '.', ',') );
			} else if( !empty($data['listing']['baths'])){
				$data['listing']['totalbaths'] = $data['listing']['baths'];
			}


			$time_listed = '';
			$listed_date = !empty($data['listing']['listdate']) ? $data['listing']['listdate'] : '';
			if( !empty($listed_date) ){
				if( $time_listed = strtotime($listed_date) ){
					$data['listed_time'] = human_time_diff($time_listed);
				}
			}

			$time_sold = '';
			$sold_date = !empty($data['listing']['saledate']) ? $data['listing']['saledate'] : '';
			if( !empty($sold_date) ){
				if( $time_sold = strtotime($sold_date) ){
					$data['sold_time'] = human_time_diff($time_sold);
				}
			}


			if( ffd_is_favorite($post->ID) ){
				$data['is_favorite'] = 1;
			}

		}

		//the loop variables;
		$data['the_permalink'] = get_permalink();
		$data['the_slug'] = $post->post_slug;
		$data['the_title'] = get_the_title();
		$data['the_ID'] = $post->ID;
    $data['mapaddres'] = str_replace(' #', ' ', get_the_title());

		$data['the_excerpt'] = get_the_excerpt();
		$data['the_date'] = get_the_date('M j, Y');
		$data['the_image'] = $this->get_featured_image();

		if( $context === 'single' ){
			$data['the_content'] = get_the_content();
		} else if( $context === 'loop' ){


		}

		wp_reset_postdata();

		if( isset($_GET['debug_post_data']) ){
			echo '<pre>data '.print_r($data, true).'</pre>';
			if(isset($_GET['exit'])){
				exit;
			}
		}

		//_Todo_
		//$ffd_post_data = $data;

		return $data;
    }


        function get_featured_image($post_id = false, $size = 'full', $placeholder = true){

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
                $featured_img_url =  $this->get_img_placeholder();
            else
                return '';
        }

        return apply_filters('ffdl_get_featured_image', $featured_img_url);
    }


	public function get_img_placeholder(){

		return  apply_filters('ffd_placeholder_img', FFD()->plugin_url() . "/assets/images/placeholder.jpg");

	}

	public function wp_head(){
		global $post;

		$media = get_post_meta($post->ID,"ffd_media",true);
		if($media && count($media)>0 && isset($media[0]) ){
			$output='<meta property="og:image" content="'. $media[0] . '" />';
			echo $output;
		}




	}
    public function wp_footer(){
		global $template;

		$listing_posttype = ffd_get_listing_posttype();

		if( !is_user_logged_in() && is_singular($listing_posttype) ){

			$enabled = get_option('ffd_listing_view_limit_enabled');


			if( $enabled === 'yes' ){

				$limit = (int) get_option('ffd_listing_view_limit');
				$post_id  = get_the_ID();
				$user_ip = FFD_Analytic()->get_user_ip();
				$visit_counter = FFD_Analytic()->visit_counter;
				$view_count = (int) $visit_counter[$user_ip][$post_id];

				if( $limit && $view_count && $view_count >= $limit ){

					$html_id = 'ffd-listing-view-limit-popup';
					$content = get_option('ffd_listing_view_limit_popup');
					echo $html =  '<div data-view_count="'.$view_count.'" data-user_ip="'.$user_ip.'" id="'.$html_id.'" class="ffd-listing-limit-popup"><div style="overflow:hidden;margin:0 auto;padding:10px;width:800px;background-color:#fff;">'.do_shortcode($content).'</div></div>';

					?>

					<script type="text/javascript">
					jQuery(function($){

						if( typeof jQuery.magnificPopup !== 'undefined' ){

							var html = jQuery('#<?php echo $html_id; ?>').html();
							console.log(html);
							jQuery.magnificPopup.open({
								items: {
									src: '#<?php echo $html_id; ?>',
									type: 'inline',
								},
								disableOn: 200,
								type: 'inline',
								mainClass: 'mfp-fade ffd-mfp-popup ffd-limit-view',
								removalDelay: 160,
								preloader: false,
								fixedContentPos: false,
								closeOnBgClick: false,
								closeBtnInside: false,
								showCloseBtn: false,
								closeOnContentClick: false,
								enableEscapeKey: false,
						}, 0);
						}


					});
					</script>
					<?php
				}
			}

		}
        echo '<!-- current template file:' . basename($template) . ' -->';
	}



	public function query_args($atts){

		global $post;

		$params = array(
			//////Author Parameters - Show posts associated with certain author.
				'author' => '',                      //(int) - use author id [use minus (-) to exclude authors by ID ex. 'author' => '-1,-2,-3,']
				'author_name' => '',              //(string) - use 'user_nicename' (NOT name)
				'author__in' => '',            //(array) - use author id (available with Version 3.7).
				'author__not_in' => '',        //(array)' - use author id (available with Version 3.7).
				'cat' => '',//(int) - use category id.
				'category_name' => '',          //(string) - Display posts that have these categories, using category slug.
				'category_name' => '',           //(string) - Display posts that have "all" of these categories, using category slug.
				'category__and' => '',         //(array) - use category id.
				'category__in' => '',          //(array) - use category id.
				'category__not_in' => '',      //(array) - use category id.

				'tag' => '',                       //(string) - use tag slug.
				'tag_id' => '',                            //(int) - use tag id.
				'tag__and' => '',               //(array) - use tag ids.
				'tag__in' => '',                //(array) - use tag ids.
				'tag__not_in' => '',            //(array) - use tag ids.
				'tag_slug__and' => '', //(array) - use tag slugs.
				'tag_slug__in' => '',  //(array) - use tag slugs.

				'tax_query' => '',

				'p' => '',                               //(int) - use post id.
				'name' => '',                //(string) - use post slug.
				'page_id' => '',                         //(int) - use page id.
				'pagename' => '',            //(string) - use page slug.
				'pagename' => '',      //(string) - Display child page using the slug of the parent and the child page, separated ba slash
				'post_parent' => '',                     //(int) - use page id. Return just the child Pages. (Only works with heirachical post types.)
				'post_parent__in' => '',       //(array) - use post ids. Specify posts whose parent is in an array. NOTE: Introduced in 3.6
				'post_parent__not_in' => '',  //(array) - use post ids. Specify posts whose parent is not in an array.
				'post__in' => '',             //(array) - use post ids. Specify posts to retrieve. ATTENTION If you use sticky posts, they will be included (prepended!) in the posts you retrieve whether you want it or not. To suppress this behaviour use ignore_sticky_posts
				'post__not_in' => '',         //(array) - use post ids. Specify post NOT to retrieve.

				'post_type' => '',                   // - retrieves any type except revisions and types with 'exclude_from_search' set to true.
				'post_status' => '',                 // - retrieves any status except those from post types with 'exclude_from_search' set to true.



				'posts_per_page' => 50,                 //(int) - number of post to show per page (available with Version 2.1). Use 'posts_per_page'=-1 to show all posts (the 'offset' parameter is ignored with a -1 value). Note if the query is in a feed, wordpress overwrites this parameter with the stored 'posts_per_rss' option. Treimpose the limit, try using the 'post_limits' filter, or filter 'pre_option_posts_per_rss' and return -1
				'nopaging' => false,                    //(bool) - show all posts or use pagination. Default value is 'false', use paging.
														//NOTE: The query variable 'page' holds the pagenumber for a single paginated Post or Page that includes the <!--nextpage--> Quicktag in the post content.
				'ignore_sticky_posts' => '',         // (boolean) - ignore sticky posts or not (available with Version 3.1, replaced caller_get_posts parameter). Default value is 0 - don't ignore sticky posts. Note: ignore/exclude sticky posts being included at the beginning of posts returned, but the sticky post will still be returned in the natural order of that list of posts returned.

				'order' => '',
				'orderby' => '',                    //(string) - Sort retrieved posts by parameter. Defaults to 'date'. One or more options can be passed. EX: 'orderby' => 'menu_order title'
														  //Possible Values:
														  //'none' - No order (available with Version 2.8).
														  //'ID' - Order by post id. Note the captialization.
														  //'author' - Order by author.
														  //'title' - Order by title.
														  //'name' - Order by post name (post slug).
														  //'date' - Order by date.
														  //'modified' - Order by last modified date.
														  //'parent' - Order by post/page parent id.
														  //'rand' - Random order.
														  //'comment_count' - Order by number of comments (available with Version 2.9).
														  //'menu_order' - Order by Page Order. Used most often for Pages (Order field in the EdiPage Attributes box) and for Attachments (the integer fields in the Insert / Upload MediGallery dialog), but could be used for any post type with distinct 'menu_order' values (theall default to 0).
														  //'meta_value' - Note that a 'meta_key=keyname' must also be present in the query. Note alsthat the sorting will be alphabetical which is fine for strings (i.e. words), but can bunexpected for numbers (e.g. 1, 3, 34, 4, 56, 6, etc, rather than 1, 3, 4, 6, 34, 56 as yomight naturally expect).
														  //'meta_value_num' - Order by numeric meta value (available with Version 2.8). Also notthat a 'meta_key=keyname' must also be present in the query. This value allows for numericasorting as noted above in 'meta_value'.
														  //'title menu_order' - Order by both menu_order AND title at the same time. For more info see: http://wordpress.stackexchange.com/questions/2969/order-by-menu-order-and-title
														  //'post__in' - Preserve post ID order given in the post__in array (available with Version 3.5).


				'year' => '',                         //(int) - 4 digit year (e.g. 2011).
				'monthnum' => '',                        //(int) - Month number (from 1 to 12).
				'w' =>  '',                             //(int) - Week of the year (from 0 to 53). Uses the MySQL WEEK command. The mode is dependenon the "start_of_week" option.
				'day' => '',                            //(int) - Day of the month (from 1 to 31).
				'hour' => '',                           //(int) - Hour (from 0 to 23).
				'minute' => '',                         //(int) - Minute (from 0 to 60).
				'second' => '',                         //(int) - Second (0 to 60).
				'm' => '',                          //(int) - YearMonth (For e.g.: 201307).
				'date_query' => '',

				'meta_key' => '',                    //(string) - Custom field key.
				'meta_value' => '',                //(string) - Custom field value.
				'meta_value_num' => '',                 //(number) - Custom field value.
				'meta_compare' => '=',                  //(string) - Operator to test the 'meta_value'. Possible values are '!=', '>', '>=', '<', or ='. Default value is '='.
				'meta_query' => '',
				'cache_results' => '',                //(bool) Default is true - Post information cache.
				'update_post_term_cache' => '',       //(bool) Default is true - Post meta information cache.
				'update_post_meta_cache' => '',       //(bool) Default is true - Post term information cache.

				'no_found_rows' => '',               //(bool) Default is false. WordPress uses SQL_CALC_FOUND_ROWS in most queries in order to implement pagination. Even when you donÑ‚ÐÐ©t need pagination at all. By Setting this parameter to true you are telling wordPress not to count the total rows and reducing load on the DB. Pagination will NOT WORK when this parameter is set to true. For more information see: http://flavio.tordini.org/speed-up-wordpress-get_posts-and-query_posts-functions


				's' => '',                              //(string) - Passes along the query string variable from a search. For example usage see: http://www.wprecipes.com/how-to-display-the-number-of-results-in-wordpress-search
				'exact' => '',                        //(bool) - flag to make it only match whole titles/posts - Default value is false. For more information see: https://gist.github.com/2023628#gistcomment-285118
				'sentence' => '',                     //(bool) - flag to make it do a phrase search - Default value is false. For more information see: https://gist.github.com/2023628#gistcomment-285118

				'fields' => ''                       //(string) - Which fields to return. All fields are returned by default.
			);

			//echo '<pre>'.print_r($atts, true); '</pre>';


			$args = array(); // array to be returned after parsing
			foreach($atts as $att_name => $att_value ){

				$att_value = str_replace(
					array('the_title', 'the_slug', 'the_id', 'the_date', 'mapaddres'),
					array($post->post_title, $post->post_name, $post->ID, $post->post_date),
					$att_value
				);
				//only parse fields in the params array
				if( isset($params[$att_name]) ){

					//set string true as bolean true
					if( $att_value === 'true' )
						$att_value = true;

					//set false true as bolean false
					if( $att_value === 'false' )
						$att_value = false;


					if( $att_name == 'tax_query'){

						//parse tax query
						$pieces = explode('|', $att_value);
						$query = array('taxonomy'=>'', 'field'=>'', 'terms'=>'', 'include_children'=>'', 'operator'=>'');
						$att_value = $this->create_subquery($query, $pieces);

					} else if( $att_name == 'meta_query'){



						//parse meta query
						$pieces = explode('|', $att_value);
						$query = array('key'=>'', 'value'=>'', 'type'=>'', 'compare'=>'');
						$att_value = $this->create_subquery($query, $pieces);

					} else {
						if(  $this->has_string($att_name, array('_in', '_and') || in_array($att_name, array('post_type', 'post_status'))) ) {

							$att_value = explode(',', $att_value);
							$att_value = array_map('trim', $att_value);
						}
					}

					$args[$att_name] = $att_value;

				}
			}

			if( isset($_GET['debug_ui_query']) ){
				echo '<pre>'.print_r($args, true); '</pre>';
			}

		return $args;
	}


	function create_subquery($query, $peices){

		$relation = '';
		$query_value = '';
		foreach($peices as $peice){

			$params = explode(';', $peice);
			foreach($params as $param ){
				$_piece = explode(':', $param);
				$_piece = array_map('trim', $_piece);

				$_key = $_piece[0];
				$_value = isset($_piece[1]) ? $_piece[1] : '';

				if($_key == 'relation' && !empty($_value) ){
					$relation = $_value;
				} else {
					if( isset($query[$_key]) && !empty($_value) ){

						if( $_key==='compare' || $_key === 'operator' ){
							$_value = $this->sql_compare($_value);
						} else if( $_key==='type'){
							$_value = $this->sql_type($_value);
						}

						$query[$_key] = $_value;
					}
				}
			}
		}

		$query = array_filter($query, 'strlen');
		if( $relation !== '' && !empty($query)){
			$query_value = array('relation' => $relation, $query);
		} else if  (!empty($query) ){
			$query_value = array($query);
		}


		return $query_value;

	}



	function has_string($haystack, $needle='', $flag="OR"){

		if( is_string($needle) && $needle !== '' ){

			return  strpos($haystack, $needle) !== false;

		} else if( is_array($needle) && !empty($needle) && $flag !== "AND" ){

			foreach($needle as $value ){
				if( strpos($haystack, $value) !== false ){
					return true;
				}
			}
		} else if( is_array($needle) && !empty($needle) && $flag === "AND" ) {


			foreach($needle as $value ){
				if( strpos($haystack, $value) === false ){
					return false;
				}
			}

		}

		return false;
	}



	function sql_type( $type = '' ) {
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


	function sql_compare($compare){

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
                'RLIKE',
            )
        ) ) {
            $compare = '=';
        }

        return $compare;
	}

}


/**
 * Main instance of FFD_Integration_UI.
 *
 * Returns the main instance of FFD_UI to prevent the need to use globals.
 *
 * @since  1.0
 * @return FFD_Integration_UI
 */
function FFD_UI() {
	return FFD_Integration_UI::instance();
}
FFD_UI();
