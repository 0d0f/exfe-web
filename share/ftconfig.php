// configs
window._ENV_ = {
    APP_MODE: '<?php echo JS_DEBUG ? 'development' : 'production'; ?>'
  , MAP_KEY: '<?php echo GOOGLE_MAP_KEY; ?>'
  , api_url: '<?php echo API_URL; ?>/v2'
  , apiv3_url: '<?php echo API_URL; ?>/v3'
  , img_url: '<?php echo IMG_URL; ?>'
  , site_url: '<?php echo SITE_URL; ?>'
  , streaming_api_url: '<?php echo STREAMING_API_URL; ?>'
  , app_scheme: '<?php echo APP_SCHEME; ?>'
  , timestamp: <?php echo Time(); ?>
  , backgrounds: <?php echo json_encode($this->getVar('backgrounds')); ?>
  , location: <?php echo json_encode($this->getVar('location')); ?>
  , photo_providers: ['facebook', 'dropbox', 'flickr', 'instagram']
  , timevalid: Math.abs(Math.round(+new Date() / 1000) - <?php echo Time(); ?>) < 15 * 60
};
