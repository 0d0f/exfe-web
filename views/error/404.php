<?php
include "share/header.php";

$error = $this->getVar("error");

?>
<link rel="stylesheet" type="text/css" href="/static/css/404.css">
</head>
<body id="error_404_page">
    <?php include "share/nav.php" ?>
    <div class="img_404">
        <div class="error_info"><?php echo $error; ?></div>
    </div>
</body>
</html>
