<?php
$property_types = FFDL_Helper::get_property_types();
if (isset($_GET['property-type']) && $_GET['property-type'] != '') {
    $_GET['proptype'] = urldecode($_GET['property-type']);
} else {
    $_GET['proptype'] = '';
} 

$background_color = $container_style = $layout = '';
$wrapper_class = '';
$wrapper_style = array();
$container_class = $container_style = '';
if( isset($ui_setting) ){

    $layout = isset($ui_setting['layout']) ? $ui_setting['layout'] : 'Fluid';

    if( !empty($ui_setting['background']['enabled']) ){
        $background_color = !empty($ui_setting['background']['color']) ? 'background-color:'.$ui_setting['background']['color'].';' : '';
    }

    if( $layout == 'Boxed'){
        $box_width = isset($ui_setting['box_width']) ? $ui_setting['box_width'] : '';
        
        $container_styles = array();
        if(!empty($box_width))
            $container_styles[] = 'max-width:'.$box_width.'px';
      
        $container_class = 'container'; 
        $container_style = 'style=" '.implode(';', $container_styles).' "';
    } 
    $wrapper_style  = array();
    $wrapper_style[] = $background_color;
    $wrapper_class = 'container-fluid  ui-content ui-content-' . $ui_setting_key . ' ui-content-quicksearch ui-content-quicksearch-' . $ui_setting_key;
}

?>
<div class="<?php echo $wrapper_class; ?>" style="<?php echo implode(';', $wrapper_style); ?>">

<div class="<?php echo $container_class; ?>" <?php echo $container_style; ?>>
    <form method="GET" action="<?php echo apply_filters( 'ffdl_quick_search_action_url', '/mls-search' ); ?>">
        <div class="row home-search-filter">
            <?php if( false ): ?>
            <!-- <div class="col-md-2 hidden">
                <label class="search-field-label">REALTY TYPE</label>
                <select id="property-type" class="form-control" name="proptype">
                    <option value="">Choose</option>
                    < ?php if (!empty($property_types)) : ?>
                        < ?php foreach ($property_types as $property_type) : ?>
                            <option value="< ?php echo $property_type; ?>" < ?php selected($_GET['proptype'], $property_type); ?>>< ?php echo $property_type; ?></option>
                        < ?php endforeach; ?>
                    < ?php endif; ?>
                </select>
            </div>
           
            <div class="< ?php if( (is_home() || is_front_page()) ) echo 'col-md-8'; else echo 'col-md-12'; ?>  autocompleteparent">
               
                <div class="input-group">
                    <input type="text" class="form-control advance-search-input" placeholder="Search by location name, zipcode or MLS#" name="keywords" id="keywords" />
                    <span class="input-group-btn">
                        <button class="btn btn-default search-btn" type="submit"><i class="glyphicon glyphicon-search"></i></button>
                    </span>
                </div>
            </div> -->
            <?php else: ?>
            
               
            <div class="<?php if( true ) echo 'col-md-9'; else echo 'col-md-12'; ?> autocompleteparent">
                <div class="input-group">
                    <input autocomplete="off" type="text" class="form-control advance-search-input" placeholder="Search by location name, zipcode or MLS#" name="keywords" id="keywords" />
                    <span class="input-group-btn">
                        <button class="btn btn-default search-btn" type="submit"><i class="glyphicon glyphicon-search"></i></button>
                    </span>
                </div>
            </div>

            <?php if( true ): ?>
                <div class="col-md-3">
                    <a href="<?php echo apply_filters( 'ffdl_quick_search_action_url', '/mls-search' ); ?>" class="btn btn-primary btn-blue advance-search-link">Advanced Home Search</a>
                </div>
            <?php endif ?>

            <?php endif ?>
            
           

        </div>
    </form>
</div><!-- /.container -->


</div>