<?php include "header.php"; ?>
<body>
<?php include "nav.php"; ?>
<?php 

$isNewIdentity=$this->getVar("isNewIdentity");
if($isNewIdentity===true)
    include "welcomebox.php"; 
else
    include "loginbox.php"; 
?>
</body>
</html>
