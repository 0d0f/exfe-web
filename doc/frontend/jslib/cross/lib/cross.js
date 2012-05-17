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
      })
        .done(function (data) {
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

  var cross_defe = function (data) {
    var match = window.location.href.match(/id=([0-9a-z]+)/);
    var cross_id;

    // 先简单处理
    if (!match) {
      alert('Not found `cross_id`');
      return false;
    }
    cross_id = match[1];

    return $.ajax({
      url: 'http://api.localexfe.me/v2/crosses/' + cross_id + '?token=' + data.token,
      type: 'GET',
      dataType: 'JSON',
      xhrFields: {withCredentials: true}
    })
      .done(function (data) {
        if (data.meta.code === 200) {
          var response = data.response;
          var cross = response.cross;

          // 设置背景
          var bkWidget = cross.widget[0];
          $('.cross-background')
            .css('background-image', 'url(/img/xtb/' + bkWidget.image + '_web.jpg)');

          // 填充标题
          $('.cross-title')
            .find('h1').html(cross.title);

          // 分割段落
          var description = '<p>' + cross.description.split(/\s+\n/).join('</p><p>') + '</p>';
          // 填充 Descritpion
          $('.cross-description')
            .html(description);

          // 填充时间
          //$('.cross-date').find('h2').html();
          $('.cross-date').find('time').html(cross.time.origin);

          // 填充地点
          $('.cross-place').find('h2').html(cross.place.title);
          $('.cross-place').find('address').html(cross.place.description);
        }
      });
  };

  signin_defe.then([cross_defe]);

});
