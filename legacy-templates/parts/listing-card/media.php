<?php 

global $post;
$listing = $post;

$media=get_post_meta($listing->ID, "ffd_media", true);

if (is_array($media) && sizeof($media)>0) {
    $img="https://images1-focus-opensocial.googleusercontent.com/gadgets/proxy?url=" . $media[0] . "&container=focus&resize_w=287&refresh=2592000";
} else {
    $img= ffdl_plugin_url() ."/legacy-assets/images/default-preview.jpg";
}

$imagesCount = !empty($media) ? count($media) : 0;
$lazyLoadAfter = 1;

?><div class="swiper-container">
    <div class="swiper-wrapper">
        <?php if($imagesCount>0): ?>

            <?php for($i = 0; $i < $imagesCount; $i++): ?>
                <div class="swiper-slide">
                    <figure>
                        <?php
                        if ($i >= $lazyLoadAfter) { // Do lazy loading?>
                            <a href="<?php echo get_permalink($listing->ID) ?>">
                                <!-- Required swiper-lazy class and image source specified in data-src attribute -->
                                <img data-src="<?php echo  $media[$i]; ?>" class="swiper-lazy"  onerror="app.common.imgError(this);">
                            </a>
                            <!-- Preloader image -->
                            <!--<div class="swiper-lazy-preloader"></div>-->
                            <div class="loader"><img src="<?php echo ffdl_plugin_url() ?>/legacy-assets/images/loader.gif" /></div>
                        <?php
                        } else {  // Directly show images
                            ?>
                            <a href="<?php echo get_permalink($listing->ID) ?>">
                                <img src="<?php echo $media[$i]; ?>" alt=""  onerror="app.common.imgError(this);">
                            </a>
                        <?php
                        } ?>
                    </figure>
                </div>
            <?php endfor; ?>  

        <?php else: ?>
            <div class="swiper-slide">
            <figure>
                <a href="<?php echo get_permalink($listing->ID) ?>">
                        <img src="<?php echo  get_template_directory_uri()."/assets/images/default-preview.jpg"; ?>" alt="">
                    </a>
                </figure>
            </div>
        <?php endif; ?>
    </div>

    <div class="swiper-button-next swiper-button-white"></div>
    <div class="swiper-button-prev swiper-button-white"></div>
</div>