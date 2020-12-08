<?php


function ffd_fix_ui_section_shortcodes($content){
    $array = array (
        '<p>[' => '[',
        ']</p>' => ']',
        ']<br />' => ']',
        ']<br>' => ']'
    );

    $content = strtr($content, $array);
    return $content;
}

add_filter('the_content', 'ffd_fix_ui_section_shortcodes');

// Begin Shortcodes
class FFD_Template_Render {


    protected static $_instance = null;
    protected $ui_section_name = '';
    protected $ui_section_atts = array();
    protected $ui_slider_content = false;
    protected $ui_slider = false;


    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    // ======================================================================== //
    // Initialize shortcodes and conditionally include opt-in Bootstrap scripts
    // ======================================================================== //

    function __construct() {

        //Initialize shortcodes
        add_action( 'init', array( $this, 'add_shortcodes' ) );
        add_action('ffd_get_post_data', array($this, 'exclude_unecessary_meta'), 5, 3);
    }

    // ======================================================================== //

    function exclude_unecessary_meta($return, $key, $value){

        if( strpos($key, '_') === 0  ){
            $return = false;
        }
        return $return;
    }


    /*--------------------------------------------------------------------------------------
        *
        * add_shortcodes
        *
        * @author Filip Stefansson
        * @since 1.0
        *
        *-------------------------------------------------------------------------------------*/
    function add_shortcodes() {
        remove_shortcode( 'gallery' );
        $shortcodes = array(
            'alert',
            'badge',
            'breadcrumb',
            'breadcrumb-item',
            'button',
            'button-group',
            'button-toolbar',
            'caret',
            'carousel',
            'carousel-item',
            'code',
            'collapse',
            'collapsibles',
            'column',
            'container',
            'container-fluid',
            'divider',
            'dropdown',
            'dropdown-header',
            'dropdown-item',
            'emphasis',
            'icon',
            'img',
            'embed-responsive',
            'jumbotron',
            'label',
            'lead',
            'list-group',
            'list-group-item',
            'list-group-item-heading',
            'list-group-item-text',
            'media',
            'media-body',
            'media-object',
            'modal',
            'modal-footer',
            'nav',
            'nav-item',
            'page-header',
            'panel',
            'popover',
            'progress',
            'progress-bar',
            'responsive',
            'row',
            'span',
            'tab',
            'table',
            'table-wrap',
            'tabs',
            'thumbnail',
            'tooltip',
            'well',
            'ui_section',
            'ffd_ui',
            'display_listing',
            'display_listings',
            'display_posts',
            'display_post',
            'blogposts',
            'ui_slider',
            'gallery',
            'ui_slide',
            'box-container',
        );

        if( is_admin() && !is_ajax() ){
            return;
        }

        foreach ( $shortcodes as $shortcode ) {

            $function = 'rb_' . str_replace( '-', '_', $shortcode );
            add_shortcode( $shortcode, array( $this, $function ) );

        }
    }

    function rb_ui_slide($atts, $content=null){

        $atts = shortcode_atts( array(
            "src"     => false,
        ), $atts );

        $slide = '';

        $style = ''; //height: 171px;	width: 257px;
        if( !empty($atts['slide_height']) ){
            $style = ' height:' . $atts['slide_height'] . 'px';
        }
        if( !empty($atts['slide_width']) ){
            $style = ' width:' . $atts['slide_width'] . 'px';
        }

        if( empty($content) ){
            if( $atts['src'] ){
                $content = '<img src="'.$atts['src'].'" />';
                $slide = '<div class="swiper-slide" style="'.$style.'">'.$content.'</div>';
            }
        } else {
            $slide = '<div class="swiper-slide" style="'.$style.'">'.$content.'</div>';
        }

        return $slide;

    }

    function rb_gallery($attr=array(), $content=null, $tag=null){

        if(isset($_GET['rb_debug_gallery']) ){
            var_dump($attr);
            exit;
        }
        return $this->rb_ui_slider($attr, $content, $tag);
    }


    function rb_ui_slider($attr=array(), $content=null, $tag=null){



        $slider = $slides = '';
        if( empty($attr) ){
            $attr = array();
        }

        if( !empty($content) ){

            $this->ui_slider_content = true;
            $slides = do_shortcode($content);
            $this->ui_slider_content = false;

            $slides_swiper_atts = array(
                'centered_slides' => false,
                'slides_perview' => 3,
                'space_between' => 30,
                'loop' => true,
                'md_slides_perview' => 3,
                'sm_slides_perview' => 2,
                'xs_slides_perview' => 1,
                'xs_space_between' => 0,
                'show_nav' => true,
            );


            $attr = array_merge($slides_swiper_atts, $attr);

        } else if( ( $tag == 'gallery' && !empty($attr['ids']) ) || !empty($attr['gallery_slider']) || !empty($attr['gallery_carousel']) ) {

            unset($attr['gallery_carousel']);

            if ( ! empty( $attr['ids'] ) ) {
                // 'ids' is explicitly ordered, unless you specify otherwise.
                if ( empty( $attr['orderby'] ) ) {
                    $attr['orderby'] = 'post__in';
                }
                $attr['include'] = $attr['ids'];
            }

            $atts  = shortcode_atts(
                array(
                    /* 'order'      => 'ASC', */
                    'orderby'    => 'post__in',
                    'size'       => 'medium',
                    'include'    => '',
                    'exclude'    => '',
                    'link'       => '',
                ),
                $attr,
                'ui_slider'
            );

            $_args = array(
                'include'        => $atts['include'],
                'post_status'    => 'inherit',
                'post_type'      => 'attachment',
                'post_mime_type' => 'image'
            );


            $_attachments = get_posts($_args);
            $attachments = array();
            $_arrange_attachments = array();

            $_ids = explode(',', $attr['ids']);
            $_ids = array_map('trim', $_ids);


            foreach ( $_attachments as $key => $val ) {
                $attachments[ $val->ID ] = $_attachments[ $key ];
            }
            foreach($_ids as $_id){
                if( isset( $attachments[ $_id ]) ){
                    $_arrange_attachments[$_id] = $attachments[ $_id ];
                }
            }

            $attachments = $_arrange_attachments;

            if ( !empty( $attachments ) ) {

                $style = ''; //height: 171px;	width: 257px;
                if( !empty($attr['slide_height']) ){
                    $style = ' height:' . $attr['slide_height'] . 'px';
                }
                if( !empty($attr['slide_width']) ){
                    $style = ' width:' . $attr['slide_width'] . 'px';
                }
                if( empty($style) ){
                    if( $tag == 'gallery' ) $slide_height = 200; else $slide_height = 171;
                    $style = 'height:'.$slide_height.'px;';
                }
                $i = 0;
                foreach ( $attachments as $id => $attachment ) {


                    if ( ! empty( $atts['link'] ) && 'file' === $atts['link'] ) {
                        $image_output = wp_get_attachment_link( $id, $atts['size']);
                    } elseif ( ! empty( $atts['link'] ) && 'none' === $atts['link'] ) {
                        $image_output = wp_get_attachment_image( $id, $atts['size']);
                    }  elseif ( ! empty( $atts['link'] ))  {
                        $image_output = '<a data-attachment_id="'.$attachment->ID.'" href="'.$atts['link'].'" '. ( !empty($attr['link_target']) ? 'target="'.$attr['link_target'].'"' : '' ) .' >';
                        $image_output .= wp_get_attachment_image( $id, $atts['size']) . '</a>';
                    } else {
                        $image_output = wp_get_attachment_link( $id, $atts['size'], true);
                    }



                    $slides .= '<div class="swiper-slide" style="'.$style.'">'.$image_output.'</div>';
                }
            }

            if(  $tag == 'gallery' ){

                $slider_swiper_atts = array(
                    'centered_slides' => false,
                    'slides_perview' => 4,
                    'space_between' => 30,
                    'loop' => true,
                    'md_slides_perview' => 4,
                    'sm_slides_perview' => 2,
                    'xs_slides_perview' => 1,
                    'xs_space_between' => 0,
                    'show_nav' => true,

                );

                $attr = array_merge($slider_swiper_atts, $attr);

            }

        }else if(  !empty($attr['listing_gallery']) || !empty($attr['listing_gallery']) ) {

            unset($attr['listing_gallery']);


            if( isset($attr['listing_id']) ){
                $post_id = $attr['listing_id'];
            } else {
                global $post;
                $post_id = $post->ID;
            }



            $attachments = get_post_meta($post->ID, 'ffd_media', true);

            if ( !empty( $attachments ) ) {

                $style = ''; //height: 171px;	width: 257px;
                if( !empty($attr['slide_height']) ){
                    $style = ' height:' . $attr['slide_height'] . 'px';
                }
                if( !empty($attr['slide_width']) ){
                    $style = ' width:' . $attr['slide_width'] . 'px';
                }
                if( empty($style) ){
                    if( $tag == 'gallery' ) $slide_height = 200; else $slide_height = 171;
                    $style = 'height:'.$slide_height.'px;';
                }
                $i = 0;
                foreach ( $attachments as $id => $attachment ) {

                    $image_output = '<img src="'.$attachment.'" />';

                    $slides .= '<div class="swiper-slide" style="'.$style.'">'.$image_output.'</div>';
                }
            }



            $slider_swiper_atts = array(
                'centered_slides' => true,
                'slides_perview' => 1,
                'space_between' => 0,
                'loop' => true,
                'md_slides_perview' => 1,
                'sm_slides_perview' => 1,
                'xs_slides_perview' => 1,
                'xs_space_between' => 0,
                'show_nav' => true,
            );

            $attr = array_merge($slider_swiper_atts, $attr);


        } else if( !empty($attr['properties_slider']) || !empty($attr['properties_carousel'])) {

            unset($attr['properties_carousel']);
            unset($attr['properties_slider']);
            $attr['part'] = 'listing-cards';
            $attr['wrapper_class'] = 'swiper-slide';
            $slides = $this->rb_display_listings($attr);

            $slider_swiper_atts = array(
                'centered_slides' => false,
                'slides_perview' => 2,
                'space_between' => 30,
                'loop' => true,
                'md_slides_perview' => 2,
                'sm_slides_perview' => 2,
                'xs_slides_perview' => 1,
                'xs_space_between' => 0,
                'show_nav' => true,

            );

            $attr = array_merge($slider_swiper_atts, $attr);

        } else if( !empty($attr['testimonials_slider']) || !empty($attr['testimonials_carousel']) ){


            //html for slide
            $card_template = $this->get_ui_template_html($attr['template']);

            unset($attr['template']);
            unset($attr['testimonials_slider']);
            unset($attr['testimonials_carousel']);
            if( isset($attr['limit']) ){
                $attr['posts_per_page'] = $attr['limit'];
                unset($attr['limit']);
            }
            $args  = shortcode_atts(
                array(
                    'post_type'    => 'testimonials',
                    'posts_per_page'       => 3,
                    'no_found_rows'    => true,
                    'order'          => 'DESC',
                    'orderby'        => 'menu_order',
                ),
                $attr,
                'ui_slider'
            );

            $tax_query = array();
            if( !empty($attr['category']) ){
                $tax_query[] = array(
                    'taxonomy' => 'testimonial_categories',
                    'field'    => 'slug',
                    'terms'    => $attr['category'],
                );
            }

            if( !empty($tax_query) )
                $args['tax_query'] = $tax_query;


            $query = new WP_Query($args);

            //setup swiper default settings
            $slider_swiper_atts = array(
                'centered_slides' => false,
                'slides_perview' => 3,
                'space_between' => 30,
                'loop' => true,
                'md_slides_perview' => 3,
                'sm_slides_perview' => 2,
                'xs_slides_perview' => 1,
                'xs_space_between' => 0,
                'show_nav' => true,

            );
            $attr = array_merge($slider_swiper_atts, $attr);

            if( isset($query->posts) && !empty($query->posts) ){
                global $post;

                $style = ''; //height: 171px;	width: 257px;
                if( !empty($attr['slide_height']) ){
                    $style = ' height:' . $attr['slide_height'] . 'px';
                }
                if( !empty($attr['slide_width']) ){
                    $style = ' width:' . $attr['slide_width'] . 'px';
                }

                foreach($query->posts as $post){

                    setup_postdata( $post );
                    $post_link = $post_image = $post_category = $post_title = $tagline = $post_description = $post_date = '';

                    $post_image 		= get_the_post_thumbnail_url($post->ID,'full');

                    $post_content = apply_filters('the_content', $post->post_content);
                    $post_description = wp_trim_words($post_content, 20, '');
                    $post_excerpt = apply_filters('the_excerpt', $post->post_excerpt);
                    $post_excerpt = wp_trim_words($post_excerpt, 10, '');



                    if( $post_image ){
                        $type_class = 'rb-testimonial-showimage';
                    } else {
                        $type_class = 'rb-testimonial-showquote';
                    }
                    $testimonial = !empty($tagline) ? $tagline : $post_excerpt;

                    $video = get_post_meta('video', $post->ID);
                    $tagline  	= get_post_meta( $post->ID, 'tag_line', true);
                    $description = strip_tags($post_description);
                    $excerpt 	= strip_tags($post_excerpt);
                    $qoute_icon = '<img src="'.get_template_directory_uri().'/images/icon-quotationmark-white.svg" />';

                    $extra_data = array(
                        'type_class' => $type_class,
                        'description' => $description,
                        'excerpt' => $excerpt,
                        'tagline' => $tagline,
                        'qoute_icon' => '<img src="'.get_template_directory_uri().'/images/icon-quotationmark-white.svg" />'
                    );

                    if( !empty($video) ){
                        $extra_data['youtube_icon'] = '<img src="'.get_template_directory_uri().'/images/youtube-play.png" width="32" height="32" class="rb-youtube-play-icon" />';
                    }
                    $data = $this->get_post_data_array($post->ID, $card_template);
                    $data = array_merge($data, $extra_data);

                    $testimonial_html = $this->render_content($card_template, $data);



                    $slides .= '<div class="swiper-slide" style="'.$style.'">'.$testimonial_html.'</div>';

                }

                wp_reset_postdata();
            }

        }

        if( !empty($slides) ){

            $_swiper_atts = array(
                'centered_slides',
                'slides_perview',
                'looped_slides',
                'slides_pergroup',
                'slides_offsetbefore',
                'slides_offsetafter',
                'space_between',
                'lazy',
                'touchRatio',
                'loop',
                'md_slides_perview',
                'sm_slides_perview',
                'xs_slides_perview',
                'md_space_between',
                'sm_space_between',
                'xs_space_between',
                'show_nav',
                'free_mode',
                'loop_fill',
                'grab_cursor',

                'name',
                'controller'
            );

            $attr['xclass'] = isset($attr['xclass']) ? $attr['xclass'] : '';

            $swiper_atts = '';
            foreach($_swiper_atts as $_swiper_att){
                if( isset($attr[$_swiper_att]) )
                    $swiper_atts .= ' data-'.$_swiper_att.'="'.$attr[$_swiper_att].'"';
            }

            $slider .= '<div class="swiper-atts-slider-container '.$attr['xclass'].'">';
            $slider .='<div class="swiper-atts-slider" '.$swiper_atts.' >';
            $slider .='<div class="swiper-wrapper">';
            $slider .= $slides;
            $slider .='</div><!-- swiper-wrapper -->';

            if( isset($attr['show_nav']) && $attr['show_nav'] == 'yes' ){
                $slider .='<div class="swiper-button-next swiper-button-'.( !empty($attr['nav_theme']) ? $attr['nav_theme'] : 'black' ) .'"></div>';
                $slider .='<div class="swiper-button-prev swiper-button-'.( !empty($attr['nav_theme']) ? $attr['nav_theme'] : 'black' ) .'"></div>';
            }

            $slider .='</div><!-- swiper-atts-slider -->';

            $slider .= '</div><!-- swiper-atts-slider-container -->';
        }
        //$section_content = $this->render_content($slider, $atts);

        if( isset($_GET['debug_ui_slider']) ){

            echo '<pre>'.print_r($attr, true).'</pre>';
            if(isset($_GET['exit'])){
                exit;
            }
        }


        $content = '<!-- begin ui_slider --> ' . do_shortcode($slider) . ' <!-- end ui_slider -->';



        return $content;

    }



