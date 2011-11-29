<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge;chrome=1;">
    <meta charset="UTF-8">
    <meta name="Author" content="EXFE Inc.">
    <meta name="robots" content="index, follow">
    <meta name="Keywords" content="EXFE,Gather">
    <meta name="Description" content="EXFE.COM website">
    <title>www.exfe.com</title>
    <link rel="alternate" type="application/rss+xml" title="RSS" href="">
    <link rel="home" href="http://www.exfe.com/">
    <link rel="index" href="http://www.exfe.com/sitemap/">
    <link rel="stylesheet" type="text/css" href="/static/css/global.css">
    <link rel="stylesheet" type="text/css" href="/static/css/style.css">
    <script type="text/javascript" src="/static/js/libs/jquery.js"></script>
    <script type="text/javascript" src="/static/js/libs/activity-indicator.js"></script>
    <script type="text/javascript" src="/static/js/libs/jquery.ba-outside-events.js"></script>
    <script type="text/javascript" src="/static/js/libs/jquery.ba-dotimeout.js"></script>
    <script type="text/javascript" src="/static/js/comm/ExfeUtil.js"></script>
    <script type="text/javascript" src="/static/js/comm/func.js"></script>

    <script type="text/javascript" src="/static/js/user/UserIdentification.js"></script>
    <script type="text/javascript" src="/static/js/exlibs/ExDialog.js"></script>
    <script type="text/javascript" src="/static/js/user/UserStatus.js"></script>

    <script type="text/javascript">
        var site_url = "<?php echo SITE_URL; ?>";
        var img_url  = "<?php echo IMG_URL; ?>";
        var cookies_domain = "<?php echo COOKIES_DOMAIN ?>";
    </script>

    <!--[if IE 6]>
    <script type="text/javascript" src="/static/js/libs/PNG.js"></script>
    <script type="text/javascript">
        DD_belatedPNG.fix('#header .logo,#header .mygear');
    </script>
    <![endif]-->

    <?php
    if($_SESSION["tokenIdentity"]!="" && $_GET["token"]!="")
    {
        $global_name=$_SESSION["tokenIdentity"]["identity"]["name"];
        $global_avatar_file_name=$_SESSION["tokenIdentity"]["identity"]["avatar_file_name"];
        $global_external_identity=$_SESSION["tokenIdentity"]["identity"]["external_identity"];
        $global_identity_id=$_SESSION["tokenIdentity"]["identity_id"];

    } else if($_SESSION["identity"]!="") {
        $global_name=$_SESSION["identity"]["name"];
        $global_avatar_file_name=$_SESSION["identity"]["avatar_file_name"];
        $global_external_identity=$_SESSION["identity"]["external_identity"];
        $global_identity_id=$_SESSION["identity_id"];
    }
    if(empty($global_avatar_file_name)){
        $global_avatar_file_name = "default.png";
    }
    ?>
