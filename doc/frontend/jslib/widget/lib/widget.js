define('widget', [], function (require, exports, module) {

  // Widget
  // ------
  // UI 组件的基类，主要负责 View 层的管理

  var $ = require('jquery');
  var Base = require('base');

  var Widget = Base.extend({
    options: {
      template: '<div></div>'
    },
    // 初始化
    initialize: function (options) {
      this.cid = uuid();
      this.initOptions(options);
      this.parseElement();

      this.delegateEvents();
      this.init();
    },

    initOptions: function (options) {
      this.setOptions(options);
    },

    // 获取元素
    parseElement: function () {
      var element = this.element
        , template = this.options.template;

      if (element) {
        this.element = element instanceof $ ? element : $(element);
      } else if (template) {
        this.element = $(template);
      }

      if (!this.element) {
        throw 'element is invalid';
      }
    },

    // 外部接口，方便子类初始化
    init: function () {
    },

    // 外部接口，将 widget 渲染到页面上
    render: function () {
      return this;
    },

    delegateEvents: function (events) {
      events || (events = getValue(this, 'events'));
      if (!events) return;
      this.undelegateEvents();

      var key, method, match, eventName, selector;
      for (key in events) {
        method = this[key];

        if (!method) throw 'Method "' + events[key] + '" does not exist';

        match = key.match(delegateEventSplitter);
        eventName = match[1];
        selector = match[2] || null;

        eventName += '.delegateEvents' + this.cid;
        this.element.on(eventName, selector, proxy(method, this));
      }
    },

    undelegateEvents: function () {
      this.element.off('.delegateEvents' + this.cid);
    },

    // 在当前 widget 内寻找节点
    $: function (selector) {
      return this.element.find(selector);
    },

    destory: function () {
      this.undelegateEvents();
      // remove `element`
      this.element.remove();
      Widget.__super__.destory.call(this);
    }
  });


  // Helpers
  // ------

  // 事件代理参数中，'event selector' 的分隔符
  var delegateEventSplitter = /^(\S+)\s*(.*)$/;

  var guid = 1;

  function uuid() {
    return 'widget-' + guid++;
  }

  function isFunction(f) {
    return typeof f === 'function';
  }

  function getValue(o, prop) {
    var f = o[prop];
    if (o && f)  {
      return isFunction(f) ? o[prop]() : f;
    }
  }

  function proxy(fn, context) {
    var f = function (event) {
      return fn.call(this, event);
    };
    return f;
  }


  return Widget;

});
