define('uploader', [], function (require, exports, module) {

  // Uploader Avatar Image
  // ---------------------

  var $ = require('jquery');
  var Dialog = require('dialog');
  var FileHTML5 = require('filehtml5');

  var Uploader = Dialog.extend({

    _fileInputField: null,

    _buttonBinding: null,

    init: function () {
    },

    sync: function () {
      var selectFile = this.$('.selectfile');
      this._fileInputField = $(Uploader.HTML5FILEFIELD_TEMPLATE);
      selectFile.append(this._fileInputField);
    },

    _bindSelectFile: function (e) {
      var fileinput = this._fileInputField[0];
      if (fileinput.click) {
        fileinput.click();
      }
    },

    _bindDropArea: function (e) {
    },

    _ddEventhandler: function (e) {
      e.stopPropagation();
      e.preventDefault();

      switch (e.type) {
        case 'dragenter':
          this.emit('dragenter', e);
          break;

        case 'dragover':
          this.emit('dragover', e);
          break;

        case 'dragleave':
          this.emit('dragleave', e);
          break;

        case 'drop':

          var newfiles = e.originEvent.dataTransfer.files,
              parsedFiles = [],
              newfile,
              filterFunc = this.fileFilterFunction,
              i = 0, l = newfiles.length;

          if (filterFunc) {
            for (; i < l; i++) {
              newfile = new FileHTML5(newfiles[i]);
              if (filterFunc(newfile)) {
                parsedFiles.push(newfile);
              }
            }
          }
          else {
            for (; i < l; i++) {
              parsedFiles.push(new FileHTML5(newfiles[i]));
            }
          }

          if (parsedFiles.length > 0) {
            this.emit('fileselect', {fileList: parsedFiles});
          }

          this.emit('drop', e);
          break;
      }
    }

  },
  {
    HTML5FILEFIELD_TEMPLATE: '<input type="file" style="visibility:hidden; width:0px; height:0px;" />'
  }
  );

  var uploadSettings = {

    options: {

      backdrop: false,

      // bind events
      events: {
        'click .selectfile': function (e) {
          e.stopPropagation();
          e.preventDefault();
          console.log(1);
          console.log(e);
          //this._bindSelectFile(e);
        }
      },

      onHideAfter: function () {
        var $e = this.element;
        this.offSrcNode();
        this.destory();
        $e.remove();
      },

      viewData: {

        cls: 'modal-uploader',

        title: 'Portrait',

        body: ''
          + '<div class="pull-right sider">'
            + '<div class="pull-right smallphoto">'
              + '<div class="avatar80"></div>'
              + '<div class="overlay"></div>'
            + '</div>'

            + '<div class="uploader-form">'
              + '<button class="pull-right xbtn xbtn-blue uploader-button">Done</button>'
            + '</div>'
          + '</div>'

          + '<div class="photozone">'
            + '<i class="icon20-back back"></i>'
            + '<i class="icon20-rotate rotate"></i>'
            + '<div class="avatar240"></div>'

            + '<div class="overlay dropbox">'
              + '<div class="droptips">Drop your photo or URL here, <br /> or '
                + '<span class="selectfile">open</span> '
                + 'local file.'
              + '</div>'
            + '</div>'
          + '</div>',

        footer: ''
          + '<button class="pull-right xbtn xbtn-yellow hide">Yes</button>'
          + '<button class="pull-right xbtn xbtn-white hide">No</button>',

        others: ''
          + '<div class="help-portrait hide">'
            + '<div class="modal-body">'
              + '<div class="shadow title">Use default portrait?</div>'
              + '<p>You have no portrait set, thus a default one will be assigned automatically. It means you will lose your primary visual identification, consequently poor recognizability confuse your friends.</p>'
              + '<p>Confirm using default portrait?</p>'
            + '</div>'
          + '</div>'

      }

    }

  };

  return function () {
    return new Uploader(uploadSettings);
  };

});