    function rb_display_listings($atts, $content=null){

        $template_title = isset($atts['template']) ? $atts['template'] : 'Listing Card';
        unset($atts['template'] );

        $template_id = $this->get_section_id_by('post_title', $template_title);

        if(!$template_id)
            return '';

        $card_template = get_post($template_id);
        $card_template = $card_template->post_content;

        if(empty($card_template))
            return '';

        $part = 'listings'; // parts file
        $prices = array();
        $posts_per_page=12;
        $page_type = null;
        $wrapper_class = '';
        $showMap = '';
        $mapSearch = '';

        if( isset($atts['page_type']) ){
            $page_type = $atts['page_type'];
            unset($atts['page_type']);
        }

        if( isset($atts['posts_per_page']) ){
            $posts_per_page = $atts['posts_per_page'];
            $atts['itemsperpage'] = $posts_per_page;
        }

        if( isset($atts['part']) ){
            $part = $atts['part'];
            unset($atts['part']);
        }

        if( isset($atts['wrapper_class']) ){
            $wrapper_class = $atts['wrapper_class'];
            unset($atts['wrapper_class']);
        }

        $wrapper_element = '';
        if( isset($atts['wrapper_element']) ){
            $wrapper_element = $atts['wrapper_element'];
            unset($atts['wrapper_element']);
        }



        $meta_query = array();
        $request_args = array_filter($atts);

        //todo uncomment this.
        $query = FFDL_Helper::ffdl_query_listings($atts);


        if( isset($_GET['debug_show_listings']) ){

            echo '<pre>'.print_r($query, true).'</pre>';
            exit;
        }

        if( $part === 'return' ){
            return $query;
        }


        $html = '';
        $items = null;
        if( isset($query->posts) ){
            $items = $query->posts;
        }

        if(!empty($items) ){

            $current_posts = ($query->post_count <=  $query->found_posts)? $query->post_count: $query->found_posts;
            $total_pages = ($query->found_posts>0)? round(($query->found_posts / $posts_per_page), 0, PHP_ROUND_HALF_UP) : 0;




            $recordsCount = count($query->posts);
            if($recordsCount > 0){

                foreach($items as $property_item){

                    if( $wrapper_element !== '' ){
                        $html .= '<'.$wrapper_element.' class="'.$wrapper_class.'">';
                    }


                    $property_data = $this->get_post_data($property_item->ID);
                    $html .= $this->render_content($card_template, $property_data);


                    if( $wrapper_element !== '' ){
                        $html .= '</'.$wrapper_element.'>';
                    }

                }

            } else {

                $_el = (!empty($wrapper_element) ? $wrapper_element : 'div' );
                $html .= '<'.$_el.' class="text-center">There are no property(s) available matching your search.</'.$_el.'>';
            }



        }



        return do_shortcode($html);
    }

    function rb_display_post($atts, $content=null){
        global $post;
        $_current_post = $post;

        if( isset($atts['post_id']) ){
            $post = get_post($atts['post_id']);
            if( !$post )
                return '';

            setup_postdata($post);
        }

        if( $content === null ){

            $template_title = isset($atts['template']) ? $atts['template'] : '';
            $template_id = $this->get_section_id_by('post_title', $template_title);

            if(!$template_id)
                return '';

            $template = get_post($template_id);
            $template = $template->post_content;

            if(empty($template))
                return '';
        } else {
            $template = $content;
        }


        $data = $this->get_post_data($post->ID);
        $template = do_shortcode($template);
        $html = $this->render_content($template, $data);

        $post = $_current_post;
        wp_reset_postdata();

        return $html;
    }

    function rb_display_posts($atts, $content=null){

        global $post;
        $_current_post = $post;

        $template_title = isset($atts['template']) ? $atts['template'] : '';
        $template_id = $this->get_section_id_by('post_title', $template_title);

        if(!$template_id)
            return '';

        $card_template = get_post($template_id);
        $card_template = $card_template->post_content;

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


        if( isset($atts['query_type']) && $atts['query_type']  == 'page' ){

            if(isset($atts['posts_per_page']) ){
                $atts['number'] = $atts['posts_per_page'];
            }

            $args = shortcode_atts(array(
                'sort_order' => 'asc',
                'sort_column' => 'post_title',
                'hierarchical' => 1,
                'exclude' => '',
                'include' => '',
                'meta_key' => '',
                'meta_value' => '',
                'child_of' => 0,
                'parent' => -1,
                'exclude_tree' => '',
                'number' => 6,
                'post_type' => 'page',
                'post_status' => 'publish'
            ), $atts );

            if( $args['number'] == -1 ) unset($args['number']); // no limit when -1

            if(isset($_GET['debug_show_cards']) ){
                echo '<pre>'.print_r($args, true).'</pre>';
                exit;
            }

            $query_posts = get_pages($args);

        } else if( isset($atts['query_type']) && $atts['query_type']  == 'post' ){

            $args = shortcode_atts( array(
                'posts_per_page'   => 6,
                'orderby'          => 'title',
                'order'            => 'ASC',
                'include'          => '',
                'exclude'          => '',
                'meta_key'         => '',
                'meta_value'       => '',
                'post_type'        => 'post',
                'post_mime_type'   => '',
                'post_parent'      => '',
                'post_status'      => 'publish',
            ), $atts );

            if(isset($_GET['debug_show_cards']) ){
                echo '<pre>'.print_r($args, true).'</pre>';
                exit;
            }

            $query_posts = get_posts($args);
        } else if( isset($atts['query_type']) && $atts['query_type']  == 'favorites' ){


            $favorite_type = isset($atts['post_type']) && !empty($atts['post_type']) ? $atts['post_type'] : 'property';

            $favorites = ffd_get_favorites($favorite_type);
            if(is_array($favorites) && !empty($favorites) ){

                $args = shortcode_atts( array(
                    'posts_per_page'   => 6,
                    'orderby'          => 'title',
                    'order'            => 'ASC',
                    'include'          => '',
                    'exclude'          => '',
                    'meta_key'         => '',
                    'meta_value'       => '',
                    'post_type'        => $favorite_type,
                    'post_mime_type'   => '',
                    'post_parent'      => '',
                    'post_status'      => 'publish',
                    'post__in'		   => $favorites,
                ), $atts );

                if(isset($_GET['debug_favorites']) ){
                    echo '<pre>'.print_r($args, true).'</pre>';
                    exit;
                }

                $query_posts = get_posts($args);

            } else {
                $query_posts = array();
            }

        }  else {

            $args = ffd_build_wpquery_args($atts);

            if(isset($_GET['debug_show_cards_query']) ){
                echo '<pre>'.print_r($args, true).'</pre>';
                exit;
            }

            $query = new WP_Query($args);
            global $post;
            $html = $no_posts_found = '';

            if( isset($query->posts) &&  count($query->posts) > 0 ){
                foreach($query->posts as $post){
                    setup_postdata( $post );
                    $data = $this->get_post_data($post->ID);
                    $html .= $this->render_content($card_template, $data);
                }
            } else {
                $no_posts_found = isset($atts['no_posts_message']) ? $atts['no_posts_message'] : 'No Data Found.';
                $html .= '<div class="ffd_display_posts ffd_no_posts '.( isset($args['post_type']) ? 'ffd_display_posts_' . $args['post_type'] : '') .'"><p>'.$no_posts_found.'</p></div>';
            }
            wp_reset_postdata();
            return do_shortcode($html);
        }



        $html = '';
        global $post;
        if( count($query_posts) > 0 ):

            foreach(array_chunk($query_posts, 3, true) as $posts):

                if( $this->ui_slider_content === false ){
                    $html .= '<div class="row rb-show_cards-row">';
                }

                foreach ($posts as $post):

                    $post = $post;
                    setup_postdata( $post );


                    $post_link = $post_image = $post_category = $post_title = $post_description = $post_date = '';
                    $categories = get_the_category();
                    if ( ! empty( $categories ) ) {
                        $post_category = esc_html( $categories[0]->name );
                    } else {
                        $post_category = '';
                    }
                    $post_image = ffdl_get_featured_image(false);
                    $post_image_small = ffdl_get_featured_image(false, 'small');
                    $post_image_medium = ffdl_get_featured_image(false, 'medium');
                    $post_image_large = ffdl_get_featured_image(false, 'large');
                    $post_description = wp_trim_words(get_the_content(), 20, '');
                    $post_date = get_the_date('M j, Y');
                    $post_link = get_permalink();
                    $post_title = get_the_title();

                    $favorite_class= '';
                    if( ffd_is_favorite($post->ID) )
                        $favorite_class = 'favorite';


                    $data = array(
                        'post_link' => $post_link,
                        'post_image' => $post_image,
                        'post_image_small' => $post_image_small,
                        'post_image_medium' => $post_image_medium,
                        'post_image_large' => $post_image_large,

                        'post_category' => $post_category,
                        'post_title' => $post_title,
                        'post_description' => $post_description,
                        'post_date' => $post_date,

                        /* alternative names for card  */

                        'card_link' => $post_link,
                        'card_image' => $post_image,

                        'card_image_small' => $post_image_small,
                        'card_image_medium' => $post_image_medium,
                        'card_image_large' => $post_image_large,

                        'card_category' => $post_category,
                        'card_title' => $post_title,
                        'card_description' => $post_description,
                        'card_date' => $post_date,

                        'ID' => $post->ID,
                        'favorite' => 'save-listing ' . $favorite_class,
                    );

                    if( $post->post_type == 'property' ){
                        $property_data = $this->get_property_data($post);

                        $data = array_merge($property_data, $data);
                    }

                    if( $this->ui_slider_content === false ){
                        $html .= '<div class="col-xs-12  col-sm-6 col-md-4 rb-show_cards-column">';
                    } else {
                        $html .= '<div class="swiper-slide">';
                    }

                    $html .= $this->render_content($card_template, $data);


                    $html .= '</div>';



                endforeach;
                if( $this->ui_slider_content === false ){
                    $html .= '</div>';
                }
            endforeach;
        endif;

        wp_reset_postdata();

        return do_shortcode($html);


    }

    function rb_blog_posts($atts, $content = null ){

        $template_title = isset($atts['template']) ? $atts['template'] : 'Blog Post Card';
        $template_id = $this->get_section_id_by('post_title', $template_title);

        if(!$template_id)
            return '';

        $card_template = get_post($template_id);
        $card_template = $card_template->post_content;

        if(empty($card_template))
            return '';

        $category_id = isset($atts['category_id']) ? $atts['category_id'] : false;
        $post_type = isset($atts['post_type']) ? $atts['post_type'] : 'post';
        $posts_per_page = isset($atts['posts_per_page']) ? $atts['posts_per_page'] : 3;

        if( !$category_id ){
            $category = get_queried_object();
            $category_id = isset($category->term_id) ? $category->term_id : '42';
        }

        $excludes = array(1, 58, 62);
        $excludes = array_diff($excludes, array($category_id));



        $defaults_args = array(
            'no_found_rows' => true,
            'update_post_term_cache' => false,
            'post_type' => $post_type,
            'posts_per_page' => $posts_per_page,
            'cat' => $category_id,
            'category__not_in'=>$excludes,
            'orderby' => 'date',
            'order ' => 'DESC'
        );

        $args = wp_parse_args( $atts, $defaults_args);
        $query = new WP_Query($args);

        $html = '';
        global $post;
        if( count($query->posts) > 0 ):

            foreach(array_chunk($query->posts, 3, true) as $posts):
                $html .= '<div class="row rb-post_cards-row">';
                foreach ($posts as $post):

                    $post = $post;
                    setup_postdata( $post );


                    $post_link = $post_image = $post_category = $post_title = $post_description = $post_date = '';
                    $categories = get_the_category();
                    if ( ! empty( $categories ) ) {
                        $post_category = esc_html( $categories[0]->name );
                    } else {
                        $post_category = 'Uncategorized';
                    }
                    $post_image = ffdl_get_featured_image();
                    $post_description = wp_trim_words( get_the_content(), 20, '');
                    $post_date = get_the_date('M j, Y');
                    $post_link = get_permalink();
                    $post_title = get_the_title();

                    $data = array(
                        'post_link' => $post_link,
                        'post_image' => $post_image,
                        'post_category' => $post_category,
                        'post_title' => $post_title,
                        'post_description' => $post_description,
                        'post_date' => $post_date,
                    );

                    $html .= '<div class="col-xs-12  col-sm-6 col-md-4 rb-display_cards-column">';
                    $html .= $this->render_content($card_template, $data);
                    $html .= '</div>';


                endforeach;
                $html .= '</div>';
            endforeach;
        endif;

        wp_reset_postdata();

        return do_shortcode('the_content', $html);

    }

