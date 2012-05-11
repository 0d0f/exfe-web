/**
 * rex: a lightweight JavaScript's functional library.
 *
 * Thanks:
 *    - https://github.com/ded/valentine/blob/master/valentine.js
 *    - https://github.com/documentcloud/underscore/blob/master/underscore.js
 *    - https://github.com/jquery/jquery/blob/master/src/core.js
 */

var NULL = null
  , UNDEFINED = undefined
  , ArrayProto = Array.prototype
  , ObjectProto = Object.prototype
  , hasOwn = ObjectProto.hasOwnProperty
  , NForEach = ArrayProto.forEach
  , NMap = ArrayProto.map;

var R = {};
R.each = function (a, fn, scope) {
  var i, l = a.length;
  // see: http://jsperf.com/for-vs-foreach/30
  if (l === +l) {
    for (i = 0; i < l; ++i) {
      i in a && fn.call(scope, a[i], i, a);
    }
  } else {
    for (i in a) {
      R.has(a, i) && fn.call(scope, i, a[i], a);
    }
  }
};

// see: http://jsperf.com/array-prototpy-map-vs-for
R.map = function (a, fn, scope) {
  var r = [], i, j, l = a.length;
  if (l === +l) {
    for (i = 0; i < l; ++i) {
      i in a && (r[i] = fn.call(scope, a[i], i, a));
    }
  } else {
    j = 0;
    for (i in a) {
      R.has(a, i) && (r[i++] = fn.call(scope, i, a[i], a));
    }
  }
  return r;
};

R.some = function (a, fn, scope) {
  var i = 0, l = a.length;
  for (; i < l; ++i) {
    if (i in a && fn.call(scope, a[i], i, a)) return true;
  }
  return false;
};

R.every = function (a, fn, scope) {
  var i = 0, l = a.length;
  for (; i < l; ++i) {
    if (i in a && !fn.call(scope, a[i], i, a)) return false;
  }
  return true;
};

R.filter = function (a, fn, scope) {
  var r = [], i = 0, j = 0, l = a.length;
  for (; i < l; ++i) {
    if (i in a) {
      if (!fn.call(scope, a[i], i, a)) continue;
      r[j++] = a[i];
    }
  }
  return r;
};

R.indexOf = function () {};

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

R.extend = function () {
};

R.mix = function (r, s) {
  var p;
  for (p in s) r[p] = s[p];
};

function rex(v, scope) {
  return new Rex(v, scope);
}

R.mix(rex, R);

function Rex(v, scope) {
  this._value = v;
  this._scope = scope || NULL;
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

rex.each(R.keys(rex), function (name, fn) {
  fn = rex[name]
  RP[name] = function () {
    var i = 0
      , ret = this._value
      , l = arguments.length
      , a = [ret];

    for (; i < l; ++i) a[i + 1] = arguments[i];

    ret = fn.apply(this._scope, a);
    this._value = ret;
    return this._chained ? this : ret;
  };
});
