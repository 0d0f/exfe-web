define(function (require, exports, module) {
  var $ = require('jquery');

  $.ajax({
    type: "POST",
    url: "http://api.localexfe.me/v2/users/signin",
    xhrFields: { withCredentials: true },
    dataType: 'json',
    data: {
      external_id: "cfd@demox.io",
      provider: "email",
      password: "d"
    },
    success: function (data) {
      console.dir(data);
    }
  });
});
