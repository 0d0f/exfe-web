/**
 * X webapp Bootstrap!
 */
define(function (require, exports, module) {
  var $ = require('jquery');
  var Bus = require('bus');
  var Config = require('config');

  var XAPP_LOGIN = 'xapp:login';
  var XAPP_CANNOT_LOGIN = 'xapp:cannotlogin';

  // create X webapp
  var xApp = require('odof').createApplication();

  xApp.on('unhandled', function (ctx) {
    console.log('not found');
  });

  // home
  xApp.get('/', userLogin, gotoHome);

  // cross
  xApp.get('/#!:id', gotoCross);
  xApp.get(/^\/#([\w\d]+)\/(@?[\w\d]+)/, gotoCross);

  // profile
  xApp.get(/^\/#([^@\/\s\!]+)@([^@\/\s\.]+)/, userLogin, gotoProfile);

  // 启动 x webapp
  xApp.run();

  // user login
  function userLogin(ctx, nxt) {
    Bus.on(XAPP_CANNOT_LOGIN, function () {
      nxt();
    });
    Bus.emit(XAPP_LOGIN);
  }

  // goto home
  function gotoHome(ctx, nxt) {
    console.log('home');
    $(document).find('head').eq(0).append('<link rel="stylesheet" type="text/css" href="/static/_css/home.css?t=' + Config.timestamp + '" />');
    $.ajax({
      url: '/static/views/index.html',
      success: function (data) {
        $('#home').append(data);
      }
    });
  }

  // goto cross
  function gotoCross(ctx, nxt) {
    console.log('cross');
  }

  // goto profile
  function gotoProfile(ctx, nxt) {
    console.log('profile');
  }
});
