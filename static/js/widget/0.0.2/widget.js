define('widget', function (require, exports, module) {

  // Widget
  // ------
  // UI 组件的基类，主要负责 View 层的管理

  var $ = require('jquery');
  var Base = require('base');

  var Widget = Base.extend({

    options: {
      template: '<div />',

      // 事件代理 格式：
      //  {
      //    'click .button': 'save',
      //    'click .open': function (e) { ... }
      //  }
      events: null
    },

    // 初始化
    initialize: function (options) {
      this.cid = guid();
      this.initOptions(options);
      this.parseElement();

      this.delegateEvents();
      this.init();
    },

    initOptions: function (params) {
      this.setOptions(params);

      //delete params.options;

      setAttrOptions(this, params);
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

      this.element.attr('data-widget-id', this.cid);
    },

    // 外部接口，方便子类初始化
    init: function () {
    },

    // 外部接口，将 widget 渲染到页面上
    render: function () {
      return this;
    },

    delegateEvents: function (events) {
      events || (events = getValue(this.options, 'events'));
      if (!events) { return; }
      this.undelegateEvents();

      var key, method, match, eventName, selector;
      for (key in events) {
        method = events[key] || this[key];

        if (!method) { throw 'Method "' + events[key] + '" does not exist'; }

        match = key.match(delegateEventSplitter);
        eventName = match[1];
        selector = match[2] || null;

        eventName += '.delegateEvents' + this.cid;
        this.element.on(eventName, selector, proxy(method, this)); // $.proxy
      }
    },

    undelegateEvents: function () {
      this.element.off('.delegateEvents' + this.cid);
    },

    // 在当前 widget 内寻找节点
    $: function (selector) {
      return this.element.find(selector);
    },

    _destory: function () {
      this.undelegateEvents();
      Widget.superclass.destory.call(this);
    }
  });


  // Helpers
  // ------

  // 事件代理参数中，'event selector' 的分隔符
  var delegateEventSplitter = /^(\S+)\s*(.*)$/;

  var uuid = 0;

  function guid() {
    return 'widget-' + uuid++;
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

  function setAttrOptions(r, s) {
    var k;
    for (k in s) {
      if (k !== 'options') { r[k] = s[k]; }
    }
  }

  function proxy(f, c) {
    if (!f) { return; }
    return function cb(e) {
      return f.call(c, e);
    };
  }


  return Widget;

});
