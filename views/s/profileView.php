<?php include 'share/header.php'; ?>
<link rel="stylesheet" type="text/css" href="/static/?f=css/profile.css&t=<?php echo STATIC_CODE_TIMESTAMP; ?>">
<script type="text/javascript" src="/static/?g=js_uploader&t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>
</head>
<body>
<?php include 'share/nav.php'; ?>
<?php
    $identities = $this->getVar('identities');
    $user = $this->getVar('user');
    $cross_num = $this->getVar('cross_num');
    $user['avatar_file_name'] = $user['avatar_file_name'] ?: 'default.png';
?>
<style>
html, body {height: 100%; margin: 0; overflow: hidden;}
</style>
</head>
<body>
<script>

window.addEventListener('message', function (e) {
  var data = e.data;
  if (data === 'logout') {
    window.location.href = '/s/logout';
  } else if (data === 'gather') {
    window.location.href = '/x/gather';
  } else if (/^cross/.test(data)) {
    var id = data.substring(6);
    window.location.href = '/!' + id;
  } else if (/^profile/.test(data)) {
    var p = data.substring(8);
    window.location.href = p;
  }
});

window.addEventListener('resize', function (e) {
  var o = document.getElementsByName('v2Frame')[0];
  iframe_setup(o);
});

function iframe_setup(o) {
  o.height = v2Frame.parent.document.body.scrollHeight - 56;
}
</script>
<iframe scrolling="yes" name="v2Frame" src="http://v2.localexfe.me/profile_iframe.html?domain=<?php echo SITE_URL; ?>&token=<?php echo $this->getVar('token'); ?>&time=<?php echo time(); ?>" width="100%" frameborder="0" onload="iframe_setup(this)"></iframe>
</body>
</html>
