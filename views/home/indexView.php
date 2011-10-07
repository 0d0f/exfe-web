<?php include "share/header.php" ?>
    <link type="text/css" rel="stylesheet" href="/static/css/home.style.css" />
    <link type="text/css" rel="stylesheet" href="/static/css/style.css" />
    <link type="text/css" href="/static/css/ui-lightness/jquery-ui-1.7.2.custom.css" rel="stylesheet" />
    <link type="text/css" href="/static/css/simplemodal.css" rel="stylesheet" />
    <script type="text/javascript" src="/static/js/libs/jquery-ui-1.7.2.custom.min.js"></script>
    <script type="text/javascript" src="/static/js/libs/jquery.simplemodal.1.4.1.min.js"></script>
    <script type="text/javascript" src="/static/js/libs/timepicker.js"></script>
    <script type="text/javascript" src="/static/js/libs/activity-indicator.js"></script>
    <script type="text/javascript" src="/static/js/comm/dialog.js"></script>
</head>
<body id="home">
    <!-- div id="global_header">
        <p class="logo"><img src="/static/images/exfe_logo.jpg" alt="EXFE" title="EXFE.COM" /></p>
        <p class="user_info"><a id="home_user_login_btn" href="javascript:void(0);">Sign In</a></p>
    </div -->
    <?php include "share/nav.php" ?>
    <div class="home_banner"></div>
    <div class="home_bottom">
        <p class="gather_btn">
            <a href="/x/gather"><img src="/static/images/home_gather_btn.jpg" alt="Gather" title="Gather" /></a>
        </p>
        <p class="bottom_btn"></p>
    </div>
    <script type="text/javascript">
        /*
        var floatWrapX, floatWrapY, dragX, dragY, pX, pY, tX, tY;
        var cX = document.documentElement.clientWidth;
        var cY = document.documentElement.clientHeight;
        var floatWrapPerX = floatWrapPerY = floatshowOne = 0;
        var drag = false;
        var divscroll = true;
        var resizeswitch = true;

        jQuery(document).ready(function() {
            jQuery("#home_user_login_btn").click(function() {
                var html = showdialog("reg");
                jQuery(html).modal();
                bindDialogEvent("reg");
            });
            $('#home_user_login_btn').click(function() {
                $(window).scroll(function() {
                    if (!drag && divscroll) {
                        floatWrapX = $(window).scrollLeft() + pX;
                        floatWrapY = $(window).scrollTop() + pY;
                        $('#fBox').css({ top: floatWrapY, left: floatWrapX });
                    }
                });
                $(window).resize(function() {
                    if (!drag && resizeswitch) {
                        cX = document.documentElement.clientWidth;
                        cY = document.documentElement.clientHeight;
                        floatWrapX = $(window).scrollLeft() + cX * floatWrapPerX;
                        floatWrapY = $(window).scrollTop() + cY * floatWrapPerY;
                        $('#fBox').css({ top: floatWrapY, left: floatWrapX });
                        pX = parseInt($('#fBox').css("left")) - $(window).scrollLeft();
                        pY = parseInt($('#fBox').css("top")) - $(window).scrollTop();
                    }
                });
                $('#fBoxHeader').mousedown(function(event) {
                    $(this).css({cursor:'move'});
                    $('#floatWarpClone').remove();
                    $('#fBox').clone(true).insertAfter('#fBox').attr('id', 'floatWarpClone').show();
                    $('body').bind("selectstart",function(){return false});
                    $('#fBox').hide();
                    dragX = ($(window).scrollLeft() + event.clientX) - (parseInt($('#fBox').css("left")));
                    dragY = ($(window).scrollTop() + event.clientY) - (parseInt($('#fBox').css("top")));
                    drag = true;
                });
                $('body').mousemove(function(event) {
                    if (drag) {
                        tX = event.pageX - dragX;
                        tY = event.pageY - dragY;
                        $('#floatWarpClone').css({ left: tX, top: tY });
                        pX = tX - $(window).scrollLeft();
                        pY = tY - $(window).scrollTop();
                        floatWrapPerX = pX / cX;
                        floatWrapPerY = pY / cY;
                    }
                });
                $('#fBoxHeader').mouseup(function() {
                    $('#fBox').css({ left: tX, top: tY });
                    $('#floatWarpClone').remove();
                    $('body').unbind("selectstart");
                    $('#fBoxHeader').css({cursor:'default'});
                    $('#fBox').show();
                    drag = false;
                });
                $('#closeFBox').click(function() {
                    $('#fBox').hide(); 
                    //floatshowOne = 0;
                });
            });
        });
        */
    </script>
</body>
</html>
