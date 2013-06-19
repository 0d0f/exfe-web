<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8" />
  <!--[if IE]><meta http-equiv="X-UA-Compatible" content="IE=edge" /><![endif]-->
  <title>EXFE - The group utility for gathering.</title>
  <meta name="author" content="EXFE Inc." />
  <meta name="robots" content="index, follow" />
  <meta name="keywords" content="EXFE, ·X·, cross, exfee, gather, Gather a ·X·, hangout, gathering, invite, RSVP" />
  <meta name="description" content="EXFE, the group utility for gathering." />
  <meta name="copyright" content="Copyright &copy; 2013 EXFE Inc" />
  <meta name="viewport" content="initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <meta name="apple-mobile-web-app-capable" content="yes" />
  <meta name="apple-mobile-web-app-status-bar-style" content="black" />
<?php if (($sms_token = $this->getVar('sms_token'))) { ?>
  <meta name="sms-token" content="<?php echo htmlentities(json_encode($sms_token)); ?>" />
<?php } ?>
  <link rel="icon" type="image/x-icon" href="/static/img/favicon.ico" />
