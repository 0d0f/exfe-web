define(function (require, exports, module) {
  var $ = require('jquery');
  var Store = require('store');
  var Handlebars = require('handlebars');
  var R = require('rex');

  var signin_defe = function () {
    var user_id = Store.get('user_id')
      , token = Store.get('token')
      , dfd;

    // 先检查下 本地缓存
    if (user_id && token) {
      dfd = $.Deferred();
      // 重新确定数据
      dfd.resolve({
        user_id: user_id,
        token: token
      });
    } else {
      dfd = $.ajax({
        url: 'http://api.localexfe.me/v2/users/signin',
        type: 'POST',
        dataType: 'JSON',
        xhrFields: {withCredentials: true},
        data: {
          'external_id': 'cfd@demox.io',
          'provder': 'email',
          'password': 'd'
        }
      }).done(function (data) {
        if (data.meta.code === 200) {
          user_id = data.resolve.user_id;
          token = data.resolve.token;
          Store.set('user_id', user_id);
          Store.set('token', token);
          dfd.resolve({
            user_id: user_id,
            token: token
          });
        } else {
          alert('Sign In Fail.');
        }
      });
    }
    return dfd;
  }();

  // 用户信息,包括多身份信息
  var identities_defe = function (data) {
    // 返回一个 promise 对象
    return $.ajax({
      url: 'http://api.localexfe.me/v2/users/' + data.user_id + '?token=' + data.token,
      type: 'GET',
      dataType: 'JSON',
      xhrFields: { withCredentials: true}
    })
      .done(function (data) {
        if (data.meta.code === 200) {
          console.dir(data);
        }
      });

  };

  // crosses 信息
  var crosses_defe = function (data) {
    // 返回一个 promise 对象
    return $.ajax({
      url: 'http://api.localexfe.me/v2/users/' + data.user_id + '/crosses?token=' + data.token,
      type: 'GET',
      dataType: 'JSON',
      xhrFields: { withCredentials: true}
    })
      .done(function (data) {
        if (data.meta.code === 200) {
          var now = +new Date;

          //console.dir(data.response.crosses);

          var invitations = [];

          // 时间倒序
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

  };


  signin_defe.then([identities_defe, crosses_defe]);

});
