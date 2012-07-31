define('api', [], function (require, exports, module) {

  // import $.ajax
  var $ = require('jquery');
  var Config = require('config');

  // urls of API
  // 区分大小写
  var urls = {

    // base url
    base_url: Config.api_url,


    // Users
    signin: '/users/signin',

    getRegistrationFlag: '/users/getRegistrationFlag',

    checkAuthorization: '/users/checkAuthorization',

    // -------------- Must Use Token ---------------------- //
    getUser: '/users/:user_id',

    signout: '',

    updateUser: '/users/update',

    setPassword: '/users/:user_id/setPassword',

    crosses: '/users/:user_id/crosses',

    crosslist: '/users/:user_id/crosslist',

    addIdentity: '/users/addIdentity',

    deleteIdentity: '/users/deleteIdentity',

    setDefaultIdentity: '/users/setDefaultIdentity',


    // Identity
    getIdentityById: '/identities/:identity_id',

    complete: '/identities/complete',

    getIdentity: '/identities/get',

    updateIdentity: '/identities/:identity_id/update',


    // Cross
    getCross: '/crosses/:cross_id',

    gather: '/crosses/gather',

    editCross: '/crosses/:cross_id/edit',


    // Exfee
    rsvp: '/exfee/:exfee_id/rsvp',

    editExfee: '/exfee/:exfee_id/edit',


    // Conversation
    conversation: '/conversation/:exfee_id',

    addConversation: '/conversation/:exfee_id/add',


    // Verify
    // 登陆前
    verifyIdentity: '/users/verifyIdentity',

    // 登陆后
    verifyUserIdentity: '/users/verifyUserIdentity',

    forgotPassword: '/users/forgotPassword',

    avatarUpdate: '/avatar/update',

    // Cross Token
    // ep:
    //  http -f post api.local.exfe.com/v2/crosses/GetCrossByInvitationToken?token="249ceff8cbdc3fd20ce95ea391739b59" invitation_token="d8983af0ff726256851e0a4e5c41d6db"
    getCrossByInvitationToken: '/crosses/getCrossByInvitationToken',

    // follow exfe
    followExfe: '/oauth/followExfe'
  };

  // Not Use Token
  var ignore = 'signin getRegistrationFlag checkAuthorization verifyIdentity verifyUserIdentity forgotPassword getIdentityById getCrossByInvitationToken';

  var Api = {

    // request token
    _token: null,

    setToken: function (token) {
      this._token = token;
    },

    getToken: function () {
      return this._token;
    },

    /**
     *
     * Usage:
     *
     *    Api.request('/users/:user_id/crosslist?token=xxx'
     *      , {
     *        // url params
     *        params {
     *          more: true
     *        },
     *        // url resources
     *        resources: {
     *          user_id: 233
     *        },
     *        type: 'POST',
     *        data: {}
     *      }
     *      , function done() {}
     *      , function fail() {}
     *    );
     *
     */
    request: function (channel, options, done, fail) {
      var url = urls[channel]
        , k
        , params = options.params
        , resources = options.resources;

      if (!url) { return; }

      if (ignore.split(' ').indexOf(channel) === -1) {

        if (!Api._token) { return; }

        if (!params) {
          params = {};
        }

        params.token = Api._token;
      }

      if (params) {
        params = $.param(params);
        url += params ? '?' + params : '';
      }

      if (resources) {
        for (k in resources) {
          url = url.replace(':' + k, encodeURIComponent(resources[k]));
        }
      }

      options.url = urls.base_url + url;

      delete options.params;
      delete options.resources;

      return _ajax(options, doneCallback(done, fail), fail);
    }
  }

  // helper

  function doneCallback(done, fail) {
    return function cb() {
      var args = _slice(arguments), data = args[0];
      // status-code 200 success
      if (data && data.meta.code === 200) {
        args[0] = data.response;

        if (done) {
          done.apply(this, args);
        }

      } else {
        if (fail) {
          fail.apply(this, args);
        }
      }
      return this;
    }
  }

  var __slice = Array.prototype.slice;

  function _slice(args) {
    return __slice.call(args, 0);
  }

  function _extend(r, s) {
    var k;
    for (k in s) {
      r[k] = s[k];
    }
    return r;
  }

  // See jQuery.ajax's settings
  // http://api.jquery.com/jQuery.ajax/
  var defaultOptions = {
    type: 'GET',
    dataType: 'JSON',
    // cors: Cross Origin Resource Share
    xhrFields: { withCredentials: true }
  };

  function _ajax(options, done, fail) {
    var o = {}, dfd;

    _extend(o, defaultOptions);

    _extend(o, options);

    dfd = $.ajax(o)
      // done callback
      .done(done)
      // fail callback
      .fail(fail);

    return dfd;
  }

  return Api;

});
