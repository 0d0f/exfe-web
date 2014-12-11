<?php $page="mute";?>
<?php
$mute=$this->getVar("mute");
$cross=$this->getVar("cross");

?>
<?php include "share/header.php"; ?>
</head>
<body>
<?php include "share/nav.php"; ?>
<?php
$status="mute";
if(intval($mute["status"])==0)
    $status="resume";

?>
cross <?php echo $cross["title"];?> status:<?php echo $status?>
</body>
</html>
