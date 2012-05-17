define('widget', [], function (require, exports, module) {

  // Widget
  // ------
  // UI 组件的基类，主要负责 View 层的管理

  var $ = require('jquery');
  var Base = require('Base');

  var Widget = Base.extend({
    // 初始化
    initialize: function (options) {
      this.cid = uuid();
      this.initOptions(options);
      this.parseElement();
    },

    initOptions: function (options) {},

    // 外部接口，方便子类初始化
    init: function () {},

    // 外部接口，将 widget 渲染到页面上
    render: function () {
      return this;
    },

    delegateEvents: function (events) {},

    undelegateEvents: function () {
      this.element.off('.delegateEvents' + this.cid);
    },

    // 在当前 widget 内寻找节点
    $: function (selector) {
      return this.element.find(selector);
    },

    destory: function () {
      Widget.__super__.destory.call(this);
      this.undelegateEvents();
    }
  });

  return Widget;

  // Helpers
  // ------

  var idCounter = 0;
  function uuid() {return 'widget' + idCounter++;}

});
