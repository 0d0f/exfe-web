<?php include 'share/header.php'; ?>
<link rel="stylesheet" type="text/css" href="/static/css/forbidden.css">
</head>
<body id="special_page_body">
<?php
$userName = $this->getVar('user_name');
$userAvatar = $this->getVar('user_avatar');
$exfeOfficeAccount = $this->getVar('exfe_office_account');
?>

<?php include 'share/nav.php'; ?>
<div class="special_page">
    <div class="special_main_area">
    <p style="text-align:center;"><img src="<?php echo $userAvatar; ?>" title="twitter avatar" alt="twitter avatar" /></p>
    <p class="special_page_title" style="font-size:12pt;">
        Hi, <strong><?php echo $userName; ?> </strong>, Welcome to EXFE, Follow <strong><?php echo $exfeOfficeAccount; ?></strong> ?
    </p>
    <p class="special_page_subtitle">
        <a href="/oAuth/confirmTwitterFollowing?confirm=yes">Yes</a>
        <a href="/oAuth/confirmTwitterFollowing?confirm=no">No</a>
    </p>
    </div>
</div>
</body>
</html>
