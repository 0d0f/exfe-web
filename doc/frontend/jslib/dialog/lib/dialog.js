define('dialog', [], function (require, exports, module) {
  /**
   *
   * Dependence:
   *  - jQuery
   *  - Emitter
   *
   * Thanks to:
   *  - https://github.com/twitter/bootstrap/blob/master/js/bootstrap-modal.js
   *  - https://github.com/defunkt/facebox/blob/master/src/facebox.js
   *  - https://github.com/makeusabrew/bootbox/blob/master/bootbox.js
   */

  var $ = require('jquery');
  var Widget = require('widget');

  var BODY = document.body;

  var Dialog = Widget.extend({

    options: {

      template: '',

      srcNode: '',

      parentNode: BODY

    },

    init: function () {
    },

    render: function () {
      this.parentNode = this.options.parentNode;
      this.element.appendTo(this.parentNode);
      this.sync();
      return this;
    },

    sync: function () {
      var options = this.options;
      this.emit('sync');

      this.element.css({
        width: options.width,
        height: options.height,
        zIndex: options.zIndex,
        minHeight: options.minHeight
      });

      return this;
    },

    show: function () {
      this.emit('show');
      this.element.show();
      return this;
    },

    hide: function () {
      this.element.hide();
      this.emit('hidden');
      return this;
    }

  });

  // Helper
  // ------

  return Dialog;
});
