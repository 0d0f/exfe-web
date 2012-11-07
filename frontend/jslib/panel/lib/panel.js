define('panel', function (request, exports, module) {

  var $ = require('jquery');
  var Widget = require('widget');

  /*
   * HTML
   *
   *    div.panel tabindex="-1" role="panel"
   *      > div.panel-header
   *      > div.panel-body
   *      > div.panel-footer
   */

  var Panel = Widget.extend({

      options: {

          // keyboard
          keyboard: true

          // 开/关 拟态
        , backdrop: false

        , templates: '<div class="panel" tabindex="-1" role="panel"><div class="panel-header"></div><div class="panel-body"></div><div class="panel-footer"></div></div>'

        , parentNode: null

        , srcNode: null

      }

    , init: function () {}

    , sync: function () {}

    , render: function () {}

    , destory: function () {}

    , show: function () {}

    , hide: function () {}

  });


  return Panel;

});
