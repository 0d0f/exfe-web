/**
 * X webapp Bootstrap!
 */
define(function (require, exports, module) {
  var $ = require('jquery');
  var xApp = require('odof').createApplication();

  // home
  xApp.get('/', gotoHome);

  // cross
  xApp.get('/#!:id', gotoCross);
  xApp.get(/^\/#([\w\d]+)\/(@?[\w\d]+)/, gotoCross);

  // profile
  xApp.get(/^\/#([^@\/\s\!]+)@([^@\/\s\.]+)/, gotoProfile);

  // 启动 x webapp
  xApp.run();

  // goto home
  function gotoHome(ctx, nxt) {
    console.log('home');
    $.ajax({
      url: '/static/views/index.html',
      success: function (data) {
        console.log(data);
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
