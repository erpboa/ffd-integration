<?php

add_action('ffdl_listing_template_content', 'ffdl_listing_template_content_func');
function ffdl_listing_template_content_func(){
   
    global $post;
    global $ui_section_post;
   
    //$section_id = FFD_Template_Rendrer()->get_section_id_by('post_title', 'single-' . $post->post_type);
    //$ui_section_post = get_post($template_id);
    //$ui_section_content = $ui_section_post->post_content;

     
    ob_start();
    echo $ui_section_content = do_shortcode($ui_section_post->post_content);
    $ui_section_content = ob_get_clean();
    
    //$ui_section_content = get_the_content(null, false, $ui_section_post);
        
   echo FFD_Template_Rendrer()->rb_display_post(array(), $ui_section_content); 

}