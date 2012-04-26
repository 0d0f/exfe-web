<?php include "share/header.php"; ?>
<style type="text/css">
.link_invalid_msg { width:860px; margin:auto; padding:90px 0px; overflow:hidden; }
.link_invalid_msg p { font-size:18px; text-align:center; }
.link_invalid_msg p.titles{ font-size:34px; height:70px; }
.link_invalid_msg p a{
    display:block; height:24px; width:85px; padding-top:2px; text-align:center;
    border: 0 none; cursor:pointer; font-size: 14px; text-decoration:none;
    background: url("/static/images/btn.png") no-repeat scroll 0 -580px transparent;
 }
</style>
</head>
<body>
<?php include "share/nav.php"; ?>
<div class="link_invalid_msg" id="link_invalid_msg">
    <p class="titles">Invalid Link</p>
    <p>Sorry, your token is invalid, maybe it’s expired.</p>
    <p style="padding:50px 0 0 385px;"><a href="/">OK</a></p>
</div>
</body>
</html>
