define(function (require, exports, module) {
  var $ = require('jquery');
  var Store = require('store');
  var Handlebars = require('handlebars');
  var R = require('rex');

  var signin_defe = $.ajax({
    url: 'http://api.localexfe.me/v2/users/signin',
    type: 'POST',
    dataType: 'JSON',
    xhrFields: {withCredentials: true},
    data: {
      'external_id': 'cfd@demox.io',
      'provder': 'email',
      'password': 'd'
    }
  });

  // 登陆，保存 user_id & token
  signin_defe.done(function (data) {
    if (data.meta.code === 200) {
      Store.set('user_id', data.response.user_id);
      Store.set('token', data.response.token);
    }
  });

  var crosses_defe = function (data) {
    var user_id = Store.get('user_id');
    var token = Store.get('token');

    // 返回一个 promise 对象
    return $.ajax({
      url: 'http://api.localexfe.me/v2/users/' + user_id + '/crosses?token=' + token,
      type: 'GET',
      dataType: 'JSON',
      xhrFields: { withCredentials: true}
    });
  }();

  crosses_defe.done(function (data) {
    if (data.meta.code === 200) {
      var now = +new Date;

      //console.dir(data.response.crosses);

      var invitations = [];

      data.response.crosses.reverse();
      var jst_crosses = $('#jst-crosses-container');

      // 注册帮助函数, 获取 `ACCEPTED` 人数
      Handlebars.registerHelper('confirmed_nums', function (context) {
        return R.filter(context, function (v) {
          if (v.rsvp_status === 'ACCEPTED') return true;
        }).length;
      });

      // 注册帮助函数, 获取总人数
      Handlebars.registerHelper('total', function (context) {
        return context.length;
      });

      // 注册帮助函数，列出 confirmed identities
      Handlebars.registerHelper('confirmed_identities', function (context) {
        return R(context).map(function (v) {
          return v.identity.name;
        }).join(', ');
      });

      // 注册子模版
      Handlebars.registerPartial('jst-cross-box', $('#jst-cross-box').html())
      // 编译模版
      var s = Handlebars.compile(jst_crosses.html());
      // 填充数据
      var h = s(data.response);
      $('.crosses').append(h);
    }
  });

  signin_defe.then(crosses_defe);

});
