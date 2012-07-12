define(function (require, exports, module) {
  var $ = require('jquery');

  $(function () {
    function _docddEventhandler(e) {
      e.stopPropagation();
      e.preventDefault();
      return false;
    }

    $(document.body)
      .on('drop', _docddEventhandler)
      .on('dragenter', _docddEventhandler)
      .on('dragleave', _docddEventhandler)
      .on('dragover', _docddEventhandler);
  });
});
