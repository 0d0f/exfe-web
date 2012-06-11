<style>
html, body {height: 100%; margin: 0;}
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
  o.height = v2Frame.parent.document.body.scrollHeight;
}
</script>
<iframe name="v2Frame" src="http://v2.localexfe.me/profile_iframe.html?domain=<?php echo SITE_URL; ?>&token=<?php echo $this->getVar('token'); ?>&time=<?php echo time(); ?>" width="100%" frameborder="0" onload="iframe_setup(this)"></iframe>
</body>
</html>
