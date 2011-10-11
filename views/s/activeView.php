<?php $page="active";?>
<?php
$result=$this->getVar("result");
$status=$result["result"];
$external_identity=$result["external_identity"];

if($status=="verified")
    header( 'refresh: 6; url=/s/profile') ; 
?>
<?php include "share/header.php"; ?>
</head>
<body>
<?php include "share/nav.php"; ?>
<?php
?>
<?php
if ($status=="verified"){
?>
Identity email <?php echo $external_identity; ?>
has already been verified.

<br/>Redirecting to <a href="<?php echo SITE_URL;?>/s/profile">Profile page</a>
<?php } else { ?>
Identity email <?php echo $external_identity; ?> 
verifiation has been expired.
<?php } ?>
<div id="footerBao">
</div><!--/#footerBao-->
</body>
</html>

