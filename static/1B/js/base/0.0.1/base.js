define('base', [], function (require) {
  // Thanks to:
  //
  // - https://github.com/mootools/mootools-core/blob/master/Source/Class/Class.Extras.js
  // - https://github.com/alipay/arale/blob/master/lib/base/src/options.js


  // Helpers
  // -------
  var EVENT_PREFIX = /^on[A-Z]/;

  var PROTO = Object.__proto__;

  var toString = Object.prototype.toString;

  var isArray = Array.isArray;
  if (!isArray) isArray = function (a) {return toString.call(a) === '[object Array]';}

  function isFunction(f) {
    return toString.call(f) === '[object Function]';
  }

  function isPlainObject(o) {
    return o &&
      // 排除 boolean/string/number/function 等
      // 标准浏览器下，排除 window 等非 JS 对象
      // 注：ie8- 下，toString.call(window 等对象)  返回 '[object Object]'
        toString.call(o) === '[object Object]' &&
      // ie8- 下，排除 window 等非 JS 对象
        ('isPrototypeOf' in o);
  }

  function merge(receiver, supplier) {
    var key, value;

    for (key in supplier) {
      value = supplier[key];

      if (isArray(value)) {
        value = value.slice();
      } else if (isPlainObject(value)) {
        value = merge(receiver[key] || {}, value);
      }

      receiver[key] = value;
    }

    return receiver;
  }

  function isFunction (f) {
    return typeof f === 'function';
  }

  // Convert `onChangeTitle` to `changeTitle`
  function getEventName(name) {
    return name[2].toLowerCase() + name.substring(3);
  }

  var Class = require('class');
  var Emitter = require('emitter');

  return Class.create(Emitter, {

    setOptions: function (custom) {
      var key, value, options;
      if (!this.hasOwnProperty('options')) {
        this.options = {};
      }

      options = this.options;

      // 父类 options
      if (this.constructor.superclass.options) {
        merge(options, this.constructor.superclass.options);
      }

      // 子类 options
      if (this.constructor.prototype.options) merge(options, this.constructor.prototype.options);

      // 实例 options
      if (custom && custom.options) merge(options, custom.options);

      if (this.on) {
        for (key in options) {
          value = options[key];
          if (isFunction(value) && EVENT_PREFIX.test(key)) {
            this.on(getEventName(key), value);
            delete options[key];
          }
        }
      }
    },

    destory: function () {
      var k;
      for (k in this) {
        if (this.hasOwnProperty(k)) {
          delete this[k];
        }
      }

      if (PROTO) this.__proto__ = PROTO;
    }

  });

});
