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

          // 开/关 模态窗口
        , backdrop: false

        , template: '<div class="panel" tabindex="-1" role="panel"><div class="panel-header"></div><div class="panel-body"></div><div class="panel-footer"></div></div>'

        , parentNode: null

        , srcNode: null

        , events: null
      }

    , init: function () {
      }

    , sync: function () {
        this.emit('sync');
        return this;
      }

    , render: function () {
        var options = this.options;
        this.parentNode = options.parentNode;
        this.srcNode = options
        delete options.parentNode;
        delete options.srcNode;

        this.on('escape', $.proxy(this.escapable, this));
      }

    , escapable: function () {
        var self = this;
        $(document).on('keydown.panel', function (e) {
          if (27 !== e.which) {
            return;
          }
          self.emit('escape');
        });
      }

    , show: function () {

        this.emit('showBefore');
        this.escapable();

        this.element.appendTo(this.parentNode);

        this.emit('showAfter');
        return self;
      }

    , hide: function (ms) {
        var self = this;
        $(document).off('keydown.panel');

        // prevent thrashing
        self.hiding = true;

        // duration
        if (ms) {
          setTimeout(function () {
            self.hide();
          }, ms);
        }

        // hide / remove
        self.element.addClass('hide');
        if (self._effect) {
          setTimeout(function () {
            self.destory();
          }, 500);
        }
        else {
          self.destory();
        }

        return this;
      }

    , close: function () {}

    , effect: function () {}

    , destory: function () {
      }
  });


  return Panel;

});