    function ffd_ui($atts, $content=null, $tag=null){
        return $this->rb_ui_section($atts, $content, $tag);
    }


    function rb_ui_section($atts, $content = null ){

        $section_id = null;




        if( isset($atts['id']) ){
            $field_name = 'ID';
            $field_value = $atts['ID'];
        }

        if( empty($atts['name']) && !empty($atts['template']) ){
            $atts['name'] = $atts['template'];
        }

        if( isset($atts['name']) ){
            $field_name = 'post_title';
            $field_value = $atts['name'];
        }

        if( isset($atts['slug']) ){
            $field_name = 'post_name';
            $field_value = $atts['slug'];
        }

        $section_id = $this->get_section_id_by($field_name, $field_value);

        if(!$section_id)
            return '';

        $this->ui_section_atts = $atts;

        $section = get_post($section_id);
        $this->ui_section_name = $section->post_name;
        $section_content = $section->post_content;

        $section_content = do_shortcode($section_content);
        $content = $this->render_content($section_content, $atts);
        $content .= '<!-- FFD-UI '.$section_id.' '.$field_name.' /FFD-UI -->';
        return $content;
    }
    /*--------------------------------------------------------------------------------------
        *
        * rb_button
        *
        * @author Filip Stefansson, Nicolas Jonas
        * @since 1.0
        * //DW mod added xclass var
        *-------------------------------------------------------------------------------------*/
    function rb_button( $atts, $content = null ) {

        $atts = shortcode_atts( array(
            "type"     => false,
            "size"     => false,
            "block"    => false,
            "dropdown" => false,
            "link"     => '',
            "target"   => false,
            "disabled" => false,
            "active"   => false,
            "xclass"   => false,
            "title"    => false,
            "data"     => false,
            "bg_image" => false
        ), $atts );

        $class  = '';
        $class .= ( $atts['type'] )     ? ' btn-' . $atts['type'] : ' btn btn-default';
        $class .= ( $atts['size'] )     ? ' btn-' . $atts['size'] : '';
        $class .= ( $atts['block'] == 'true' )    ? ' btn-block' : '';
        $class .= ( $atts['dropdown']   == 'true' ) ? ' dropdown-toggle' : '';
        $class .= ( $atts['disabled']   == 'true' ) ? ' disabled' : '';
        $class .= ( $atts['active']     == 'true' )   ? ' active' : '';
        $class .= ( $atts['xclass'] )   ? ' ' . $atts['xclass'] : '';


        $class .= ( $atts['bg_image'] ) ? ' ' . 'rb-bg-image' : '';
        $bg_image = ($atts['bg_image']) ? 'background-image:url('.$atts['bg_image'].');' : '';

        $data_props = $this->parse_data_attributes( $atts['data'] );

        return sprintf(
            '<a href="%s" class="%s"%s%s%s style="%s">%s</a>',
            esc_url( $atts['link'] ),
            esc_attr( trim($class) ),
            ( $atts['target'] )     ? sprintf( ' target="%s"', esc_attr( $atts['target'] ) ) : '',
            ( $atts['title'] )      ? sprintf( ' title="%s"',  esc_attr( $atts['title'] ) )  : '',
            ( $data_props ) ? ' ' . $data_props : '',
            esc_attr( trim($bg_image) ),
            do_shortcode( $content )
        );

    }

    /*--------------------------------------------------------------------------------------
        *
        * rb_button_group
        *
        * @author M. W. Delaney
        *
        *-------------------------------------------------------------------------------------*/
    function rb_button_group( $atts, $content = null ) {

        $atts = shortcode_atts( array(
            "size"      => false,
            "vertical"  => false,
            "justified" => false,
            "dropup"    => false,
            "xclass"    => false,
            "data"      => false
        ), $atts );

        $class  = 'btn-group';
        $class .= ( $atts['size'] )         ? ' btn-group-' . $atts['size'] : '';
        $class .= ( $atts['vertical']   == 'true' )     ? ' btn-group-vertical' : '';
        $class .= ( $atts['justified']  == 'true' )    ? ' btn-group-justified' : '';
        $class .= ( $atts['dropup']     == 'true' )       ? ' dropup' : '';
        $class .= ( $atts['xclass'] )       ? ' ' . $atts['xclass'] : '';

        $data_props = $this->parse_data_attributes( $atts['data'] );

        return sprintf(
            '<div class="%s"%s>%s</div>',
            esc_attr( trim($class) ),
            ( $data_props ) ? ' ' . $data_props : '',
            do_shortcode( $content )
        );
    }

    /*--------------------------------------------------------------------------------------
        *
        * rb_button_toolbar
        *
        *
        *-------------------------------------------------------------------------------------*/
    function rb_button_toolbar( $atts, $content = null ) {

        $atts = shortcode_atts( array(
            "xclass" => false,
            "data"   => false
        ), $atts );

        $class  = 'btn-toolbar';
        $class .= ( $atts['xclass'] )   ? ' ' . $atts['xclass'] : '';

        $data_props = $this->parse_data_attributes( $atts['data'] );

        return sprintf(
            '<div class="%s"%s>%s</div>',
            esc_attr( trim($class) ),
            ( $data_props ) ? ' ' . $data_props : '',
            do_shortcode( $content )
        );
    }

    /*--------------------------------------------------------------------------------------
           *
           * rb_caret
           *
           * @author Filip Stefansson
           * @since 1.0
           *
           *-------------------------------------------------------------------------------------*/
    function rb_caret( $atts, $content = null ) {

        $atts = shortcode_atts( array(
            "xclass" => false,
            "data"   => false
        ), $atts );

        $class  = 'caret';
        $class .= ( $atts['xclass'] )   ? ' ' . $atts['xclass'] : '';

        $data_props = $this->parse_data_attributes( $atts['data'] );

        return sprintf(
            '<span class="%s"%s>%s</span>',
            esc_attr( trim($class) ),
            ( $data_props ) ? ' ' . $data_props : '',
            do_shortcode( $content )
        );
    }

    /*--------------------------------------------------------------------------------------
           *
           * rb_container
           *
           * @author Robin Wouters
           * @since 3.0.3.3
           *
           *-------------------------------------------------------------------------------------*/
    function rb_container( $atts, $content = null ) {

        $atts = shortcode_atts( array(
            "fluid"  => false,
            "xclass" => false,
            "xid" => false,
            "xstyle"   => '',
            "data"   => false
        ), $atts );

        $id = ( $atts['xid'] )   ? ' ' . $atts['xid'] : '';
        $style = ( $atts['xstyle'] )   ? ' ' . $atts['xstyle'] : '';
        $class  = ( $atts['fluid']   == 'true' )  ? 'container-fluid' : 'container';
        $class .= ( $atts['xclass'] )   ? ' ' . $atts['xclass'] : '';

        $data_props = $this->parse_data_attributes( $atts['data'] );

        return sprintf(
            '<div class="%s"%s id="%s" style="%s">%s</div>',
            esc_attr( trim($class) ),
            ( $data_props ) ? ' ' . $data_props : '',
            esc_attr( trim($id) ),
            $style,
            do_shortcode( $content )
        );
    }


    function rb_box_container( $atts, $content = null ) {

        $atts = shortcode_atts( array(
            "fluid"  => false,
            "xclass" => false,
            "xid" => false,
            "xstyle"   => '',
            "data"   => false
        ), $atts );

        $id = ( $atts['xid'] )   ? ' ' . $atts['xid'] : '';
        $style = ( $atts['xstyle'] )   ? ' ' . $atts['xstyle'] : '';
        $class  = ( $atts['fluid']   == 'true' )  ? 'rb-box-container-fluid' : 'rb-box-container';
        $class .= ( $atts['xclass'] )   ? ' ' . $atts['xclass'] : '';

        $data_props = $this->parse_data_attributes( $atts['data'] );

        return sprintf(
            '<div class="%s"%s id="%s" style="%s">%s</div>',
            esc_attr( trim($class) ),
            ( $data_props ) ? ' ' . $data_props : '',
            esc_attr( trim($id) ),
            $style,
            do_shortcode( $content )
        );
    }

    /*--------------------------------------------------------------------------------------
         *
         * rb_container_fluid
         *
         * @author Robin Wouters
         * @since 3.0.3.3
         *
         *-------------------------------------------------------------------------------------*/
    function rb_container_fluid( $atts, $content = null ) {

        $atts = shortcode_atts( array(
            "xclass" => false,
            "xid" => false,
            "xstyle"   => '',
            "data"   => false,
            "bg_image"   => false,
        ), $atts );

        if( isset($this->ui_section_atts) ){
            $atts = array_merge($atts, $this->ui_section_atts);
        }

        $id = ( $atts['xid'] )   ? ' ' . $atts['xid'] : '';
        $style = ( $atts['xstyle'] )   ? ' ' . $atts['xstyle'] : '';
        $class  = 'container-fluid' .  ( !empty($this->ui_section_name) ? ' ' . $this->ui_section_name.'-container' : '');
        $class .= ( $atts['xclass'] )   ? ' ' . $atts['xclass'] : '';

        $class .= ( $atts['bg_image'] ) ? ' ' . 'rb-bg-image' : '';
        $bg_image = ($atts['bg_image']) ? 'background-image:url('.$atts['bg_image'].');' : '';

        $data_props = $this->parse_data_attributes( $atts['data'] );

        $this->ui_section_atts = null;

        return sprintf(
            '<div class="%s"%s id="%s" style="%s%s">%s</div>',
            esc_attr( trim($class) ),
            ( $data_props ) ? ' ' . $data_props : '',
            esc_attr( trim($id) ),
            $style,
            esc_attr( trim($bg_image) ),
            do_shortcode( $content )
        );
    }

    /*--------------------------------------------------------------------------------------
        *
        * rb_dropdown
        *
        * @author M. W. Delaney
        *
        *-------------------------------------------------------------------------------------*/
    function rb_dropdown( $atts, $content = null ) {

        $atts = shortcode_atts( array(
            "xclass" => false,
            "data"   => false
        ), $atts );

        $class  = 'dropdown-menu';
        $class .= ( $atts['xclass'] )   ? ' ' . $atts['xclass'] : '';

        $data_props = $this->parse_data_attributes( $atts['data'] );

        return sprintf(
            '<ul role="menu" class="%s"%s>%s</ul>',
            esc_attr( trim($class) ),
            ( $data_props ) ? ' ' . $data_props : '',
            do_shortcode( $content )
        );
    }

    /*--------------------------------------------------------------------------------------
        *
        * rb_dropdown_item
        *
        * @author M. W. Delaney
        *
        *-------------------------------------------------------------------------------------*/
    function rb_dropdown_item( $atts, $content = null ) {

        $atts = shortcode_atts( array(
            "link"        => false,
            "disabled"    => false,
            "xclass"      => false,
            "data"        => false
        ), $atts );

        $li_class  = '';
        $li_class .= ( $atts['disabled']  == 'true' ) ? ' disabled' : '';

        $a_class  = '';
        $a_class .= ( $atts['xclass'] ) ? ' ' . $atts['xclass'] : '';

        $data_props = $this->parse_data_attributes( $atts['data'] );

        return sprintf(
            '<li role="presentation" class="%s"><a role="menuitem" href="%s" class="%s"%s>%s</a></li>',
            esc_attr( $li_class ),
            esc_url( $atts['link'] ),
            esc_attr( $a_class ),
            ( $data_props ) ? ' ' . $data_props : '',
            do_shortcode( $content )
        );
    }

    /*--------------------------------------------------------------------------------------
        *
        * rb_dropdown_divider
        *
        * @author M. W. Delaney
        *
        *-------------------------------------------------------------------------------------*/
    function rb_divider( $atts, $content = null ) {

        $atts = shortcode_atts( array(
            "xclass" => false,
            "data" => false
        ), $atts );

        $class  = 'divider';
        $class .= ( $atts['xclass'] )   ? ' ' . $atts['xclass'] : '';

        $data_props = $this->parse_data_attributes( $atts['data'] );

        return sprintf(
            '<li class="%s"%s>%s</li>',
            esc_attr( trim($class) ),
            ( $data_props ) ? ' ' . $data_props : '',
            do_shortcode( $content )
        );
    }

    /*--------------------------------------------------------------------------------------
        *
        * rb_dropdown_header
        *
        * @author M. W. Delaney
        *
        *-------------------------------------------------------------------------------------*/
    function rb_dropdown_header( $atts, $content = null ) {

        $atts = shortcode_atts( array(
            "xclass" => false,
            "data"   => false
        ), $atts );

        $class  = 'dropdown-header';
        $class .= ( $atts['xclass'] ) ? ' ' . $atts['xclass'] : '';

        $data_props = $this->parse_data_attributes( $atts['data'] );

        return sprintf(
            '<li class="%s"%s>%s</li>',
            esc_attr( trim($class) ),
            ( $data_props ) ? ' ' . $data_props : '',
            do_shortcode( $content )
        );
    }

    /*--------------------------------------------------------------------------------------
        *
        * rb_nav
        *
        *
        *-------------------------------------------------------------------------------------*/
    function rb_nav( $atts, $content = null ) {

        $atts = shortcode_atts( array(
            "type"      => false,
            "stacked"   => false,
            "justified" => false,
            "xclass"    => false,
            "data"      => false
        ), $atts );

        $class  = 'nav';
        $class .= ( $atts['type'] )         ? ' nav-' . $atts['type'] : ' nav-tabs';
        $class .= ( $atts['stacked']   == 'true' )      ? ' nav-stacked' : '';
        $class .= ( $atts['justified'] == 'true' )    ? ' nav-justified' : '';
        $class .= ( $atts['xclass'] )       ? ' ' . $atts['xclass'] : '';

        $data_props = $this->parse_data_attributes( $atts['data'] );

        return sprintf(
            '<ul class="%s"%s>%s</ul>',
            esc_attr( trim($class) ),
            ( $data_props ) ? ' ' . $data_props : '',
            do_shortcode( $content )
        );
    }

