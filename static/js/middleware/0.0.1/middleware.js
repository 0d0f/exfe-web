define('middleware', function (require, exports, module) {
  var Api = require('api')
    , Bus = require('bus')
    , Store = require('store');

  var middleware = module.exports = {};

  /**
   * Rules:
   *      1. 先检查 localStorage `authorization`
   *      2. 检查 header.meta.name = 'authorization'
   *
   *  本地缓存已经有 `authorization`，则其他 `token` 身份进来的都为 `browsing identity`
   *
   */
  middleware.basicAuth = function (req, res, next) {
    var session = req.session;

    // 清掉上次 `browsing` 数据
    //if (session.browsing_authorization) {
      //delete session.browsing_authorization;
    //}
    //if (session.browsing_user) {
      //delete session.browsing_user;
    //}

    // Step 1
    var authorization = Store.get('authorization')
      , user = Store.get('user');

    // Step 2
    var authMeta = getAuthFromHeader();

    if (authorization && !authMeta) {
      session.authorization = authorization;
      session.user = user;
    }

    else if (!authorization && authMeta) {
      Store.set('authorization', session.authorization = authMeta.authorization);
      Store.set('user', session.user = null);
      Store.set('oauth', session.oauth = {
        type: authMeta.type,
        following: authMeta.following,
        identity_id: authMeta.identity_id
      });
    }

    else if (authorization && authMeta) {
      if (authorization.token !== authMeta.authorization.token
          && authorization.user_id !== authMeta.authorization.user_id) {
        Store.set('oauth', session.oauth = {
          type: authMeta.type,
          following: authMeta.following,
          identity_id: authMeta.identity_id
        });
        Store.set('user', session.user = null);
        Store.set('authorization', session.authorization = authMeta.authorization);
      }
    }

    //else if (!authorization && !authMeta) {
    //}

    next();
  };



  // errorHandler
  middleware.errorHandler = function (req, res, next) {
    res.redirect('/404');
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
