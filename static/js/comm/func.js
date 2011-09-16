var moduleNameSpace = "odof.comm.func";
var ns = odof.util.initNameSpace(moduleNameSpace);

(function(ns){
    ns.getBak = function(){
        var oall = document.getElementById("oall");
        var lightBox = document.getElementById("fBox");
        if(oall && lightBox){
            oall.style.display = "block";
            oall.style.height = document.body.scrollHeight + "px";
            lightBox.style.display = "block";
            function reset()
            {
                var d = document.documentElement;
                var x1 = d.scrollLeft;
                var sUserAgent = navigator.userAgent;
                var isChrome = sUserAgent.indexOf("Chrome") > -1 ;
                if(isChrome){
                    var y1 = document.body.scrollTop;
                }
                else{
                    var y1 = d.scrollTop;
                }
                var w1 = d.clientWidth;
                var h1 = d.clientHeight;


                var w = parseInt(lightBox.offsetWidth);
                var h = parseInt(lightBox.offsetHeight);
                var x = Math.ceil((w1 - w)/2) + x1;
                var y = Math.ceil((h1 - h)/2) + y1;


                lightBox.style.left = x + "px";
                lightBox.style.top = y + "px";
            }
            window.onresize = reset;
            window.onscroll = reset;
            reset();

        }
    };
    ns.cancel = function(){
        var oall = document.getElementById("oall");
        var lightBox = document.getElementById("fBox");
        if(oall && lightBox){
            oall.style.display = "none";
            lightBox.style.display = "none";
        }
    };
})(ns);

jQuery(document).ready(function() {
    jQuery('.name').mousemove(function() {
        jQuery('#goldLink a').addClass('nameh');
        jQuery('#myexfe').show();
    });
    jQuery('.name').mouseout(function() {
        jQuery('#goldLink a').removeClass('nameh');
        jQuery('#myexfe').hide();
    });
    jQuery('#private_icon').mousemove(function() {
        jQuery('#private_hint').show();
    });
    jQuery('#private_icon').mouseout(function() {
        jQuery('#private_hint').hide();
    });
    jQuery('#edit_icon').mousemove(function() {
        jQuery('#edit_icon_desc').show();
    });
    jQuery('#edit_icon').mouseout(function() {
        jQuery('#edit_icon_desc').hide();
    });

    //  jQuery('.newbg').mousemove(function(){
    //	jQuery(this).addClass('fbg');
    //	jQuery('.fbg button').show();
    //});
    //
    //  jQuery('.newbg').mouseout(function(){
    //	jQuery(this).removeClass('fbg');
    //	jQuery('button').hide();
    //});
    //
    //  jQuery('.bnone').mousemove(function(){
    //	jQuery(this).addClass('bdown');
    //	jQuery('.bdown button').show();
    //});
    //
    //  jQuery('.bnone').mouseout(function(){
    //	jQuery(this).removeClass('bdown');
    //	jQuery('dd button').hide();
    //});
    //
    //
    //  jQuery('.lb').mousemove(function(){
    //	  jQuery(this).addClass('labtn');
    //	  jQuery('.labtn button').show();
    //	  jQuery('.lb span').hide()
    //});
    //
    //  jQuery('.lb').mouseout(function(){
    //	  jQuery(this).removeClass('labtn');
    //	  jQuery('button').hide();
    //	  jQuery('.lb span').show()
    //
    //});
    //
    //  jQuery('.uplb').mousemove(function(){
    //	jQuery(this).addClass('uabtn');
    //	  jQuery('.uabtn button').show();
    //	  jQuery('.uplb span').hide()
    //});
    //
    //  jQuery('.uplb').mouseout(function(){
    //	  jQuery(this).removeClass('uabtn');
    //	  jQuery('button').hide();
    //	  jQuery('.uplb span').show()
    //});

    jQuery('.lbl').mousemove(function(){
        jQuery('.lt').addClass('lton');
    });
    jQuery('.lbl').mouseout(function(){
        jQuery('.lt').removeClass('lton');
    });
    jQuery('.lbr').mousemove(function(){
        jQuery('.rt').addClass('rton');
    });
    jQuery('.lbr').mouseout(function(){
        jQuery('.rt').removeClass('rton');
    });

    // jQuery('.addjn').mousemove(function(){
    //	jQuery(this).addClass('bgrond');
    //	jQuery('.bgrond .exfee_del').show();
    //});
    //
    //  jQuery('.addjn').mouseout(function(){
    //	jQuery(this).removeClass('bgrond');
    //	jQuery('.exfee_del').hide();
    //});

    jQuery('.redate').mousemove(function(){
        jQuery(this).addClass('bgdq');
    });

    jQuery('.redate').mouseout(function(){
        jQuery(this).removeClass('bgdq');
    });

    jQuery('.coming').mousemove(function(){
        jQuery(this).addClass('bgcom');
    });

    jQuery('.coming').mouseout(function(){
        jQuery(this).removeClass('bgcom');
    });

});
