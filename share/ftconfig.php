define('config', [], function (require, exports, module) {
  return {
    api_url: '<?php echo API_URL; ?>/v2',
    timestamp: '<?php echo STATIC_CODE_TIMESTAMP; ?>'
  };
});