    /*--------------------------------------------------------------------------------------
        *
        * rb_nav_item
        *
        *
        *-------------------------------------------------------------------------------------*/
    function rb_nav_item( $atts, $content = null ) {

        $atts = shortcode_atts( array(
            "link"     => false,
            "active"   => false,
            "disabled" => false,
            "dropdown" => false,
            "xclass"   => false,
            "data"     => false,
        ), $atts );

        $li_classes  = '';
        $li_classes .= ( $atts['dropdown'] ) ? 'dropdown' : '';
        $li_classes .= ( $atts['active']   == 'true' )   ? ' active' : '';
        $li_classes .= ( $atts['disabled'] == 'true' ) ? ' disabled' : '';

        $a_classes  = '';
        $a_classes .= ( $atts['dropdown']   == 'true' ) ? ' dropdown-toggle' : '';
        $a_classes .= ( $atts['xclass'] )   ? ' ' . $atts['xclass'] : '';

        $data_props = $this->parse_data_attributes( $atts['data'] );

        # Wrong idea I guess ....
        #$pattern = ( $dropdown ) ? '<li%1$s><a href="%2$s"%3$s%4$s%5$s></a>%6$s</li>' : '<li%1$s><a href="%2$s"%3$s%4$s%5$s>%6$s</a></li>';

        //* If we have a dropdown shortcode inside the content we end the link before the dropdown shortcode, else all content goes inside the link
        $content = ( $atts['dropdown'] ) ? str_replace( '[dropdown]', '</a>[dropdown]', $content ) : $content . '</a>';

        return sprintf(
            '<li%1$s><a href="%2$s"%3$s%4$s%5$s>%6$s</li>',
            ( ! empty( $li_classes ) ) ? sprintf( ' class="%s"', esc_attr( $li_classes ) ) : '',
            esc_url( $atts['link'] ),
            ( ! empty( $a_classes ) )  ? sprintf( ' class="%s"', esc_attr( $a_classes ) )  : '',
            ( $atts['dropdown'] )   ? ' data-toggle="dropdown"' : '',
            ( $data_props ) ? ' ' . $data_props : '',
            do_shortcode( $content )
        );

    }

    /*--------------------------------------------------------------------------------------
        *
        * rb_alert
        *
        * @author Filip Stefansson
        * @since 1.0
        *
        *-------------------------------------------------------------------------------------*/
    function rb_alert( $atts, $content = null ) {

        $atts = shortcode_atts( array(
            "type"          => false,
            "dismissable"   => false,
            "xclass"        => false,
            "data"          => false
        ), $atts );

        $class  = 'alert';
        $class .= ( $atts['type'] )         ? ' alert-' . $atts['type'] : ' alert-success';
        $class .= ( $atts['dismissable']   == 'true' )  ? ' alert-dismissable' : '';
        $class .= ( $atts['xclass'] )       ? ' ' . $atts['xclass'] : '';

        $dismissable = ( $atts['dismissable'] ) ? '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>' : '';

        $data_props = $this->parse_data_attributes( $atts['data'] );

        return sprintf(
            '<div class="%s"%s>%s%s</div>',
            esc_attr( trim($class) ),
            ( $data_props )  ? ' ' . $data_props : '',
            $dismissable,
            do_shortcode( $content )
        );
    }

    /*--------------------------------------------------------------------------------------
        *
        * rb_progress
        *
        *
        *-------------------------------------------------------------------------------------*/
    function rb_progress( $atts, $content = null ) {

        $atts = shortcode_atts( array(
            "striped"   => false,
            "animated"  => false,
            "xclass"    => false,
            "data"      => false
        ), $atts );

        $class  = 'progress';
        $class .= ( $atts['striped']  == 'true' )  ? ' progress-striped' : '';
        $class .= ( $atts['animated']  == 'true' ) ? ' active' : '';
        $class .= ( $atts['xclass'] )   ? ' ' . $atts['xclass'] : '';

        $data_props = $this->parse_data_attributes( $atts['data'] );

        return sprintf(
            '<div class="%s"%s>%s</div>',
            esc_attr( trim($class) ),
            ( $data_props )  ? ' ' . $data_props : '',
            do_shortcode( $content )
        );
    }

    /*--------------------------------------------------------------------------------------
        *
        * rb_progress_bar
        *
        *
        *-------------------------------------------------------------------------------------*/
    function rb_progress_bar( $atts, $content = null ) {

        $atts = shortcode_atts( array(
            "type"      => false,
            "percent"   => false,
            "label"     => false,
            "xclass"    => false,
            "data"      => false
        ), $atts );

        $class  = 'progress-bar';
        $class .= ( $atts['type'] )   ? ' progress-bar-' . $atts['type'] : '';
        $class .= ( $atts['xclass'] ) ? ' ' . $atts['xclass'] : '';

        $data_props = $this->parse_data_attributes( $atts['data'] );

        return sprintf(
            '<div class="%s" role="progressbar" %s%s>%s</div>',
            esc_attr( trim($class) ),
            ( $atts['percent'] )      ? ' aria-value="' . (int) $atts['percent'] . '" aria-valuemin="0" aria-valuemax="100" style="width: ' . (int) $atts['percent'] . '%;"' : '',
            ( $data_props )   ? ' ' . $data_props : '',
            ( $atts['percent'] )      ? sprintf('<span%s>%s</span>', ( !$atts['label'] ) ? ' class="sr-only"' : '', (int) $atts['percent'] . '% Complete') : ''
        );
    }

    /*--------------------------------------------------------------------------------------
        *
        * rb_code
        *
        * @author Filip Stefansson
        * @since 1.0
        *
        *-------------------------------------------------------------------------------------*/
    function rb_code( $atts, $content = null ) {

        $atts = shortcode_atts( array(
            "inline"      => false,
            "scrollable"  => false,
            "xclass"      => false,
            "data"        => false
        ), $atts );

        $class  = '';
        $class .= ( $atts['scrollable']   == 'true' )  ? ' pre-scrollable' : '';
        $class .= ( $atts['xclass'] )   ? ' ' . $atts['xclass'] : '';

        $data_props = $this->parse_data_attributes( $atts['data'] );

        return sprintf(
            '<%1$s class="%2$s"%3$s>%4$s</%1$s>',
            ( $atts['inline'] ) ? 'code' : 'pre',
            esc_attr( trim($class) ),
            ( $data_props ) ? ' ' . $data_props : '',
            do_shortcode( $content )
        );
    }

    /*--------------------------------------------------------------------------------------
        *
        * rb_row
        *
        * @author Filip Stefansson
        * @since 1.0
        *
        *-------------------------------------------------------------------------------------*/
    function rb_row( $atts, $content = null ) {

        $atts = shortcode_atts( array(
            "xclass" => false,
            "data"   => false
        ), $atts );

        $class  = 'row' .  ( !empty($this->ui_section_name) ? ' ' . $this->ui_section_name.'-row' : '');;
        $class .= ( $atts['xclass'] )   ? ' ' . $atts['xclass'] : '';

        $data_props = $this->parse_data_attributes( $atts['data'] );

        return sprintf(
            '<div class="%s"%s>%s</div>',
            esc_attr( trim($class) ),
            ( $data_props ) ? ' ' . $data_props : '',
            do_shortcode( $content )
        );
    }

    /*--------------------------------------------------------------------------------------
        *
        * rb_column
        *
        * @author Simon Yeldon
        * @since 1.0
        * @todo pull and offset
        *-------------------------------------------------------------------------------------*/
    function rb_column( $atts, $content = null ) {

        $atts = shortcode_atts( array(
            "lg"          => false,
            "md"          => false,
            "sm"          => false,
            "xs"          => false,
            "offset_lg"   => false,
            "offset_md"   => false,
            "offset_sm"   => false,
            "offset_xs"   => false,
            "pull_lg"     => false,
            "pull_md"     => false,
            "pull_sm"     => false,
            "pull_xs"     => false,
            "push_lg"     => false,
            "push_md"     => false,
            "push_sm"     => false,
            "push_xs"     => false,
            "xclass"      => false,
            "data"        => false,
            "bg_image"    => false
        ), $atts );

        $class  = '';
        $class .= ( $atts['lg'] )			                                ? ' col-lg-' . $atts['lg'] : '';
        $class .= ( $atts['md'] )                                           ? ' col-md-' . $atts['md'] : '';
        $class .= ( $atts['sm'] )                                           ? ' col-sm-' . $atts['sm'] : '';
        $class .= ( $atts['xs'] )                                           ? ' col-xs-' . $atts['xs'] : '';
        $class .= ( $atts['offset_lg'] || $atts['offset_lg'] === "0" )      ? ' col-lg-offset-' . $atts['offset_lg'] : '';
        $class .= ( $atts['offset_md'] || $atts['offset_md'] === "0" )      ? ' col-md-offset-' . $atts['offset_md'] : '';
        $class .= ( $atts['offset_sm'] || $atts['offset_sm'] === "0" )      ? ' col-sm-offset-' . $atts['offset_sm'] : '';
        $class .= ( $atts['offset_xs'] || $atts['offset_xs'] === "0" )      ? ' col-xs-offset-' . $atts['offset_xs'] : '';
        $class .= ( $atts['pull_lg']   || $atts['pull_lg'] === "0" )        ? ' col-lg-pull-' . $atts['pull_lg'] : '';
        $class .= ( $atts['pull_md']   || $atts['pull_md'] === "0" )        ? ' col-md-pull-' . $atts['pull_md'] : '';
        $class .= ( $atts['pull_sm']   || $atts['pull_sm'] === "0" )        ? ' col-sm-pull-' . $atts['pull_sm'] : '';
        $class .= ( $atts['pull_xs']   || $atts['pull_xs'] === "0" )        ? ' col-xs-pull-' . $atts['pull_xs'] : '';
        $class .= ( $atts['push_lg']   || $atts['push_lg'] === "0" )        ? ' col-lg-push-' . $atts['push_lg'] : '';
        $class .= ( $atts['push_md']   || $atts['push_md'] === "0" )        ? ' col-md-push-' . $atts['push_md'] : '';
        $class .= ( $atts['push_sm']   || $atts['push_sm'] === "0" )        ? ' col-sm-push-' . $atts['push_sm'] : '';
        $class .= ( $atts['push_xs']   || $atts['push_xs'] === "0" )        ? ' col-xs-push-' . $atts['push_xs'] : '';
        $class .= ( $atts['xclass'] )                                       ? ' ' . $atts['xclass'] : '';

        $class .= ( $atts['bg_image'] ) ? ' ' . 'rb-bg-image' : '';
        $bg_image = ($atts['bg_image']) ? 'background-image:url('.$atts['bg_image'].');' : '';
        $data_props = $this->parse_data_attributes( $atts['data'] );

        return sprintf(
            '<div class="%s"%s style="%s">%s</div>',
            esc_attr( trim($class) ),
            ( $data_props ) ? ' ' . $data_props : '',
            esc_attr( trim($bg_image) ),
            do_shortcode( $content )
        );
    }

    /*--------------------------------------------------------------------------------------
        *
        * rb_list_group
        *
        * @author M. W. Delaney
        *
        *-------------------------------------------------------------------------------------*/
    function rb_list_group( $atts, $content = null ) {

        $atts = shortcode_atts( array(
            "linked" => false,
            "xclass" => false,
            "data"   => false
        ), $atts );

        $class  = 'list-group';
        $class .= ( $atts['xclass'] )   ? ' ' . $atts['xclass'] : '';

        $data_props = $this->parse_data_attributes( $atts['data'] );

        return sprintf(
            '<%1$s class="%2$s"%3$s>%4$s</%1$s>',
            ( $atts['linked'] == 'true' ) ? 'div' : 'ul',
            esc_attr( trim($class) ),
            ( $data_props ) ? ' ' . $data_props : '',
            do_shortcode( $content )
        );
    }

    /*--------------------------------------------------------------------------------------
        *
        * rb_list_group_item
        *
        * @author M. W. Delaney
        *
        *-------------------------------------------------------------------------------------*/
    function rb_list_group_item( $atts, $content = null ) {

        $atts = shortcode_atts( array(
            "link"    => false,
            "type"    => false,
            "active"  => false,
            "target"   => false,
            "xclass"  => false,
            "data"    => false
        ), $atts );

        $class  = 'list-group-item';
        $class .= ( $atts['type'] )     ? ' list-group-item-' . $atts['type'] : '';
        $class .= ( $atts['active']   == 'true' )   ? ' active' : '';
        $class .= ( $atts['xclass'] )   ? ' ' . $atts['xclass'] : '';

        $data_props = $this->parse_data_attributes( $atts['data'] );

        return sprintf(
            '<%1$s %2$s %3$s class="%4$s"%5$s>%6$s</%1$s>',
            ( $atts['link'] )     ? 'a' : 'li',
            ( $atts['link'] )     ? 'href="' . esc_url( $atts['link'] ) . '"' : '',
            ( $atts['target'] )   ? sprintf( ' target="%s"', esc_attr( $atts['target'] ) ) : '',
            esc_attr( trim($class) ),
            ( $data_props ) ? ' ' . $data_props : '',
            do_shortcode( $content )
        );
    }

    /*--------------------------------------------------------------------------------------
        *
        * rb_list_group_item_heading
        *
        *
        *-------------------------------------------------------------------------------------*/
    function rb_list_group_item_heading( $atts, $content = null ) {

        $atts = shortcode_atts( array(
            "xclass" => false,
            "data"   => false
        ), $atts );

        $class  = 'list-group-item-heading';
        $class .= ( $atts['xclass'] )   ? ' ' . $atts['xclass'] : '';

        $data_props = $this->parse_data_attributes( $atts['data'] );

        return sprintf(
            '<h4 class="%s"%s>%s</h4>',
            esc_attr( trim($class) ),
            ( $data_props ) ? ' ' . $data_props : '',
            do_shortcode( $content )
        );
    }

    /*--------------------------------------------------------------------------------------
        *
        * rb_list_group_item_text
        *
        *
        *-------------------------------------------------------------------------------------*/
    function rb_list_group_item_text( $atts, $content = null ) {

        $atts = shortcode_atts( array(
            "xclass" => false,
            "data"   => false
        ), $atts );

        $class  = 'list-group-item-text';
        $class .= ( $atts['xclass'] )   ? ' ' . $atts['xclass'] : '';

        $data_props = $this->parse_data_attributes( $atts['data'] );

        return sprintf(
            '<p class="%s"%s>%s</p>',
            esc_attr( trim($class) ),
            ( $data_props ) ? ' ' . $data_props : '',
            do_shortcode( $content )
        );
    }

