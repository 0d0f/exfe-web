define('middleware', function (require, exports, module) {
  var Api = require('api')
    , Bus = require('bus')
    , Store = require('store')
    , $ = require('jquery');

  var middleware = module.exports = {};

  /**
   * Rules:
   *      1. 先检查 localStorage `authorization`
   *      2. 检查 header.meta.name = 'authorization'
   *
   *  本地缓存已经有 `authorization`，则其他 `token` 身份进来的都为 `browsing identity`
   */
  middleware.basicAuth = function (req, res, next) {
    middleware.FixedFaceBookURL(req, res, next);
    var session = req.session;

    // Step 1
    var authorization = Store.get('authorization')
      , user = Store.get('user');

    // Step 2
    var authMeta = getAuthFromHeader();

    if (authorization && (!authMeta || (authMeta && !authMeta.authorization))) {
      session.authorization = authorization;
      session.user = user;
    }

    else if (!authorization && authMeta && authMeta.authorization && authMeta.data && !authMeta.event) {
      Store.set('oauth', session.oauth = {
        identity: authMeta.data.identity,
        following: authMeta.data.identity.provider === 'twitter' ? !!authMeta.data.twitter_following : false,
        // status: connected, new, revoked
        identity_status: authMeta.data.identity_status
      });

      delete session.user;
      Store.remove('user');
      Store.set('authorization', session.authorization = authMeta.authorization);
    }

    else if (authorization && authMeta && authMeta.authorization && authMeta.data && !authMeta.event) {
      if (authorization.user_id === authMeta.authorization.user_id
         && authorization.token !== authMeta.authorization.token
         ) {
        authorization.token = authMeta.authorization.token;
        Store.set('authorization', session.authorization = authorization);
      }
      else if (authorization.user_id !== authMeta.authorization.user_id
          && authorization.token !== authMeta.authorization.token
          && authMeta.identity
          ) {
        Store.set('oauth', session.oauth = {
          identity: authMeta.data.identity,
          following: authMeta.data.identity.provider === 'twitter' ? !!authMeta.data.twitter_following : false,
          // status: connected, new, revoked
          identity_status: authMeta.data.identity_status
        });

        delete session.user;
        Store.remove('user');
        Store.set('authorization', session.authorization = authMeta.authorization);
      }
    }

    if (authMeta) {

      // 保存回调事件
      if (authMeta.event) {
        session.event = JSON.parse(authMeta.event);
        session.event.data = authMeta.data
      }

      if (authMeta.verification_token) {
        session.verification_token = authMeta.verification_token;
      }

      if (authMeta.refere && authMeta.refere !== window.location.protocol + '//' + window.location.hostname + '/') {
        window.location.href = authMeta.refere || '/';
      }
    }

    next();
  };



  // errorHandler
  middleware.errorHandler = function (req, res, next) {
    var url = /^\/404/;
    if (url.exec(window.location.pathname)) {
      Bus.emit('app:page:home', false, true);
      var authorization = Store.get('authorization');
      Bus.emit('app:page:usermenu', !!authorization);
      if (authorization) {
        var user = Store.get('user');
        Bus.emit('app:usermenu:updatenormal', user);
        Bus.emit('app:usermenu:crosslist'
          , authorization.token
          , authorization.user_id
        );
      }
      return;
    }
    res.location('/404');
  };


  // remove #app-tmp content
  middleware.cleanupAppTmp = function (req, res, next) {
    var $TMP = $('#app-tmp')
      , $widgets = $TMP.find('[data-widget-id]');
    $widgets.trigger('destory.widget');
    next();
  };


  // fixed `Facebook` callback url
  // http://stackoverflow.com/questions/7485111/weird-url-appended?lq=1
  // http://stackoverflow.com/questions/7131909/facebook-callback-appends-to-return-url
  // https://developers.facebook.com/blog/post/552/
  middleware.FixedFaceBookURL = function (req, res, next) {
    if (window.location.hash === '#_=_') {
      window.location.hash = '';
      req.updateUrl();
    }
  };

  // Helers:
  // ----------------------------
  function getAuthFromHeader() {
    var header = document.getElementsByTagName('head')[0]
      , meta = document.getElementsByName('authorization')[0]
      , authMeta = null;

    if (meta) {
      authMeta = JSON.parse(meta.content);
      header.removeChild(meta);
    }

    return authMeta;
  }

});
