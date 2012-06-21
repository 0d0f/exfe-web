/**
 * rex: a lightweight JavaScript's functional library.
 *
 * Thanks:
 *    - https://github.com/kriskowal/es5-shim/blob/master/es5-shim.js
 *    - https://github.com/ded/valentine/blob/master/valentine.js
 *    - https://github.com/documentcloud/underscore/blob/master/underscore.js
 *    - https://github.com/jquery/jquery/blob/master/src/core.js
 *    - http://cjohansen.no/talks/2012/sdc-functional/#1
 *    - http://osteele.com/sources/javascript/functional/
 *    - https://github.com/osteele/functional-javascript
 *    - http://fitzgen.github.com/wu.js/
 */

define('rex', [], function (require, exports, module) {

  var NULL = null
    , UNDEFINED = undefined
    , ArrayProto = Array.prototype
    , ObjectProto = Object.prototype
    , hasOwn = ObjectProto.hasOwnProperty
    , toString = ObjectProto.toString
    , slice = ArrayProto.slice
    , NForEach = ArrayProto.forEach
    , NMap = ArrayProto.map
    , NIndexOf = ArrayProto.indexof
    , NLastIndexOf = ArrayProto.lastIndexOf
    , NReduce = ArrayProto.reduce
    , NReduceRight = ArrayProto.reduceRight
    , R = {};

  R.each = function (a, fn, ctx) {
    var i, l = a.length;
    // see: http://jsperf.com/for-vs-foreach/30
    if (l === +l) {
      for (i = 0; i < l; ++i) {
        i in a && fn.call(ctx, a[i], i, a);
      }
    } else {
      for (i in a) {
        R.has(a, i) && fn.call(ctx, i, a[i], a);
      }
    }
  };

  // see: http://es5.github.com/#x15.4.4.19
  //      http://jsperf.com/array-prototpy-map-vs-for
  R.map = function (a, fn, ctx) {
    var r = [], i, j, l = a.length;
    if (l === +l) {
      for (i = 0; i < l; ++i) {
        i in a && (r[i] = fn.call(ctx, a[i], i, a));
      }
    } else {
      j = 0;
      for (i in a) {
        R.has(a, i) && (r[j++] = fn.call(ctx, i, a[i], a));
      }
    }
    return r;
  };

  // see: http://es5.github.com/#x15.4.4.17
  R.some = function (a, fn, ctx) {
    var i = 0, l = a.length;
    for (; i < l; ++i) {
      if (i in a && fn.call(ctx, a[i], i, a)) return true;
    }
    return false;
  };

  // see: http://es5.github.com/#x15.4.4.16
  R.every = function (a, fn, ctx) {
    var i = 0, l = a.length;
    for (; i < l; ++i) {
      if (i in a && !fn.call(ctx, a[i], i, a)) return false;
    }
    return true;
  };

  // see: http://es5.github.com/#x15.4.4.20
  R.filter = R.select = function (a, fn, ctx) {
    var r = [], i = 0, j = 0, l = a.length;
    for (; i < l; ++i) {
      if (i in a) {
        if (!fn.call(ctx, a[i], i, a)) continue;
        r[j++] = a[i];
      }
    }
    return r;
  };

  // see: http://es5.github.com/#x15.4.4.14
  R.indexOf = NIndexOf ? function (a, el, i) {
    return a.indexOf(el, isFinite(i) ? i : 0);
  } :
  function (a, el, i) {
    var l = a.length;
    if (!l) return -1;
    i || (i = 0);
    if (i > l) return -1;
    (i < 0) && (i = Math.max(0, l + i));
    for (; i < l; ++i) {
      if (i in a && a[i] === el) return i;
    }
    return -1;
  };

  // see http://es5.github.com/#x15.4.4.15
  R.lastIndexOf = NLastIndexOf ? function (a, el, start) {
    return a.lastIndexOf(el, isFinite(start) ? start : a.length);
  } :
  function (a, el, i) {
    var l = a.length, i = l - 1;
    if (!l) return -1;
    (arguments.length > 1) && (i = Math.min(i, arguments[1]));
    (i < 0) && (i += l);
    for (; i >= 0; --i) {
      if (i in a && a[i] === el) return i;
    }
    return -1;
  };

  // http://es5.github.com/#x15.4.4.21
  R.reduce = NReduce ? function (o, fn, mem, ctx) {
    return o.reduce(fn, mem, ctx);
  } :
  function (o, fn, mem, ctx) {
    o || (o = []);
    var i = 0, l = o.length;

    if (arguments.length < 3) {
      do {
        if ( i in o) {
          mem = o[i++];
          break;
        }

        if (++i >= l) {
          throw new TypeError('Empty array');
        }
      } while (true);
    }
    for (; i < l; i++) {
      if (i in o) {
        mem = fn.call(ctx, mem, o[i], i, o);
      }
    }
    return mem;
  };

  // http://es5.github.com/#x15.4.4.22
  R.reduceRight = NReduceRight ? function (o, fn, mem, ctx) {
    return o.reduce(fn, mem, ctx);
  } :
  function (o, fn, mem, ctx) {
    o || (o = []);
    var i = o.length - 1;

    if (arguments.length < 3) {
      do {
        if (i in o) {
          mem = o[i--];
          break;
        }

        if (--i < 0) {
          throw new TypeError('Empty array');
        }
      } while (true);
    }
    for (; i >= 0; --i) {
      if (i in o) {
        mem = fn.call(ctx, mem, o[i], i, o);
      }
    }
    return mem;
  };

  R.find = function (a, fn, ctx) {
    var i = 0, l = a.length, r;
    for (; i < l; ++i) {
      if (i in a && fn.call(ctx, a[i], i, a)) {
        r = a[i];
        break;
      }
    }
    return r;
  }

  R.reject = function (a, fn, ctx) {
    var r = [], i = 0, j = 0, l = a.length;
    for (; i < l; ++i) {
      if (i in a) {
        if (fn.call(ctx, a[i], i, a)) {
          continue;
        }
        r[i++] = a[i];
      }
    }
    return r;
  };

  R.toArray = function (a) {
    if (!a) return [];
    if (R.isArray(a)) return a;
    if (a.toArray) return a.toArray();
    if (R.isArgs(a)) return slice.call(a);
    var r = [], k, j = 0;
    for (k in a) {
      R.has(a, k) && (r[j++] = a[k]);
    }
    return r;
  };

  R.first = function (a) {
    return a[0];
  };

  R.last = function (a) {
    return a[a.length - 1];
  };

  R.size = function (a) {
    return R.toArray(a).length;
  };

  R.compact = function (a) {
    return R.filter(a, function (v) {
      return !!v;
    });
  };

  R.flatten = function (a) {
    return R.reduce(a, function (memo, value) {
      if (R.isArray(value)) {
        return memo.concat(R.flatten(value));
      }
      memo[memo.length] = value;
      return memo;
    }, []);
  };

  R.unique = function (a) {
    var r = [], i = a.length - 1, j = 0;
    label:
    for (; i >= 0; --i) {
      for (; j < r.length; ++j) {
        if (r[j] === a[i]) {
          continue label;
        }
      }
      r[r.length] = a[i];
    }
    return r;
  };

  R.merge = function (one, two) {
    var i = one.length, j = 0, l
    if (isFinite(two.length)) {
      for (l = two.length; j < l; j++) {
        one[i++] = two[j];
      }
    } else {
      while (two[j] !== undefined) {
        first[i++] = second[j++];
      }
    }
    one.length = i;
    return one;
  };

  R.inArray = function (a, v) {
    return !!~R.indexOf(a, v);
  };

  R.compose = function () {
    var fns = arguments, i = fns.length, args;

    return cp;

    function cp() {
      args = arguments;
      for (; i >= 0; --i) {
        args = [fns[i].apply(this, args)];
      }
      return args[0];
    };
  };

  R.pluck = function (a, k) {

  };

  R.has = function (o, k) {
    return hasOwn.call(o, k);
  };

  R.keys = Object.keys || function (o) {
    var r = [], i = 0, k;
    for (k in o) {
      if (R.has(o, k)) r[i++] = k;
    }
    return r;
  };

  R.values = function (o) {
    var r = [], i = 0, k;
    for (k in o) {
      if (R.has(o, k)) r[i++] = o[k];
    }
    return r;
  };

  // TODO: 深拷贝/潜拷贝
  R.extend = function () {
  };

  R.mixin = function (r, s) {
    var p;
    for (p in s) r[p] = s[p];
  };

  // https://gist.github.com/2708275
  R.tap = function (o, f) {
    var r;
    if (f) r = fn(o);v
    return !r ? o : r;
  };

  R.nextTick = function (f) {
    setTimeout(f, 0);
  };

  R.countDown = function (n, f) {
    return cb;
    function cb() {
      if (--n === 0) f();
    }
  };

  R.isFunction = function (f) {
    return typeof f === 'function';
  };

  R.isString = function (s) {
    return toString.call(s) === '[object String]';
  };

  R.isElement = function (el) {
    return !!(el && el.nodeType && el.nodeType == 1)
  };

  R.isArray = Array.isArray || function (a) {
    return a instanceof Array;
  };

  R.isArrayLike = function (a) {
    return (a && a.length && isFinite(a.length));
  };

  R.isObject = function (o) {
    return o instanceof Object && !R.isFunction(o) && !R.isArray(o);
  };

  R.isDate = function (d) {
    return !!(d && d.getTimezoneOffset && d.setUTCFullYear)
  };

  R.isRegex = function (r) {
    return !!(r && r.test && r.exec && (r.ignoreCase || r.ignoreCase === false));
  };

  R.isUndefined = function (o) {
    return typeof o === 'undefined';
  };

  R.isDefined = function (o) {
    return typeof o !== 'undefined';
  };

  R.isNaN = function (n) {
    return n !== n;
  };

  R.isNull = function (o) {
    return o === null;
  };

  R.isNumber = function (n) {
    return toString.call(n) === '[object Number]';
  };

  R.isBoolean = function (b) {
    // return typeof b === 'boolean';
    return (b === true) || (b === false);
  };

  R.isArgs = function (a) {
    return !!(a && R.has(a, 'callee'))
  };

  R.isEmpty = function (o) {
    var i = 0, k;
    return R.isArray(o) ? o.length === 0 :
      R.isObject(o) ? (function () {
      for (k in o) {
        i++;
        break;
      }
      return (i === 0)
    }()) :
    o === '';
  };

  function rex(v, ctx) {
    return new Rex(v, ctx);
  }

  rex.chain = function (v, ctx) {
    return new Rex(v, ctx).chain();
  };

  R.mixin(rex, R);

  function Rex(v, ctx) {
    this._value = v;
    this._context = ctx || NULL;
    this._chained = false;
  }

  var RP = Rex.prototype;

  RP.constructor = Rex;

  RP.chain = function () {
    this._chained = true;
    return this;
  };

  RP.value = function () {
    return this._value;
  };

  rex.each(R.keys(R), function (name, fn) {
    fn = rex[name]
    RP[name] = function () {
      var i = 0
        , ret = this._value
        , l = arguments.length
        , a = [ret];

      for (; i < l; ++i) a[i + 1] = arguments[i];

      ret = fn.apply(this._context, a);
      this._value = ret;
      return this._chained ? this : ret;
    };
  });

  return rex;

});
