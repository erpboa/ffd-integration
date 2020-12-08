<div class="container-fluid properties-list-content-section topPad-mini">
 <div class="container">
    <div class="row">
    <div class="col-md-12">
 
        <div class="property-item-wrap">
            
            <div class="row properties-container">
                <?php for($i=1; $i<=8; $i++ ){ ?>
                    <?php echo get_template_part('template-parts/properties-loop-item'); ?>
                <?php } ?>    
            </div><!-- .properties-container -->

            <div class="page-load-status" style="display:none">
                <div class="loader-ellips infinite-scroll-request">
                    <span class="loader-ellips__dot"></span>
                    <span class="loader-ellips__dot"></span>
                    <span class="loader-ellips__dot"></span>
                    <span class="loader-ellips__dot"></span>
                </div>
            </div><!-- .page-load-status -->

            <div class="adv_search_disclaimer">MLS listing information is deemed reliable but is not guaranteed by the service.</div>

        </div><!-- .property-item-wrap -->

    </div>
 </div>
 </div>
</div><!-- .properties-list-content-section -->