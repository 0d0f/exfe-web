<?php include 'share/header.php'; ?>
</head>
<body id="special_page_body">
<?php include 'share/nav.php'; ?>
<?php $fromaddress = $this->getVar('fromaddress'); ?>
<div class="special_page">
    <div class="special_main_area">
        <p class="special_page_title">Sorry, the <span class="special_x">X</span> you're requesting is private.</p>
        <p class="special_page_subtitle">Please sign in as invited user.</p>
    </div>
    <div class="spacial_page_buttons">
        <?php if ($fromaddress) { ?>
        <button onclick="alert('<?php echo $fromaddress; ?>')">Back</button>
        <?php } ?>
        <button onclick="javascript:location.href = '/s/login';">Sign in</button>
    </div>
</div>
</body>
</html>
