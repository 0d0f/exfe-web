<!DOCTYPE html>
<html class="no-js" lang="en" dir="ltr">
<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge, chrome=1" />
  <title>EXFE - A utility for gathering with friends.</title>
  <meta name="author" content="EXFE Inc." />
  <meta name="robots" content="index, follow" />
  <meta name="keywords" content="EXFE, ·X·, cross, exfee, gather, Gather a ·X·, hangout, gathering, invite, RSVP" />
  <meta name="description" content="EXFE, a utility for gathering with friends." />
  <meta name="copyright" content="Copyright &copy; 2012 EXFE Inc" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
  <meta name="apple-mobile-web-app-capable" content="yes" />
  <meta name="apple-mobile-web-app-status-bar-style" content="black" />
<?php if (($oauth = $this->getVar('oauth'))) { ?>
  <meta name="authorization" content="<?php echo htmlentities(json_encode($oauth)); ?>" />
<?php } ?>
  <link rel="shortcut icon" href="/static/img/favicon.png" />
  <link rel="apple-touch-icon" href="/static/img/favicon.png" />
  <link rel="stylesheet" media="screen" type="text/css" href="/static/css/exfe_mobile.min.css?<?php echo STATIC_CODE_TIMESTAMP; ?>" />
