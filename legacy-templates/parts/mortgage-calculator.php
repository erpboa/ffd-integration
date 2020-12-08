<?php 
global $post;
$listing = $post;
$listing_price = get_post_meta($listing->ID, "ffd_listingprice_pb", true);

if( empty($listing_price) ){
    $listing_price = '0000000';
}

?><div id="sidebar-mortgage-cal" class="bottomPad-sm sidebar-mortgage-cal">
    <h4 class="h4 mortgage-cal-heading"><span class="d-iblock underline underline-blue">mortgage calculator.</span></h4>
    <p class="text-semibold">$ Property price</p>
    <div id="homeprice-rangeslider"></div>
    <p class="last-row">&nbsp;</p>
    <p class="text-semibold">% Down payment</p>
    <div id="downpayment-rangeslider"></div>
    <p class="text-right text-grey3 down-payment"></p>
    <div class="bg-lightgrey box-mini" style="margin: 0;">
        <p class="text-grey  text-center "><small>30 Year Fixed, 4.00% Interest</small></p>
        <div class="box-mini borderedbox border-white text-bold text-center bottomPad-xs"><large class="payment-permonth">$13,664</large> <small>per month</small></div>
        <ul class="mortgage_custom small text-semibold">
            <li class="collapsed custom-section-toggle" data-toggle="collapse" data-target="#mortgage-customization">
                <i class="fa fa-angle-right text-blue text-semibold" ></i>
                <i class="fa fa-angle-down text-blue text-semibold" ></i>Customize Calculations</li>
            <li id="mortgage-customization" class="collapse customSection row">
                <div class="col-sm-4">Years</div>
                <div class="col-sm-8">
                    <select id="ddlyears" onchange="calculateMortgage();" class="form-control" style="height:34px;">
                        <option value="10">10</option>
                        <option value="15">15</option>
                        <option value="20">20</option>
                        <option value="25">25</option>
                        <option value="30" selected>30</option>
                        <option value="40">40</option>
                    </select>
                </div>
                <div class="clearfix">&nbsp;</div>
                <div class="col-sm-4">Interest</div>
                <div class="col-sm-8"><input onchange="calculateMortgage();" type="number" value="4" id="interest-rate" class="form-control" min="0" step="0.01" style="height:34px;" /></div>

            </li>
            
        </ul>

    </div>
    <input type="hidden" value="<?php echo $listing_price; ?>" id="hdnListingPrice" />
    <input type="hidden" value="20" id="down-payment-per" />
</div>

<script>

function calculateMortgage() {
    var $ = jQuery;

    var homePrice = parseInt($("#sidebar-mortgage-cal #homeprice-rangeslider").slider("option", "value"));
    var percDP = parseInt($("#sidebar-mortgage-cal #downpayment-rangeslider").slider("option", "value"));

    var downPayment = homePrice * (percDP / 100);

    var interest = parseFloat($("#interest-rate").val());
    var numberOfYears = parseInt($("#ddlyears").val());

    //Subtract down payment
    var loanAmount = (homePrice - downPayment);

    // rate of interest and number of payments for monthly payments
    var rateOfInterest = interest / 1200;
    var numberOfPayments = numberOfYears * 12;

    // loan amount = (interest rate * loan amount) / (1 - (1 + interest rate)^(number of payments * -1))
    var paymentAmount = (rateOfInterest * loanAmount) / (1 - Math.pow(1 + rateOfInterest, numberOfPayments * -1));

    paymentAmount = paymentAmount.toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, '$1,').replace(".00", "");
    $(".payment-permonth").text("$" + paymentAmount);
    $(".down-payment").text("$" + downPayment.toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, '$1,'));

}

function mortgageValues(type) {
    var $ = jQuery;

    var price = $("#hdnListingPrice").val().replace(/,/g, "").replace('$', "");
    if (price != "") {
        price = parseInt(price);
    } else {
        price = 0;
    }

    if (type == "down-payment")
        price = $("#down-payment-per").val();

    return price;

}

Number.prototype.formatMoney = function (c, d, t) {
    var n = this,
        c = isNaN(c = Math.abs(c)) ? 2 : c,
        d = d == undefined ? "." : d,
        t = t == undefined ? "," : t,
        s = n < 0 ? "-" : "",
        i = String(parseInt(n = Math.abs(Number(n) || 0).toFixed(c))),
        j = (j = i.length) > 3 ? j % 3 : 0;
    return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
};

jQuery(function($){

if ($("#sidebar-mortgage-cal #homeprice-rangeslider").length > 0) {
    
    var pValues = [];
    var stepAmount = 50000;

    for (var i = 0; i < parseInt($("#hdnListingPrice").val()) ; i += stepAmount) {
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

    $("#sidebar-mortgage-cal #homeprice-rangeslider").slider({
        range: 'min',
        min: 0,
        max: (mortgageValues("")*2),
        value: mortgageValues(""),
        slide: function (event, ui) {
            var value = parseInt($("#sidebar-mortgage-cal #homeprice-rangeslider").slider("option", "value"));

            var txt = '<span class="cval">$' + value.formatMoney(2, '.', ',') + '</span>';
            $("#sidebar-mortgage-cal #homeprice-rangeslider").find(".ui-slider-handle").html(txt);
            calculateMortgage();
        },
        change: function (event, ui) {
        var value = parseInt($("#sidebar-mortgage-cal #homeprice-rangeslider").slider("option", "value"));

        var txt = '<span class="cval">$' + value.formatMoney(2, '.', ',') + '</span>';
        $("#sidebar-mortgage-cal #homeprice-rangeslider").find(".ui-slider-handle").html(txt);
        calculateMortgage();
    }
    });
    var value = parseInt($("#sidebar-mortgage-cal #homeprice-rangeslider").slider("option", "value"));
    var txt = '<span class="cval">$' + value.formatMoney(2, '.', ',') + '</span>';
    
    $("#sidebar-mortgage-cal #homeprice-rangeslider").find(".ui-slider-handle").html(txt);
    
    
}
if ($("#sidebar-mortgage-cal #downpayment-rangeslider").length > 0) {
    $("#sidebar-mortgage-cal #downpayment-rangeslider").slider({
        range: 'min',
        min: 0,
        max: 100,
        step:5,
        value: mortgageValues("down-payment"),
        slide: function (event, ui) {
            var value = parseInt($("#sidebar-mortgage-cal #downpayment-rangeslider").slider("option", "value"));

            var txt = '<span class="cval">' + value.formatMoney(0, '.', ',') + '%</span>';
            $("#sidebar-mortgage-cal #downpayment-rangeslider").find(".ui-slider-handle").html(txt);
            calculateMortgage();
        },
        change: function (event, ui) {
        var value = parseInt($("#sidebar-mortgage-cal #downpayment-rangeslider").slider("option", "value"));

        var txt = '<span class="cval">' + value.formatMoney(0, '.', ',') + '%</span>';
        $("#sidebar-mortgage-cal #downpayment-rangeslider").find(".ui-slider-handle").html(txt);
        calculateMortgage();
    }
    });
    var value = parseInt($("#sidebar-mortgage-cal #downpayment-rangeslider").slider("option", "value"));
    var txt = '<span class="cval">' + value.formatMoney(0, '.', ',') + '%</span>';

    $("#sidebar-mortgage-cal #downpayment-rangeslider").find(".ui-slider-handle").html(txt);
    calculateMortgage();
}

});

</script>