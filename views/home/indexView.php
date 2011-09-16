<?php include "share/header.php" ?>
    <link type="text/css" rel="stylesheet" href="/static/css/home.style.css" />
    <link type="text/css" rel="stylesheet" href="/static/css/style.css" />
    <link type="text/css" href="/static/css/ui-lightness/jquery-ui-1.7.2.custom.css" rel="stylesheet" />
    <link type="text/css" href="/static/css/simplemodal.css" rel="stylesheet" />
    <script type="text/javascript" src="/static/js/libs/jquery-ui-1.7.2.custom.min.js"></script>
    <script type="text/javascript" src="/static/js/libs/jquery.simplemodal.1.4.1.min.js"></script>
    <script type="text/javascript" src="/static/js/libs/timepicker.js"></script>
    <script type="text/javascript" src="/static/js/libs/activity-indicator.js"></script>
    <script type="text/javascript" src="/static/js/apps/gather.js"></script>
    <script type="text/javascript" src="/static/js/comm/dialog.js"></script>
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
