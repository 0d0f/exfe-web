  // Front-End Configs
  define('config', function () {
    var config = {
      APP_ENV: '<?php echo JS_DEBUG ? 'development' : 'production'; ?>',
      MAP_KEY: '<?php echo GOOGLE_MAP_KEY; ?>',
      api_url: '<?php echo API_URL; ?>/v2',
      img_url: '<?php echo IMG_URL; ?>',
      site_url: '<?php echo SITE_URL; ?>',
      timestamp: <?php echo STATIC_CODE_TIMESTAMP; ?>,
      backgrounds: <?php echo json_encode($this->getVar('backgrounds')); ?>,
      location: <?php echo json_encode($this->getVar('location')), "\n"; ?>,
      photo_providers: <?php echo json_encode(['facebook', 'dropbox', 'flickr']); ?>
    };

    config.timevalid = Math.abs(Math.round(+new Date() / 1000) - <?php echo Time(); ?>) < 15 * 60;

    return config;
  });
