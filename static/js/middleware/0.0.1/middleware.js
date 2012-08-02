define('middleware', function (require, exports, module) {
  var Api = require('api')
    , Bus = require('bus')
    , Store = require('store');

  var middleware = module.exports = {};

  middleware.basicAuth = function (req, res, next) {
    if (req.session.checked) {
      next();
      return;
    }

    var signin, token, user_id, content;

    var meta_signin = document.getElementsByName('signin-token')[0];
    if (meta_signin) {
      content = JSON.parse(meta_signin.content);
      Store.set('signin', signin = content.signin);
      document.getElementsByTagName('head')[0].removeChild(meta_signin);
      // TODO: 总感觉放这里不合适，后面再想想看 {{{
      Bus.emit('xapp:usertoken', signin.token, signin.user_id, 2);
      Bus.emit('xapp:usersignin');
      // }}}
    }

    signin = signin || Store.get('signin');
    token = (signin && signin.token) || false;
    user_id = (signin && signin.user_id) || false;

    if (!token) {
      next();
      return;
    }

    req.session.user_id = user_id;

    req.session.token = token;

    req.session.checked = true;

    next();
    /*
    Api.request('checkAuthorization'
      , {
        type: 'POST',
        data: {
          token: token
        }
      }
      , function (data) {
        console.dir(data);
        req.session.identities_status = data.identities_status;
        req.session.password = data.password;
        next();
      }
    );
    */
  };

});
