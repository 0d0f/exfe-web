  <script>
  define(function (require) {
    var $ = require('jquery');
    $(function () {
      $('a.sign-in').click();
      $('#js-modal-backdrop').remove();
      $('.modal-id').find('.close').hide();
      $(document.body).off('click.dialog.data-api keyup.dismiss.modal');
      document.title = 'EXFE - Login';
    });
  });
  </script>
