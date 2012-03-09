<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge;chrome=1;">
    <meta charset="UTF-8">
    <meta name="Author" content="EXFE Inc.">
    <meta name="robots" content="index, follow">
    <meta name="Keywords" content="EXFE, Gather">
    <meta name="Description" content="EXFE.COM website">
    <title>EXFE.COM</title>
    <link rel="stylesheet" type="text/css" href="/static/css/global.css">
    <link rel="stylesheet" type="text/css" href="/static/css/style.css">
    <script src="/static/js/libs/jquery.js"></script>
    <script src="/static/js/libs/activity-indicator.js"></script>
    <script src="/static/js/libs/jquery.ba-outside-events.js"></script>
    <script src="/static/js/libs/jquery.ba-dotimeout.js"></script>
    <script src="/static/js/comm/ExfeUtil.js"></script>
    <script src="/static/js/comm/func.js"></script>
    <script src="/static/js/user/UserIdentification.js"></script>
    <script src="/static/js/exlibs/ExDialog.js"></script>
    <script src="/static/js/user/UserStatus.js"></script>

    <script>
        var site_url = "<?php echo SITE_URL; ?>";
        var img_url  = "<?php echo IMG_URL; ?>";
        var cookies_domain = "<?php echo COOKIES_DOMAIN; ?>";
        var utc      = <?php echo time(); ?>;
    </script>

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
