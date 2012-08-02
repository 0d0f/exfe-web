define(function (require, exports, module) {
  var $ = require('jquery')
    , $DOC = $(document);

  $(function () {
    function _docddEventhandler(e) {
      e.stopPropagation();
      e.preventDefault();
      return false;
    }
    $DOC
      .on('drop', _docddEventhandler)
      //.on('dragenter', _docddEventhandler)
      //.on('dragleave', _docddEventhandler)
      .on('dragover', _docddEventhandler);

    var toggle = '[data-toggle="dropdown"]';
    function clearMenus(e) {
      $(toggle).removeClass('open');
    }
    $DOC
      .on('click.dropdown.data-api', clearMenus);

  });
});
