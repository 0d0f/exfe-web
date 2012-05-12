define(function (require, exports, module) {
  var $ = require('jquery');
  var Store = require('store');
  var Handlebars = require('handlebars');

  var signin_defe = $.when(
    $.ajax({
      url: 'http://api.localexfe.me/v2/users/signin',
      type: 'POST',
      xhrFields: { withCredentials: true},
      data: {gt
        'external_id': 'cfd@demox.io',
        'provder': 'email',
        'password': 'd'
      }
    })
  );

  signin_defe.done(function (data) {
    console.dir(data);
  });

  var jst_crosses = $('#jst-crosses-container');
});
