/**
 * X webapp Bootstrap!
 */
define(function (require, exports, module) {
  var Config = require('config')
    , Handlebars = require('handlebars');

  var middleware = require('middleware')
    , routes = require('routes');


  var lightsaber = require('lightsaber');


  // Create App   ***********************************
  var app = lightsaber();

  app.use(middleware.basicAuth);
  app.initRouter();
  // *注: 要使 `errorHandler` 生效，`app.initRouter` 必须先初始化。
  app.use(middleware.errorHandler);

  app.set('timestamp', Config.timestamp);
  app.set('view cache', true);
  app.set('view engine', Handlebars);
  app.set('views', '/static/views');


  // Routes       ***********************************

  // index - `/#?`
  app.get('/+#?', routes.index);


  // gather a x - `/#gather`
  app.get('/#gather', routes.gather);


  // resolve-token - `/#token=5c9a628f2b4f863435bc8d599a857c21`
  app.get(/^\/#token=([a-zA-Z0-9]{32})$/, routes.resolveToken);


  // cross - `/#!233`
  app.get(/^\/#!([1-9][0-9]*)$/, routes.cross);


  // cross-token - `/#!token=63435bc8d599a857c215c9a628f2b4f8`
  app.get(/^\/#!token=([a-zA-Z0-9]{32})$/, routes.crossToken);


  // profile
  //        email:    cfd@exfe.com        - `/#cfd@exfe.com`
  //      twitter:    @cfddream           - `/#@cfddream`
  //     facebook:    cfddream@facebook   - `/#cfddream@facebook`
  app.get(/^\/#([^@\/\s\!=]+)?@([^@\/\s]+)(?:\/?(.*))$/, routes.profile);


  // invalid link
  app.get(/^\/#invalid\/token=([a-zA-Z0-9]{32})$/, routes.invalid);


  // signout
  app.get('/#signout', routes.signout);


  // app running
  app.run();

  // global
  //window.app = app;
});
