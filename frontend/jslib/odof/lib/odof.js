//
// TODO: 先实现分发
  //  - Module
//  - View
//  - Controller / Operation
//  - Event
//
// Refer
//    - https://github.com/visionmedia/page.js
//    - https://github.com/maccman/spine/blob/master/lib/route.js

// odof
define('odof', [], function (require, exports, module) {
  var Emitter = require('emitter');
  var OdOf = {};

  var historySupport = (window.history !== null ? window.history.pushState : void 0) !== null;

  var hashStrip = /^#*/;

  var namedParam = /:([\w\d]+)/g;

  var splatParam = /\*([\w\d]+)/g;

  var escapeRegExp = /[-[\]{}()+?.,\\^$|#\s]/g;

  var basepath = '';

  OdOf.createApplication = function () {
    function app() {}
    merge(app, _app);
    merge(app, Emitter.prototype);
    app.init();
    return app;
  };

  var _app = {};

  _app.getPath = function () {
    var path = window.location.pathname;
    if (path.substr(0, 1) !== '/') {
      path = '/' + path;
    }
    return path;
  };

  _app.getHash = function () {
    return window.location.hash;
  };

  _app.getFragment = function () {
    return this.getHash().replace(hashStrip, '');
  };

  _app.getHost = function () {
    return (document.location + '').replace(this.getPath() + this.getHash(), '');
  };

  _app.basePath = function (path) {
    if (0 === arguments.length) return basepath;
    basepath = path;
  };

  _app.start = function () {
    if (historySupport) {
      window.addEventListener('popstate', proxy(this, this.change), false);
    } else {
      window.addEventListener('hashchange', proxy(this, this.change), false);
    }
  };

  // hash change
  _app.change = function (e) {
    var current = this.getFragment();
    if (current === this.fragment) return;
    this.replace(window.location.pathname + window.location.hash + window.location.search, e.state);
  };

  _app.stop = function () {
    if (historySupport) {
      window.removeEventListener('popstate', proxy(this, this.change));
    } else {
      window.removeEventListener('hashchange', proxy(this, this.change));
    }
  };

  _app.init = function () {
    var self = this;
    this._dispatch = true;
    this.callbacks = [];
    this.start();
  };

  _app.get = function (path, fn) {
    if ('function' === typeof fn) {
      var route = new Route(path);
      for (var i = 1, l = arguments.length; i < l; ++i) {
        this.callbacks.push(route.middleware(arguments[i]));
      }
    }
  };

  _app.dispatch = function (ctx) {
    var self = this;
    var i = 0;

    function next() {
      var fn = self.callbacks[i++];
      if (!fn) return self.unhandled(ctx);
      fn(ctx, next);
    }

    next();
  };

  _app.unhandled = function (ctx) {
    this.emit('unhandled', ctx);
  };

  _app.replace = function (path, state, init, dispatch) {
    var ctx = new Context(path, state);
    ctx.init = init;
    if (!dispatch) this._dispatch = true;
    if (this._dispatch) this.dispatch(ctx);
    this.fragment = this.getFragment();
    return ctx;
  };

  _app.run = function (options) {
    options = options || {};
    if (this.running) return;
    this.running = true;
    if (false === options.dispatch) this._dispatch = false;
    if (!this._dispatch) return;
    this.replace(window.location.pathname + window.location.hash + window.location.search, null, true, this._dispatch);
  };

  // Context
  function Context(path, state) {
    if ('/' === path[0] && 0 !== path.indexOf(basepath)) path = basepath + path;
    var i = path.indexOf('?');
    this.canonicalPath = path;
    this.path = path.replace(basepath, '') || '/';
    this.title = document.title;
    this.state = state || {};
    this.state.path = path;
    this.querystring = ~i ? path.slice(i + 1) : '';
    this.pathname = ~i ? path.slice(0, i) : path;
    this.params = [];
  }

  // Route
  function Route(path, options) {
    options = options || {};
    this.path = path;
    this.regexp = pathToRegexp(path
      , this.keys = []
      , options.sensitive
      , options.strict
    );
  }

  Route.prototype.middleware = function (fn) {
    var self = this;
    return function (ctx, next) {
      if (self.match(ctx.path, ctx.params)) {
        return fn(ctx, next);
      }
      next();
    };
  };

  Route.prototype.match = function (path, params) {
    var keys = this.keys
      , qsIndex = path.indexOf('?')
      , pathname = ~qsIndex ? path.slice(0, qsIndex) : path
      , m = this.regexp.exec(pathname);

    if (!m) return false;

    for (var i = 1, len = m.length; i < len; ++i) {
      var key = keys[i - 1];

      var val = 'string' === typeof m[i]
        ? decodeURIComponent(m[i])
        : m[i];

      if (key) {
        params[key.name] = undefined !== params[key.name]
          ? params[key.name]
          : val;
      } else {
        params.push(val);
      }
    }

    return true;
  };


  // Helper --------------------

  function proxy(o, fn) {
    return cb;
    function cb() {
      fn.apply(o, arguments);
    };
  }

  function pathToRegexp(path, keys, sensitive, strict) {
    if (path instanceof RegExp) return path;
    if (path instanceof Array) path = '(' + path.join('|') + ')';
    path = path
      .concat(strict ? '' : '/?')
      .replace(/\/\(/g, '(?:/')
      .replace(/\+/g, '__plus__')
      .replace(/(\/)?(\.)?:(\w+)(?:(\(.*?\)))?(\?)?/g, function(_, slash, format, key, capture, optional){
        keys.push({ name: key, optional: !! optional });
        slash = slash || '';
        return ''
          + (optional ? '' : slash)
          + '(?:'
          + (optional ? slash : '')
          + (format || '') + (capture || (format && '([^/.]+?)' || '([^/]+?)')) + ')'
          + (optional || '');
      })
      .replace(/([\/.])/g, '\\$1')
      .replace(/__plus__/g, '(.+)')
      .replace(/\*/g, '(.*)');
    return new RegExp('^' + path + '$', sensitive ? '' : 'i');
  }

  function merge(t, s) {
    if (t && s) {
      for (var k in s) {
        t[k] = s[k];
      }
    }
    return t;
  }

  var _slice = Array.prototype.slice;

  module.exports = OdOf;

});
