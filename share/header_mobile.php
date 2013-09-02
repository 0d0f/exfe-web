<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8" />
  <!--[if IE]><meta http-equiv="X-UA-Compatible" content="IE=edge" /><![endif]-->
  <title><?php echo $this->getVar('title'); ?></title>
  <meta name="author" content="EXFE Inc.">
  <meta name="robots" content="index, follow">
  <meta name="keywords" content="EXFE, ·X·, cross, exfee, gather, Gather a ·X·, hangout, gathering, invite, RSVP">
  <meta name="description" content="EXFE, the group utility for gathering.">
  <meta name="copyright" content="Copyright &copy; 2013 EXFE Inc">
  <meta name="viewport" content="initial-scale=1.0,maximum-scale=1,user-scalable=no,width=device-width">
  <meta name="viewport" content="initial-scale=1.0,maximum-scale=1,user-scalable=no" media="(device-height: 568px)">
  <meta name="apple-mobile-web-app-title" content="EXFE">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black">
<?php if (($sms_token = $this->getVar('sms_token'))) { ?>
  <meta name="sms-token" content="<?php echo htmlentities(json_encode($sms_token)); ?>">
<?php } ?>
<?php if (($oauth = $this->getVar('oauth'))) { ?>
  <meta name="authorization" content="<?php echo htmlentities(json_encode($oauth)); ?>" />
<?php } ?>
  <link rel="icon" type="image/x-icon" href="/static/img/favicon.ico" />
  <link rel="stylesheet" media="screen" type="text/css" href="/static/css/<?php echo $frontConfigData->css->exfemobilemin; ?>" />
