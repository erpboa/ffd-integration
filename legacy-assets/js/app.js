var isLoading = false;
var lastSection = "";
var app = {
    init: {
        initJRangeSlider: function (selector) {
            var pValues = [];
            var stepAmount = 50000;

            for (var i = 0; i <= parseInt(jQuery("#ulPrice").val()); i += stepAmount) {
                pValues.push(i);

                if (i >= 5000000)
                    stepAmount = 1000000;
                else if (i >= 1000000)
                    stepAmount = 250000;
                else if (i >= 500000)
                    stepAmount = 100000;
                else
                    stepAmount = 50000;
            }

            jQuery(selector).jRange({
                from: 0,
                to: pValues.length - 1,
                step: 1,
                //scale: pValues,
                format: '$%s',
                width: '100%',
                showLabels: false,
                isRange: true,
                theme: "theme-gray",
                onstatechange: function (e, a) {
                    var vals = e.split(",");

                    jQuery("#minprice").val(pValues[vals[0]]);
                    jQuery("#maxprice").val(pValues[vals[1]]);

                    jQuery('#minprice_show').val(app.common.formatThousands(parseInt(pValues[vals[0]])));
                    jQuery('#maxprice_show').val(app.common.formatThousands(parseInt(pValues[vals[1]])));

                },
                ondragend: function (e, a) {
                    var vals = e.split(",");

                    //console.log(vals);
                    jQuery("#minprice").val(pValues[vals[0]]);
                    jQuery("#maxprice").val(pValues[vals[1]]);
                    jQuery("#maxprice").trigger("change");
                    //app.advanceSearch.getSearchResultsAjax();

                    jQuery('#minprice_show').val(app.common.formatThousands(parseInt(pValues[vals[0]])));
                    jQuery('#maxprice_show').val(app.common.formatThousands(parseInt(pValues[vals[1]])));

                }
            });

            jQuery(selector).jRange('setValue', '0,' + pValues.length);
            jQuery('#maxprice_show').val(app.common.formatThousands(parseInt(jQuery("#ulPrice").val())));
        },
        initJRangeSingle: function($selector, params, form_type){
            
            params = params || {};
            params = jQuery.extend(true, {
               from:0,
               to: 100,
               format: '%s',
               width: '100%',
            }, params);

           
            var current_val = params.current_val;
            var step = 1;
            var to = parseInt(params.to);
            step = (to > 100) ? Math.round( to / 100 ) : 1;
           

            function _JRangeSingleUpdate(_this, e, trigger){

                var $this = _this.inputNode[0];;
                var val = e;
                var target = $this.getAttribute('data-target');
                var input = target.replace('#', '');
                    input = input.replace('_show', '');
                
                val = app.common.formatNumber(val);
                val = $this.getAttribute('data-format').replace('%s',  val);
                jQuery(target).val(val);
                if( form_type != 'quick_search' && trigger == true ){
                    jQuery('#frmSearch #'+input).val(val).trigger('change');
                } else {
                    jQuery('form #'+input).val(val);
                }
                
            }
            $selector.jRange({
                from: params.from,
                to: params.to,
                step: step,
                format: params.format,
                width: params.width,
                showLabels: false,
                theme: "theme-gray",
                onstatechange: function (e) {

                    _JRangeSingleUpdate(this, e, false);

                },
                ondragend: function(e){

                    _JRangeSingleUpdate(this, e, true);

                    
                }
            });

           
            $selector.jRange('setValue', current_val);

            jQuery('.range-slider-single').each(function(e){
                var $this = jQuery(this);
                var target = $this.attr('data-target');
                var input = target.replace('#', '').replace('_show', '');
               
                if( jQuery(target).length > 0 ){
                    jQuery(target).on('keyup', function(e2){
                        var $this2 = jQuery(this);
                        var value = $this2.val();
                        value = value.replace(/[^0-9.]/g, "");
                        console.log(value);
                        if( value == '' )
                            return;
                        
                        //var step_value = (value > 100) ? Math.round( value / 100 ) : 1;
                        $this.jRange('setValue', value);
                        if(  form_type != 'quick_search' ){
                            jQuery('#frmSearch #'+input).val(value).trigger('change');
                        }
                    });
                }
            });

        },
        
        initJRangeMulti: function($selector, params, form_type)
        {
                    
            var delay = (function(){
                var timer = 0;
                return function(callback, ms){
                clearTimeout (timer);
                timer = setTimeout(callback, ms);
                };
            })();

            params = params || {};
            params = jQuery.extend(true, {
            from:0,
            to: 100,
            format: '%s',
            width: '100%',
            }, params);

        
            var current_val = params.current_val.split(',');
            var step = 1;
            var to = parseInt(params.to);
            step = (to > 100) ? Math.round( to / 100 ) : 1;

            var has_price_range = false;
            var trigger_state_change = true;
            var pValues = [];
            var ulPrice = jQuery("#ulPrice").val() || to;
                ulPrice = parseInt(ulPrice);

            var stepAmount = 50000;

            range_from = params.from;
            range_to = params.to;
            range_step = 1;

           

            if( $selector.hasClass('has_price_range') && to > stepAmount){
                
                has_price_range = true;
                for (var i = 0; i <= ulPrice; i += stepAmount) {
                    pValues.push(i);
                    if (i >= 5000000)
                        stepAmount = 1000000;
                    else if (i >= 1000000)
                        stepAmount = 250000;
                    else if (i >= 500000)
                        stepAmount = 100000;
                    else
                        stepAmount = 50000;
                }

                range_to = pValues.length - 1;
                
            }

            function _JRangeClosest (num, arr) {
                if( num <= 0 )
                    return num;

                if( num >= params.to ){
                    return arr.length - 1;
                }
                var curr = arr[0];
                var diff = Math.abs (num - curr);
                var closest_index=0;
                for (var val = 0; val < arr.length; val++) {
                    var newdiff = Math.abs (num - arr[val]);
                    if (newdiff < diff) {
                        diff = newdiff;
                        curr = arr[val];
                        closest_index = val;
                    }
                }
                return closest_index;
            }
        

            function _JRangeMultiUpdate(_this, e, a){

                var $this = _this.inputNode[0];
                var val = e.split(',');
                var val1 = val[0];
                var val2 = val[1];

                if( val1 == 0 && val2 == 0 )
                    return;

                if( has_price_range ){
                    /*  we need to get the index value from price values array */
                    val1 = pValues[val1] + '';
                    val2 = pValues[val2] + '';
                }
                
                var target = $this.getAttribute("data-target").split(',');
                var target1 = target[0];
                var target2 = target[1];

                var input1 = target1.replace('_show', '');
                var input2 = target2.replace('_show', '');

                if( jQuery('#frmSearch').length > 0 ){
                    jQuery('#frmSearch '+input1).val(val1);
                    jQuery('#frmSearch '+input2).val(val2);
                } else {
                    jQuery('form  '+input1).val(val1);
                    jQuery('form  '+input2).val(val2);
                }
                
                
                val1 = app.common.formatNumber(val1);
                val2 = app.common.formatNumber(val2);
            

                val1 = $this.getAttribute('data-format').replace('%s',  val1);
                val2 = $this.getAttribute('data-format').replace('%s',  val2);
                
                jQuery(target1).val(val1);
                jQuery(target2).val(val2);

                

            }



            function _JRangeMultiPriceRangeStepValue(from, to, max_limit, pValues){

                    var jPriceMin, jPriceMax;
                    jPriceMin = from;
                    jPriceMax = to;
                    

                    if(jPriceMin > max_limit )
                        jPriceMin = 0;
                    
                    if( jPriceMax > max_limit )
                        jPriceMax = max_limit;
                
                    if( jPriceMin >= jPriceMax )
                        jPriceMin = parseInt(jPriceMax / 2)
                
                
                var jMinIndex=0, jMaxIndex = 0;
                for (var pi = 0; pi < pValues.length - 1; pi++) {
                    if( jMinIndex == 0 && pValues[pi] > jPriceMin ){
                        jMinIndex = pi;
                    }

                    if( jMaxIndex == 0 && jPriceMax < pValues[pi]  ){
                        jMaxIndex = pi;
                    }
                    
                }

                if( jMinIndex == jMaxIndex ){
                    if( jMinIndex == 0 ){
                        jMinIndex = 0;
                        jMaxIndex = 1;
                    } else {
                        
                        jMinIndex = (jMaxIndex - 3  > 1 ) ? jMaxIndex - 3  : jMaxIndex - 1;
                    }
                }

                var jNewValue = jMinIndex + "," + jMaxIndex;

                return jNewValue;

            }

            
            
            var $this =  $selector;
            var target = $this.attr('data-target').split(',');
            var target1 = target[0];
            var target2 = target[1];
            var input1 = target1.replace('_show', '');
            var input2 = target2.replace('_show', '');
            
            $selector.jRange({
                from: range_from,
                to: range_to,
                step: range_step,
                format: params.format,
                width: params.width,
                showLabels: false,
                isRange: true,
                theme: "theme-gray",
                onstatechange: function (e) {

                    var test = e.split(',');
                    if( test[0]  == 0 && test[1] == 0 ){
                        return;
                    }
                    
                    if( trigger_state_change !== 1 && trigger_state_change === true ){
                        _JRangeMultiUpdate(this, e);
                    } else  if( trigger_state_change === 1 ){
                        trigger_state_change = true;
                    } else {
                        trigger_state_change = 1;
                    }

                },
                ondragend: function (e, a) {

                    var $this = this.inputNode[0];
                    var target = $this.getAttribute("data-target").split(',');
                    var target2 = target[1];
                    var input2 = target2.replace('_show', '');
                    _JRangeMultiUpdate(this, e,a);

                    if( form_type != 'quick_search'){
                        jQuery('#frmSearch '+input2).trigger('change');
                    }
                    
                }
            });


            function _jPriceRangeSet($selector, current_val){

                var _current_val1 = _JRangeClosest(current_val[0], pValues);
                var _current_val2 = _JRangeClosest(current_val[1], pValues);
                $selector.jRange('setValue', _current_val1+','+_current_val2);

            }


            function _jPriceInputSet($this, val1, val2){

                if( jQuery('#frmSearch').length > 0 ){
                    jQuery('#frmSearch '+input1).val(val1);
                    jQuery('#frmSearch '+input2).val(val2);
                } else {
                    jQuery('form  '+input1).val(val1);
                    jQuery('form  '+input2).val(val2);
                }
                
                
                val1 = app.common.formatNumber(val1);
                val2 = app.common.formatNumber(val2);
            

                val1 = $this.attr('data-format').replace('%s',  val1);
                val2 = $this.attr('data-format').replace('%s',  val2);
                
                jQuery(target1).val(val1);
                jQuery(target2).val(val2);

            }


            
            
            if( has_price_range ){
                
                _jPriceRangeSet($selector, current_val);
                 _jPriceInputSet($selector, current_val[0], current_val[1]);

            } else {
                $selector.jRange('setValue', current_val[0]+','+current_val[1]);
            }
            
          

            

            jQuery(target1).on('keyup', function(e2){
                var $this2 = jQuery(this);
                delay(function(){
                   
                    var last_value = $this2.attr('data-last_value') || '';
                    var value1 = $this2.val();
                    var value2 = jQuery(target2).val();
                
                    value1 = value1.replace(/[^0-9.]/g, "");
                    value2 = value2.replace(/[^0-9.]/g, "");
                    
                    if( value1 == '' || last_value == value1 )
                        return;

                    $this2.attr('data-last_value', value1);

                    trigger_state_change = false;
                    if( has_price_range ){
                        _jPriceRangeSet($this, [value1, value2]);
                    } else { 
                        $this.jRange('setValue', value1 + ',' + value2);
                    }

                    if( jQuery('#frmSearch').length > 0 ){
                        jQuery('#frmSearch '+input1).val(value1);
                        jQuery('#frmSearch '+input2).val(value2);
                    } else {
                        jQuery('form '+input1).val(value1);
                        jQuery('form '+input2).val(value2);
                    }

                    if( form_type != 'quick_search'){
                        jQuery(input2).trigger('change');
                    }


                }, 1000);
            });

            jQuery(target2).on('keyup', function(e2){
                var $this2 = jQuery(this);
                delay(function(){
                    console.log('.range-slider-multi on keyup2');
                    
                    var last_value = $this2.attr('data-last_value') || '';
                    var value2 = $this2.val();
                    var value1 = jQuery(target1).val();

                    value1 = value1.replace(/[^0-9.]/g, "");
                    value2 = value2.replace(/[^0-9.]/g, "");
                   
                    if( value2 == '' || last_value == value2 )
                        return;

                    $this2.attr('data-last_value', value2);


                    trigger_state_change = false;
                    if( has_price_range ){
                        _jPriceRangeSet($this, [value1, value2]);
                    } else {
                        $this.jRange('setValue', value1 + ',' + value2);
                    }
                    
                    if( jQuery('#frmSearch').length > 0 ){
                        jQuery('#frmSearch '+input1).val(value1);
                        jQuery('#frmSearch '+input2).val(value2);
                    } else {
                        jQuery('form '+input1).val(value1);
                        jQuery('form '+input2).val(value2);
                    }
                    if( form_type != 'quick_search'){
                        jQuery(input2).trigger('change');
                    }

                }, 1000);
            });

                
            
        }
    },
    common: {

        replaceValueInStr: function(str, matches, values){

            jQuery.each(matches, function (i, match) { 
                 
                str = str.replace(match, values[i]);

            });

            return str;

        },

        formatThousands: function (number, decimals, decimal_sep, thousands_sep) {
           
            return '$' + app.common.formatNumber(number, decimals, decimal_sep, thousands_sep);
        },

        formatNumber: function (number, decimals, decimal_sep, thousands_sep) {

            

            number = number.replace('$', '');
            number = number.replace(/,/g, '');
            number = ( isNaN( parseInt(number) ) ) ? '' : parseInt(number) + '';

            

            var n = number,
                c = isNaN(decimals) ? 0 : Math.abs(decimals),
                d = decimal_sep || '.',
                t = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
                sign = (n < 0) ? '-' : '',
                i = parseInt(n = Math.abs(n).toFixed(c)) + '',

                j = ((j = i.length) > 3) ? j % 3 : 0;

            return sign + (j ? i.substr(0, j) + t : '') +
                i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) +
                (c ? d + Math.abs(n - i).toFixed(c).slice(2) : '');
        },

        nFormatter: function (num, digits) {
            var si = [
                { value: 1E18, symbol: "E" },
                { value: 1E15, symbol: "P" },
                { value: 1E12, symbol: "T" },
                { value: 1E9, symbol: "G" },
                { value: 1E6, symbol: "M" },
                { value: 1E3, symbol: "k" }
            ], rx = /\.0+$|(\.[0-9]*[1-9])0+$/, i;
            for (i = 0; i < si.length; i++) {
                if (num >= si[i].value) {
                    return (num / si[i].value).toFixed(digits).replace(rx, "$1") + si[i].symbol;
                }
            }
            num = parseFloat(num);
            return num.toFixed(digits).replace(rx, "$1");
        },
        showFullScreenLoader: function () {
            jQuery(".full-page-overlay").show();
        },
        hideFullScreenLoader: function () {
            jQuery(".full-page-overlay").hide();
        },
        isMobile: function () {
            if (jQuery("#apply-filters").length > 0 && jQuery("#apply-filters").is(":visible")) {
                return true;
            }
            return false;
        },
        hideprediction: function () {
            jQuery('.ulPredictionResults').hide();
        },
        showprediction: function () {
            if (jQuery(this).val() != '')
                jQuery('.ulPredictionResults').show();
        },
        initRangeSlider: function (form_type){

            jQuery('.range-slider-single').each(function(e){
                var $this = jQuery(this);
                var params = {current_val:$this.attr('data-current_val'),from: parseInt($this.attr('data-from')), to: parseInt($this.attr('data-to')), format:$this.attr('data-format')}
                app.init.initJRangeSingle($this, params, form_type);
               
            });
    
            jQuery('.range-slider-multi').each(function(e){
                var $this = jQuery(this);
                var params = {current_val:$this.attr('data-current_val'), from: parseInt($this.attr('data-from')), to: parseInt($this.attr('data-to')), format:$this.attr('data-format')}
                app.init.initJRangeMulti($this, params, form_type);
               
            });

        },
        initScripts: function () {
            jQuery("body").delegate(".favorite", "click", app.common.saveFavorite);

            jQuery("body").delegate("input.advance-search-input", "keyup", app.common.predictOptions);

            //jQuery("body").delegate("input.advance-search-input","focusout",app.common.hideprediction);

            jQuery("body").delegate("input.advance-search-input", "focus", app.common.showprediction);

            //jQuery('body:not(.input-group)').click(function(){app.common.hideprediction();})
            jQuery(document).on("click", function (event) {
                var $trigger = jQuery(".autocompleteparent");

                if ($trigger !== event.target && !$trigger.has(event.target).length) {
                    app.common.hideprediction();
                    if (!app.advanceSearch.vars.filterbyajax) {
                        app.advanceSearch.vars.filterbyajax = true;
                        jQuery('#keywords').trigger('change');
                    }
                }
            });
        },
        addslashes: function (str) {
            return (str + '').replace(/[\\"']/g, '\\$&').replace(/\u0000/g, '\\0');
        },
        doSearch: function (section, value) {
            jQuery("#keywords").unbind("change");
            if (jQuery(".advance-search-input").val() == "") {
                alert("Please enter search");
                return false;
            }
            var proptype = '';
            //if (jQuery('#property-type').val() != '')
            //    proptype = '&proptype=' + jQuery('#property-type').val();

            var data = jQuery("#frmSearch").serialize();
            var cleanData = "";

            var parts = data.split('&');

            for (var i = 0; i < parts.length; i++) {
                var subParts = parts[i].split('=');

                if (subParts.length > 1 && subParts[1] != "" && subParts[1] != "Select" && subParts[0] != section && subParts[0] != "keywords" && subParts[1] != "--Please+Select--" && subParts[0] != "ttlitems")
                    cleanData += subParts[0] + "=" + subParts[1] + "&";
            }

            var redirect = ffdl_vars.search_page_url + "?search&" + section + "=" + value + proptype;

            if (cleanData != "")
                redirect += "&" + cleanData.substring(0, cleanData.length - 1);

            window.location = redirect;
            return false;
        },
        redirect: function (url) {
            window.location = url.replace("\'", "'");
        },
        showSearchResults: function () {
            $_this = jQuery('input.advance-search-input');

            if (!$_this.closest('div').children('.ulPredictionResults').length)
                $_this.closest('div').append('<ul class="ulPredictionResults"></ul>');
            else
                $_this.closest('div').children('.ulPredictionResults').show();

            $_this.closest('div').children('.ulPredictionResults').html('<div class="text-center"><i class="fa fa-spin fa-spinner"></i></div>');
        },
        predictOptions: function (e) {
            app.advanceSearch.vars.filterbyajax = false;
            var delay = (function () {
                var timer = 0;
                return function (callback, ms) {
                    clearTimeout(timer);
                    timer = setTimeout(callback, ms);
                };
            })();

            delay(function () {
                app.common.showSearchResults();
                app.common.doAutoComplete();
            }, 200);
        },
        doAutoComplete: function () {
            $_this = jQuery('input.advance-search-input');
            if ($_this.val() == "") {
                jQuery(".ulPredictionResults").empty();
                jQuery(".ulPredictionResults").hide();
                return;
            }

            app.common.autoComplete($_this.val(), 4, app.common.populateSearch);
        },
        autoComplete: function (term, limit, callback) {
            jQuery.ajax({
                type: "post",
                dataType: "json",
                url: ffdl_vars.ajaxurl,
                data: { action: "autoCompleteListings", limit: limit, kw: term },
                success: function (response) {
                    callback(response);
                },
                error: function (err) {
                    //alert(err);
                }
            });
        },
        populateSearch: function (response) {
            if (response.data.html != '')
                jQuery(".ulPredictionResults").html(response.data.html);
            else
                jQuery(".ulPredictionResults").hide();

            return;
        },
        saveFavorite: function (e) {//alert('MIA');
            var $_this = jQuery(this);
            if ($_this.hasClass('btn')) {
                if ($_this.find('.label').text() == 'Loading...' || $_this.hasClass('link-login')) {
                    return;
                } else {
                    $_this.find('.label').text('Loading...');
                }
            } else {
                if ($_this.hasClass('icon')) {

                    var $i2svg = $_this.children('.svg-inline--fa');

                    if ( $i2svg.length > 0 ) {
                        if ($i2svg.hasClass('fa-heartbeat')) {
                            $i2svg.attr('data-icon', 'spinner');
                            $i2svg.toggleClass('fa-heartbeat fa-spin');
                        } else {
                            $i2svg.attr('data-icon', 'spinner');
                            $i2svg.toggleClass('fa-heart fa-spin');
                        }

                    } else {
                    
                        if ($_this.children('i').hasClass('fa-heartbeat')) {
                            $_this.children('i').toggleClass('fa-heartbeat fa-spinner fa-spin');
                        }
                        else {
                            $_this.children('i').toggleClass('fa-heart fa-spinner fa-spin');
                        }
                    }
                }
                else {
                    $_this.text("Updating...");
                }
            }

            var data = {
                'prop_id': $_this.attr('data-prop-id'),
                'action': 'ajax_favorite'
            };
            jQuery.ajax({
                url: ffdl_vars.ajaxurl,
                type: 'POST',
                cache: false,
                data: data,
                success: function (response) {
                    if (true == response.success) {
                        if (true == response.data.favorite) {
                            if ($_this.hasClass('icon')) {
                                var $i2svg = $_this.children('.svg-inline--fa');
                                if ( $i2svg.length > 0 ) {
                                    $i2svg.attr('data-icon', 'heartbeat');
                                    $i2svg.toggleClass('fa-heartbeat fa-spin')
                                } else {
                                    $_this.children('i').toggleClass('fa-heartbeat fa-spinner fa-spin');
                                }    
                                $_this.attr('title', 'Remove from Favorites');
                            }
                            else {
                                $_this.find('.bt-heart').removeClass('btm');
                                $_this.find('.bt-heart').addClass('bts');
                                $_this.text("Remove as Favorite");
                                //console.log('is favorite');
                            }
                        }
                        else {
                            if ($_this.hasClass('icon')) {
                                var $i2svg = $_this.children('.svg-inline--fa');
                                if ( $i2svg.length > 0 ) {
                                    $i2svg.attr('data-icon', 'heart');
                                    $i2svg.toggleClass('fa-heart fa-spin')
                                } else {
                                    $_this.children('i').toggleClass('fa-heart fa-spinner fa-spin');
                                }
                                $_this.attr('title', 'Add to Favorites');
                            }
                            else {
                                $_this.find('.bt-heart').removeClass('bts');
                                $_this.find('.bt-heart').addClass('btm');
                                $_this.text("Save Property");
                                //console.log('not favorite');
                            }
                        }
                    }
                },
                error: function (errorThrown) {
                },
                complete: function () {
                    $_this.parents('.item').removeClass('busy');
                }
            });

            return false;
        },
        imgError: function (img) {
            img.onerror = "";
            img.src = ffdl_vars.listing_img_placeholder;
            return true;
        }
    },
    accountSettings: {
        initScripts: function () {
            jQuery('.cta-delete').click(function (e) {
                e.preventDefault();
                var confirmation = confirm('This saved searched will be deleted. Are you sure?');
                var $_this = jQuery(this);

                if (!confirmation) {
                    return false;
                }

                var data = {
                    'id': jQuery(this).attr('data-id'),
                    'action': 'ajax_save_search',
                    'command': 'del'
                }
                jQuery.ajax({
                    url: ffdl_vars.ajaxurl,
                    type: 'POST',
                    cache: false,
                    data: data,
                    success: function (response) {
                        if (true == response.success) {
                            $_this.closest('tr').remove();
                        } else {
                            console.log('response.success expected to be true:', response);
                        }
                    },
                    error: function (errorThrown) {
                        console.log('Ajax error:', errorThrown);
                    }
                });

                return false;
            });

        }
    },
    advanceSearch: {
        vars: {
            $search_form: null,
            $pricesort: null,
            property_markers: {},
            infowindows: {},
            last_info_window: false,
            search_map: false,
            mapData: "",
            areaChanged: false,
            last_hovered_item_id: null,
            shapes: [],
            searchMapInit: false,
            drawingManager: null,
            filterbyajax: true,
            bounds: null,

        },
        initScripts: function () {
            this.registerNiceSelect();
            this.vars.$search_form = jQuery('.advanced-search #search_form');
            this.vars.$pricesort = jQuery("#sorting");
            if (!jQuery('.properties-container').hasClass('map-hidden')) {
                this.vars.search_map = this.initMap(jQuery('#map'));
                this.initMapChangedEvents();
                this.initMapScrollBehavior();
            }

            if (jQuery(".save-search-action-container").length > 0) {
                app.advanceSearch.registerSavedSearchesDropdown();
            }
            app.advanceSearch.registerMoreOptionsDropdown();

            if (null != this.vars.search_map) {
                this.init_property_markers();
            }
            this.registerRemoveFilterTagsEvents();

            //Setup filters from querystring
            jQuery.each(["#orderby", "#keywords", "#district", "#property-type", "#maxbeds", "#maxbaths", "#beds", "#baths", "#maxprice", "#community", "#daysm", "#changed", "#minsq", "#maxsq", "#minacr", "#maxacr", "#frmSearch input[name='status[]']", "#frmSearch input[name='view[]']", "#frmSearch input[name='frontage[]']", "#frmSearch input[name='pool']", "#frmSearch input[name='tenure']", "#frmSearch input[name='featured']", "#frmSearch input[name='favorites']", "#projectname", "#postalcode", "#city", "#streetname", "#subdivision"], function (index, tag) {
                app.advanceSearch.updateSearchFilterTags(jQuery(tag));
            });
            jQuery("body").delegate(".showonmap", "click", app.advanceSearch.showOnMap);

            jQuery('#cta-ssearch').click(function (e) {
                e.preventDefault();
                var added = new Date();
                added = added.toLocaleString();
                var data = {
                    'name': jQuery('#ssearch-name').val(),
                    'form_data': jQuery("#frmSearch").serialize(),
                    'added': added,
                    'action': 'ajax_save_search',
                    'command': 'save'
                }
                jQuery.ajax({
                    url: ffdl_vars.ajaxurl,
                    type: 'POST',
                    cache: false,
                    data: data,
                    success: function (response) {
                        if (true == response.success) {
                            app.advanceSearch.update_ssearch_list(response.data);
                        } else {
                            console.log('response.success expected to be true:');
                            console.log(response);
                        }
                    },
                    error: function (errorThrown) {
                        console.log('Ajax error:', errorThrown);
                    }
                });

                return false;
            });

        },
        showOnMap: function (e) {
            jQuery(this).closest(".property-item").addClass('test');
            app.advanceSearch.search_result_hovered(jQuery(this).closest('.property-item'));
        },
        update_ssearch_list: function (data) {
            var list = '';
            for (var i = 0; i < data.length; i++) {
                list += '<li><a href="' +
                    jQuery('.ssearch-list').attr('data-search-url') +
                    '?' + data[i].search_options +
                    '">' + data[i].name +
                    '</a>' +
                    /*'<a class="ssearch-delete" data-name="' + 
                    data[i].name + '" title="Delete this saved search">x</a>' +*/
                    '</li>';
            }
            jQuery('.ssearch-list').html(list);

            jQuery('.ssearch-link').click(function (e) {
                e.preventDefault();
                get_saved_search($(this).html());

            });

            jQuery('.ssearch-delete').click(function (e) {
                e.preventDefault();
                var data = {
                    'name': $(this).attr('data-name'),
                    'action': 'hbg_ajax_save_search',
                    'command': 'delete'
                }
                jQuery.ajax({
                    url: hbg.ajaxurl,
                    type: 'POST',
                    cache: false,
                    data: data,
                    success: function (response) {
                        update_ssearch_list(response.data);
                    },
                    error: function (errorThrown) {
                        console.log('Ajax error:', errorThrown)
                    }
                });

                return false;
            });

        },
        registerSavedSearchesDropdown: function () {
            jQuery(".save-search-action").on("click", function () {
                jQuery('#overlay_save').toggle();
                jQuery(".options.ssearch").toggle();
                jQuery(".save-search-action i").toggleClass("fa-chevron-down fa-chevron-up");

                jQuery('.save-search-action').css('z-index', '8');
                jQuery('.save-search-action').children('.options').css('z-index', '8');
            });

            jQuery("#overlay_save").on("click", function () {
                jQuery('#overlay_save').toggle();
                jQuery(".options.ssearch").toggle();
                jQuery(".save-search-action i").toggleClass("fa-chevron-down fa-chevron-up");

                jQuery('.save-search-action').css('z-index', '0');
                jQuery('.save-search-action').children('.options').css('z-index', '0');
            });
        },
        registerMoreOptionsDropdown: function () {
            jQuery(".more-options-action").on("click", function () {
                jQuery("#overlay_options").toggle();
                jQuery(".options.more-options").toggle();
                jQuery(".more-options-action i").toggleClass("fa-chevron-down fa-chevron-up");

                jQuery('.more-options-action').css('z-index', '8');
                jQuery('.more-options-action').children('.options').css('z-index', '8');
            });
            jQuery("#overlay_options").on("click", function () {
                jQuery('#overlay_options').toggle();
                jQuery(".options.more-options").toggle();
                jQuery(".more-options-action i").toggleClass("fa-chevron-down fa-chevron-up");

                jQuery('.more-options-action').css('z-index', '0');
                jQuery('.more-options-action').children('.options').css('z-index', '0');
            });
        },
        registerNiceSelect: function () {
            jQuery('.niceSelect').niceselect();
        },
        resetNiceSelect: function(input_id=''){

            jQuery("#" + input_id).val("");
            jQuery(".niceselect." + input_id + " input[type=radio]").removeAttr("checked");
            jQuery(".niceselect." + input_id + " input[type=radio]").eq(0).prop("checked", "checked");
            jQuery(".niceselect." + input_id + " > p.top").text(jQuery(".niceselect." + input_id + " input[type=radio]").eq(0).val());
            jQuery(".niceselect." + input_id + " .value_wrapper .values").removeClass("active");
            
        },
        registerRemoveFilterTagsEvents: function () {
            jQuery(document).on("click", '.clear-search-action', function () {
                if (jQuery(this).hasClass('active')) {
                    app.advanceSearch.clearPoly();
                    jQuery.each(["#minprice_show", "#maxprice_show", "#minbeds_show", "#maxbeds_show", "#minbaths_show", "#maxbaths_show", "#orderby", "#keywords", "#district", "#property-type", "#beds", "#baths", '#minbeds', '#minbaths', '#maxbeds', '#maxbaths', "#maxprice", "#community", "#daysm", "#changed", "#minsq", "#maxsq", "#minacr", "#maxacr", "#frmSearch input[name='status[]']", "#frmSearch input[name='view[]']", "#frmSearch input[name='frontage[]']", "#frmSearch input[name='pool']", "#frmSearch input[name='tenure']", "#frmSearch input[name='featured']", "#frmSearch input[name='favorites']", "#projectname", "#postalcode", "#city", "#streetname", "#subdivision"], function (index, tag) {
                        var input_id = jQuery(tag).attr("id");
                        var input_name = jQuery(tag).attr("name");
                        if (input_name == 'status[]' || input_name == 'frontage[]' || input_name == 'view[]') {
                            jQuery(tag).each(function () {
                                jQuery(this).prop("checked", false);
                            });
                        }
                        else if (input_name == 'pool' || input_name == 'featured' || input_name == 'favorites') {
                            jQuery(tag).prop("checked", false);
                        }
                        else if (input_name == 'tenure') {
                            jQuery(tag).filter('[value="All"]').prop("checked", true);
                        }
                        else if (input_id == "minbeds_show" || input_id == "maxbeds_show" ||input_id == "minbaths_show" || input_id == "maxbaths_show" || input_id == "minprice_show" || input_id == "maxprice_show" || input_id == "keywords" || input_id == "beds" || input_id == "baths" || input_id == "community" || input_id == "projectname" || input_id == "postalcode" || input_id == "city" || input_id == "streetname" || input_id == "subdivision") {
                            
                            jQuery(tag).val('');

                            if(jQuery(tag).hasClass('niceSelect') ){
                                app.advanceSearch.resetNiceSelect(input_id);
                            }

                        }
                        else if (input_id == "district" || input_id == "property-type" || input_id == "daysm" || input_id == "changed") {
                            jQuery("#" + input_id).val("");
                            jQuery(".niceselect." + input_id + " input[type=radio]").removeAttr("checked");
                            jQuery(".niceselect." + input_id + " input[type=radio]").eq(0).prop("checked", "checked");
                            jQuery(".niceselect." + input_id + " > p.top").text(jQuery(".niceselect." + input_id + " input[type=radio]").eq(0).val());
                            jQuery(".niceselect." + input_id + " .value_wrapper .values").removeClass("active");
                        }
                        else if (input_id == "orderby") {
                            jQuery("#" + input_id).val("pba__listingprice_pb__c:desc");
                            jQuery(".niceselect." + input_id + " > p.top").text(jQuery(".niceselect." + input_id + " input[type=radio]").eq(2).data('text'));
                            jQuery(".niceselect." + input_id + " .value_wrapper .values").removeClass("active");
                        } else if (input_id == "maxprice" || input_id == "minprice") {

                            if( jQuery(".range-slider-price").hasClass('range-slider-single') ){
                                var newprice = jQuery(".range-slider-price").attr('data-from');
                            } else {
                                var newprice = jQuery(".range-slider-price").attr('data-from')+ "," + jQuery(".range-slider-price").attr('data-to');
                            }
                            
                            jQuery(".range-slider-price").jRange("setValue", newprice);
                            jQuery('#minprice').val('');
                            jQuery('#maxprice').val('');

                        } else if (input_id == "maxprice") {

                            var prices = jQuery(".range-slider").jRange("getOptions");
                            var newPrices = prices.from + "," + prices.to;
                            jQuery(".range-slider").jRange("setValue", newPrices);

                        }else if (input_id == "maxbeds" || input_id == "minbeds") {

                            if( jQuery(".range-slider-beds").hasClass('range-slider-single') ){
                                var newbeds = jQuery(".range-slider-beds").attr('data-from');
                            } else {
                                var newbeds = jQuery(".range-slider-beds").attr('data-from')+ "," + jQuery(".range-slider-beds").attr('data-to');
                            }
                            
                            jQuery(".range-slider-beds").jRange("setValue", newbeds);
                            jQuery('#minbeds').val('');
                            jQuery('#maxbeds').val('');

                        } else if (input_id == "maxbaths" || input_id == "minbaths" ) {

                            if( jQuery(".range-slider-baths").hasClass('range-slider-single') ){
                                var newbaths = jQuery(".range-slider-baths").attr('data-from');
                            } else {
                                var newbaths = jQuery(".range-slider-baths").attr('data-from')+ "," + jQuery(".range-slider-baths").attr('data-to');
                            }

                            jQuery(".range-slider-baths").jRange("setValue", newbaths);

                            jQuery('#minbaths').val('');
                            jQuery('#maxbaths').val('');

                        } else {
                            if(jQuery(tag).hasClass('niceSelect') ){
                                app.advanceSearch.resetNiceSelect(input_id);
                            } else if(jQuery(tag).is('[class^=range-slider-]')){

                            } else {
                                jQuery(tag).val('');
                            }
                        }
                        jQuery('input[name="status[]"][value="Active"]').prop("checked", true);
                        jQuery('input[name="status[]"][value="Contingent"]').prop("checked", true);
                        app.advanceSearch.updateSearchFilterTags(jQuery(tag));
                    });

                    jQuery(".price-range-tag").hide();
                    jQuery(".range-slider-beds").hide();
                    jQuery(".range-slider-baths").hide();
                    //jQuery(".filters-tags").hide();
                    jQuery("#keywords").trigger("change");
                    //app.advanceSearch.getSearchResultsAjax();
                    jQuery(this).removeClass('active');
                }
            });

            jQuery("body").delegate(".filters-tags span .remove-filter-tag", "click", function () {
                if (jQuery(this).parent().hasClass("keyword-tag")) {
                    jQuery("#keywords").val("");
                    jQuery("#keywords").trigger("change");
                }
                else if (jQuery(this).parent().hasClass("favorites-tag") || jQuery(this).parent().hasClass("featured-tag") || jQuery(this).parent().hasClass("pool-tag")) {
                    var class_name = jQuery(this).parent('span.btn').attr('class').replace('btn btn-info search-filter-tag ', '').replace('-tag', '');
                    jQuery("#frmSearch input[name='" + class_name + "']").prop("checked", false);
                    jQuery("#frmSearch input[name='" + class_name + "']").trigger("change");
                }
                else if (jQuery(this).parent().hasClass("district-tag") || jQuery(this).parent().hasClass("property-type-tag") || jQuery(this).parent().hasClass("daysm-tag") || jQuery(this).parent().hasClass("changed-tag")) {
                    var class_name = jQuery(this).parent('span.btn').attr('class').replace('btn btn-info search-filter-tag ', '').replace('-tag', '');

                    jQuery("#" + class_name).val("");
                    jQuery(".niceselect." + class_name + " input[type=radio]").removeAttr("checked");
                    jQuery(".niceselect." + class_name + " input[type=radio]").eq(0).prop("checked", "checked");
                    jQuery(".niceselect." + class_name + " > p.top").text(jQuery(".niceselect." + class_name + " input[type=radio]").eq(0).val());
                    jQuery(".niceselect." + class_name + " .value_wrapper .values").removeClass("active");
                    jQuery("#" + class_name).trigger("change");
                }
                else if (jQuery(this).parent().hasClass("orderby-tag")) {
                    //alert('good');
                    var class_name = jQuery(this).parent('span.btn').attr('class').replace('btn btn-info search-filter-tag ', '').replace('-tag', '');

                    jQuery("#" + class_name).val(jQuery("#" + class_name + " option:first").val());
                    jQuery(".niceselect." + class_name + " input[type=radio]").removeAttr("checked");
                    jQuery(".niceselect." + class_name + " input[type=radio]").eq(0).prop("checked", "checked");
                    jQuery(".niceselect." + class_name + " > p.top").text(jQuery(".niceselect." + class_name + " input[type=radio]").eq(0).data('text'));
                    jQuery(".niceselect." + class_name + " .value_wrapper .values").removeClass("active");
                    jQuery("#" + class_name).trigger("change");
                }
                else if (jQuery(this).parent().hasClassRegEx(/^status-/) || jQuery(this).parent().hasClassRegEx(/^frontage-/) || jQuery(this).parent().hasClassRegEx(/^view-/)) {
                    var class_name = jQuery(this).parent('span.btn').attr('class').replace('btn btn-info search-filter-tag ', '').replace('-tag', '');
                    var keyval = class_name.split('-');
                    keyval[1] = keyval[1].replace("_v_", " ");
                    jQuery("#frmSearch input[name='" + keyval[0] + "[]'][value='" + keyval[1] + "']").prop("checked", false);
                    jQuery("#frmSearch input[name='" + keyval[0] + "[]'][value='" + keyval[1] + "']").trigger("change");
                }
                else if (jQuery(this).parent().hasClass("tenure-tag")) {
                    jQuery("#frmSearch input[name='tenure']").filter("[value='All']").prop("checked", true).change();
                    //jQuery("#frmSearch input[name='tenure']").trigger("change");
                }
                else if (jQuery(this).parent().hasClass("acreage-tag") || jQuery(this).parent().hasClass("squarefootage-tag")) {
                    var class_name = jQuery(this).parent('span.btn').attr('class').replace('btn btn-info search-filter-tag ', '').replace('-tag', '');
                    var minid = 'minsq';
                    var maxid = 'maxsq';
                    if (class_name == 'acreage') {
                        minid = 'minacr';
                        maxid = 'maxacr';
                    }

                    jQuery("#" + minid).val('');
                    jQuery("#" + maxid).val('');
                    jQuery("#" + maxid).trigger("change");
                }
                else if (jQuery(this).parent().hasClass("beds-tag") || jQuery(this).parent().hasClass("baths-tag") || jQuery(this).parent().hasClass("community-tag")) {
                    var class_name = jQuery(this).parent('span.btn').attr('class').replace('btn btn-info search-filter-tag ', '').replace('-tag', '');
                    jQuery("#" + class_name).val("");
                    jQuery("#" + class_name).trigger("change");
                }
                else if (jQuery(this).parent().hasClass("price-range-tag")) {
                    var prices = jQuery(".range-slider").jRange("getOptions");
                    var newPrices = prices.from + "," + prices.to;
                    jQuery(".range-slider").jRange("setValue", newPrices);
                    jQuery("#maxprice").trigger("change");
                    jQuery(".price-range-tag").hide();
                }else if (jQuery(this).parent().hasClass("beds-range-tag")) {
                    
                    var newBeds = jQuery(".range-slider-beds").attr('data-from')+ "," + jQuery(".range-slider-beds").attr('data-to');
                    jQuery(".range-slider-beds").jRange("setValue", newBeds);
                    jQuery("#maxbeds").trigger("change");
                    jQuery(".beds-range-tag").hide();
                }else if (jQuery(this).parent().hasClass("baths-range-tag")) {
                    
                    var newbaths = jQuery(".range-slider-baths").attr('data-from')+ "," + jQuery(".range-slider-baths").attr('data-to');
                    jQuery(".range-slider-baths").jRange("setValue", newbaths);
                    jQuery("#maxbaths").trigger("change");
                    jQuery(".baths-range-tag").hide();
                }
                else {
                    var class_name = jQuery(this).parent('span.btn').attr('class').replace('btn btn-info search-filter-tag ', '').replace('-tag', '');

                    //console.log('WTF:'+class_name);

                    //if(class_name=='projectname' || class_name=='subdivision' || class_name=='streetname' || class_name=='city' || class_name=='postalcode')
                    //{
                    jQuery("#" + class_name).val("");
                    jQuery("#" + class_name).trigger("change");
                    //}
                }

                if (jQuery(".filters-tags span:visible").length == 0) {
                    jQuery(".filters-tags").hide();
                    jQuery(".clear-search-action").removeClass('active');
                }
            });
        },
        initMapScrollBehavior: function () {
            var distance = jQuery('.map-wrap').offset().top,
                width = jQuery('.map-wrap').width(),
                $window = jQuery(window);

            $window.scroll(function () {
                if ($window.scrollTop() >= distance && !app.common.isMobile()) {
                    // div has reached the top
                    var left = jQuery(".total-results-info").offset().left;
                    jQuery(".map-wrap").css({ "position": "fixed", "top": "0px", "left": left, "width": "47.2%" });
                    jQuery(".map-wrap > div").css("height", window.innerHeight);
                }
                else {
                    jQuery(".map-wrap").css({ "position": "relative", "top": "0px", "left": "0px", "width": "100%" });
                }
            });
        },
        getSearchParams: function (asQueryString) {

            var keywords = jQuery("#keywords").val();
            var district = jQuery("#district").val();
            var propertyType = jQuery("#property-type").val();
            var beds = jQuery("#beds").val();
            var baths = jQuery("#baths").val();
            var minPrice = jQuery("#minprice").val();
            var maxPrice = jQuery("#maxprice").val();
            var minBeds = jQuery("#minbeds").val();
            var maxBeds = jQuery("#maxbeds").val();
            var minBaths = jQuery("#minbaths").val();
            var maxBaths = jQuery("#maxbaths").val();
            var orderby = jQuery("#orderby").val();
            var community = jQuery("#community").val();
            var parking = jQuery("#parking").val();
            var hoa_dues_max = jQuery("#hoa_dues_max").val();
            var minsq = jQuery("#minsq").val();
            var maxsq = jQuery("#maxsq").val();
            var minacr = jQuery("#minacr").val();
            var maxacr = jQuery("#maxacr").val();
            var daysm = jQuery("#daysm").val();
            var changed = jQuery("#changed").val();
            var tenure = jQuery("#frmSearch input[name='tenure']:checked:enabled").val();
            var pool = jQuery("#frmSearch input[name='pool']:checked:enabled").val();
            var featured = jQuery("#frmSearch input[name='featured']:checked:enabled").val();
            var favorites = jQuery("#frmSearch input[name='favorites']:checked:enabled").val();

            var projectname = jQuery("#projectname").val();
            var postalcode = jQuery("#postalcode").val();
            var city = jQuery("#city").val();
            var streetname = jQuery("#streetname").val();
            var subdivision = jQuery("#subdivision").val();

            var status = "";
            jQuery("input[name='status[]']:checked:enabled").each(function () {
                if (status == "")
                    status = jQuery(this).val();
                else
                    status = jQuery(this).val() + "," + status;
            });
            var frontage = "";
            jQuery("input[name='frontage[]']:checked:enabled").each(function () {
                if (frontage == "")
                    frontage = jQuery(this).val();
                else
                    frontage = jQuery(this).val() + "," + frontage;
            });
            var view = "";
            jQuery("input[name='view[]']:checked:enabled").each(function () {
                if (view == "")
                    view = jQuery(this).val();
                else
                    view = jQuery(this).val() + "," + view;
            });
            var params = {
                keywords: keywords || "",
                proptype: propertyType || "",
                beds: beds || "",
                baths: baths || "",
                district: district || "",
                minprice: minPrice || "",
                maxprice: maxPrice || "",
                minbeds: minBeds || "",
                maxbeds: maxBeds || "",
                minbaths: minBaths || "",
                maxbaths: maxBaths || "",
                orderby: orderby || "",
                community: community || "",
                status: status || "",
                pool: pool || "",
                tenure: tenure || "",
                daysm: daysm || "",
                changed: changed || "",
                featured: featured || "",
                favorites: favorites || "",
                maxacr: maxacr || "",
                parking: parking || "",
                hoa_dues_max: hoa_dues_max || "",
                maxsq: maxsq || "",
                minsq: minsq || "",
                frontage: frontage || "",
                view: view || "",
                projectname: projectname || "",
                postalcode: postalcode || "",
                city: city || "",
                streetname: streetname || "",
                subdivision: subdivision || "",
            }

            if (app.advanceSearch.vars.mapData != "") {
                var mapDataArr = app.advanceSearch.vars.mapData.split('&');
                for (i = 0; i < mapDataArr.length; i++) {
                    var temp = mapDataArr[i].split('=');
                    params[temp[0]] = temp[1];
                }
            }

            var qs = "keywords=" + (keywords || "") + "&community=" + (community || "") + "&proptype=" + (propertyType || "") + "&beds=" + (beds || "") + "&baths=" + (baths || "") + "&district=" + (district || "") + "&minprice=" + (minPrice || "") + "&maxprice=" + (maxPrice || "") + "&status=" + (status || "") + "&pool=" + (pool || "") + "&tenure=" + (tenure || "") + "&daysm=" + (daysm || "") + "&changed=" + (changed || "") + "&featured=" + (featured || "") + "&favorites=" + (favorites || "") + "&maxacr=" + (maxacr || "") + "&minacr=" + (minacr || "") + "&maxsq=" + (maxsq || "") + "&minsq=" + (minsq || "") + "&orderby=" + (orderby || "") + "&frontage=" + (frontage || "") + "&projectname=" + (projectname || "") + "&postalcode=" + (postalcode || "") + "&city=" + (city || "") + "&streetname=" + (streetname || "") + "&subdivision=" + (subdivision || "") + "&" + app.advanceSearch.vars.mapData;

            if (asQueryString)
                return qs;
            console.log(params);
            return params;
        },
        registerSearchFormEvents: function () {
            jQuery("#orderby, #keywords, #district, #property-type, #minbeds, #minbaths, #maxbeds, #maxbaths, #baths, #maxprice,#community,#daysm,#changed,#parking,#hoa_dues_max,#minsq,#maxsq,#minacr,#maxacr,#frmSearch input[name='status[]'],#frmSearch input[name='view[]'],#frmSearch input[name='frontage[]'],#frmSearch input[name='pool'],#frmSearch input[name='tenure'],#frmSearch input[name='featured'],#frmSearch input[name='favorites'],#projectname,#postalcode,#city,#streetname,#subdivision").change(function (e) {
                console.log('registerSearchFormEvents:', e);
                if (app.advanceSearch.vars.filterbyajax) {
                    if (app.common.isMobile()) {
                        jQuery(".advance-search-menu-overlay").trigger("click");
                    }
                    app.advanceSearch.updateSearchFilterTags(jQuery(this));
                    app.advanceSearch.getSearchResultsAjax();
                }
            });
        },
        updateSearchFilterTags: function (o) {
           
            jQuery(".clear-search-action").addClass('active');

            if (o.attr("name") == 'status[]' || o.attr("name") == 'frontage[]' || o.attr("name") == 'view[]') {
                o.each(function () {

                    var class_name = jQuery(this).attr("name").replace('[]', '') + "-" + jQuery(this).val().replace(" ", "_v_");
                    if (jQuery(this).prop("checked")) {
                        jQuery(".filters-tags").show();
                        if (jQuery("." + class_name + "-tag").length) {
                            jQuery("." + class_name + "-tag .search-filter-tag-text").text(jQuery(this).val());
                            jQuery("." + class_name + "-tag").show();
                        }
                        else {

                            var matches = ['{{class_name}}', '{{value}}'];
                            var matches_value = [class_name, jQuery(this).val()];
                            var tag_html = app.common.replaceValueInStr(ffdl_vars.filter_tag_html, matches, matches_value);
                            
                            jQuery(".filters-tags").append(tag_html);
                        }
                    }
                    else {
                        jQuery("." + class_name + "-tag").hide();
                    }
                });
            }
            else if (o.attr("name") == 'pool' || o.attr("name") == 'featured' || o.attr("name") == 'favorites') {
                var class_name = o.attr("name").replace(" ", "_v_");
                var class_value = o.parent("label").children("span").html();

                if (o.prop("checked")) {
                    jQuery(".filters-tags").show();
                    if (jQuery("." + class_name + "-tag").length) {
                        jQuery("." + class_name + "-tag .search-filter-tag-text").text(class_value);
                        jQuery("." + class_name + "-tag").show();
                    }
                    else {

                        var matches = ['{{class_name}}', '{{value}}'];
                        var matches_value = [class_name, class_value];
                        var tag_html = app.common.replaceValueInStr(ffdl_vars.filter_tag_html, matches, matches_value);
                        
                        jQuery(".filters-tags").append(tag_html);
                    }
                }
                else {
                    jQuery("." + class_name + "-tag").hide();
                }
            }
            else if (o.attr("name") == 'tenure') {
                //jQuery(tag).filter('[value="All"]').prop("checked", true);
                var class_name = o.attr("name").replace(" ", "_v_");
                var class_value = o.parent("label").children("span").html();

                if (o.val() != 'All') {//alert(o.val()); 
                    jQuery(".filters-tags").show();
                    if (jQuery("." + class_name + "-tag").length) {
                        jQuery("." + class_name + "-tag .search-filter-tag-text").text(class_value);
                        jQuery("." + class_name + "-tag").show();
                    }
                    else {

                        var matches = ['{{class_name}}', '{{value}}'];
                        var matches_value = [class_name, class_value];
                        var tag_html = app.common.replaceValueInStr(ffdl_vars.filter_tag_html, matches, matches_value);
                        
                        jQuery(".filters-tags").append(tag_html);
                    }
                }
                else {//alert(o.val()+'wtf'); 
                    jQuery("." + class_name + "-tag").hide();
                }

            }
            else if (o.attr("id") == "keywords") {
                if (o.val() == "") {
                    jQuery(".keyword-tag").hide();
                }
                else {
                    jQuery(".filters-tags").show();
                    jQuery(".keyword-tag .search-filter-tag-text").text(o.val());
                    jQuery(".keyword-tag").show();
                }
            }
            else if (o.attr("id") == "minsq" || o.attr("id") == "maxsq") {
                var min = jQuery("#minsq").val();
                var max = jQuery("#maxsq").val();
                var class_name = 'squarefootage';

                if (min <= 0 && max <= 0) {
                    jQuery("." + class_name + "-tag").hide();
                }
                else {
                    var sval = 'SF: ';
                    if (min > 0 && max > 0)
                        sval += min + ' to ' + max;
                    else if (min > 0)
                        sval += 'From ' + min;
                    else if (max > 0)
                        sval += 'Upto ' + max;

                    jQuery(".filters-tags").show();
                    if (jQuery("." + class_name + "-tag").length) {
                        jQuery("." + class_name + "-tag .search-filter-tag-text").text(sval);
                        jQuery("." + class_name + "-tag").show();
                    }
                    else {

                        var matches = ['{{class_name}}', '{{value}}'];
                        var matches_value = [class_name, sval];
                        var tag_html = app.common.replaceValueInStr(ffdl_vars.filter_tag_html, matches, matches_value);
                        
                        jQuery(".filters-tags").append(tag_html);
                    }
                }
            }
            else if (o.attr("id") == "minacr" || o.attr("id") == "maxacr") {
                var min = jQuery("#minacr").val();
                var max = jQuery("#maxacr").val();
                var class_name = 'acreage';

                if (min <= 0 && max <= 0) {
                    jQuery("." + class_name + "-tag").hide();
                }
                else {
                    var sval = 'Acreage: ';
                    if (min > 0 && max > 0)
                        sval += min + ' to ' + max;
                    else if (min > 0)
                        sval += 'From ' + min;
                    else if (max > 0)
                        sval += 'Upto ' + max;

                    jQuery(".filters-tags").show();
                    if (jQuery("." + class_name + "-tag").length) {
                        jQuery("." + class_name + "-tag .search-filter-tag-text").text(sval);
                        jQuery("." + class_name + "-tag").show();
                    }
                    else {

                        var matches = ['{{class_name}}', '{{value}}'];
                        var matches_value = [class_name, sval];
                        var tag_html = app.common.replaceValueInStr(ffdl_vars.filter_tag_html, matches, matches_value);
                        
                        jQuery(".filters-tags").append(tag_html);
                    }
                }
            }
            else if (o.attr("id") == "district" || o.attr("id") == "property-type" || o.attr("id") == "changed" || o.attr("id") == "daysm") {

                if (o.val() == "" || !o.val()) {
                    jQuery("." + o.attr("id") + "-tag").hide();
                }
                else {
                    jQuery(".filters-tags").show();
                    if (jQuery("." + o.attr("id") + "-tag").length) {
                        jQuery("." + o.attr("id") + "-tag .search-filter-tag-text").text(o.val());
                        jQuery("." + o.attr("id") + "-tag").show();
                    }
                    else {

                        var matches = ['{{class_name}}', '{{value}}'];
                        var matches_value = [o.attr("id"), o.val()];
                        var tag_html = app.common.replaceValueInStr(ffdl_vars.filter_tag_html, matches, matches_value);
                        
                        jQuery(".filters-tags").append(tag_html);

                    }

                }
            }
            else if (o.attr("id") == "orderby") {
                //alert(o.val() )
                if (o.val() == "" || o.val() == "listed_date__c:desc" || !o.val()) {
                    jQuery("." + o.attr("id") + "-tag").hide();
                }
                else {
                    jQuery(".filters-tags").show();

                    var selval = jQuery('#orderby option:selected').text();

                    if (jQuery("." + o.attr("id") + "-tag").length) {
                        jQuery("." + o.attr("id") + "-tag .search-filter-tag-text").text(selval);
                        jQuery("." + o.attr("id") + "-tag").show();
                    }
                    else {

                    var matches = ['{{class_name}}', '{{value}}'];
                    var matches_value = [o.attr("id"), selval];
                    var tag_html = app.common.replaceValueInStr(ffdl_vars.filter_tag_html, matches, matches_value);
                    
                    jQuery(".filters-tags").append(tag_html);

                    }

                }
            }
            else if (o.attr("id") == "beds" || o.attr("id") == "baths") {
                var class_name = o.attr("id");
                if (o.val() == "") {
                    jQuery("." + class_name + "-tag").hide();
                }
                else {
                    jQuery(".filters-tags").show();
                    jQuery("." + class_name + "-tag .search-filter-tag-text").text(o.val() + " " + class_name + "");
                    jQuery("." + class_name + "-tag").show();
                }
            }
            else if (o.attr("id") == "community") {
                if (o.val() == "") {
                    jQuery(".community-tag").hide();
                }
                else {
                    jQuery(".filters-tags").show();
                    jQuery(".community-tag .search-filter-tag-text").text(o.val() + " Community");
                    jQuery(".community-tag").show();
                }
            }
            else if (o.attr("id") == "maxprice" && o.val() != "") {
                jQuery(".filters-tags").show();
                var text = "$" + jQuery("#minprice").val() + " to $" + jQuery("#maxprice").val();
                jQuery(".price-range-tag .search-filter-tag-text").text(text);
                jQuery(".price-range-tag").show();
            }else if (o.attr("id") == "maxbeds" || o.attr("id") == "minbeds" ) {
              
                var minbeds = jQuery("#minbeds").val();
                var maxbeds = jQuery("#maxbeds").val();
                
                if( minbeds != '' || maxbeds != '' ){
                    if( minbeds == '') minbeds = 0;
                    if( maxbeds == '') maxbeds = jQuery("#ulBeds").val() || 15;


                    jQuery(".filters-tags").show();
                    
                  

                    if( jQuery(".beds-range-tag").hasClass('single') ){
                        var text = minbeds + ' beds';
                    }  else {
                        var text = minbeds + " to " + maxbeds + ' beds';
                    }
                    jQuery(".beds-range-tag .search-filter-tag-text").text(text);
                    jQuery(".beds-range-tag").show();
                } else {
                    if( minbeds == '' && maxbeds == '' ){
                        jQuery(".beds-range-tag").hide();
                    }
                }

            }else if (o.attr("id") == "maxbaths" || o.attr("id") == "minbaths" ) {
               
                 var minbaths = jQuery("#minbaths").val();
                 var maxbaths = jQuery("#maxbaths").val();

                if( minbaths != '' || maxbaths != '' ){


                    if( minbaths == '') minbaths = 0;
                    if( maxbaths == '') maxbaths = jQuery("#ulBaths").val() || 15;
    
    
                    jQuery(".filters-tags").show();

                    
                    if( jQuery(".baths-range-tag").hasClass('single') ){
                        var text = minbaths + ' baths';
                    }  else {
                        var text = minbaths + " to " + maxbaths + ' baths';
                    }

                    jQuery(".baths-range-tag .search-filter-tag-text").text(text);
                    jQuery(".baths-range-tag").show();
                }else {
                    if( minbaths == '' && maxbaths == '' ){
                        jQuery(".baths-range-tag").hide();
                    }
                }
 
             }
            else {
                if (o.val() == "" || o.val() == null ) {
                    jQuery("." + o.attr("id") + "-tag").hide();
                }
                else {
                    jQuery(".filters-tags").show();
                    if (jQuery("." + o.attr("id") + "-tag").length) {
                        jQuery("." + o.attr("id") + "-tag .search-filter-tag-text").text(o.val().replace("\\'", "'"));
                        jQuery("." + o.attr("id") + "-tag").show();
                    }
                    else {
                        if( typeof o.val() != 'undefined'){
                            var matches = ['{{class_name}}', '{{value}}'];
                            var matches_value = [o.attr("id"), o.val().replace("\\'", "'")];
                            var tag_html = app.common.replaceValueInStr(ffdl_vars.filter_tag_html, matches, matches_value);
                            
                            jQuery(".filters-tags").append(tag_html);

                        }
                    }
                }

            }

            if (jQuery(".filters-tags span:visible").length == 0) {
                jQuery(".filters-tags").hide();
                jQuery(".clear-search-action").removeClass('active');
            }

        },
        updateSearchResultsInfo: function (showing, total) {
            jQuery("#current-results").text(showing);
            jQuery("#total-results").text(total);
        },
        getSearchResultsAjax: function () {
            
            isLoading = true;
            var params = app.advanceSearch.getSearchParams();
            params.action = "ffdl/listings/search";
            app.common.showFullScreenLoader();
            jQuery.get(ffdl_vars.ajaxurl, params, function (response) {
                app.advanceSearch.closeAllInfoWindows();
                app.advanceSearch.vars.mapData = "";
                //console.log(response);
                
                if (response.data.html == '') {
                    app.advanceSearch.updateSearchResultsInfo('0', '0');
                    var clear_button_html = '<ul class="clear-search-action-container" style="width: 110px;display: inline-block;margin-left: 10px;"><li><label class="clear-search-action active" style = "color: #fff;background: #14b5ea;" title="Clear Your Search" > Clear <span class ="customhidden"> Search </span></label></li> </ul>';
                    jQuery(".properties-container").html('<div class="col-md-12 topPad bottomPad"><div class="alert alert-info fade in alert-dismissable"><a href="#" class="close" data-dismiss="alert" aria-label="close" title="close">×</a><strong>We\'re sorry.</strong>  We cannot find any matches for your search. ' + clear_button_html + '</div></div>');
                }
                else {
                    jQuery(".properties-container").html(response.data.html);
                }
                jQuery("#current-results").text(response.data.showing);
                app.advanceSearch.updateSearchResultsInfo(response.data.showing, response.data.total_listings);

                var nextPage = response.data.currentpage + 1;
                jQuery("#currentpage").val(response.data.currentpage);
                jQuery("#nextpage").val(response.data.has_more ? nextPage : response.data.currentpage);
                jQuery("#has_more").val(response.data.has_more);

                // This is to reset infinite scroller next page callbacks.
                jQuery("#nextpage").val(2);

               
                app.advanceSearch.initPropertyResultsSlider(".properties-container .property-item .swiper-container", "initialized");
                
                console.log('null != app.advanceSearch.vars.search_map', app.advanceSearch.vars.search_map);

                if (null != app.advanceSearch.vars.search_map) {
                    //fix zoom change event firing
                    app.advanceSearch.init_property_markers(function(){
                        setTimeout(function(){
                            isLoading = false;
                            app.common.hideFullScreenLoader();
                        }, 3000);
                    });
                    //jQuery(".property-item").mouseover(function () {app.advanceSearch.search_result_hovered(jQuery(this)) });
                } else {
                    isLoading = false;
                    app.common.hideFullScreenLoader();
                }
                
            });
        },
        switchPropertyDisplay: function (displayType) {
            if (displayType == "grid") {
                jQuery(".properties-container").removeClass("list-layout");
                jQuery(".property-display-type-icons-container li").removeClass("active");
                jQuery(".property-display-type-icons-container li.listing-type-grid").addClass("active");
            }
            else if (displayType == "list") {
                jQuery(".properties-container").addClass("list-layout");
                jQuery(".property-display-type-icons-container li").removeClass("active");
                jQuery(".property-display-type-icons-container li.listing-type-list").addClass("active");
            }
            app.advanceSearch.updatePropertiesSlider();
            return false;
        },
        updatePropertiesSlider: function () {
            // jQuery.each(".property-figure-wrap .swiper-container", function(o){
            //     jQuery(this)[0].swiper.update();
            // });
            jQuery(".property-figure-wrap .swiper-container").each(function () {
                //console.log(jQuery(this)[0].swiper);
                jQuery(this)[0].swiper.update();
            });
        },
        initPropertyResultsSlider: function (selector, existingItemsClass) {
            jQuery(selector + ":not(." + existingItemsClass + ")").each(function (index, element) {
                var $this = jQuery(this);
                $this.addClass(existingItemsClass);
                new Swiper($this, {
                    lazy: { loadPrevNext: true, loadPrevNextAmount: 1, preloaderClass: 'loader' },
                    CSSWidthAndHeight: true,
                    slidesPerView: 'auto',
                    loop: true,
                    watchSlidesVisibility: true,
                    centeredSlides: true,
                    spaceBetween: 1,
                    visibilityFullFit: true,
                    autoResize: false,
                    navigation: {
                        nextEl: $this.find(".swiper-button-next")[0],
                        prevEl: $this.find(".swiper-button-prev")[0]
                    }
                });
            });
        },
        initPageScroll: function (selector) {
            var loading = false;
            function isLastPage() {
                var hasMore = jQuery("#has_more").val();
                return hasMore == "false" ? true : false;
            }
            function getNextPage() {
                var params = app.advanceSearch.getSearchParams(true);
                var nextPageUrl = ffdl_vars.ajaxurl + "?action=ffdl/listings/search&currentpage=" + jQuery("#nextpage").val() + "&" + params;
                jQuery.get(nextPageUrl, null, function (response) {
                    app.advanceSearch.closeAllInfoWindows();
                    //var content = jQuery.parseJSON(response);
                    var nextPage = response.data.currentpage + 1;

                    jQuery(selector).append(response.data.html);
                    jQuery("#current-results").text(response.data.showing);

                    jQuery("#currentpage").val(response.data.currentpage);
                    jQuery("#nextpage").val(response.data.has_more ? nextPage : response.data.currentpage);
                    jQuery("#has_more").val(response.data.has_more);

                    if (null != app.advanceSearch.vars.search_map) {
                        app.advanceSearch.init_property_markers();
                        //jQuery(".property-item").mouseover(function () {app.advanceSearch.search_result_hovered(jQuery(this)) });
                    }
                    hideLoader();
                    app.advanceSearch.initPropertyResultsSlider(".properties-container .property-item .swiper-container", "initialized");
                });
            }
            function showLoader() {
                loading = true;
                jQuery(".property-item-wrap .page-load-status").show();
            }
            function hideLoader() {
                jQuery(".property-item-wrap .page-load-status").hide();
                loading = false;
            }
            jQuery(window).on('scroll', function () {
                if (jQuery(this).scrollTop() >= jQuery(selector).offset().top + jQuery(selector).outerHeight() - window.innerHeight) {
                    if (!loading && !isLastPage()) {
                        showLoader();
                        getNextPage();
                    }
                }
            });
        },
        initInfiniteScroller: function (selector, destroyFirst) {
            if (destroyFirst)
                jQuery(selector).infiniteScroll('destroy');
            var $container = jQuery(selector).infiniteScroll({
                path: function () {
                    var params = app.advanceSearch.getSearchParams(true);
                    return ffdl_vars.ajaxurl + "?action=ffdl/listings/search&currentpage=" + jQuery("#nextpage").val() + "&" + params;
                },
                append: false,
                status: '.scroller-status',
                responseType: 'text',
                history: false,
                historyTitle: false,
                hideNav: '.pagination',
                status: '.page-load-status',
                onInit: function () {
                    //console.log("Infinite Scroll Initialized");
                }
            });
            $container.on('load.infiniteScroll', function (event, response, path) {
                var content = jQuery.parseJSON(response);
                if (content.data.has_more == false)
                    return false;
                var nextPage = content.data.currentpage + 1;
                jQuery("#currentpage").val(content.data.currentpage);
                jQuery("#nextpage").val(content.data.has_more ? nextPage : content.data.currentpage);
                jQuery("#has_more").val(content.data.has_more);
                $container.infiniteScroll('appendItems', jQuery(content.data.html));
                //console.log(app.advanceSearch.vars.search_map);
                if (null != app.advanceSearch.vars.search_map) {
                    app.advanceSearch.init_property_markers();
                    //jQuery(".property-item").mouseover(function () {app.advanceSearch.search_result_hovered(jQuery(this)) });
                }
                app.advanceSearch.initPropertyResultsSlider(".properties-container .property-item .swiper-container", "initialized");
                return false;
            });
        },

        initMap: function ($map_obj, map_args) {
            var map;

            map_args = map_args || {};
            map_args = jQuery.extend(true, {
                center: { lat: 37.7749, lng: -122.4194 },
                scrollwheel: false,
                mapTypeControl: true,
                zoom: 12,
                maxZoom: 16
            }, map_args);

            var map = new google.maps.Map($map_obj.get(0), map_args);
            return map;
        },

        

        on_zoom_change: function (event) {

            if (isLoading || !app.advanceSearch.vars.searchMapInit || app.advanceSearch.vars.areaChanged || jQuery(".full-page-overlay").is(":visible") || jQuery(".page-load-status").is(":visible")) return;

            console.log('map zoom_changed:', isLoading , !app.advanceSearch.vars.searchMapInit , app.advanceSearch.vars.areaChanged , jQuery(".full-page-overlay").is(":visible") , jQuery(".page-load-status").is(":visible") );

            app.advanceSearch.clearPoly();
            app.advanceSearch.vars.areaChanged = true;

            var bounds = app.advanceSearch.vars.search_map.getBounds();
            var ne = bounds.getNorthEast();
            var sw = bounds.getSouthWest();

            app.advanceSearch.vars.mapData = "maxlat=" + ne.lat() + "&minlat=" + sw.lat() + "&minlong=" + sw.lng() + "&maxlong=" + ne.lng();

            
            //update_search_results();


            app.advanceSearch.getSearchResultsAjax();
        },

        initMapChangedEvents: function () {

            app.advanceSearch.vars.search_map.addListener('idle', function (event) {
                if (!app.advanceSearch.vars.searchMapInit)
                    app.advanceSearch.vars.searchMapInit = true;
            });

            app.advanceSearch.vars.search_map.addListener('zoom_changed',  app.advanceSearch.on_zoom_change);

            app.advanceSearch.vars.search_map.addListener('dragend', function () {
                console.log("dragend", "areaChanged: " + app.advanceSearch.vars.areaChanged + ", shapes: " + app.advanceSearch.vars.shapes);
                if (app.advanceSearch.vars.areaChanged || app.advanceSearch.vars.shapes.length > 0 || jQuery(".page-load-status").is(":visible") || !jQuery("#search-move").is(":checked")) return;

                app.advanceSearch.vars.areaChanged = true;

                var bounds = app.advanceSearch.vars.search_map.getBounds();
                var ne = bounds.getNorthEast();
                var sw = bounds.getSouthWest();

                app.advanceSearch.vars.mapData = "maxlat=" + ne.lat() + "&minlat=" + sw.lat() + "&minlong=" + sw.lng() + "&maxlong=" + ne.lng();

                console.log(app.advanceSearch.vars.mapData);

                console.log('map dragend:', app.advanceSearch.vars.mapData);
                app.advanceSearch.getSearchResultsAjax();
            });

            app.advanceSearch.vars.drawingManager = new google.maps.drawing.DrawingManager({
                drawingMode: null,
                drawingControl: true,
                drawingControlOptions: {
                    position: google.maps.ControlPosition.TOP_CENTER,
                    drawingModes: [
                        google.maps.drawing.OverlayType.POLYGON
                    ]
                }
            });
            app.advanceSearch.vars.drawingManager.setMap(app.advanceSearch.vars.search_map);

            google.maps.event.addListener(app.advanceSearch.vars.drawingManager, 'overlaycomplete', function (event) {
                var newShape = event.overlay;
                newShape.type = event.type;
                app.advanceSearch.vars.shapes.push(newShape);

                app.advanceSearch.vars.areaChanged = true;

                var len = newShape.getPath().getLength();
                var poly = [];

                for (var i = 0; i < len; i++)
                    poly.push(newShape.getPath().getAt(i).toUrlValue(5).replace(",", " "));

                var polydata = poly.join() + "," + poly[0];

                console.log(polydata);

                app.advanceSearch.vars.mapData = "poly=" + polydata;

                app.advanceSearch.vars.search_map.fitBounds(event.overlay.getBounds());
                //var pcoords = getPolyCoords(event);

                //mapData="minlat=" + pcoords.MinLat + "&maxlat=" + pcoords.MaxLat + "&minlong=" + pcoords.MinLong + "&maxlong=" + pcoords.MaxLong;

                //update_search_results(event);
                app.advanceSearch.getSearchResultsAjax();

                if (app.advanceSearch.vars.drawingManager.getDrawingMode()) {
                    app.advanceSearch.vars.drawingManager.setDrawingMode(null);
                }
            });
            google.maps.event.addListener(app.advanceSearch.vars.drawingManager, "drawingmode_changed", function () {
                if (app.advanceSearch.vars.drawingManager.getDrawingMode() != null) {
                    app.advanceSearch.clearPoly();
                }
            });

            if (!google.maps.Polygon.prototype.getBounds) {

                google.maps.Polygon.prototype.getBounds = function () {
                    var bounds = new google.maps.LatLngBounds();
                    this.getPath().forEach(function (element, index) { bounds.extend(element) });
                    return bounds;
                }

            }
        },


        clearPoly: function () {
            for (var i = 0; i < app.advanceSearch.vars.shapes.length; i++) {
                app.advanceSearch.vars.shapes[i].setMap(null);
            }
            app.advanceSearch.vars.shapes = [];
        },

        init_property_markers: function (markersCallBack) {
            var marker_ids = [],
                markers_to_delete,
                bounds = new google.maps.LatLngBounds();

            if (null === this.vars.search_map) {
                if( typeof markersCallBack == 'function')
                    markersCallBack();
                return;
            }

            if (app.advanceSearch.vars.areaChanged)
                app.advanceSearch.vars.areaChanged = false;

            jQuery('.properties-container .property-item').each(function () {
                var $th = jQuery(this).find(".property-figure-wrap"),
                    id = $th.attr('data-id'),
                    lat = $th.attr('data-lat'),
                    lng = $th.attr('data-lng'),
                    adr = $th.attr('data-address'),
                    title = $th.attr('data-title'),
                    mls = $th.attr('data-mls'),
                    dis = $th.attr('data-district'),
                    price = $th.attr('data-price'),
                    img = $th.attr('data-thumbnail'),
                url = $th.attr('data-url');

                app.advanceSearch.vars.infowindows[id] = new google.maps.InfoWindow({
                    content: '<div class="marker-popup"><div class="container clearfix">' +
                        '<a href="' + url + '">' +
                        '<div class="marker-thumbnail" style="background-image: url(' + img + ')"> </div></a>' +
                        '<div class="info">' +
                        '<p class="title" title="' + title + '"><a href="' + url + '">' + title + '</a></p>' +
                        '<p class="price">$' + price + '</p>' +
                        '<p class="mls">MLS# ' + mls + '</p>' +
                        '</div></div>',
                    maxWidth: 200,
                    disableAutoPan: true
                });

                /* Info Window Customization Starts */
                google.maps.event.addListener(app.advanceSearch.vars.infowindows[id], 'domready', function () {
                    // Reference to the DIV that wraps the bottom of infowindow
                    var iwOuter = jQuery('.gm-style-iw');

                    // Since this div is in a position prior to .gm-div style-iw.
                    // We use jQuery and create a iwBackground variable,
                    // and took advantage of the existing reference .gm-style-iw for the previous div with .prev().

                    var iwBackground = iwOuter.prev();
                    //iwBackground.attr('style', function (i, s) { return s + 'left: -10px !important; top: 11px !important' })

                    // Removes background shadow DIV
                    //iwBackground.children(':nth-child(2)').css({ 'background': 'none' });
                    //iwBackground.children(':nth-child(2)').css({ 'display': 'none' });

                    // Removes white background DIV
                    //iwBackground.children(':nth-child(4)').css({ 'background': 'none' });
                    //iwBackground.children(':nth-child(4)').css({ 'display': 'none' });

                    // Moves the infowindow 115px to the right.
                    //iwOuter.parent().parent().css({ left: '50px' });

                    // Moves the shadow of the arrow 76px to the left margin.
                    //iwBackground.children(':nth-child(1)').attr('style', function (i, s) { return s + 'left: 76px !important;border:none;' });

                    // Moves the arrow 76px to the left margin.
                    //iwBackground.children(':nth-child(3)').attr('style', function (i, s) { return s + 'left: 76px !important;' });

                    // Changes the desired tail shadow color.
                    //iwBackground.children(':nth-child(3)').find('div').children().css({'box-shadow': 'rgba(72, 181, 233, 0.6) 0px 1px 6px', 'z-index' : '1'});
                    //iwBackground.children(':nth-child(3)').find('div').children().css({ 'z-index': '1' });

                    //iwBackground.children(':nth-child(3)').children(':nth-child(1)').find('div').attr('style', function (i, s) { return s + "transform: skewX(45deg) !important;" });
                    //iwBackground.children(':nth-child(3)').children(':nth-child(2)').find('div').attr('style', function (i, s) { return s + "transform: skewX(-45deg) !important;" });

                    // Reference to the div that groups the close button elements.
                    var iwCloseBtn = iwOuter.next();

                    // Apply the desired effect to the close button
                    //iwCloseBtn.css({ right: '87px', top: '30px' });

                    // If the content of infowindow not exceed the set maximum height, then the gradient is removed.
                    // if(jQuery('.iw-content').height() < 140){
                    //     jQuery('.iw-bottom-gradient').css({display: 'none'});
                    // }

                    // The API automatically applies 0.7 opacity to the button after the mouseout event. This function reverses this event to the desired value.
                    iwCloseBtn.mouseout(function () {
                        jQuery(this).css({ opacity: '1' });
                    });
                });
                /* Info Window Customization Ends */

                //var $correspondig_item = $th.find('.item');
                var $correspondig_item = jQuery(this);

                if (undefined !== lat && undefined !== lng) {
                    lat = parseFloat(lat);
                    lng = parseFloat(lng);

                    if (!isNaN(lat) && !isNaN(lng)) {
                        if (undefined === app.advanceSearch.vars.property_markers[id]) {
                            var p = app.common.nFormatter(price.replace(/,/g, ""), 1);

                            app.advanceSearch.vars.property_markers[id] = new MarkerWithLabel({ //google.maps.Marker({
                                map: app.advanceSearch.vars.search_map,
                                position: {
                                    lat: lat,
                                    lng: lng
                                },
                                url: $th.attr('data-url'),
                                icon: " ",// hbg.themeurl + '/img/mapicon.png',
                                labelContent: "<div class='markerLabelInside'>$" + p + "</div>",
                                //labelAnchor: new google.maps.Point(off,10),
                                labelInBackground: false,
                                labelClass: "markerLabelOutside"
                            });

                            //google.maps.event.addListener( property_markers[ id ], 'click', load_marker_url );
                            google.maps.event.addListener(app.advanceSearch.vars.property_markers[id], 'click', function () {
                                app.advanceSearch.closeAllInfoWindows();

                                if (app.advanceSearch.vars.last_info_window) {
                                    app.advanceSearch.vars.last_info_window.close();
                                }
                                jQuery('.marker-hovered').removeClass('marker-hovered');
                                app.advanceSearch.vars.last_info_window = app.advanceSearch.vars.infowindows[id];
                                app.advanceSearch.vars.infowindows[id].open(app.advanceSearch.vars.search_map, app.advanceSearch.vars.property_markers[id]);
                                $correspondig_item.addClass('marker-hovered');
                                //jQuery(".scroll-pane").scrollTo( $correspondig_item, 300);

                            });

                            google.maps.event.addListener(app.advanceSearch.vars.property_markers[id], 'mouseover', function () {
                                if (false == app.advanceSearch.vars.last_info_window || (app.advanceSearch.vars.last_info_window && !app.advanceSearch.vars.last_info_window.getMap())) {
                                    jQuery('.marker-hovered').removeClass('marker-hovered');
                                    $correspondig_item.addClass('marker-hovered');
                                }
                            });

                            google.maps.event.addListener(app.advanceSearch.vars.property_markers[id], 'mouseout', function () {
                                if (app.advanceSearch.vars.last_info_window && !app.advanceSearch.vars.last_info_window.getMap()) {
                                    jQuery('.marker-hovered').removeClass('marker-hovered');
                                }
                            });
                        }
                        marker_ids.push(id);
                        bounds.extend(app.advanceSearch.vars.property_markers[id].getPosition());
                    }
                }
            });

            markers_to_delete = _.difference(Object.keys(app.advanceSearch.vars.property_markers), marker_ids);
            for (var i = 0; i < markers_to_delete.length; i++) {
                app.advanceSearch.vars.property_markers[markers_to_delete[i]].setMap(null);
                delete app.advanceSearch.vars.property_markers[markers_to_delete[i]];
            }
            app.advanceSearch.vars.bounds = bounds;
            if (app.advanceSearch.vars.mapData == "" && marker_ids.length && !jQuery('.properties-container').hasClass('map-hidden')) {

                if (!jQuery('#search-move').prop('checked')) {
                    app.advanceSearch.vars.search_map.setCenter(bounds.getCenter());
                    app.advanceSearch.vars.search_map.fitBounds(bounds);
                }
            }

            

            if( typeof markersCallBack == 'function')
                markersCallBack();
        },

        search_result_hovered: function (t) {
            var id = t.find(".property-figure-wrap").attr('data-id');
            var lat = t.find(".property-figure-wrap").attr('data-lat');
            var lng = t.find(".property-figure-wrap").attr('data-lng');

            // console.log("t: ", t);
            // console.log("lat: " + lat + " lng: " + lng + " id: " + id);
            // console.log("search_map", app.advanceSearch.vars.search_map);
            // console.log("infowindows: ", app.advanceSearch.vars.infowindows);
            // console.log("property_markers: ", app.advanceSearch.vars.property_markers);

            if (jQuery(".properties-container").hasClass('busy')) {
                return;
            }
            jQuery('.properties-container .property-item').removeClass('marker-hovered');
            //jQuery( this ).find( '.item' ).addClass('marker-hovered');
            t.addClass('marker-hovered');

            if (null == app.advanceSearch.vars.search_map) {
                return;
            }
            if (jQuery('.properties-container').hasClass('map-hidden')) {
                return;
            }

            if (app.advanceSearch.vars.last_hovered_item_id == id) {
                return;
            }

            app.advanceSearch.vars.last_hovered_item_id = t.find(".property-figure-wrap").attr('data-id');


            app.advanceSearch.closeAllInfoWindows();

            if (lat == "" || lng == "")
                return;


            app.advanceSearch.vars.search_map.panTo(new google.maps.LatLng(lat, lng));
            app.advanceSearch.vars.infowindows[id].open(app.advanceSearch.vars.search_map, app.advanceSearch.vars.property_markers[id]);
        },

        closeAllInfoWindows: function () {
            console.log(app.advanceSearch.vars.infowindows);
            jQuery.each(app.advanceSearch.vars.infowindows, function (index, iw) {
                iw.close();
            });
        }
    },
    advanceSearchOtherPage: {
        initScripts: function () {
            app.advanceSearch.registerNiceSelect();
            app.advanceSearch.registerMoreOptionsDropdown();

            jQuery('.saved-searches-container').remove();
            jQuery('.keywords-field-container').removeClass('col-md-2 col-sm-3');
            jQuery('.keywords-field-container').addClass('col-md-3 col-sm-6');

            jQuery('#frmSearch input').change(function () {
                // jQuery(this).change(function(){
                //alert(jQuery(this).attr('name')+':'+jQuery(this).attr('id')); return;
                if (jQuery(this).hasClass('range-slider')) {
                }else {
                    var url = ffdl_vars.search_page_url + '?advance=1';

                    if (jQuery(this).attr('name') == 'status[]') {
                        jQuery('#frmSearch input[name="status[]"]').each(function () {
                            if (jQuery(this).prop('checked'))
                                url += '&' + jQuery(this).attr('name') + '=' + jQuery(this).val();
                        });
                    }
                    else if (jQuery(this).val() != '') {
                        jQuery('#frmSearch input[name="status[]"]').each(function () {
                            if (jQuery(this).prop('checked'))
                                url += '&' + jQuery(this).attr('name') + '=' + jQuery(this).val();
                        });
                        url += '&' + jQuery(this).attr('name') + '=' + jQuery(this).val();

                        if (jQuery(this).attr('name') == 'maxprice')
                            url += '&minprice=' + jQuery('#frmSearch input[name="minprice"]').val();
                    }
                    app.common.showFullScreenLoader();
                    window.location.href = url;
                }
                //});
            });

        },
    },
    communitySingle:{
         vars: {
             community_map: false
         },
         init: function () {
              community_map = app.advanceSearch.initMap(jQuery('#community_map'));
         }
    },
    communityDetails: {
        vars: {
            community_map: false
        },
        init: function () {

            var last_info_window = false;
            if (jQuery('.map-locations-wrapper').length) {
                var $location_links = jQuery('.community-listings'),
                    $location_listings_wrap = jQuery('#comm_prop_listing'),
                    community_map = app.advanceSearch.initMap(jQuery('#community_map')),
                    kml_layers = {};
                var comminfowindows = {};
                var commMarkers = {};
                var countCircle = null;
                var community_id = $location_listings_wrap.attr('data-community_id');

                //maybe_show_community_results_count($current_active_link);
                  init_Community_markers(community_id);
                       
                //Start Community Markers
                var last_hovered_community_id;

                function community_hovered() {
                    if (jQuery(".results-content").hasClass('busy')) {
                        return;
                    }
                    jQuery('#searchresults .item').removeClass('marker-hovered');
                    jQuery(this).find('.item').addClass('marker-hovered');

                    if (null == community_map) {
                        return;
                    }
                    if (jQuery('.map-container').hasClass('map-hidden')) {
                        return;
                    }



                    id = jQuery(this).attr('data-id');
                    if (last_hovered_community_id == id) {
                        return;
                    }
                    last_hovered_community_id = jQuery(this).attr('data-id');
                    var lat = jQuery(this).attr('data-lat');
                    var lng = jQuery(this).attr('data-lng');

                    community_map.panTo(new google.maps.LatLng(lat, lng));
                    jQuery.each(comminfowindows, function (index, iw) {
                        iw.close();
                    });
                    comminfowindows[id].open(community_map, commMarkers[id]);
                }


                function init_Community_markers(commId) {
                    console.log(commId);
                    var marker_ids = [],
                        markers_to_delete,
                        bounds = new google.maps.LatLngBounds();

                    if (null === community_map) {
                        return;
                    }

                    jQuery("div[data-community-id='" + commId + "'] .property-item-atts").each(function () {
                        var $th = jQuery(this),
                            id = $th.attr('data-id')
                        lat = $th.attr('data-lat'),
                            lng = $th.attr('data-lng'),
                            adr = $th.attr('data-address'),
                            prc = $th.attr('data-price'),
                            lnk = $th.attr('data-url'),
                            mls = $th.attr('data-mls'),
                            dis = $th.attr('data-district'),
                            price = $th.attr('data-price'),
                            img = $th.attr('data-thumbnail');

                        comminfowindows[id] = new google.maps.InfoWindow({
                            content: '<div class="marker-popup"><div class="container clearfix">' +
                                '<div class="marker-thumbnail" style="background-image: url(' + img + ')"> </div>' +
                                '<div class="info">' +
                                '<p class="address"><a href="' + lnk + '">' + adr + '</a></p>' +
                                '<p class="price">$' + prc + '</p>' +
                                '<p class="district">' + dis + '</p>' +
                                '<p class="mls">MLS# ' + mls + '</p>' +
                                '</div></div>'
                        });

                        /* Info Window Customization Starts */
                        google.maps.event.addListener(comminfowindows[id], 'domready', function () {
                            // Reference to the DIV that wraps the bottom of infowindow
                            var iwOuter = jQuery('.gm-style-iw');

                            // Since this div is in a position prior to .gm-div style-iw.
                            // We use jQuery and create a iwBackground variable,
                            // and took advantage of the existing reference .gm-style-iw for the previous div with .prev().

                            var iwBackground = iwOuter.prev();
                            //iwBackground.attr('style', function (i, s) { return s + 'left: -10px !important; top: 11px !important' })

                            // Removes background shadow DIV
                            //iwBackground.children(':nth-child(2)').css({ 'background': 'none' });
                            //iwBackground.children(':nth-child(2)').css({ 'display': 'none' });

                            // Removes white background DIV
                            //iwBackground.children(':nth-child(4)').css({ 'background': 'none' });
                            //iwBackground.children(':nth-child(4)').css({ 'display': 'none' });

                            // Moves the infowindow 115px to the right.
                            //iwOuter.parent().parent().css({ left: '50px' });

                            // Moves the shadow of the arrow 76px to the left margin.
                            //iwBackground.children(':nth-child(1)').attr('style', function (i, s) { return s + 'left: 76px !important;border:none;' });

                            // Moves the arrow 76px to the left margin.
                            //iwBackground.children(':nth-child(3)').attr('style', function (i, s) { return s + 'left: 76px !important;' });

                            // Changes the desired tail shadow color.
                            //iwBackground.children(':nth-child(3)').find('div').children().css({'box-shadow': 'rgba(72, 181, 233, 0.6) 0px 1px 6px', 'z-index' : '1'});
                            //iwBackground.children(':nth-child(3)').find('div').children().css({ 'z-index': '1' });

                            //iwBackground.children(':nth-child(3)').children(':nth-child(1)').find('div').attr('style', function (i, s) { return s + "transform: skewX(45deg) !important;" });
                            //iwBackground.children(':nth-child(3)').children(':nth-child(2)').find('div').attr('style', function (i, s) { return s + "transform: skewX(-45deg) !important;" });

                            // Reference to the div that groups the close button elements.
                            var iwCloseBtn = iwOuter.next();

                            // Apply the desired effect to the close button
                            //iwCloseBtn.css({ right: '87px', top: '30px' });

                            // If the content of infowindow not exceed the set maximum height, then the gradient is removed.
                            // if(jQuery('.iw-content').height() < 140){
                            //     jQuery('.iw-bottom-gradient').css({display: 'none'});
                            // }

                            // The API automatically applies 0.7 opacity to the button after the mouseout event. This function reverses this event to the desired value.
                            iwCloseBtn.mouseout(function () {
                                jQuery(this).css({ opacity: '1' });
                            });
                        });
                        /* Info Window Customization Ends */

                        var $correspondig_item = $th.find('.item');

                        if (undefined !== lat && undefined !== lng) {
                            lat = parseFloat(lat);
                            lng = parseFloat(lng);

                            if (!isNaN(lat) && !isNaN(lng)) {
                                if (undefined === commMarkers[id]) {
                                    var p = app.common.nFormatter(price.replace(/,/g, ""), 1);
                                    // var p = nFormatter(price.replace(/,/g, ""), 1);
                                    // var off = 18;

                                    // if (p.length >= 5)
                                    //     off = 18;
                                    // else if (p.length == 4)
                                    //     off = 14;
                                    // else if (p.length == 3)
                                    //     off = 10;
                                    // else if (p.length == 2)
                                    //     off = 8;

                                    commMarkers[id] = new MarkerWithLabel({ //google.maps.Marker({
                                        map: community_map,
                                        position: {
                                            lat: lat,
                                            lng: lng
                                        },
                                        url: $th.attr('data-url'),
                                        icon: " ", //hbg.themeurl + '/img/mapicon.png',
                                        labelContent: "<div class='markerLabelInside'>" + p + "</div>",
                                        //labelAnchor: new google.maps.Point(off,10),
                                        labelInBackground: false,
                                        labelClass: "markerLabelOutside"
                                    });

                                    $th.addClass("community-marker-binded");

                                    //google.maps.event.addListener( property_markers[ id ], 'click', load_marker_url );
                                    google.maps.event.addListener(commMarkers[id], 'click', function () {
                                        if (last_info_window) {
                                            last_info_window.close();
                                        }
                                        jQuery('#searchresults > li .marker-hovered').removeClass('marker-hovered');
                                        last_info_window = comminfowindows[id];
                                        comminfowindows[id].open(community_map, commMarkers[id]);
                                        $correspondig_item.addClass('marker-hovered');
                                        // jQuery(".scroll-pane").scrollTo($correspondig_item, 300);
                                    });

                                    google.maps.event.addListener(commMarkers[id], 'mouseover', function () {
                                        if (false == last_info_window || (last_info_window && !last_info_window.getMap())) {
                                            jQuery('#searchresults > li .marker-hovered').removeClass('marker-hovered');
                                            $correspondig_item.addClass('marker-hovered');
                                        }
                                    });

                                    google.maps.event.addListener(commMarkers[id], 'mouseout', function () {
                                        if (last_info_window && !last_info_window.getMap()) {
                                            jQuery('.marker-hovered').removeClass('marker-hovered');
                                        }
                                    });

                                }

                                marker_ids.push(id);
                                bounds.extend(commMarkers[id].getPosition());
                            }
                        }
                    });

                    markers_to_delete = _.difference(Object.keys(commMarkers), marker_ids);
                    for (var i = 0; i < markers_to_delete.length; i++) {
                        commMarkers[markers_to_delete[i]].setMap(null);
                        delete commMarkers[markers_to_delete[i]];
                    }

                    if (marker_ids.length && !jQuery('.results-container').hasClass('map-hidden')) {
                        community_map.setCenter(bounds.getCenter());
                        community_map.fitBounds(bounds);
                    }
                }

                //End Community Markers
                jQuery('.community-marker-binded').mouseover(community_hovered);
                jQuery('.community-marker-binded').clicked(community_hovered);

                function maybe_show_community_kml_layer($curr_location) {
                    console.log("maybe_show_community_kml_layer");
                    console.log($curr_location.data());
                    console.log("KML Layers: ", kml_layers);
                    var curr_id = $curr_location.attr('data-id');

                    //Delete all previous kml layers for the other ids
                    Object.keys(kml_layers).map(function (key) {
                        console.log("key: ", key);
                        console.log("curr_id: ", curr_id);
                        if (key != curr_id) {
                            kml_layers[key].setMap(null);
                        }
                    });
                    console.log("KML Layers: ", kml_layers);
                    console.log(kml_layers[curr_id]);
                    //Create new kml layer if it do not exists for the current
                    if ($curr_location.attr('data-kml')) {
                        if (undefined == kml_layers[curr_id] || kml_layers[curr_id] == "") {
                            kml_layers[curr_id] = new google.maps.KmlLayer({
                                url: $curr_location.attr('data-kml'),
                                suppressInfoWindows: true,
                                preserveViewport: false
                            });
                            console.log("kml_layers[curr_id]: ", kml_layers[curr_id]);
                        }

                        kml_layers[curr_id].setMap(community_map);
                        console.log("KML Layers: ", kml_layers);
                        console.log("community_map: ", community_map);
                    }
                }

                function maybe_show_community_results_count($curr_location) {
                    console.log("KML Layers123: ", kml_layers);
                    var curr_id = $curr_location.attr('data-id');
                    var total = $curr_location.attr('data-total');
                    if (typeof total == 'undefined') {
                        total = '...';
                    };
                    var href = './community-details/?ID=' + curr_id;

                    //Delete all previous kml layers for the other ids
                    Object.keys(kml_layers).map(function (key) {
                        if (key != curr_id) {
                            kml_layers[key].setMap(null);
                        }
                    });

                    //Create new kml layer if it do not exists for the current id
                    if ($curr_location.attr('data-kml')) {
                        if (undefined == kml_layers[curr_id]) {
                            kml_layers[curr_id] = new google.maps.KmlLayer({
                                url: $curr_location.attr('data-kml') + '?ver=4',
                                suppressInfoWindows: true
                            });

                            console.log("KML Test");
                            console.log(kml_layers[curr_id]);

                        }
                        kml_layers[curr_id].setMap(community_map);
                       
                    } else {
                        if (null !== countCircle && undefined !== countCircle) {
                            countCircle.setMap(null);
                        }
                        return;
                    }

                    google.maps.event.addListener(kml_layers[curr_id], "defaultviewport_changed", function () {
                        //Delete all markers
                        if (null !== countCircle && undefined !== countCircle) {
                            countCircle.setMap(null);
                        }

                        //Render new marker
                        //countCircle = new google.maps.Marker({
                        //	position: kml_layers[ curr_id ].getDefaultViewport().getCenter(),
                        //	icon: {
                        //		path: google.maps.SymbolPath.CIRCLE,
                        //		scale: 16,
                        //		fillColor: '#ffffff',
                        //		fillOpacity: 1,
                        //		strokeWeight: 1,
                        //	},
                        //	label:{
                        //		text: total + '', //this must be a string, otherwise console error is thrown
                        //		color: 'black',
                        //		fontSize: "15px"
                        //	},
                        //	map: community_map
                        //});

                        //google.maps.event.addListener(countCircle, 'click', function() {
                        //	window.location.href = href;
                        //});
                    });
                }

                maybe_show_community_kml_layer($location_links.filter('.active'));

            }

            google.maps.event.addListenerOnce(community_map, 'idle', function () {
                // do something only the first time the map is loaded
                jQuery('#community_map').addClass('tk-hidden-mobile');
            });

        }
    }
};

