<?php

if (isset($_GET['property-type']) && $_GET['property-type'] != '') {
    $_GET['proptype'] = urldecode($_GET['property-type']);
} else {
    $_GET['proptype'] = isset($_GET['proptype']) ? $_GET['proptype'] : '';
} 
//ffdl_debug$property_types);
$minbeds_show = isset($_GET['minbeds']) ? intval($_GET['minbeds']) : 0;
$maxbeds_show = !empty($_GET['maxbeds']) ? intval($_GET['maxbeds']) : $maxBeds;

$minbaths_show = isset($_GET['minbaths']) ? intval($_GET['minbaths']) : 0;
$maxbaths_show = !empty($_GET['maxbaths']) ? intval($_GET['maxbaths']) : $maxBaths;

?>
<div class="container-fluid advance-search-filter">
    <div class="container text-center">
        <a id="apply-filters" href="#" class="btn btn-blue">Apply Filters</a>
    </div>
    <div class="advance-search-menu-overlay"></div>
    <div id="overlay_options" class="advance-search-menu-overlay-inner"></div>
    <div id="overlay_save" class="advance-search-menu-overlay-inner"></div>
    <div class="container-fluid advance-search-filters-container">
        <i class="fa fa-close close-button"></i>
        <form id="frmSearch" action="#" method="post">
            <input id="parking" name="parking" type="hidden" value="<?php echo isset($_GET["parking"])?$_GET["parking"]:"" ?>"/>
            <input id="hoa_dues_max" name="hoa_dues_max" type="hidden" value="<?php echo isset($_GET["hoa_dues_max"])?$_GET["hoa_dues_max"]:"" ?>"/>
            <input id="community" name="community" type="hidden" value="<?php echo isset($_GET["community"])?$_GET["community"]:"" ?>"/>
            <input id="minprice" name="minprice"    data-original_value="<?php echo isset($_GET['minprice'])?$_GET['minprice']:'0'?>" value="<?php echo isset($_GET['minprice'])?$_GET['minprice']:'0'?>" type="hidden" />
            <input id="maxprice" name="maxprice"    data-original_value="<?php echo isset($_GET['maxprice'])?$_GET['maxprice']:''?>"  value="<?php echo isset($_GET['maxprice'])?$_GET['maxprice']:''?>"  type="hidden" />
            <input id="minbeds" name="minbeds"      data-original_value="<?php echo isset($_GET['minbeds'])?$_GET['minbeds']:''?>"   value="<?php echo isset($_GET['minbeds'])?$_GET['minbeds']:''?>"   type="hidden" />
            <input id="maxbeds" name="maxbeds"      data-original_value="<?php echo isset($_GET['maxbeds'])?$_GET['maxbeds']:''?>"    value="<?php echo isset($_GET['maxbeds'])?$_GET['maxbeds']:''?>"    type="hidden" />
            <input id="minbaths" name="minbaths"    data-original_value="<?php echo isset($_GET['minbaths'])?$_GET['minbaths']:''?>" value="<?php echo isset($_GET['minbaths'])?$_GET['minbaths']:''?>"     type="hidden" />
            <input id="maxbaths" name="maxbaths"    data-original_value="<?php echo isset($_GET['maxbaths'])?$_GET['maxbaths']:''?>"  value="<?php echo isset($_GET['maxbaths'])?$_GET['maxbaths']:''?>"  type="hidden" /> 
            
            <input id="ttlitems" name="ttlitems" value="<?php echo $result->found_posts ?>" type="hidden" />
            <input id="projectname" name="projectname" value="<?php echo isset($_GET['projectname'])?$_GET['projectname']:''?>" type="hidden" />
            <input id="postalcode" name="postalcode" value="<?php echo isset($_GET['postalcode'])?$_GET['postalcode']:''?>" type="hidden" />
            <input id="city" name="city" value="<?php echo isset($_GET['city'])?$_GET['city']:''?>" type="hidden" />
            <input id="streetname" name="streetname" value="<?php echo isset($_GET['streetname'])?$_GET['streetname']:''?>" type="hidden" />
            <input id="subdivision" name="subdivision" value="<?php echo isset($_GET['subdivision'])?$_GET['subdivision']:''?>" type="hidden" />
            <div class="row searchfrm-filters-row">
                <div class="col-md-2 col-sm-3 col-xs-12 keywords-field-container autocompleteparent">
                    <label class="search-field-label">&nbsp;</label>
                    <input id="keywords" type="text" class="form-control advance-search-input" placeholder="Search by location name, zipcode or MLS#" name="keywords" value="<?php echo isset($_GET["keywords"])?$_GET["keywords"]: ""?>" autocomplete="off" />
                </div>
                <div class="col-md-2 col-sm-3 col-xs-12">
                    <label class="search-field-label">NEIGHBORHOOD</label>
                    <select id="district" name="district" class="form-control niceSelect">
                        <option value="">Select</option>
                        <?php
                            foreach($districts as $d){
                                if(isset($_GET["district"]) && strtolower($_GET["district"])==strtolower($d)):
                                ?>
                                    <option selected value="<?php echo $d; ?>"><?php echo $d; ?></option>
                                <?php else: ?>
                                    <option value="<?php echo $d; ?>"><?php echo $d; ?></option>
                                <?php
                                endif;
                            }
                        ?>
                    </select>
                </div>
                <div class="col-md-1 col-sm-3 col-xs-12">
                    <label class="search-field-label">TYPE</label>
                    <select id="property-type" name="property-type" class="form-control niceSelect">
                            <option value="">Select</option>
                            <?php if(!empty($property_types) ): ?>
                                <?php 
                                $show_types = apply_filters( 'ffdl_show_proptypes', array());

                                foreach($property_types as $property_type): 
                                    
                                    if( !empty($show_types) && !in_array(strtoupper(trim($property_type)), $show_types) ) continue; ?>
                                        <?php   if(isset($_GET["proptype"]) && strtolower($_GET["proptype"])==strtolower($property_type)): ?>
                                            <option selected value="<?php echo $property_type; ?>"><?php echo $property_type; ?></option>
                                        <?php else: ?>
                                            <option value="<?php echo $property_type; ?>"><?php echo $property_type; ?></option>
                                        <?php endif; ?>

                                <?php endforeach; ?>
                            <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-1 col-sm-3 col-xs-12">
                    <label class="filter-dropdown-label">STATUS</label>
                    <div>
                    <button class="btn-filter-dropdown btn btn-default dropdown-toggle" type="button" id="dropdownMenuStatus" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                        <p>SELECT</p>
                        <span class="selectcon">&nbsp;</span>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuStatus">
                        <li><a href="javascript:void(0);"><label class="custom-checkbox"><input value="Active" name="status[]" <?php if(isset($_GET['status'])){ if(in_array('Active',$_GET['status'])){ echo 'checked';} }else{ echo 'checked';}?>  type="checkbox"> <span>Active</span></label></a></li>
                        <li><a href="javascript:void(0);"><label class="custom-checkbox"><input value="Contingent" name="status[]" <?php if(isset($_GET['status'])){ if(in_array('Contingent',$_GET['status'])){ echo 'checked';} }else{ echo 'checked';}?> type="checkbox"> <span>Contingent</span></label></a></li>
                        <li><a href="javascript:void(0);"><label class="custom-checkbox"><input value="Sold" name="status[]" <?php if(isset($_GET['status'])){ if(in_array('Sold',$_GET['status'])){ echo 'checked';} } ?> type="checkbox"> <span>Sold</span></label></a></li>
                    </ul>
                    </div>
                </div>
                <div class="col-md-2 col-sm-3 col-xs-12">
                    <label class="search-field-label">BEDS</label>
                    <input type="hidden" class="range-slider-multi range-slider-beds" data-current_val="<?php echo $minbeds_show.','.$maxbeds_show; ?>" data-format="%s"  data-target="#minbeds_show,#maxbeds_show" data-from="0" data-to="<?php echo $maxBeds; ?>" />
                    <div class="range multi">
                        <input type="text" class="form-control" id="minbeds_show" placeholder="Min beds" min="0" value="<?php echo $minbeds_show; ?>">
                        <span>to</span>
                        <input type="text" class="form-control" id="maxbeds_show" placeholder="Max beds" min="0" value="<?php echo $maxbeds_show; ?>">
                       <!--  <i data-toggle="tooltip" data-placement="top" title="set value to 0 for studio" class="fa fa-info-circle" aria-hidden="true" style="font-size: 12px;margin: 0 5px;font-weight: 300 !important;"></i> -->        
                    </div>
                </div>
                <!-- <div class="col-md-1 col-sm-3 col-xs-6">
                    <label class="search-field-label">BATH</label>
                    <input id="baths" name="baths" type="text" class="form-control" placeholder="" value="<?php echo isset($_GET["baths"])?$_GET["baths"]: ""?>" />
                </div> -->
                <div class="col-md-2 col-sm-3 col-xs-12">
                    <label class="search-field-label">PRICE</label>
                    <input class="range-slider" type="hidden" value="0,999999"/>
                    <div class="range range-price">
                        <input type="text" class="form-control" id="minprice_show" placeholder="Min Price" min="0" value="">
                        <span>to</span>
                        <input type="text" class="form-control" id="maxprice_show" placeholder="Max Price" min="0" value="">
                    </div>
                </div>
                <div class="col-md-1 col-sm-3 col-xs-12 ">
                    <label class="search-field-label">&nbsp;</label>
                    <ul class="more-options-container">
                        <li>
                            <label class="more-options-action" title="More Options">More <span class="customhidden">Options</span> <i class="fa fa-chevron-down button_action_right"></i></i></label>
                            <ul class="options more-options" style="display:none">
                              <!--   <li>
                                    <h4>Listing Status:<?php if(isset($_GET['status']) && !is_array($_GET['status'])){ $get_statuses =  urldecode($_GET['status']); unset($_GET['status']); $_GET['status'] = explode(',',$get_statuses); }  ?></h4>
                                    <ul class="check-list">
                                        <li><label class="custom-checkbox"><input value="Active" name="status[]" <?php if(isset($_GET['status'])){ if(in_array('Active',$_GET['status'])){ echo 'checked';} }else{ echo 'checked';}?>  type="checkbox"> <span>Active</span></label></li>
                                        <li><label class="custom-checkbox"><input value="Contingent" name="status[]" <?php if(isset($_GET['status'])){ if(in_array('Contingent',$_GET['status'])){ echo 'checked';} }else{ echo 'checked';}?> type="checkbox"> <span>Contingent</span></label></li>
                                        
                                    </ul>
                                </li> -->
                                <li>
                                    <h4>Square Footage:</h4>
                                    <div class="range">
                                        <input name="minsq" id="minsq" placeholder="Enter Minimum" min="0" value="<?php echo @$_GET['minsq'];?>" type="number"  class="form-control" style="width:45%;display:inline-block">
                                        <span> to </span>
                                        <input name="maxsq" id="maxsq" placeholder="Enter Maximum" min="0" value="<?php echo @$_GET['maxsq'];?>" type="number"  class="form-control" style="width:45%;display:inline-block">
                                    </div>
                                </li>
                                <li class="half left">
                                    <h4>Parking:</h4>
                                    <input class="range-slider-single"   data-format="%s"  data-target="#parking_show" data-from="0" data-to="<?php echo $maxParking; ?>" value="100" type="hidden"/>
                                    <div class="range single">
                                        <input id="parking_show" placeholder="Parking" value="<?php echo @$_GET['parking'];?>" type="text" class="form-control" />
                                    </div>
                                </li>
                                <li class="half right">
                                    <h4>HOA Dues:</h4>
                                    <input class="range-slider-single"  data-format="$%s" data-target="#hoa_dues_max_show" data-from="0" data-to="<?php echo $maxHOADues; ?>"  value="100"  type="hidden"/>
                                    <div class="range single">
                                        <input id="hoa_dues_max_show" placeholder="HOA Dues" value="<?php echo @$_GET['hoa_dues_max'];?>" type="text" class="form-control" />
                                    </div>
                                </li>
                               
                                <li class="half left">
                                    <h4>Price Reduced:</h4>
                                    <!--<ul class="check-list"></ul>-->
                                    <div class="range" >
                                        <!--<input type="txt" name="changed" placeholder="mm/dd/yyy HH:MM AM/PM"><small>e.g. 9/28/2016 1:07 AM</small>-->
                                        <select name="changed" id="changed"  class="form-control niceSelect">
                                            <option value="">--Please Select--</option>
                                            <option value="1 day or less" <?php if(str_replace(" day or less","",@$_GET['daysm'])=='1') echo 'selected';?>>1 day or less</option>
                                            <option value="5 days or less" <?php if(str_replace(" days or less","",@$_GET['daysm'])=='5') echo 'selected';?>>5 days or less</option>
                                            <option value="15 days or less" <?php if(str_replace(" days or less","",@$_GET['daysm'])=='15') echo 'selected';?>>15 days or less</option>
                                            <option value="30 days or less" <?php if(str_replace(" days or less","",@$_GET['daysm'])=='30') echo 'selected';?>>30 days or less</option>
                                            <option value="60 days or less" <?php if(str_replace(" days or less","",@$_GET['daysm'])=='60') echo 'selected';?>>60 days or less</option>
                                            <option value="90 days or less" <?php if(str_replace(" days or less","",@$_GET['daysm'])=='90') echo 'selected';?>>90 days or less</option>
                                        </select>
                                    </div>
                                </li>
                                <li class="half right">
                                    <h4>Days on Market:</h4>
                                    <!--<ul class="check-list"></ul>-->
                                    <div class="range">
                                        <!--<input type="number" name="daysm" placeholder="Enter no of days" min="0">-->
                                        <select name="daysm" id="daysm" class="form-control niceSelect">
                                            <option value="">--Please Select--</option>
                                            <option value="1 day or less" <?php if(str_replace(" day or less","",@$_GET['daysm'])=='1') echo 'selected';?>>1 day or less</option>
                                            <option value="5 days or less" <?php if(str_replace(" days or less","",@$_GET['daysm'])=='5') echo 'selected';?>>5 days or less</option>
                                            <option value="15 days or less" <?php if(str_replace(" days or less","",@$_GET['daysm'])=='15') echo 'selected';?>>15 days or less</option>
                                            <option value="30 days or less" <?php if(str_replace(" days or less","",@$_GET['daysm'])=='30') echo 'selected';?>>30 days or less</option>
                                            <option value="60 days or less" <?php if(str_replace(" days or less","",@$_GET['daysm'])=='60') echo 'selected';?>>60 days or less</option>
                                            <option value="90 days or less" <?php if(str_replace(" days or less","",@$_GET['daysm'])=='90') echo 'selected';?>>90 days or less</option>
                                            <option value="180 days or less" <?php if(str_replace(" days or less","",@$_GET['daysm'])=='180') echo 'selected';?>>180 days or less</option>
                                            <option value="365 days or less" <?php if(str_replace(" days or less","",@$_GET['daysm'])=='365') echo 'selected';?>>365 days or less</option>
                                        </select>
                                    </div>
                                </li>
                                <li class="half left">
                                    <h4>Favorites:</h4>
                                    <ul class="check-list">
                                        <li><label class="custom-checkbox"><input name="favorites" value="1" type="checkbox" <?php echo (isset($_GET["favorites"]) && !empty($_GET["favorites"]))?"checked":"" ?>> <span>Search in favorites</span></label></li>
                                    </ul>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
                <div class="col-md-1 col-sm-3 col-xs-12 saved-searches-container">
                    <?php
                        if(is_user_logged_in()):
                    ?>
                        <ul class="save-search-action-container">
                            <li>
                                <label class="save-search-action" title="Save Your Search">Save <span class="customhidden"> Search</span><i class="fa fa-chevron-down button_action_right"></i></label>
                                <ul class="options ssearch" style="display:none">
                                    <li class="ssearch-save clearfix">
                                            <input type="text" id="ssearch-name" class="form-control" name="ssearch-name" placeholder="Enter name for this search" />
                                            <select id="search-frequency" name="search-frequency">
                                                <option value="Daily">Daily</option>
                                                <option value="Weekly">Weekly</option>
                                                <option value="Monthly">Monthly</option>
                                            </select>
                                            <button id="cta-ssearch" name="cta-ssearch"><i class="fa fa-save">&nbsp;&nbsp;</i>Save</button>
                                            <a class="gotopanel" title="Manage your saved searches" href="/account-settings?active_tab=2"><i class="fa fa-cogs"> &nbsp;</i> Manage ...</a>
                                    </li>
                                    <li>
                                        <ul class="ssearch-list" data-search-url="<?php echo esc_url( get_permalink() ); ?>">
                                        <?php
                                            $saved_searches = FFDL_Searches::get_searches_for_user();
                                            foreach( $saved_searches as $ssearch ) :
                                                $ssearch_url = get_permalink() . '?' . $ssearch['search_options'] . 
                                                '&search_name=' . $ssearch[ 'name' ];
                                                ?>
                                                <li>
                                                    <a href="<?php echo esc_url( $ssearch_url ) ?>">
                                                    <?php echo esc_html($ssearch[ 'name' ])?>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    <?php else:?>
                    <a href="/login?redirect=<?php echo urlencode(get_permalink()) ?>">
                        <label class="save-search-action" title="Save Your Search">Save <span class="customhidden"> Search</span><i class="fa fa-chevron-down button_action_right"></i></label>
                    </a>
                    <?php endif;?>
                    <ul class="clear-search-action-container">
                        <li>
                            <label class="clear-search-action active" title="Clear Your Search">Clear <span class="customhidden"> Search</span><i class="fa fa-times button_action_right"></i></label>
                            
                        </li>
                    </ul>
                </div>
            </div>
            <input type="hidden" name="_search_page_id" value="<?php echo isset($search_page_id) ? $search_page_id : get_the_ID(); ?>"
        </form>
    </div>
</div><!-- /.container-fluid -->