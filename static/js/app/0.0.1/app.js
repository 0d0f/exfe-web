/**
 * X webapp Bootstrap!
 */
define(function (require, exports, module) {
  /**
   */
  var Config = require('config')
    , Handlebars = require('handlebars');

  var middleware = require('middleware')
    , routes = require('routes');


  var lightsaber = require('lightsaber');

  // Create App
  var app = lightsaber();

  app.use(middleware.basicAuth);

  app.set('timestamp', Config.timestamp);
  app.set('view cache', true);
  app.set('view engine', Handlebars);
  app.set('views', '/static/views');

  // routes
  // index - '/#?'
  app.get('/#?', routes.switchPage, routes.index);

  // gahter a x
  app.get('/#gather', routes.switchPage, routes.signin, routes.gather);

  // profile
  app.get(/^\/#([^@\/\s\!]+)?@([^@\/\s\.]+)/, routes.switchPage, routes.signin, routes.profile);

  // cross
  // cross token
  app.param('token', routes.crossTokenParam);
  app.get('/#!token=:token', routes.switchPage, routes.signin, routes.crossToken);

  // normal cross
  app.param('cross_id', routes.crossParam);
  app.get('/#!:cross_id', routes.switchPage, routes.signin, routes.cross);

  // app running
  app.run();

});
