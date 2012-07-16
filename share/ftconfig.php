// Front-End Configs
  define('config', [], function (require, exports, module) {
    return {
      api_url: '<?php echo API_URL; ?>/v2',
      img_url: '<?php echo IMG_URL; ?>',
      site_url: '<?php echo SITE_URL; ?>',
      timestamp: '<?php echo STATIC_CODE_TIMESTAMP; ?>',
      backgrounds: <?php echo json_encode($this->getVar('backgrounds')), "\n"; ?>
    };
  });
