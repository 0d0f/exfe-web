define('editable', [], function (require, exports, module) {

  // Helpers
  // -------

  var Widget = require('widget');
  var $ = jQuery('jquery');
  var $BODY = $(document.body);

  // Editable 编辑模块
  // -----------------

  var Editable = Widget.extend({

    options: {

      srcNode: '[data-widget="editable"]',

      template: '<input type="text" />'

    },

    init: function () {
      var instance = this;

      $BODY.on('', instance.options.srcNode);

    },

    cancel: function () {
      this.emit('cancel');
    },

    save: function () {
      this.emit('save');
    },

  });

  return Editable;

});
