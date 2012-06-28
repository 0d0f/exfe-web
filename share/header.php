<!doctype html>
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="no-js lt-ie9 lt-ie8" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="no-js lt-ie9" lang="en"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang="en" dir="ltr"> <!--<![endif]-->
<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
  <meta name="author" content="EXFE Inc." />
  <meta name="robots" content="index, follow" />
  <meta name="keywords" content="EXFE, X, cross, exfee, gather, Gather a X, hangout, gathering, invite, RSVP" />
  <meta name="description" content="EXFE, an utility for hanging out with friends." />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1, user-scalable=no" />
  <title>EXFE.COM</title>

  <!-- V2 -->
  <link href="/static/1b/css/exfe.min.css?t=<?php echo STATIC_CODE_TIMESTAMP; ?>" rel="stylesheet" type="text/css" />
  <script src="/static/1b/js/modernizr/2.5.3/modernizr.js?t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>

  <!-- V1 -->
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
