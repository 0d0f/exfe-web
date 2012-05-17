define('base', [], function (require) {
  // Thanks to:
  //
  // - https://github.com/mootools/mootools-core/blob/master/Source/Class/Class.Extras.js
  // - https://github.com/alipay/arale/blob/master/lib/base/src/options.js

  var Class = require('class');
  var Emitter = require('emitter');

  return Class.create(Emitter, {
    setOptions: function (customOptions) {
      var key, value;

      var options = this.options = merge({}, customOptions);

      if (this.on) {
        for (key in options) {
          value = options[key];
          if (isFunction(value) && EVENT_PREFIX.test(key)) {
            this.on(getEventName(name), value);
            delete options[key];
          }
        }
      }
    },
    destory: function () {
      var k;
      for (k in this) {
        if (this.hasOwnProperty(k)) {
          delete this[p];
        }
      }
    }
  });

  // Helpers
  // -------
  var EVENT_PREFIX = /^on[A-Z]/;

  var isArray = Array.isArray;
  if (!isArray) isArray = function (a) {return typeof a === 'array';}

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

});
