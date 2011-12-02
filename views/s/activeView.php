<?php
    $page   = 'active';
    $result = $this->getVar('result');
    $status = $result['result'];
    $external_identity = $result['external_identity'];

    include 'share/header.php';
?>
    <link type="text/css" rel="stylesheet" href="/static/css/specialpage.css"/>
</head>
<body id="special_page_body">
<?php include "share/nav.php"; ?>
<div class="special_page">
    <div class="special_main_area">
        <p class="special_page_title">
            Identity email <span class="special_page_identity"><?php echo $external_identity; ?></span><br/>verifiation has been expired.
        </p>
    </div>
</div>
</body>
</html>