    /*--------------------------------------------------------------------------------------
        *
        * rb_breadcrumb
        *
        *
        *-------------------------------------------------------------------------------------*/
    function rb_breadcrumb( $atts, $content = null ) {

        $atts = shortcode_atts( array(
            "xclass" => false,
            "data"   => false
        ), $atts );

        $class  = 'breadcrumb';
        $class .= ( $atts['xclass'] )   ? ' ' . $atts['xclass'] : '';

        $data_props = $this->parse_data_attributes( $atts['data'] );

        return sprintf(
            '<ol class="%s"%s>%s</ol>',
            esc_attr( trim($class) ),
            ( $data_props ) ? ' ' . $data_props : '',
            do_shortcode( $content )
        );
    }

    /*--------------------------------------------------------------------------------------
        *
        * rb_breadcrumb_item
        *
        * @author M. W. Delaney
        *
        *-------------------------------------------------------------------------------------*/
    function rb_breadcrumb_item( $atts, $content = null ) {

        $atts = shortcode_atts( array(
            "link" => false,
            "xclass" => false,
            "data" => false
        ), $atts );

        $class  = '';
        $class .= ( $atts['xclass'] )   ? ' ' . $atts['xclass'] : '';

        $data_props = $this->parse_data_attributes( $atts['data'] );

        return sprintf(
            '<li><a href="%s" class="%s"%s>%s</a></li>',
            esc_url( $atts['link'] ),
            esc_attr( trim($class) ),
            ( $data_props ) ? ' ' . $data_props : '',
            do_shortcode( $content )
        );
    }

    /*--------------------------------------------------------------------------------------
        *
        * rb_label
        *
        * @author Filip Stefansson
        * @since 1.0
        *
        *-------------------------------------------------------------------------------------*/
    function rb_label( $atts, $content = null ) {

        $atts = shortcode_atts( array(
            "type"      => false,
            "xclass"    => false,
            "data"      => false
        ), $atts );

        $class  = 'label';
        $class .= ( $atts['type'] )     ? ' label-' . $atts['type'] : ' label-default';
        $class .= ( $atts['xclass'] )   ? ' ' . $atts['xclass'] : '';

        $data_props = $this->parse_data_attributes( $atts['data'] );

        return sprintf(
            '<span class="%s"%s>%s</span>',
            esc_attr( trim($class) ),
            ( $data_props ) ? ' ' . $data_props : '',
            do_shortcode( $content )
        );
    }

    /*--------------------------------------------------------------------------------------
        *
        * rb_badge
        *
        * @author Filip Stefansson
        * @since 1.0
        *
        *-------------------------------------------------------------------------------------*/
    function rb_badge( $atts, $content = null ) {

        $atts = shortcode_atts( array(
            "right"   => false,
            "xclass"  => false,
            "data"    => false
        ), $atts );

        $class  = 'badge';
        $class .= ( $atts['right']   == 'true' )    ? ' pull-right' : '';
        $class .= ( $atts['xclass'] )   ? ' ' . $atts['xclass'] : '';

        $data_props = $this->parse_data_attributes( $atts['data'] );

        return sprintf(
            '<span class="%s"%s>%s</span>',
            esc_attr( trim($class) ),
            ( $data_props ) ? ' ' . $data_props : '',
            do_shortcode( $content )
        );
    }

    /*--------------------------------------------------------------------------------------
        *
        * rb_icon
        *
        * @author Filip Stefansson
        * @since 1.0
        *
        *-------------------------------------------------------------------------------------*/
    function rb_icon( $atts, $content = null ) {

        $atts = shortcode_atts( array(
            "type"   => false,
            "xclass" => false,
            "data"   => false
        ), $atts );

        $class  = 'glyphicon';
        $class .= ( $atts['type'] )     ? ' glyphicon-' . $atts['type'] : '';
        $class .= ( $atts['xclass'] )   ? ' ' . $atts['xclass'] : '';

        $data_props = $this->parse_data_attributes( $atts['data'] );

        return sprintf(
            '<span class="%s"%s>%s</span>',
            esc_attr( trim($class) ),
            ( $data_props ) ? ' ' . $data_props : '',
            do_shortcode( $content )
        );
    }

    /*--------------------------------------------------------------------------------------
        *
        * simple_table
        *
        * @author Filip Stefansson
        * @since 1.0
        *
        *-------------------------------------------------------------------------------------
    function rb_table( $atts ) {
            extract( shortcode_atts( array(
                    'cols' => 'none',
                    'data' => 'none',
                    'bordered' => false,
                    'striped' => false,
                    'hover' => false,
                    'condensed' => false,
            ), $atts ) );
            $cols = explode(',',$cols);
            $data = explode(',',$data);
            $total = count($cols);
            $return  = '<table class="table ';
            $return .= ($bordered) ? 'table-bordered ' : '';
            $return .= ($striped) ? 'table-striped ' : '';
            $return .= ($hover) ? 'table-hover ' : '';
            $return .= ($condensed) ? 'table-condensed ' : '';
            $return .='"><tr>';
            foreach($cols as $col):
                    $return .= '<th>'.$col.'</th>';
            endforeach;
            $output .= '</tr><tr>';
            $counter = 1;
            foreach($data as $datum):
                    $return .= '<td>'.$datum.'</td>';
                    if($counter%$total==0):
                            $return .= '</tr>';
                    endif;
                    $counter++;
            endforeach;
                    $return .= '</table>';
            return $return;
    }
    */

    /*--------------------------------------------------------------------------------------
        *
        * rb_table_wrap
        *
        * @author Filip Stefansson
        * @since 1.0
        *
        *-------------------------------------------------------------------------------------*/
    function rb_table_wrap( $atts, $content = null ) {

        $atts = shortcode_atts( array(
            'bordered'   => false,
            'striped'    => false,
            'hover'      => false,
            'condensed'  => false,
            'responsive' => false,
            'xclass'     => false,
            'data'       => false
        ), $atts );

        $class  = 'table';
        $class .= ( $atts['bordered']  == 'true' )    ? ' table-bordered' : '';
        $class .= ( $atts['striped']   == 'true' )    ? ' table-striped' : '';
        $class .= ( $atts['hover']     == 'true' )    ? ' table-hover' : '';
        $class .= ( $atts['condensed'] == 'true' )    ? ' table-condensed' : '';
        $class .= ( $atts['xclass'] )                 ? ' ' . $atts['xclass'] : '';

        $return = '';

        $tag = array('table');
        $content = do_shortcode($content);

        $return .= $this->scrape_dom_element($tag, $content, $class, '', $atts['data']);
        $return = ( $atts['responsive'] ) ? '<div class="table-responsive">' . $return . '</div>' : $return;
        return $return;
    }


    /*--------------------------------------------------------------------------------------
        *
        * rb_well
        *
        * @author Filip Stefansson
        * @since 1.0
        *
        * Options:
        *   size: sm = small, lg = large
        *
        *-------------------------------------------------------------------------------------*/
    function rb_well( $atts, $content = null ) {

        $atts = shortcode_atts( array(
            "size"   => false,
            "xclass" => false,
            "data"   => false
        ), $atts );

        $class  = 'well';
        $class .= ( $atts['size'] )     ? ' well-' . $atts['size'] : '';
        $class .= ( $atts['xclass'] )   ? ' ' . $atts['xclass'] : '';

        $data_props = $this->parse_data_attributes( $atts['data'] );

        return sprintf(
            '<div class="%s"%s>%s</div>',
            esc_attr( trim($class) ),
            ( $data_props ) ? ' ' . $data_props : '',
            do_shortcode( $content )
        );
    }

    /*--------------------------------------------------------------------------------------
        *
        * rb_panel
        *
        * @author M. W. Delaney
        * @since 1.0
        *
        *-------------------------------------------------------------------------------------*/
    function rb_panel( $atts, $content = null ) {

        $atts = shortcode_atts( array(
            "title"   => false,
            "heading" => false,
            "type"    => false,
            "footer"  => false,
            "xclass"  => false,
            "data"    => false
        ), $atts );

        $class  = 'panel';
        $class .= ( $atts['type'] )     ? ' panel-' . $atts['type'] : ' panel-default';
        $class .= ( $atts['xclass'] )   ? ' ' . $atts['xclass'] : '';

        if( ! $atts['heading'] && $atts['title'] ) {
            $atts['heading'] = $atts['title'];
            $atts['title'] = true;
        }

        $data_props = $this->parse_data_attributes( $atts['data'] );

        $footer = ( $atts['footer'] ) ? '<div class="panel-footer">' . $atts['footer'] . '</div>' : '';

        if ( $atts['heading'] ) {
            $heading = sprintf(
                '<div class="panel-heading">%s%s%s</div>',
                ( $atts['title'] ) ? '<h3 class="panel-title">' : '',
                esc_html( $atts['heading'] ),
                ( $atts['title'] ) ? '</h3>' : ''
            );
        }
        else {
            $heading = '';
        }

        return sprintf(
            '<div class="%s"%s>%s<div class="panel-body">%s</div>%s</div>',
            esc_attr( trim($class) ),
            ( $data_props ) ? ' ' . $data_props : '',
            $heading,
            do_shortcode( $content ),
            ( $footer ) ? ' ' . $footer : ''
        );
    }

    /*--------------------------------------------------------------------------------------
        *
        * rb_tabs
        *
        * @author Filip Stefansson
        * @since 1.0
        * Modified by TwItCh twitch@designweapon.com
        * Now acts a whole nav/tab/pill shortcode solution!
        *-------------------------------------------------------------------------------------*/
    function rb_tabs( $atts, $content = null ) {

        if( isset( $GLOBALS['tarb_count'] ) )
            $GLOBALS['tarb_count']++;
        else
            $GLOBALS['tarb_count'] = 0;

        $GLOBALS['tarb_default_count'] = 0;

        $atts = apply_filters('rb_tarb_atts',$atts);

        $atts = shortcode_atts( array(
            "type"    => false,
            "xclass"  => false,
            "data"    => false,
            "name"    => false,
        ), $atts );

        $ul_class  = 'nav';
        $ul_class .= ( $atts['type'] )     ? ' nav-' . $atts['type'] : ' nav-tabs';
        $ul_class .= ( $atts['xclass'] )   ? ' ' . $atts['xclass'] : '';

        $tabs_content_class = ( $atts['xclass'] ) ? $atts['xclass'] . '_content' : '' ;
        $div_class = 'tab-content' ;

        // If user defines name of group, use that for ID for tab history purposes
        if(isset($atts['name'])) {
            $id = $atts['name'];
        } else {
            $id = 'custom-tabs-' . $GLOBALS['tarb_count'];
        }


        $data_props = $this->parse_data_attributes( $atts['data'] );

        $atts_map = ffd_rb_attribute_map( $content );

        // Extract the tab titles for use in the tab widget.
        if ( $atts_map ) {
            $tabs = array();
            $GLOBALS['tarb_default_active'] = true;
            foreach( $atts_map as $check ) {
                if( !empty($check["tab"]["active"]) ) {
                    $GLOBALS['tarb_default_active'] = false;
                }
            }
            $i = 0;
            foreach( $atts_map as $tab ) {

                $class  ='';
                $class .= ( !empty($tab["tab"]["active"]) || ($GLOBALS['tarb_default_active'] && $i == 0) ) ? 'active' : '';
                $class .= ( !empty($tab["tab"]["xclass"]) ) ? ' ' . esc_attr($tab["tab"]["xclass"]) : '';

                if(!isset($tab["tab"]["link"])) {
                    $tab_id = 'custom-tab-' . $GLOBALS['tarb_count'] . '-' . md5( $tab["tab"]["title"] );
                } else {
                    $tab_id = $tab["tab"]["link"];
                }

                $tabs[] = sprintf(
                    '<li%s><a href="#%s" data-toggle="tab" >%s</a></li>',
                    ( !empty($class) ) ? ' class="' . $class . '"' : '',
                    sanitize_html_class($tab_id),
                    $tab["tab"]["title"]
                );
                $i++;
            }
        }
        $output = sprintf(
            '<ul class="%s" id="%s"%s>%s</ul><div class="%s %s">%s</div>',
            esc_attr( $ul_class ),
            sanitize_html_class( $id ),
            ( $data_props ) ? ' ' . $data_props : '',
            ( $tabs )  ? implode( $tabs ) : '',
            sanitize_html_class( $div_class ),
            sanitize_html_class( $tabs_content_class ),
            do_shortcode( $content )
        );

        return apply_filters('rb_tabs', $output);
    }

    /*--------------------------------------------------------------------------------------
        *
        * rb_tab
        *
        * @author Filip Stefansson
        * @since 1.0
        *
        *-------------------------------------------------------------------------------------*/
    function rb_tab( $atts, $content = null ) {

        $atts = shortcode_atts( array(
            'title'   => false,
            'active'  => false,
            'fade'    => false,
            'xclass'  => false,
            'data'    => false,
            'link'    => false
        ), $atts );

        if( $GLOBALS['tarb_default_active'] && $GLOBALS['tarb_default_count'] == 0 ) {
            $atts['active'] = true;
        }
        $GLOBALS['tarb_default_count']++;

        $class  = 'tab-pane';
        $class .= ( $atts['fade']   == 'true' )                            ? ' fade' : '';
        $class .= ( $atts['active'] == 'true' )                            ? ' active' : '';
        $class .= ( $atts['active'] == 'true' && $atts['fade'] == 'true' ) ? ' in' : '';
        $class .= ( $atts['xclass'] )                                      ? ' ' . $atts['xclass'] : '';


        if(!isset($atts['link']) || $atts['link'] == NULL) {
            $id = 'custom-tab-' . $GLOBALS['tarb_count'] . '-' . md5( $atts['title'] );
        } else {
            $id = $atts['link'];
        }
        $data_props = $this->parse_data_attributes( $atts['data'] );

        return sprintf(
            '<div id="%s" class="%s"%s>%s</div>',
            sanitize_html_class($id),
            esc_attr( trim($class) ),
            ( $data_props ) ? ' ' . $data_props : '',
            do_shortcode( $content )
        );

    }




