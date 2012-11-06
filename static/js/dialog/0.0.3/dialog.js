define('dialog', function (require, exports, module) {
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

  var $BODY = $(document.body);
  var $TMP = $('#app-tmp');

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

      // keyboard
      keyboard: true

      // 开/关 遮罩
    , backdrop: false

      // 弹出窗口基础结构
    , template: '<div class="modal"><div class="modal-header"><button class="close" data-dismiss="dialog">×</button><h3></h3></div><div class="modal-main"><div class="modal-body"></div><div class="modal-footer"></div></div></div>'

      // 父节点，插入方式 appendTo
    , parentNode: $TMP

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
      this.srcNode = this.options.srcNode;

      if ((data = this.options.viewData)) {
        var title = data.title
          , body = data.body
          , footer = data.footer
          , others = data.others
          , cls = data.cls;
        this.element.attr('tabIndex', -1);
        if (cls) { this.element.addClass(cls); }
        if (title) { this.element.find('h3').eq(0).html(title); }
        if (body) { this.element.find('div.modal-body').html(body); }
        if (footer) { this.element.find('div.modal-footer').html(footer); }
        if (others) { this.element.find('div.modal-main').append(others); }
      }

      this.element.appendTo(this.parentNode);

      this.element.on('click.dismiss.dialog', '[data-dismiss="dialog"]', $.proxy(this.hide, this));

      this.element.on('destory.widget', $.proxy(this.destory, this));

      this.sync();

      return this;
    },

    sync: function () {

      this.emit('sync');

      return this;
    },

    show: function (data) {
      // 临时
      $TMP.find('.modal').addClass('hide');

      this.emit('showBefore', data);

      this.element.removeClass('hide');

      this.isShown = true;
      escape.call(this);
      backdrop.call(this);

      this.element.addClass('in');

      // after
      this.emit('showAfter', data);

      return this;
    },

    hide: function (e) {
      // before
      this.emit('hideBefore', e);

      this.element.addClass('hide');

      this.isShown = false;
      escape.call(this);
      backdrop.call(this);

      this.element.removeClass('in');

      // if (this.options.lifecycle) {}
      // after
      this.emit('hideAfter', e);

      if (e && 'stopPropagation' in e) {
        e.stopPropagation();
        e.preventDefault();
      }
      return this;
    },

    offSrcNode: function () {
      var srcNode = this.options.srcNode;
      if (srcNode) {
        srcNode.data('dialog', null);
        // <div data-destory="true" />
        if (srcNode.data('destory')) {
          srcNode.remove();
        }
      }
    },

    destory: function () {
      var $e = this.element;
      var dataType = this.options.srcNode.data('dialog-type');
      this.offSrcNode();
      $BODY
        .find('[data-dialog-type="' + dataType + '"]')
        .not($e)
        .removeData('dialog');
      this._destory();
      $e.remove();
    }

  });

  // Helper
  // ------

  var backdropNode = '<div id="js-modal-backdrop" class="modal-backdrop" />'
  function backdrop(callback) {
    // 遮罩层结构
    var that = this;

    if (this.isShown && this.options.backdrop) {
      this.$backdrop = $(backdropNode).appendTo(this.parentNode);

      this.$backdrop.click($.proxy(this.hide, this));

      this.$backdrop.addClass('in');
    } else if (!this.isShown && this.$backdrop) {
      this.$backdrop.removeClass('in');
      removeBackdrop.call(this);
    }
  }

  function removeBackdrop() {
    this.$backdrop.remove();
    this.$backdrop = null;
  }

  function escape() {
    var that = this;
    if (this.isShown && this.options.keyboard) {
      $BODY.on('keyup.dismiss.modal', function (e) {
        if (e.which === 27) {
          e.stopPropagation();
          e.preventDefault();
          that.hide();
          return false;
        }
      });
    } else if (!this.isShown) {
      $BODY.off('keyup.dismiss.modal');
    }
  }

  return Dialog;
});
