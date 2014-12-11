<?php
  $frontConfigData = json_decode(file_get_contents('static/package.json'));
  if (!$frontConfigData) {
      header('location: /500');
      return;
  }

  include "share/header.php";
?>
</head>
<body>
  <?php include "share/nav.php" ?>

  <!-- Container {{{-->
  <div class="container" id="app-container">
    <div role="main" id="app-main"></div>
  </div>
  <!--/Container }}}-->

  <!-- Tmp -->
  <div class="tmp" id="app-tmp"></div>

  <noscript>EXFE.COM can't load if JavaScript is disabled</noscript>
  <?php include 'share/footer.php'; ?>
</body>
</html>
