<?php

add_filter('shortcode_atts_wpcf7', 'custom_shortcode_atts_wpcf7_filter', 10, 3); 
add_filter('shortcode_atts_wpcf7', 'tkffdlegacy_shortcode_atts_wpcf7_filter', 10, 3 );
add_shortcode('podcast_feed', 'shortcode_podcast_feed');
add_shortcode('podcast_links', 'shortcode_podcast_links');
add_shortcode('render_ui_content', 'shortcode_render_ui_content');
add_shortcode('featured_image', 'shortcode_thumbnail_in_content');
add_shortcode('embed', 'ffdl_responsive_embed');
add_shortcode('iframe', 'ffdl_responsive_iframe');
add_shortcode('properties_list', 'properties_list_shortcode');
add_shortcode('ffdl_search_page', 'shortcode_ffd_legacy_search_page');

add_shortcode('ffdl_map_search', 'shortcode_ffd_legacy_map_search');


function shortcode_ffd_legacy_map_search($atts, $content=null){
	ob_start();
    
		ffdl_get_template_part('map-search', $atts);

	return ob_get_clean();

}

function shortcode_ffd_legacy_search_page(){
	ob_start();

		ffdl_get_template_part('listings-search-content');

	return ob_get_clean();

}

function properties_list_shortcode($atts=array()){

    $columns = $atts['columns'];
    $wp_query = ffdl_ui_properties_listing_wp_query($atts);

    
    return ffdl_ui_render_properties_list_query($wp_query, $columns);
    

}




function ffdl_responsive_embeding($atts, $content, $tag){

    $ratio = array('21by9', '16by9', '4by3', '1by1');
    $custom_ratio = '';
    $ratio_class = '';
    if( isset($atts['ratio']) && in_array($atts['ratio'], $ratio) ){
        $ratio_class = 'embed-responsive-' . $atts['ratio'];
    } else {
        if( !isset($atts['ratio']) ){
            $ratio_class = 'embed-responsive-' . $ratio[3];
            $atts['ratio'] = $ratio[3];
        } else {
            //custom
            $pieces = explode('by', $atts['ratio']);
            $width = $pieces[0];
            $height = $pieces[1];
            $aspect_ratio = ($height / $width ) * 100;
            $aspect_ratio = round($aspect_ratio, 2);
            $custom_ratio = 'padding-top:'.$aspect_ratio.'%!important;';
        }

    }
    
    $atts_str = '';
    if( !empty($atts['atts']) ){
        $html_atts = explode(',', $atts['atts']);
        foreach($html_atts as $html_att){
            $pieces = explode(':', $html_att);
            $atts_str .= ' ' . trim($pieces[0]) . '=' . '"'.trim($pieces[1]).'"';
        }
    }

    if( strpos($atts['src'], 'stats.10kresearch') !== false )
        $src_attr = 'data-src="'.$atts['src'].'" ' . $atts_str;
    else
        $src_attr = 'src="'.$atts['src'].'" ' . $atts_str;

    ob_start();
?>
<div class="embed-responsive <?php echo $ratio_class; ?>" style="<?php echo $custom_ratio; ?>">
  <<?php echo trim($tag); ?> data-ratio="<?php echo $atts['ratio']; ?>" style="border:0;" class="embed-responsive-item" <?php echo $src_attr; ?>  <?php echo $atts_str; ?>></<?php echo trim($tag); ?>>
</div>
<?php 
return ob_get_clean();

}


function ffdl_responsive_iframe($atts, $content=''){
    $tag = 'iframe';
    return ffdl_responsive_embeding($atts, $content, $tag);
}



function ffdl_responsive_embed($atts, $content=''){
    $tag = 'embed';
    return ffdl_responsive_embeding($atts, $content, $tag);
}

function shortcode_thumbnail_in_content($atts) {
    if( isset($atts['page']) ){
        $post   = get_post($atts['page']);
    } else {
        global $post;
    }    
    return get_the_post_thumbnail($post->ID, $atts['size']);
}



function shortcode_render_ui_content($atts=array(), $content=''){

    global $post;
    $_post = $post;

    $ID = isset($atts['page']) && !empty($atts['page']) ? $atts['page'] : null;
    $content_type = isset($atts['content_type']) && !empty($atts['content_type']) ? strtolower( str_replace(' ', '_', trim($atts['content_type']) )) : '';
    $content_id = isset($atts['content_id']) && !empty($atts['content_id']) ? (int) $atts['content_id'] : '';

    if( $content_id != ''  ){
        $content_id = $content_id - 1;
    }

    $params = array('using_shortcode' => true, 'content_id'=>$content_id, 'content_type'=>$content_type );
    $post   = get_post($ID);
    if(isset($_GET['debug']) ){
        ffdl_debug($params, false);
        ffdl_debug($post, false);
    }
    $reset = false;
    if( $post !== null ){
        setup_postdata($post);
        $reset = true;
    }
    ob_start();
        ffdl_get_template_part('ui-content', $params);
        if( $reset ){ wp_reset_postdata(); $post = $_post; }
    return ob_get_clean();
}


function shortcode_podcast_links($atts=array(), $content=''){

    ob_start();
    ffdl_get_template_part('podcast-links', $atts);
    return ob_get_clean();
}




function custom_shortcode_atts_wpcf7_filter($out, $pairs, $atts)
{
    if (isset($atts["mlsid"])) {
        $out["mlsid"] = $atts["mlsid"];
    }
 
    if (isset($atts["listing-id"])) {
        $out["listing-id"] = $atts["listing-id"];
    }

    if (isset($atts["send-to"])) {
        $out["send-to"] = $atts["send-to"];
    }

    if (isset($atts["your-phone"])) {
        $out["your-phone"] = $atts["your-phone"];
    }

    return $out;
}



 
function tkffdlegacy_shortcode_atts_wpcf7_filter( $out, $pairs, $atts ) {
    
    $my_attrs = array();
    $my_attrs[] = 'listing-id';
    $my_attrs[] = 'listing-url';
    
    foreach ($my_attrs as $key => $my_attr) {    
        if ( isset( $atts[$my_attr] ) ) {
            $out[$my_attr] = $atts[$my_attr];
        }
    }
 
    return $out;
}



function shortcode_podcast_feed($atts=array(), $content=''){

    ob_start();
    
    ffdl_get_template_part('podcast-feed', array());
    return ob_get_clean();
}