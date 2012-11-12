define('panel', function(require, exports, module) {

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
        this.render();
      }

    , render: function () {
        var options = this.options;
        this.parentNode = options.parentNode;
        this.srcNode = options.srcNode;
        delete options.parentNode;
        delete options.srcNode;

        this.on('escape', $.proxy(this.hide, this));

        this.on('showBefore', $.proxy(this.showBefore, this));
        this.on('showAfter', $.proxy(this.showAfter, this));

        this.element.on('destory.widget', $.proxy(this.destory, this));

        return this;
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

        //this.element.css({ });

        this.emit('showAfter');

        return this;
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

    , effect: function (type) {
        this._effect = type;
        this.element.addClass(type);
        return this;
      }

    , _destory: function () {
        this.undelegateEvents();
        Widget.superclass.destory.call(this);
      }
  });


  return Panel;
});