    /*--------------------------------------------------------------------------------------
        *
        * rb_collapsibles
        *
        * @author Filip Stefansson
        * @since 1.0
        *
        *-------------------------------------------------------------------------------------*/
    function rb_collapsibles( $atts, $content = null ) {

        if( isset($GLOBALS['collapsibles_count']) )
            $GLOBALS['collapsibles_count']++;
        else
            $GLOBALS['collapsibles_count'] = 0;

        $atts = shortcode_atts( array(
            "xclass" => false,
            "data"   => false
        ), $atts );

        $class = 'panel-group';
        $class .= ( $atts['xclass'] )   ? ' ' . $atts['xclass'] : '';

        $id = 'custom-collapse-'. $GLOBALS['collapsibles_count'];

        $data_props = $this->parse_data_attributes( $atts['data'] );

        return sprintf(
            '<div class="%s" id="%s"%s>%s</div>',
            esc_attr( trim($class) ),
            esc_attr($id),
            ( $data_props ) ? ' ' . $data_props : '',
            do_shortcode( $content )
        );

    }


    /*--------------------------------------------------------------------------------------
        *
        * rb_collapse
        *
        * @author Filip Stefansson
        * @since 1.0
        *
        *-------------------------------------------------------------------------------------*/
    function rb_collapse( $atts, $content = null ) {

        if( isset($GLOBALS['single_collapse_count']) )
            $GLOBALS['single_collapse_count']++;
        else
            $GLOBALS['single_collapse_count'] = 0;

        $atts = shortcode_atts( array(
            "title"   => false,
            "type"    => false,
            "active"  => false,
            "xclass"  => false,
            "data"    => false
        ), $atts );

        $panel_class = 'panel';
        $panel_class .= ( $atts['type'] )     ? ' panel-' . $atts['type'] : ' panel-default';
        $panel_class .= ( $atts['xclass'] )   ? ' ' . $atts['xclass'] : '';

        $collapse_class = 'panel-collapse';
        $collapse_class .= ( $atts['active'] == 'true' )  ? ' in' : ' collapse';

        $a_class = '';
        $a_class .= ( $atts['active'] == 'true' )  ? '' : 'collapsed';

        $parent = isset( $GLOBALS['collapsibles_count'] ) ? 'custom-collapse-' . $GLOBALS['collapsibles_count'] : 'single-collapse';
        $current_collapse = $parent . '-' . $GLOBALS['single_collapse_count'];

        $data_props = $this->parse_data_attributes( $atts['data'] );

        return sprintf(
            '<div class="%1$s"%2$s>
				<div class="panel-heading">
					<h4 class="panel-title">
						<a class="%3$s" data-toggle="collapse"%4$s href="#%5$s">%6$s</a>
					</h4>
				</div>
				<div id="%5$s" class="%7$s">
					<div class="panel-body">%8$s</div>
				</div>
			</div>',
            esc_attr( $panel_class ),
            ( $data_props )   ? ' ' . $data_props : '',
            $a_class,
            ( $parent )       ? ' data-parent="#' . $parent . '"' : '',
            $current_collapse,
            $atts['title'],
            esc_attr( $collapse_class ),
            do_shortcode( $content )
        );
    }


    /*--------------------------------------------------------------------------------------
        *
        * rb_carousel
        *
        * @since 1.0
        *
        *-------------------------------------------------------------------------------------*/
    function rb_carousel( $atts, $content = null ) {

        if( isset($GLOBALS['carousel_count']) )
            $GLOBALS['carousel_count']++;
        else
            $GLOBALS['carousel_count'] = 0;

        $GLOBALS['carousel_default_count'] = 0;

        $atts = shortcode_atts( array(
            "interval" => false,
            "pause"    => false,
            "wrap"     => false,
            "xclass"   => false,
            "data"     => false,
        ), $atts );

        $div_class  = 'carousel slide';
        $div_class .= ( $atts['xclass'] ) ? ' ' . $atts['xclass'] : '';

        $inner_class = 'carousel-inner';

        $id = 'custom-carousel-'. $GLOBALS['carousel_count'];

        $data_props = $this->parse_data_attributes( $atts['data'] );

        $atts_map = ffd_rb_attribute_map( $content );

        // Extract the slide titles for use in the carousel widget.
        if ( $atts_map ) {
            $indicators = array();
            $GLOBALS['carousel_default_active'] = true;
            foreach( $atts_map as $check ) {
                if( !empty($check["carousel-item"]["active"]) ) {
                    $GLOBALS['carousel_default_active'] = false;
                }
            }
            $i = 0;
            foreach( $atts_map as $slide ) {
                $indicators[] = sprintf(
                    '<li class="%s" data-target="%s" data-slide-to="%s"></li>',
                    ( !empty($slide["carousel-item"]["active"]) || ($GLOBALS['carousel_default_active'] && $i == 0) ) ? 'active' : '',
                    esc_attr( '#' . $id ),
                    esc_attr( $i )
                );
                $i++;
            }
        }
        return sprintf(
            '<div class="%s" id="%s" data-ride="carousel"%s%s%s%s>%s<div class="%s">%s</div>%s%s</div>',
            esc_attr( $div_class ),
            esc_attr( $id ),
            ( $atts['interval'] )   ? sprintf( ' data-interval="%d"', $atts['interval'] ) : '',
            ( $atts['pause'] )      ? sprintf( ' data-pause="%s"', esc_attr( $atts['pause'] ) ) : '',
            ( $atts['wrap'] == 'true' )       ? sprintf( ' data-wrap="%s"', esc_attr( $atts['wrap'] ) ) : '',
            ( $data_props ) ? ' ' . $data_props : '',
            ( $indicators ) ? '<ol class="carousel-indicators">' . implode( $indicators ) . '</ol>' : '',
            esc_attr( $inner_class ),
            do_shortcode( $content ),
            '<a class="left carousel-control"  href="' . esc_url( '#' . $id ) . '" data-slide="prev"><span class="glyphicon glyphicon-chevron-left"></span></a>',
            '<a class="right carousel-control" href="' . esc_url( '#' . $id ) . '" data-slide="next"><span class="glyphicon glyphicon-chevron-right"></span></a>'
        );
    }


    /*--------------------------------------------------------------------------------------
        *
        * rb_carousel_item
        *
        * @author Filip Stefansson
        * @since 1.0
        *
        *-------------------------------------------------------------------------------------*/
    function rb_carousel_item( $atts, $content = null ) {

        $atts = shortcode_atts( array(
            "active"  => false,
            "caption" => false,
            "xclass"  => false,
            "data"    => false
        ), $atts );

        if( $GLOBALS['carousel_default_active'] && $GLOBALS['carousel_default_count'] == 0 ) {
            $atts['active'] = true;
        }
        $GLOBALS['carousel_default_count']++;

        $class  = 'item';
        $class .= ( $atts['active']   == 'true' ) ? ' active' : '';
        $class .= ( $atts['xclass'] ) ? ' ' . $atts['xclass'] : '';

        $data_props = $this->parse_data_attributes( $atts['data'] );

        //$content = preg_replace('/class=".*?"/', '', $content);
        $content = preg_replace('/alignnone/', '', $content);
        $content = preg_replace('/alignright/', '', $content);
        $content = preg_replace('/alignleft/', '', $content);
        $content = preg_replace('/aligncenter/', '', $content);

        return sprintf(
            '<div class="%s"%s>%s%s</div>',
            esc_attr( trim($class) ),
            ( $data_props ) ? ' ' . $data_props : '',
            do_shortcode( $content ),
            ( $atts['caption'] ) ? '<div class="carousel-caption">' . esc_html( $atts['caption'] ) . '</div>' : ''
        );
    }

    /*--------------------------------------------------------------------------------------
        *
        * rb_tooltip
        *
        * @author
        * @since 1.0
        *
        *-------------------------------------------------------------------------------------*/


    function rb_tooltip( $atts, $content = null ) {

        $atts = shortcode_atts( array(
            'title'     => '',
            'placement' => 'top',
            'animation' => 'true',
            'html'      => 'false',
            'data'      => ''
        ), $atts );

        $class  = 'bs-tooltip';

        $atts['data']   .= ( $atts['animation'] ) ? $this->check_for_data($atts['data']) . 'animation,' . $atts['animation'] : '';
        $atts['data']   .= ( $atts['placement'] ) ? $this->check_for_data($atts['data']) . 'placement,' . $atts['placement'] : '';
        $atts['data']   .= ( $atts['html'] )      ? $this->check_for_data($atts['data']) . 'html,'      .$atts['html']      : '';

        $return = '';
        $tag = 'span';
        $content = do_shortcode($content);
        $return .= $this->get_dom_element($tag, $content, $class, $atts['title'], $atts['data']);
        return $return;

    }

    /*--------------------------------------------------------------------------------------
        *
        * rb_popover
        *
        *
        *-------------------------------------------------------------------------------------*/

    function rb_popover( $atts, $content = null ) {

        $atts = shortcode_atts( array(
            'title'     => false,
            'text'      => '',
            'placement' => 'top',
            'animation' => 'true',
            'html'      => 'false',
            'data'      => ''
        ), $atts );

        $class = 'bs-popover';

        $atts['data']   .= $this->check_for_data($atts['data']) . 'toggle,popover';
        $atts['data']   .= $this->check_for_data($atts['data']) . 'content,' . str_replace(',', '&#44;', $atts['text']);
        $atts['data']   .= ( $atts['animation'] ) ? $this->check_for_data($atts['data']) . 'animation,' . $atts['animation'] : '';
        $atts['data']   .= ( $atts['placement'] ) ? $this->check_for_data($atts['data']) . 'placement,' . $atts['placement'] : '';
        $atts['data']   .= ( $atts['html'] )      ? $this->check_for_data($atts['data']) . 'html,'      . $atts['html']      : '';

        $return = '';
        $tag = 'span';
        $content = do_shortcode($content);
        $return .= $this->get_dom_element($tag, $content, $class, $atts['title'], $atts['data']);
        return html_entity_decode($return);

    }


    /*--------------------------------------------------------------------------------------
        *
        * rb_media
        *
        * @author
        * @since 1.0
        *
        *-------------------------------------------------------------------------------------*/

    function rb_media( $atts, $content = null ) {

        $atts = shortcode_atts( array(
            "xclass" => false,
            "data"   => false
        ), $atts );

        $class  = 'media';
        $class .= ( $atts['xclass'] )   ? ' ' . $atts['xclass']: '';

        $data_props = $this->parse_data_attributes( $atts['data'] );

        return sprintf(
            '<div class="%s"%s>%s</div>',
            esc_attr( trim($class) ),
            ( $data_props ) ? ' ' . $data_props : '',
            do_shortcode( $content )
        );
    }

    function rb_media_object( $atts, $content = null ) {

        $atts = shortcode_atts( array(
            "pull"   => false,
            "media"  => "left",
            "xclass" => false,
            "data"   => false
        ), $atts );

        $class = "media-object img-responsive";
        $class .= ($atts['xclass']) ? ' ' . $atts['xclass'] : '';

        $media_class ='';
        $media_class = ($atts['media']) ? 'media-' . $atts['media'] : '';
        $media_class = ($atts['pull'])  ? 'pull-' . $atts['pull'] : $media_class;

        $return = '';

        $tag = array('figure', 'div', 'img', 'i', 'span');
        $content = do_shortcode(preg_replace('/(<br>)+$/', '', $content));
        $return .= $this->scrape_dom_element($tag, $content, $class, '', $atts['data']);
        $return = '<span class="' . $media_class . '">' . $return . '</span>';
        return $return;
    }

    function rb_media_body( $atts, $content = null ) {

        $atts = shortcode_atts( array(
            "title"  => false,
            "xclass" => false,
            "data"   => false
        ), $atts );

        $div_class  = 'media-body';
        $div_class .= ( $atts['xclass'] )   ? ' ' . $atts['xclass'] : '';

        $h4_class  = 'media-heading';
        $h4_class .= ( $atts['xclass'] )   ? ' ' . $atts['xclass'] : '';

        $data_props = $this->parse_data_attributes( $atts['data'] );

        return sprintf(
            '<div class="%s"%s><h4 class="%s">%s</h4>%s</div>',
            esc_attr( $div_class ),
            ( $data_props ) ? ' ' . $data_props : '',
            esc_attr( $h4_class ),
            esc_html(  $atts['title']),
            do_shortcode( $content )
        );
    }

    /*--------------------------------------------------------------------------------------
        *
        * rb_jumbotron
        *
        *
        *-------------------------------------------------------------------------------------*/
    function rb_jumbotron( $atts, $content = null ) {

        $atts = shortcode_atts( array(
            "title"  => false,
            "xclass" => false,
            "data"   => false
        ), $atts );

        $class  = 'jumbotron';
        $class .= ( $atts['xclass'] )   ? ' ' . $atts['xclass'] : '';

        $data_props = $this->parse_data_attributes( $atts['data'] );

        return sprintf(
            '<div class="%s"%s>%s%s</div>',
            esc_attr( trim($class) ),
            ( $data_props ) ? ' ' . $data_props : '',
            ( $atts['title'] ) ? '<h1>' . esc_html( $atts['title'] ) . '</h1>' : '',
            do_shortcode( $content )
        );
    }

    /*--------------------------------------------------------------------------------------
        *
        * rb_page_header
        *
        *
        *-------------------------------------------------------------------------------------*/
    function rb_page_header( $atts, $content = null ) {

        $atts = shortcode_atts( array(
            "xclass" => false,
            "data"   => false
        ), $atts );

        $data_props = $this->parse_data_attributes( $atts['data'] );

        $class = "page-header";
        $class .= ($atts['xclass']) ? ' ' . $atts['xclass'] : '';

        $return = '';
        $title = '';
        $tag = 'div';
        $content = $this->strip_paragraph($content);
        $content = $this->nest_dom_element('h1', 'div', $content);
        $return .= $this->get_dom_element($tag, $content, $class, '', $atts['data']);
        return $return;

    }

