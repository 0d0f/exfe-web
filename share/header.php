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
    <link rel="stylesheet" type="text/css" href="/static/?g=css_global&t=<?php echo STATIC_CODE_TIMESTAMP; ?>" />

    <script type="text/javascript" src="/static/?g=js_libs&t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>
    <script type="text/javascript" src="/static/?g=js_util&t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>
    <script type="text/javascript" src="/static/js/comm/func.js?t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>
    <script type="text/javascript" src="/static/?g=js_user&t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>

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
