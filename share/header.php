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
<?php if (($twitterSignin = $this->getVar('twitter_signin'))) { ?>
  <meta name="sign-token" content="<?php echo htmlentities(json_encode($twitterSignin)); ?>" />
<?php } ?>
  <link rel="shortcut icon" href="/static/img/favicon_16.png" />
  <link rel="apple-touch-icon" href="/static/img/favicon_32.png" />
  <title>EXFE.COM</title>
  <link rel="stylesheet" media="screen" type="text/css" href="/static/css/exfe.min.css?t=<?php echo STATIC_CODE_TIMESTAMP; ?>" />
  <script src="/static/js/modernizr/2.5.3/modernizr.js?t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>