    /*--------------------------------------------------------------------------------------
        *
        * rb_lead
        *
        *
        *-------------------------------------------------------------------------------------*/
    function rb_lead( $atts, $content = null ) {

        $atts = shortcode_atts( array(
            "xclass" => false,
            "data"   => false
        ), $atts );

        $class  = 'lead';
        $class .= ( $atts['xclass'] )   ? ' ' . $atts['xclass'] : '';

        $data_props = $this->parse_data_attributes( $atts['data'] );

        return sprintf(
            '<p class="%s"%s>%s</p>',
            esc_attr( trim($class) ),
            ( $data_props ) ? ' ' . $data_props : '',
            do_shortcode( $content )
        );
    }

    /*--------------------------------------------------------------------------------------
        *
        * rb_emphasis
        *
        *
        *-------------------------------------------------------------------------------------*/
    function rb_emphasis( $atts, $content = null ) {

        $atts = shortcode_atts( array(
            "type"   => false,
            "xclass" => false,
            "data"   => false
        ), $atts );

        $class  = '';
        $class .= ( $atts['type'] )   ? 'text-' . $atts['type'] : 'text-muted';
        $class .= ( $atts['xclass'] ) ? ' ' . $atts['xclass'] : '';

        $data_props = $this->parse_data_attributes( $atts['data'] );

        return sprintf(
            '<span class="%s"%s>%s</span>',
            esc_attr( trim($class) ),
            ( $data_props ) ? ' ' . $data_props : '',
            do_shortcode( $content )
        );
    }

    /*--------------------------------------------------------------------------------------
        *
        * rb_img
        *
        *
        *-------------------------------------------------------------------------------------*/
    function rb_img( $atts, $content = null ) {

        $atts = shortcode_atts( array(
            "type"       => false,
            "responsive" => false,
            "xclass"     => false,
            "data"       => false
        ), $atts );

        $class  = '';
        $class .= ( $atts['type'] )       ? 'img-' . $atts['type'] . ' ' : '';
        $class .= ( $atts['responsive']   == 'true' ) ? ' img-responsive' : '';
        $class .= ( $atts['xclass'] )     ? ' ' . $atts['xclass'] : '';

        $return = '';
        $tag = array('img');
        $content = do_shortcode($content);
        $return .= $this->scrape_dom_element($tag, $content, $class, '', $atts['data']);
        return $return;

    }

    /*--------------------------------------------------------------------------------------
        *
        * rb_embed_responsive
        *
        *
        *-------------------------------------------------------------------------------------*/
    function rb_embed_responsive( $atts, $content = null ) {

        $atts = shortcode_atts( array(
            "ratio"      => false,
            "xclass"     => false,
            "data"       => false
        ), $atts );

        $class  = 'embed-responsive ';
        $class .= ( $atts['ratio'] )       ? ' embed-responsive-' . $atts['ratio'] . ' ' : '';
        $class .= ( $atts['xclass'] )     ? ' ' . $atts['xclass'] : '';

        $embed_class = 'embed-responsive-item';

        $tag = array('iframe', 'embed', 'video', 'object');
        $content = do_shortcode($content);
        $data_props = $this->parse_data_attributes( $atts['data'] );

        return sprintf(
            '<div class="%s"%s>%s</div>',
            esc_attr( trim($class) ),
            ( $data_props ) ? ' ' . $data_props : '',
            $this->scrape_dom_element($tag, $content, $embed_class, '', '')
        );

    }

    /*--------------------------------------------------------------------------------------
        *
        * rb_thumbnail
        *
        *
        *-------------------------------------------------------------------------------------*/
    function rb_thumbnail( $atts, $content = null ) {

        $atts = shortcode_atts( array(
            "xclass"  => false,
            "has_content" => false,
            "data"    => false
        ), $atts );

        $class  = "thumbnail";
        $class .= ($atts['xclass']) ? ' ' . $atts['xclass'] : '';

        $return = '';
        if($atts['has_content']) {
            $content = '<div>' . $content . '</div>';
            $tag = array('div');
        } else {
            $tag = array('a', 'img');
        }
        $content = do_shortcode($content);
        $return .= $this->scrape_dom_element($tag, $content, $class, '', $atts['data']);
        return $return;

    }

    /*--------------------------------------------------------------------------------------
    *
    * rb_responsive
    *
    *
    *-------------------------------------------------------------------------------------*/
    function rb_responsive( $atts, $content = null ) {

        $atts = shortcode_atts( array(
            "visible" => false,
            "hidden"  => false,
            "block"  => false,
            "inline"  => false,
            "inline_block"  => false,
            "xclass"  => false,
            "data"    => false
        ), $atts );

        $class = '';
        if( $atts['visible'] ) {
            $visible = explode( ' ', $atts['visible'] );
            foreach($visible as $v):
                $class .= "visible-$v ";
            endforeach;
        }
        if( $atts['hidden'] ) {
            $hidden = explode( ' ', $atts['hidden'] );
            foreach( $hidden as $h ):
                $class .= "hidden-$h ";
            endforeach;
        }
        if( $atts['block'] ) {
            $block = explode( ' ', $atts['block'] );
            foreach( $block as $b ):
                $class .= "visible-$b-block ";
            endforeach;
        }
        if( $atts['inline'] ) {
            $inline = explode( ' ', $atts['inline'] );
            foreach( $inline as $i ):
                $class .= "visible-$i-inline ";
            endforeach;
        }
        if( $atts['inline_block'] ) {
            $inline_block = explode( ' ', $atts['inline_block'] );
            foreach( $inline_block as $ib ):
                $class .= "visible-$ib-inline ";
            endforeach;
        }
        $class .= ( $atts['xclass'] ) ? ' ' . $atts['xclass'] : '';

        $data_props = $this->parse_data_attributes( $atts['data'] );

        return sprintf(
            '<span class="%s"%s>%s</span>',
            esc_attr( trim($class) ),
            ( $data_props ) ? ' ' . $data_props : '',
            do_shortcode( $content )
        );
    }

    /*--------------------------------------------------------------------------------------
        *
        * rb_modal
        *
        * @author M. W. Delaney
        * @since 1.0
        *
        *-------------------------------------------------------------------------------------*/
    function rb_modal( $atts, $content = null ) {

        if( isset($GLOBALS['modal_count']) )
            $GLOBALS['modal_count']++;
        else
            $GLOBALS['modal_count'] = 0;

        $atts = shortcode_atts( array(
            "text"    => false,
            "title"   => false,
            "size"    => false,
            "xclass"  => false,
            "data"    => false
        ), $atts );

        $a_class  = '';
        $a_class .= ( $atts['xclass'] )   ? ' ' . $atts['xclass'] : '';

        $div_class  = 'modal fade';
        $div_class .= ( $atts['size'] ) ? ' bs-modal-' . $atts['size'] : '';

        $div_size = ( $atts['size'] ) ? ' modal-' . $atts['size'] : '';

        $id = 'custom-modal-' . $GLOBALS['modal_count'];

        $data_props = $this->parse_data_attributes( $atts['data'] );

        $modal_output = sprintf(
            '<div class="%1$s" id="%2$s" tabindex="-1" role="dialog" aria-hidden="true">
						<div class="modal-dialog %3$s">
								<div class="modal-content">
										<div class="modal-header">
												<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
												%4$s
										</div>
										<div class="modal-body">
												%5$s
										</div>
								</div> <!-- /.modal-content -->
						</div> <!-- /.modal-dialog -->
				</div> <!-- /.modal -->
				',
            esc_attr( $div_class ),
            esc_attr( $id ),
            esc_attr( $div_size ),
            ( $atts['title'] ) ? '<h4 class="modal-title">' . $atts['title'] . '</h4>' : '',
            do_shortcode( $content )
        );

        add_action('wp_footer', function() use ($modal_output) {
            echo $modal_output;
        }, 100,0);

        return sprintf(
            '<a data-toggle="modal" href="#%1$s" class="%2$s"%3$s>%4$s</a>',
            esc_attr( $id ),
            esc_attr( $a_class ),
            ( $data_props ) ? ' ' . $data_props : '',
            esc_html( $atts['text'] )
        );
    }

    /*--------------------------------------------------------------------------------------
        *
        * rb_modal_footer
        *
        * @author M. W. Delaney
        * @since 1.0
        *
        *-------------------------------------------------------------------------------------*/
    function rb_modal_footer( $atts, $content = null ) {

        $atts = shortcode_atts( array(
            "xclass" => false,
            "data"   => false,
        ), $atts );

        $class  = 'modal-footer';
        $class .= ( $atts['xclass'] ) ? ' ' . $atts['xclass'] : '';

        $data_props = $this->parse_data_attributes( $atts['data'] );

        return sprintf(
            '</div><div class="%s"%s>%s',
            esc_attr( trim($class) ),
            ( $data_props ) ? ' ' . $data_props : '',
            do_shortcode( $content )
        );
    }

    /*--------------------------------------------------------------------------------------
        *
        * Parse data-attributes for shortcodes
        *
        *-------------------------------------------------------------------------------------*/
    function parse_data_attributes( $data ) {

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

    /*--------------------------------------------------------------------------------------
        *
        * get DOMDocument element and apply shortcode parameters to it. Create the element if it doesn't exist
        *
        *-------------------------------------------------------------------------------------*/
    function get_dom_element( $tag, $content, $class, $title = '', $data = null ) {

        //clean up content
        $content = trim(trim($content), chr(0xC2).chr(0xA0));
        $previous_value = libxml_use_internal_errors(TRUE);

        $dom = new DOMDocument;
        $dom->loadXML(utf8_encode($content));

        libxml_clear_errors();
        libxml_use_internal_errors($previous_value);

        if(!$dom->documentElement) {
            $element = $dom->createElement($tag, utf8_encode($content));
            $dom->appendChild($element);
        }

        $dom->documentElement->setAttribute('class', $dom->documentElement->getAttribute('class') . ' ' . esc_attr( utf8_encode($class) ));
        if( $title ) {
            $dom->documentElement->setAttribute('title', $title );
        }
        if( $data ) {
            $data = explode( '|', $data );
            foreach( $data as $d ):
                $d = explode(',',$d);
                $dom->documentElement->setAttribute('data-'.$d[0],trim($d[1]));
            endforeach;
        }
        return utf8_decode( $dom->saveXML($dom->documentElement) );
    }

    /*--------------------------------------------------------------------------------------
        *
        * Scrape the shortcode's contents for a particular DOMDocument tag or tags, pull them out, apply attributes, and return just the tags.
        *
        *-------------------------------------------------------------------------------------*/
    function scrape_dom_element( $tag, $content, $class, $title = '', $data = null ) {

        $previous_value = libxml_use_internal_errors(TRUE);

        $dom = new DOMDocument;
        $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));

