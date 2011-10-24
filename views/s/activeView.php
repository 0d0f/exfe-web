<?php
    $page   = 'active';
    $result = $this->getVar('result');
    $status = $result['result'];
    $external_identity = $result['external_identity'];

    if ($status === 'verified') {
        header('refresh: 6; url=/s/profile');
    }

    include 'share/header.php';
?>
</head>
<body id="special_page_body">
<?php include "share/nav.php"; ?>
<div class="special_page">
    <div class="special_main_area">
        <?php if ($status === 'verified') { ?>
            <p class="special_page_title">
                Identity email <span class="special_page_identity"><?php echo $external_identity; ?></span> has already been verified.
            </p>
            <p class="special_page_subtitle">
                Redirecting to <a href="<?php echo SITE_URL;?>/s/profile">Profile page</a>
            </p>
        <?php } else { ?>
            <p class="special_page_title">
                Identity email <span class="special_page_identity"><?php echo $external_identity; ?></span><br/>verifiation has been expired.
            </p>
        <?php } ?>
    </div>
</div>
<div id="footerBao"></div><!--/#footerBao-->
</body>
</html>
