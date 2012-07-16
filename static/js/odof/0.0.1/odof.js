//
// TODO: 先实现分发
//  - Module
//  - View
//  - Controller / Operation
//  - Event
//
// Refer:
//    - https://github.com/senchalabs/connect
//    - https://github.com/visionmedia/page.js
//    - https://github.com/maccman/spine/blob/master/lib/route.js

// odof
define('odof', [], function (require, exports, module) {
  var $ = require('jquery');

  var _firstLoad = false;

  // http://cacheandquery.com/blog/category/technology/
  $(window).on('load', function (e) {
    _firstLoad = true;

    setTimeout(function () { _firstLoad = false; }, 0);
  });

  // window.location
  var location = window.location;

  // window.history
  var history = window.history;


  var Emitter = require('emitter');

  // export module
  exports = module.exports = createApplication;

  /**
   * Create an odof application
   */
  function createApplication() {
    function app() {}
    merge(app, appProto);
    merge(app, Emitter.prototype);
    app.route = '/';
    app.stack = [];
    app.request = new Request();
    app.response = new Response();
    for (var i = 0, len = arguments.length; i < len; ++i) {
      app.use(arguments[i]);
    }
    app.init();
    return app;
  }

  /**
   * Application prototype
   */
  var appProto = {};

  // Initialization middleware
  function odofInit(app) {
    return function init(req, res, next) {
      req.app = res.app = app;

      //req.res = res;
      //res.req = req;

      req.next = next;
      next();
    }
  }

  appProto.init = function () {
    this.use(odofInit(this));
    this.cache = {};
    this.settings = {};
    this.engine = {};
    this.viewCallbacks = {};
    this.defaultConfiguration();
  };

  appProto.historySupport = (history !== null ? history.pushState : void 0) !== null;

  appProto.defaultConfiguration = function () {
    var self = this;
    this.set('env', 'development');

    this._dispatch = true;
    // router
    this._router = new Router(this);
    this._router.caseSensitive = this.enabled('case sensitive routing');
    this._router.strict = this.enabled('strict routing');
    this.routes = this._router.map;

    this._usedRouter = false;
  };

  appProto.use = function (route, fn) {
    var app, home, handle;

    // default route to '/'
    if ('string' !== typeof route) {
      fn = route;
      route = '/';
    }

    // '/abcdefg/' => '/abcdefg'
    if ('/' !== route && '/' === route[route.length - 1]) {
      route = route.slice(0, -1);
    }

    this.stack.push({ route: route, handle: fn });

    return this;
  };

  appProto.run = function (options) {
    options = options || {};
    var self = this
      , req = self.request
      , res = self.response;

    if (this.running) return;
    this.running = true;

    if (false === options.dispatch) this._dispatch = false;

    if (false !== options.popstate) {

      if (this.historySupport) {
        $(window).on('popstate', { app: this }, onpopstate);
      } else {
        $(window).on('hashchange', { app: this },  onpopstate);
      }

    }

    if (!this._dispatch) return;

    this.handle(req, res);
  };

  function onpopstate(e) {
    if (_firstLoad) return _firstLoad = false;
    //console.log('popstate');
    var app = e.data.app
      , req = app.request
      , res = app.response
      , url = req.url;
    req.updateLocation();
    if ('/' !== url && url === req.url) return;
    app.handle(req, res);
  }

  appProto.handle = function (req, res, out) {
    var stack = this.stack
      , index = 0;

    req.originalUrl = req.originalUrl || req.url;

    function next(err) {
      var layer, path;

      layer = stack[index++];

      //console.dir(stack);

      //console.log(index, layer);

      if (!layer) {
        if (out) {
          return out(err);
        }
        return;
      }

      path = req.url;

      if (0 !== path.indexOf(layer.route)) return next(err);

      layer.handle(req, res, next);

    }

    next();
  };

  appProto.set = function (setting, val) {
    if (1 === arguments.length) {
      if (this.settings.hasOwnProperty(setting)) {
        return this.settings[setting];
      }
    } else {
      this.settings[setting] = val;
      return this;
    }
  };

  appProto.enable = function (setting) {
    return this.set(setting, true);
  };

  appProto.enabled = function (setting) {
    return !!this.set(setting);
  };

  appProto.disable = function (setting) {
    return this.set(setting, false);
  };

  appProto.disabled = function (setting) {
    return !this.set(setting);
  };

  // get request
  appProto.get = function (path) {
    var args = [].slice.call(arguments);
    if (!this._usedRouter) {
      this._usedRouter = true;
      this.use(this._router.middleware);
    }
    return this._router.route.apply(this._router, args);
  };

  appProto.render = function (name, options, fn) {
  };

  // Request
  function Request() {
    // default `GET` for browser
    this.method = 'GET';

    this.updateLocation();
  }

  Request.prototype.updateLocation = function () {
    this.host = location.hostname;
    this.port = location.port || 80;
    this.path = location.pathname;
    this.hash = location.hash;//getHash(location.hash);
    this.querystring = location.search;// || getQuerystring(location.href);
    this.url = location.pathname + this.querystring + this.hash;
  };

  //Request.prototype.abort = function () {};

  // Response
  function Response(path, state) {
    this.path = path;
    this.title = document.title;
    this.state = state || {};
  }

  // rendre view
  Response.prototype.render = function (view, options, fn) {
    var self = this
      , options = options || {}
      , app = this.app;

    if ('function' === typeof options) {
      fn = options, options = {};
    }

    fn = fn || function () {};

    // render
    app.render(view, options, fn);
  };

  Response.prototype.redirect = function (url, title, state) {
    //console.log(url);
    this.path = url;
    this.title = title;
    document.title = this.title;
    this.state = state;
    this.pushState();
    $(window).triggerHandler('popstate');
  };

  // save the state
  Response.prototype.save = function () {
    window.history.replaceState(this.state, this.title, this.path);
  };

  // push the state onto the history stack
  Response.prototype.pushState = function () {
    window.history.pushState(this.state, this.title, this.path);
  };

  // Router
  function Router(options) {
    options = options || {};
    var self = this;
    this.map = [];
    this.params = {};
    this._params = [];
    this.caseSensitive = options.caseSensitive;
    this.strict = options.strict;
    this.middleware = function router(req, res, next) {
      self._dispatch(req, res, next);
    };
  }

  Router.prototype._dispatch = function (req, res, next) {
    var params = this.params
      , self = this;

    function pass(i, err) {
      var paramCallbacks
      , paramIndex = 0
      , paramVal
      , route
      , keys
      , key
      , ret;

      // match next route
      function nextRoute(err) {
        pass(req._route_index + 1, err);
      }

      // match route
      req.route = route = self.matchRequest(req, i);

      // no route
      if (!route) {
        return next(err);
      }

      // we have a route
      // start at param 0
      req.params = route.params;
      keys = route.keys;
      i = 0;

      // param callbacks
      function param(err) {
        paramIndex = 0;
        key = keys[i++];
        paramVal = key && req.params[key.name];
        paramCallbacks = key && params[key.name];

        if (key) {
          param();
        } else {
          i = 0;
          callbacks();
        }
      }

      param(err);

      function callbacks(err) {
        var fn = route.callbacks[i++];
        if (fn) {
          fn(req, res, callbacks);
        } else {
          nextRoute(err);
        }
      }
      //callbacks(err);

      /*
      // single paramCallbacks
      function paramCallback(err) {
        var fn = paramCallbacks[paramIndex++];
        if (err || !fn) return param(err);
        fn(req, res, paramCallback, paramVal, key.name);
      }

      // invoke route callbacks
      function callbacks(err) {
        var fn = route.callbacks[i++];


        try {

          if ('route' == err) {
            nextRoute();
          } else if (err && fn) {
            fn(err, req, res, callbacks);
          } else if (fn) {
            fn(req, res, callbacks);
          } else {
            nextRoute(err);
          }

        } catch (err) {
          callbacks(err);
        }
      }
      */

    }

    pass(0);
  };

  Router.prototype.matchRequest = function (req, i) {
    var routes = this.map
      , path = req.url
      , route
      , len = routes.length;

    i = i || 0;

    // matching routes
    for (; i < len; ++i) {
      route = routes[i];
      if (route.match(path)) {
        req._route_index = i;
        return route;
      }
    }

  };

  Router.prototype.route = function (path) {
    if (0 === arguments.length) return;
    var callbacks = [].slice.call(arguments, 1)
      , route = new Route(path, callbacks, {
          sensitive: this.caseSensitive
        , strict: this.strict
      });

    this.map.push(route);
    return this;
  };

  Router.prototype.param = function (name, fn) {
    var _params = this._params
      , len
      , ret
      , i;

    if ('function' === typeof name) {
      _params.push(name);
      return;
    }

    len = _params.length;

    for (i = 0; i < len; ++i) {
      if (ret = _params[i](name, fn)) {
        fn = ret;
      }
    }

    if ('function' !== typeof fn) {}

    (this.params[name] = this.params[name] || []).push(fn);

    return this;
  };

  function Route(path, callbacks, options) {
    options = options || {};
    //this.method = 'GET';
    this.path = path;
    this.callbacks = callbacks;
    this.regexp = pathToRegexp(path
      , this.keys = []
      , options.sensitive
      , options.strict);
  }

  Route.prototype.match = function (path) {
    var keys = this.keys
      , params = this.params = []
      , m = this.regexp.exec(path)
      , i, len
      , key, val;

    if (!m) return false;

    for (i = 1, len = m.length; i < len; ++i) {
      key = keys[i - 1];
      val = 'string' === typeof m[i]
        ? decodeURIComponent(m[i])
        : m[i];

      if (key) {
        params[key.name] = val;
      } else {
        params.push(val);
      }
    }

    return true;
  };

  // Helper
  function merge (t, s) {
    var k;
    if (t && s) {
      for (k in s) {
        t[k] = s[k];
      }
    }
    return t;
  }

  function getHash(path) {
    var path = path
      , iq = path.indexOf('?');
    if (iq > -1) path = path.substr(0, iq);
    return path;
  };

  function getQuerystring(path) {
    var path = path
      , iq = path.indexOf('?') + 1
      , ih;
    path = path.substr(iq);
    ih = path.indexOf('#');
    if (ih > iq) path = path.substr(iq, ih);
    return path;
  }

  function pathToRegexp(path, keys, sensitive, strict) {
    if (path instanceof RegExp) return path;
    if (Array.isArray(path)) path = '(' + path.join('|') + ')';
    path = path
      .concat(strict ? '' : '/?')
      .replace(/\/\(/g, '(?:/')
      .replace(/(\/)?(\.)?:(\w+)(?:(\(.*?\)))?(\?)?(\*)?/g, function(_, slash, format, key, capture, optional, star){
        keys.push({ name: key, optional: !! optional });
        slash = slash || '';
        return ''
          + (optional ? '' : slash)
          + '(?:'
          + (optional ? slash : '')
          + (format || '') + (capture || (format && '([^/.]+?)' || '([^/]+?)')) + ')'
          + (optional || '')
          + (star ? '(/*)?' : '');
      })
    .replace(/([\/.])/g, '\\$1')
    .replace(/\*/g, '(.*)');
    return new RegExp('^' + path + '$', sensitive ? '' : 'i');
  }

});
