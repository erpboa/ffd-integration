<?php 
global $post;
$listing = $post;

?>
<div class="property-item-title">
    <a href="<?php echo get_permalink($listing->ID) ?>">
        <?php
       
        $listing_title = ffdl_str_lreplace(',', '', $listing->post_title);
        $title_pieces = explode(',', $listing_title); 
        echo (isset($title_pieces[0]) ? rtrim(trim($title_pieces[0]), '#') : '');
        //if(!empty($unit_number)) echo 'Unit # '.$unit_number;
        echo (isset($title_pieces[1]) ? ', ' . $title_pieces[1] : '');
        echo (isset($title_pieces[2]) ? ', ' . $title_pieces[2] : '');
        
        ?>
    </a>
</div>