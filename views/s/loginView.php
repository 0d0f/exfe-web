<?php include "share/header.php"; ?>
</head>
<body>
<?php include "share/nav.php"; ?>
<?php 
$isNewIdentity=$this->getVar("isNewIdentity");
if($isNewIdentity===true){
    include "welcomebox.php"; 
}else{
    include "share/loginbox.php"; 
}
?>
</body>
</html>
