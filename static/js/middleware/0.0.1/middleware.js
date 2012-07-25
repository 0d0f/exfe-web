define('middleware', function (require, exports, module) {
  var Api = require('api')
    , Store = require('store');

  var middleware = module.exports = {};

  middleware.basicAuth = function (req, res, next) {
    if (req.session.checked) {
      next();
      return;
    }

    var signin = Store.get('signin')
      , token = (signin && signin.token) || false
      , user_id = (signin && signin.user_id) || false;

    if (!token) {
      next();
      return;
    }

    req.session.user_id = user_id;

    req.session.token = token;

    req.session.checked = true;

    Api.request('checkAuthorization'
      , {
        type: 'POST',
        data: {
          token: token
        }
      }
      , function (data) {
        req.session.password = data.password;
        next();
      }
    );
  };

});
