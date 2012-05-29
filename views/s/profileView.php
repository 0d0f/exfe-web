<style>
html, body {height: 100%; margin: 0;}
</style>
</head>
<body>
<?php define('DOMAIN', 'localexfe.me'); ?>
<script>
window.domain = '<?php echo DOMAIN ;?>';

window.addEventListener('message', function (e) {
  var data = e.data;
  if (data === 'logout') {
    window.location.href = '/s/logout';
  } else if (data === 'gather') {
    window.location.href = '/x/gather';
  } else if (/^cross/.test(data)) {
    var id_base62 = data.substring(6);
    window.location.href = '/!' + id_base62;
  }
});

function iframe_setup(o) {
  o.height = v2Frame.parent.document.body.scrollHeight;
}
</script>
<iframe name="v2Frame" src="https://v2.localexfe.me/profile_iframe.html?domain=<?php echo DOMAIN; ?>&token=<?php echo $this->getVar('token'); ?>&time=<?php echo time(); ?>" width="100%" frameborder="0" onload="iframe_setup(this)"></iframe>
</body>
</html>
