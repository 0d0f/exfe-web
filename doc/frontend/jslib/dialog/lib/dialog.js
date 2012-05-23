define('dialog', [], function (require, exports, module) {
  /**
   *
   * Dependence:
   *  - jQuery
   *  - Widget
   *
   * Thanks to:
   *  - https://github.com/twitter/bootstrap/blob/master/js/bootstrap-modal.js
   *  - https://github.com/defunkt/facebox/blob/master/src/facebox.js
   *  - https://github.com/makeusabrew/bootbox/blob/master/bootbox.js
   */

  var $ = require('jquery');
  var Widget = require('widget');

  var $BODY = document.body;

  /*
   * HTML
   *
   *    div.modal
   *      > div.modal-header
   *        > button.close
   *        > h3
   *      > div.modal-main
   *        > div.modal-body
   *        > div.modal-footer
   */

  var Dialog = Widget.extend({

    options: {

      // 遮罩层结构
      backdropNode: '<div id="js-modal-backdrop" class="modal-backdrop" />'

      // 开/关 遮罩
    , backdrop: false

      // 弹出窗口基础结构
    , template: '<div class="modal"><div class="modal-header"><button class="close" data-dismiss="dialog">×</button><h3></h3></div><div class="modal-main"><div class="modal-body"></div><div class="modal-footer"></div></div></div>'

      // 父节点，插入方式 appendTo
    , parentNode: $BODY

    // source target node
    , srcNode: ''

      //
    , viewData: null

    // 生命周期，1: 每次都要重建，0: 只需一次创建，无删除动作
    , lifecycle: true

    },

    init: function () {
    },

    render: function () {
      var data;

      this.parentNode = this.options.parentNode;

      if ((data = this.options.viewData)) {
        var title = data.title
          , body = data.body
          , footer = data.footer
          , others = data.others
          , cls = data.cls;
        if (cls) this.element.addClass(cls);
        if (title) this.element.find('h3').eq(0).html(title);
        if (body) this.element.find('div.modal-body').html(body);
        if (footer) this.element.find('div.modal-footer').html(footer);
        if (others) this.element.find('div.modal-main').append(others);
      }

      if (this.options.backdrop) {
        var backdropNode = $(this.options.backdropNode);
        backdropNode.appendTo(this.parentNode).addClass('in');
      }

      this.element.appendTo(this.parentNode);

      this.element.on('click.dismiss.dialog', '[data-dismiss="dialog"]', $.proxy(this.hide, this));

      this.sync();

      return this;
    },

    sync: function () {

      this.emit('sync');

      return this;
    },

    show: function (data) {
      this.emit('show', data);
      if (this.options.backdrop) {
        $('#js-modal-backdrop').removeClass('hide');
      }
      this.element.removeClass('hide');
      return this;
    },

    hide: function (data) {
      if (this.options.backdrop) {
        $('#js-modal-backdrop').addClass('hide');
      }
      this.element.addClass('hide');
      this.emit('hidden', data);

      // if (this.options.lifecycle) {}
      return this;
    }

  });

  // Helper
  // ------

  return Dialog;
});
