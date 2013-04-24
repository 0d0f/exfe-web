<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8" />
  <title>EXFE - A utility for gathering with friends.</title>
  <meta name="author" content="EXFE Inc." />
  <meta name="robots" content="index, follow" />
  <meta name="keywords" content="EXFE, ·X·, cross, exfee, gather, Gather a ·X·, hangout, gathering, invite, RSVP" />
  <meta name="description" content="EXFE, a utility for gathering with friends." />
  <meta name="copyright" content="Copyright &copy; 2012 EXFE Inc" />
  <meta name="viewport" content="initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <meta name="apple-mobile-web-app-capable" content="yes" />
  <meta name="apple-mobile-web-app-status-bar-style" content="black" />
<?php if (($sms_token = $this->getVar('sms_token'))) { ?>
  <meta name="sms-token" content="<?php echo htmlentities(json_encode($sms_token)); ?>" />
<?php } ?>
  <link rel="shortcut icon" href="/static/img/favicon.png" />
  <link rel="apple-touch-icon" href="/static/img/favicon.png" />
  <link rel="stylesheet" media="screen" type="text/css" href="/static/css/exfe_mobile.min.css?<?php echo STATIC_CODE_TIMESTAMP; ?>" />
