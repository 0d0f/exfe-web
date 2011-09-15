<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-US" lang="en-US">
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <meta name="Author" content="EXFE Inc." />
    <meta name="viewport" content="width=1024" />
    <meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7, IE=9" />
    <link id="global-stylesheet" rel="stylesheet" href="/static/css/global.style.css" type="text/css" />
    <title>EXFE.COM</title>
    <meta name="robots" content="index, follow" />
    <meta name="Keywords" content="EXFE,Gather" />
    <meta name="Description" content="EXFE.COM website" />
    <link rel="home" href="http://www.exfe.com/" />
    <link rel="alternate" type="application/rss+xml" title="RSS" href="" />
    <link rel="index" href="http://www.exfe.com/sitemap/" />
    <link type="text/css" rel="stylesheet" href="/static/css/home.style.css" />
    <link type="text/css" rel="stylesheet" href="/static/css/style.css" />
    <script type="text/javascript" src="/static/js/jquery-1.6.3.js"></script>
    <link type="text/css" href="/static/css/ui-lightness/jquery-ui-1.7.2.custom.css" rel="stylesheet" />
    <link type="text/css" href="/static/css/simplemodal.css" rel="stylesheet" />
    <script type="text/javascript" src="/static/js/jquery-ui-1.7.2.custom.min.js"></script>
    <script type="text/javascript" src="/static/js/gather.js"></script>
    <script type="text/javascript" src="/static/js/timepicker.js"></script>
    <script type="text/javascript" src="/static/js/jquery.simplemodal.1.4.1.min.js"></script>
    <script type="text/javascript" src="/static/js/activity-indicator.js"></script>
    <script type="text/javascript" src="/static/js/dialog.js"></script>
</head>
<body id="home">
    <div id="global_header">
        <p class="logo"><img src="/static/images/exfe_logo.jpg" alt="EXFE" title="EXFE.COM" /></p>
        <p class="user_info"><a id="home_user_loin_btn" href="javascript:void(0);">Sign In</a></p>
    </div>
    <div class="home_banner"></div>
    <div class="home_bottom">
        <p class="gather_btn"><a href="/x/gather"><img src="/static/images/home_gather_btn.jpg" alt="Gather" title="Gather" /></a></p>
        <p class="bottom_btn"></p>
    </div>
<script type="text/javascript">
var site_url = "<?php echo SITE_URL; ?>";
jQuery(document).ready(function() {
    jQuery("#home_user_loin_btn").click(function() {
        var html = showdialog("reg");
        jQuery(html).modal();
        bindDialogEvent("reg");
    });
});
</script>
</body>
</html>
