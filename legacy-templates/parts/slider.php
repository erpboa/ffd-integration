
<?php 
global $post;
setup_postdata($post);

$type = ffd_get_field('hero_images_type');
$images=ffd_get_field("images");
$images_with_text=ffd_get_field("images_with_text");
$slider_settings = ffd_get_field('slider_settings');
if( empty($slider_settings) ){
    $slider_settings = array('delay' => 3000, 'speed' => 300, 'effect' => 'slide');
}
$slider_atts = ' data-delay="'.$slider_settings['delay'].'" data-speed="'.$slider_settings['speed'].'" data-effect="'.$slider_settings['effect'].'" ';

$aspect_ratio = ffd_get_field('hero_image_aspect_ratio');
$apply_ratio = '';
if( $aspect_ratio && $aspect_ratio['width'] > 0 && $aspect_ratio['height'] > 0 ){
    $aspect_ratio = ($aspect_ratio['height'] / $aspect_ratio['width'] ) * 100;
    $aspect_ratio = round($aspect_ratio, 2);
    $apply_ratio = 'padding-top:'.$aspect_ratio.'%!important;';
} else {
    $aspect_ratio = (1 / 3 ) * 100;
    $aspect_ratio = round($aspect_ratio, 2);
    $apply_ratio = 'padding-top:'.$aspect_ratio.'%!important;';
}

if( empty($images) && empty($images_with_text) && is_singular('community') ){
    $featured_iamge = ffdl_get_featured_image($post_id = false, $size = 'full', $placeholder = false);
    if( !empty($featured_iamge)){
        ?>
        <div class="container-fluid home-slider-container">
            <div class="home-slider">
                <div class="home-main-slider swiper-container" <?php echo $slider_atts; ?>>
                    <div class="swiper-wrapper">
                        <div class="swiper-slide">
                            <a class="myrespbanner" style="background-image:url(<?php echo $featured_iamge; ?>); <?php echo $apply_ratio; ?>"></a>
                        </div>
                        
                    </div>
                
                </div>
            </div>
        </div><!-- /.container-fluid -->
        <?php
    }
} else if( !$type || strtolower($type) == 'images only'):
?>

<div class="container-fluid home-slider-container">
    <div class="home-slider">
        <div class="home-main-slider swiper-container" <?php echo $slider_atts; ?>>
            <div class="swiper-wrapper">
                <?php 
                    
                    

                    if($images):
                        foreach($images as $image):
                            $src=wp_get_attachment_image_src($image['ID'],array("1920","760"));
                            ?>
                            <div class="swiper-slide">
                                <a class="myrespbanner" style="background-image:url(<?php echo $src[0]; ?>); <?php echo $apply_ratio; ?>"></a>
                            </div>
                            <?php
                        endforeach;
                    endif;
                ?>
            </div>
            <!-- Add Pagination -->
            <?php if(sizeof($images)>1):?>
            <div class="swiper-pagination"></div>
            <?php endif;?>
        </div>
    </div>
</div><!-- /.container-fluid -->

<?php else: ?>

<div class="container-fluid home-slider-container withtext-slider-container">
    <div class="home-slider">
        <div class="home-main-slider swiper-container" <?php echo $slider_atts; ?>>
            <div class="swiper-wrapper">
                <?php 
                    
                    $slides = $images_with_text['slides'];

                   

                    if($slides):
                        foreach($slides as $slide):
                            
                            ?>
                            <div class="swiper-slide">
                                <a class="myrespbanner" style="background-image:url(<?php echo $slide['image']; ?>); <?php echo $apply_ratio; ?>"></a>
                                <span class="d-block slide-text">
                                    <span class="slide-text-content"><?php echo apply_filters('the_contet', $slide['text']); ?></span>
                                </span>
                            </div>
                            <?php
                        endforeach;
                    endif;
                ?>
            </div>
            <!-- Add Pagination -->
            <?php if(sizeof($slides)>1):?>
                <div class="swiper-pagination"></div>
            <?php endif;?>
        </div>
    </div>
</div><!-- /.container-fluid -->

<?php endif; ?>
<?php wp_reset_postdata(); ?>

<script>
jQuery(document).ready(function () {

    var $homeslider_settings = jQuery('.home-main-slider');
    var $slides = jQuery('.home-main-slider .swiper-slide');

    var settings = {
        pagination: { el: '.home-main-slider .swiper-pagination', clickable: true },
        speed: parseInt( $homeslider_settings.attr('data-speed') || 300 ),
        effect: $homeslider_settings.attr('data-effect') || 'slide' 
    };
    
    if( $slides.length > 1 ){
        settings['loop'] = true;
        settings['autoplay'] = { delay: parseInt($homeslider_settings.attr('data-delay') || 3000 ),
             disableOnInteraction:true,
             reverseDirection:false,
             stopOnLastSlide:false, waitForTransition:true
        };
    }

    if( settings.effect == 'fade'){
        settings['fadeEffect'] = {
            crossFade: false
        }
    }

    if( settings.effect == 'coverflow'){
        settings['coverflowEffect'] = {
            slideShadows: true,
            rotate: 50,
            stretch: 0,
            depth: 100,
            modifier: 1
        }
    }

    if( settings.effect == 'flip'){
        settings['flipEffect'] = {
                rotate: 50,
                slideShadows: true,
                limitRotation: false
        }
    }

    if( settings.effect == 'cube'){
        settings['cubeEffect'] = {
                slideShadows : true,
                shadow : true,
                shadowOffset: 20,
                shadowScale: 0.94
        }
    }


    console.log('home-main-slider:', settings);
    console.log('slides:', $slides.length);
    var homeMainSwiper = new Swiper('.home-main-slider', settings);
});
</script>