        libxml_clear_errors();
        libxml_use_internal_errors($previous_value);
        foreach ($tag as $find) {
            $tags = $dom->getElementsByTagName($find);
            foreach ($tags as $find_tag) {
                $outputdom = new DOMDocument;
                $new_root = $outputdom->importNode($find_tag, true);
                $outputdom->appendChild($new_root);

                if(is_object($outputdom->documentElement)) {
                    $outputdom->documentElement->setAttribute('class', $outputdom->documentElement->getAttribute('class') . ' ' . esc_attr( $class ));
                    if( $title ) {
                        $outputdom->documentElement->setAttribute('title', $title );
                    }
                    if( $data ) {
                        $data = explode( '|', $data );
                        foreach( $data as $d ):
                            $d = explode(',',$d);
                            $outputdom->documentElement->setAttribute('data-'.$d[0],trim($d[1]));
                        endforeach;
                    }
                }
                return $outputdom->saveHTML($outputdom->documentElement);

            }
        }
    }

    /*--------------------------------------------------------------------------------------
        *
        * Find if content contains a particular tag, if not, create it, either way wrap it in a wrapper tag
        *
        *       Example: Check if the contents of [page-header] include an h1, if not, add one, then wrap it all in a div so we can add classes to that.
        *
        *-------------------------------------------------------------------------------------*/
    function nest_dom_element($find, $append, $content) {

        $previous_value = libxml_use_internal_errors(TRUE);

        $dom = new DOMDocument;
        $dom->loadXML(utf8_encode($content));

        libxml_clear_errors();
        libxml_use_internal_errors($previous_value);

        //Does $content include the tag we're looking for?
        $hasFind = $dom->getElementsByTagName($find);

        //If not, add it and wrap it all in our append tag
        if( $hasFind->length == 0 ) {
            $wrapper = $dom->createElement($append);
            $dom->appendChild($wrapper);

            $tag = $dom->createElement($find, $content);
            $wrapper->appendChild($tag);
        }

        //If so, just wrap everything in our append tag
        else {
            $new_root = $dom->createElement($append);
            $new_root->appendChild($dom->documentElement);
            $dom->appendChild($new_root);
        }
        return $dom->saveXML($dom->documentElement);
    }

    /*--------------------------------------------------------------------------------------
           *
           * Add dividers to data attributes content if needed
           *
           *-------------------------------------------------------------------------------------*/
    function check_for_data( $data ) {
        if( $data ) {
            return "|";
        }
    }

    /*--------------------------------------------------------------------------------------
           *
           * If the user puts a return between the shortcode and its contents, sometimes we want to strip the resulting P tags out
           *
           *-------------------------------------------------------------------------------------*/
    function strip_paragraph( $content ) {
        $content = str_ireplace( '<p>','',$content );
        $content = str_ireplace( '</p>','',$content );
        return $content;
    }



    function get_section_id_by($field_name, $field_value){


        if( empty($field_name) || empty($field_value) )
            return null;

        $field_name = trim($field_name);
        $field_value = trim($field_value);


        global $wpdb;
        $query = "SELECT ID FROM {$wpdb->prefix}posts WHERE {$field_name}='{$field_value}' AND post_type='ui-section' AND post_status='publish';";
        $post_id = $wpdb->get_var($query);

        return $post_id;

    }


    function get_single_template(){
        global $post;
        global $ui_section_post;

        $ui_section = $this->get_section_id_by('post_title', 'single-' . $post->post_type);

        if( empty($ui_section) ){
            return false;
        } else{
            $ui_section_post = get_post($ui_section);
            return 'render-posttype-template';
        }

    }


    function render_content($section_content, $atts=array(), $replace_empty=true){


        if( isset($_GET['debug_render_content']) ){

            echo '<pre>data '.$section_content.'</pre>';
            echo '<pre>data '.print_r($atts, true).'</pre>';
            if(isset($_GET['exit'])){
                exit;
            }
        }

        return FFD()->liquid->render($section_content, $atts);

        $custom = array(
            'images_url' => get_template_directory_uri() . '/images',
            'date_year' => date('Y'),
            'date_day' => date('d'),
            'date_month' => date('m'),
        );
        $atts = array_merge($custom, $atts);
        $label = '';
        if (preg_match_all("/{{(.*?)}}/", $section_content, $m)) {
            foreach ($m[1] as $i => $varname) {
                $peices = explode(':', $varname);
                $varname = $peices[0];
                $label = isset($peices[1]) ? $peices[1] : '';
                $value = isset($atts[$varname]) ? $atts[$varname] . $label : '';

                if( $value !== '' ){
                    $section_content = str_replace($m[0][$i], sprintf('%s', $value), $section_content);
                } else if ( $replace_empty === true ){
                    $section_content = str_replace($m[0][$i], sprintf('%s', $value), $section_content);
                }


            }
        }


        return $section_content;
    }



    function get_ui_template_html($name, $field='post_title'){

        if( is_array($name) && isset($name['template']) ){
            $name = $name['template'];
        }

        $template_id = $this->get_section_id_by($field, $name);

        if(!$template_id)
            return '';

        $card_template = get_post($template_id);
        $template = $card_template->post_content;

        if(empty($template))
            return '';

        $template .= '<!-- FFD-UI '.$template_id.' '.$name.' /FFD-UI -->';
        return $template;

    }


    function get_post_data_array($post_id, $_template_html=null){

        if( $post_id ){
            $post = get_post($post_id);
        } else {
            global $post;
        }

        if( !$post )
            return;

        $post_link = $post_image = $post_category = $post_title = $post_description = $post_date = '';
        $categories = get_the_category();
        if ( ! empty( $categories ) ) {
            $post_category = esc_html( $categories[0]->name );
        } else {
            $post_category = '';
        }
        $post_image = ffdl_get_featured_image(false);
        $post_image_small = ffdl_get_featured_image(false, 'small');
        $post_image_medium = ffdl_get_featured_image(false, 'medium');
        $post_image_large = ffdl_get_featured_image(false, 'large');
        $post_content = apply_filters('the_content', $post->post_content);
        $post_description = wp_trim_words($post_content, 20, '');
        $post_date = get_the_date('M j, Y');
        $post_link = get_permalink();
        $post_title = get_the_title();

        $data = array(
            'link' => $post_link,
            'image' => $post_image,
            'image_small' => $post_image_small,
            'image_medium' => $post_image_medium,
            'image_large' => $post_image_large,
            'category' => $post_category,
            'title' => $post_title,
            'description' => $post_description,
            'date' => $post_date,
        );


        if( !empty($_template_html) ){
            $db_values = $this->get_post_data($post_id);
            if( is_array($db_values) && !empty($db_values) ){
                $data = array_merge($data, $db_values);
            }
        }

        if( isset($_GET['debug_data_array']) ){

            echo '<pre>html '.$_template_html.'</pre>';
            echo '<pre>data '.print_r($data, true).'</pre>';
            echo '<pre>db value '.print_r($db_values, true).'</pre>';
            if(isset($_GET['exit'])){
                exit;
            }
        }

        return $data;
    }



    /*
    * parse html/content and grab post data to replace in ui section content
    */
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
                    $data['post'][$key] = $value;
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

        $data['the_excerpt'] = get_the_excerpt();
        $data['the_date'] = get_the_date('M j, Y');
        $data['the_image'] = ffdl_get_featured_image();

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


    function get_property_data($property_item){


        $media = ffd_get_field('ffd_media', $property_item->ID);
        if( !empty($media)){
            $media = (array) $media;
            $media_image = current($media);
        }


        $id = $property_item->ID;
        $title = $property_item->post_title;
        $the_title = get_the_title();

        $status = ffd_get_field('ffd_status', $property_item->ID);
        if( $status == 'Pending (Do Not Show)')
            $status = 'Pending';

        $address = ffd_get_field('ffd_address_pb', $property_item->ID);
        $city = ffd_get_field('ffd_city_pb', $property_item->ID);
        $subdivision = ffd_get_field('ffd_subdivision', $property_item->ID);
        $state = ffd_get_field('ffd_state_pb', $property_item->ID);
        $postal_code = ffd_get_field('ffd_postalcode_pb', $property_item->ID);
        $area = ffd_get_field('ffd_area_text', $property_item->ID);


        $days_on_market = ffd_get_field('ffd_days_on_market', $property_item->ID);
        $dom = ffd_get_field('ffd_dom', $property_item->ID);
        $beds = ffd_get_field('ffd_bedrooms_pb', $property_item->ID);
        $baths = ffd_get_field('ffd_fullbathrooms_pb', $property_item->ID);
        $half_baths = ffd_get_field('ffd_halfbathrooms_pb', $property_item->ID);
        $size = ffd_get_field('ffd_totalarea_pb', $property_item->ID);
        $parking = ffd_get_field('parkingspaces', $property_item->ID);

        $time_listed = '';
        $listed_date = ffd_get_field('ffd_listed_date', $property_item->ID);
        if( !empty($listed_date) ){
            if( $time_listed = strtotime($listed_date) ){
                $time_listed = human_time_diff($time_listed);
            }
        }

        $time_sold = '';
        $sold_date = ffd_get_field('ffd_sale_date', $property_item->ID);
        if( !empty($sold_date) ){
            if( $time_sold = strtotime($sold_date) ){
                $time_sold = human_time_diff($time_sold);
            }
        }


        $mls_id = ffd_get_field('ffd_mls_id', $property_item->ID);
        $lat = ffd_get_field('ffd_latitude_pb', $property_item->ID);
        $lng = ffd_get_field('ffd_longitude_pb', $property_item->ID);

        $price = ffd_get_field('ffd_listingprice_pb', $property_item->ID);
        $price_formatted = number_format(ffd_get_field('ffd_listingprice_pb', $property_item->ID), 0, '.', ',');
        $filters = $_GET;
        if(!empty($filters)){
            $filters['action'] = 'redirectProperty';
            $url = add_query_arg($filters, get_permalink($property_item->ID));
        }else{
            $url = add_query_arg(array('action'=>'redirectProperty'), get_permalink($property_item->ID));
        }

        $permalink = get_permalink($property_item->ID);

        $image = ffdl_get_featured_image($property_item->ID);
        $image_placeholder = ffdl_get_img_placeholder();

        $favorite_class= '';
        if( ffd_is_favorite($property_item->ID) )
            $favorite_class = 'favorite';

        $listing_data = array(
            'title' => $title,
            'the_title' => $the_title,
            'image' => $image,
            'image_placeholder' => $image_placeholder,
            'status' => strtolower($status),
            'time_listed' => $time_listed,
            'time_sold' => $time_sold,
            'price' => $price_formatted,
            'permalink'	=> $permalink,
            'address' => strtolower($address),
            'city' => strtolower($city),
            'area' => strtolower($area),
            'beds' => $beds,
            'baths' => $baths,
            'half_baths' => $half_baths,
            'size' => $size,
            'mls_id' => $mls_id,
            'ID' => $property_item->ID,
            'postal_code' => $postal_code,
            'zip' => $postal_code,
            'favorite' => 'save-listing ' . $favorite_class,
        );

        return $listing_data;
    }

}



function FFD_Template_Render() {
    return FFD_Template_Render::instance();
}
//if( !(is_admin() && !wp_doing_ajax()) ){
FFD_Template_Render();
//}






if ( ! function_exists('ffd_ui_section_post_type') ) {

    // Register Custom Post Type
    function ffd_ui_section_post_type() {

        $labels = array(
            'name'                  => _x( 'UI Sections', 'Post Type General Name', 'page-ui-section' ),
            'singular_name'         => _x( 'UI Section', 'Post Type Singular Name', 'page-ui-section' ),
            'menu_name'             => __( 'FFD UI', 'page-ui-section' ),
            'name_admin_bar'        => __( 'FFD UI', 'page-ui-section' ),
            'archives'              => __( 'Item Archives', 'page-ui-section' ),
            'attributes'            => __( 'Item Attributes', 'page-ui-section' ),
            'parent_item_colon'     => __( 'Parent Item:', 'page-ui-section' ),
            'all_items'             => __( 'All Items', 'page-ui-section' ),
            'add_new_item'          => __( 'Add New Item', 'page-ui-section' ),
            'add_new'               => __( 'Add New', 'page-ui-section' ),
            'new_item'              => __( 'New Item', 'page-ui-section' ),
            'edit_item'             => __( 'Edit Item', 'page-ui-section' ),
            'update_item'           => __( 'Update Item', 'page-ui-section' ),
            'view_item'             => __( 'View Item', 'page-ui-section' ),
            'view_items'            => __( 'View Items', 'page-ui-section' ),
            'search_items'          => __( 'Search Item', 'page-ui-section' ),
            'not_found'             => __( 'Not found', 'page-ui-section' ),
            'not_found_in_trash'    => __( 'Not found in Trash', 'page-ui-section' ),
            'featured_image'        => __( 'Featured Image', 'page-ui-section' ),
            'set_featured_image'    => __( 'Set featured image', 'page-ui-section' ),
            'remove_featured_image' => __( 'Remove featured image', 'page-ui-section' ),
            'use_featured_image'    => __( 'Use as featured image', 'page-ui-section' ),
            'insert_into_item'      => __( 'Insert into item', 'page-ui-section' ),
            'uploaded_to_this_item' => __( 'Uploaded to this item', 'page-ui-section' ),
            'items_list'            => __( 'Items list', 'page-ui-section' ),
            'items_list_navigation' => __( 'Items list navigation', 'page-ui-section' ),
            'filter_items_list'     => __( 'Filter items list', 'page-ui-section' ),
        );
        $args = array(
            'label'                 => __( 'UI Section', 'page-ui-section' ),
            'description'           => __( 'ui section content', 'page-ui-section' ),
            'labels'                => $labels,
            'supports'              => array( 'title', 'editor' ),
            'hierarchical'          => false,
            'public'                => false,
            'show_ui'               => true,
            'show_in_menu'          => true,
            /* 'menu_position'         => 5, */
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => false,
            'can_export'            => true,
            'has_archive'           => false,
            'exclude_from_search'   => true,
            'publicly_queryable'    => false,
            'rewrite'               => false,
            'capability_type'       => 'page',
        );
        register_post_type( 'ui-section', $args );

    }

    if( function_exists('ffd_ui_posttype_enabled') && ffd_ui_posttype_enabled() ){
        add_action( 'init', 'ffd_ui_section_post_type', 0 );
    }
}







function ffd_ui_section_editor_settings() {
    global $post;

    if( function_exists('get_current_screen') ){
        $screen = get_current_screen();

        if ( $screen && $screen->id == 'ffd_ui_section' || $post->post_type == 'ui-section') {

            return false;
        }
    }

    return true;
}

add_filter('user_can_richedit', 'ffd_ui_section_editor_settings', 100);


// ======================================================================== //
// Create attributes map so we can get the attributes of a wrapped shortcode
//
//      Used by:
//          rb_tabs()
//          rb_carousel()
//
// ======================================================================== //

function ffd_rb_attribute_map($str, $att = null) {
    $res = array();
    $return = array();
    $reg = get_shortcode_regex();
    preg_match_all('~'.$reg.'~',$str, $matches);
    foreach($matches[2] as $key => $name) {
        $parsed = shortcode_parse_atts($matches[3][$key]);
        $parsed = is_array($parsed) ? $parsed : array();

        $res[$name] = $parsed;
        $return[] = $res;
    }
    return $return;
}

// ======================================================================== //




function ffd_build_wpquery_args($atts){

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



        'posts_per_page' => '',                 //(int) - number of post to show per page (available with Version 2.1). Use 'posts_per_page'=-1 to show all posts (the 'offset' parameter is ignored with a -1 value). Note if the query is in a feed, wordpress overwrites this parameter with the stored 'posts_per_rss' option. Treimpose the limit, try using the 'post_limits' filter, or filter 'pre_option_posts_per_rss' and return -1
        'nopaging' => '',                    //(bool) - show all posts or use pagination. Default value is 'false', use paging.
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

    $current_data = FFD_Template_Render()->get_post_data($post->ID);
    $args = array(); // array to be returned after parsing
    foreach($atts as $att_name => $att_value ){

        $att_value = FFD_Liquid()->render($att_value, $current_data);

        $att_value = str_replace(
            array('the_title', 'the_slug', 'the_id', 'the_date'),
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
                $att_value = ffd_build_wpquery_arg_query($query, $pieces);

            } else if( $att_name == 'meta_query'){



                //parse meta query
                $pieces = explode('|', $att_value);
                $query = array('key'=>'', 'value'=>'', 'type'=>'', 'compare'=>'');
                $att_value = ffd_build_wpquery_arg_query($query, $pieces);

            } else {
                if(  strpos($att_name, '_in') !== false ||  strpos($att_name, '_and') !== false || in_array($att_name, array('post_type', 'post_status')) ) {

                    $att_value = explode(',', $att_value);
                    $att_value = array_map('trim', $att_value);
                }
            }

            $args[$att_name] = $att_value;

        }
    }

    if( isset($_GET['debug_wpquery_args']) ){
        echo '<pre>'.print_r($args, true); '</pre>';
    }

    return $args;
}

if( !function_exists('ffd_wpquery_type') ){

    function ffd_wpquery_type( $type = '' ) {
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

}
if( !function_exists('ffd_wpquery_compare') ){

    function ffd_wpquery_compare($compare){

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
if( !function_exists('ffd_build_wpquery_arg_query') ){
    function ffd_build_wpquery_arg_query($query, $peices){

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
                            $_value = ffd_wpquery_compare($_value);
                        } else if( $_key==='type'){
                            $_value = ffd_wpquery_type($_value);
                        }

                        $query[$_key] = $_value;
                    }
                }
            }
        }

        $query = array_filter($query, 'ffd_is_blank');
        if( $relation !== '' && !empty($query)){
            $query_value = array('relation' => $relation, $query);
        } else if  (!empty($query) ){
            $query_value = array($query);
        }


        return $query_value;

    }
}

if( !function_exists('ffd_is_blank') ){

    function ffd_is_blank($value){
        return $value !== '';
    }

}

if( !function_exists('ffd_has_string') ){

    function ffd_has_string($haystack, $needle='', $flag="OR"){

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
}