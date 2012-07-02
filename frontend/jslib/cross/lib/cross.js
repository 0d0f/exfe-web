define(function (require, exports, module) {
  var $ = require('jquery');
  var R = require('rex');
  var Api = require('api');
  var Bus = require('bus');
  var Store = require('store');
  var Handlebars = require('handlebars');

  var cross_defe = function (data) {
    if (!data) return;

    var match = window.location.href.match(/!([0-9a-zA-Z]+)/);

    var cross_base62_id;

    // 先简单处理
    if (!match) {
      alert('Not found `cross_id`');
      return false;
    }

    cross_base62_id = match[1];

    return Api.request('getCross'
      , {
        resources: {cross_id: cross_base62_id},
        params: {by_base62_id: 1}
      }
      , function (data) {
        var cross = data.cross;

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
    );

  };

  var SIGN_IN_SUCCESS = 'app:signinsuccess';
  Bus.on(SIGN_IN_SUCCESS, function (data) {
    cross_defe(data);
  });
});
