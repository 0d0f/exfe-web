define('uploader', [], function (require, exports, module) {

  // Uploader Avatar Image
  // ---------------------

  var $ = require('jquery');
  var Dialog = require('dialog');
  var FileHTML5 = require('filehtml5');

  var Uploader = Dialog.extend({

    _fileInputField: null,

    _buttonBinding: null,

    queue: null,

    init: function () {
      this._fileInputField = null;
      this.queue = null;
      this._buttonBinding = null;
      this._fileList = [];
    },

    sync: function () {
      var selectFile = this.$('.selectfile');
      this._fileInputField = $(Uploader.HTML5FILEFIELD_TEMPLATE);
      selectFile.after(this._fileInputField);

      this._bindDropArea();

      this._fileInputField.on('change', $.proxy(this._updateFileList, this));
    },

    _bindSelectFile: function (e) {
      var fileinput = this._fileInputField[0];
      if (fileinput.click) {
        fileinput.click();
      }
    },

    _bindDropArea: function (e) {
      var ev = e || {prevVal: null};

      if (ev.prevVal !== null) {
        ev.prevVal.detach('drop', this._ddEventhandler);
        ev.prevVal.detach('dragenter', this._ddEventhandler);
        ev.prevVal.detach('dragover', this._ddEventhandler);
        ev.prevVal.detach('dragleave', this._ddEventhandler);
      }

      var _ddEventhandler = $.proxy(this._ddEventhandler, this);

      this.element.on('drop', '.photozone', _ddEventhandler);
      this.element.on('dragenter', '.photozone', _ddEventhandler);
      this.element.on('dragover', '.photozone', _ddEventhandler);
      this.element.on('dragleave', '.photozone', _ddEventhandler);

      var _docddEventhandler = $.proxy(this._docddEventhandler, this);
      $(document)
        .on('drop', _docddEventhandler)
        .on('dragenter', _docddEventhandler)
        .on('dragleave', _docddEventhandler)
        .on('dragover', _docddEventhandler);
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
          var dropbox = this.$('.photozone')[0];

          if (!$.contains(dropbox, e.target) && (e.target !== dropbox)) {
            return false;
          }

          this._fileselect(e.originalEvent.dataTransfer.files);

          this.emit('drop', e);
          break;
      }

      return false;
    },

    _docddEventhandler: function (e) {
      e.stopPropagation();
      e.preventDefault();

      switch (e.type) {
        case 'dragenter':
          this.emit('docdragenter', e);
          break;

        case 'dragover':
          this.emit('docdragover', e);
          break;

        case 'dragleave':
          this.emit('docdragleave', e);
          break;

        case 'drop':
          this.emit('docdrop', e);
          break;
      }
      return false;
    },

    _fileselect: function (files) {
      var newfiles = files,
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

      if (this.options.limit) {
        parsedFiles = parsedFiles.slice(-this.options.limit);
      }

      if (parsedFiles.length > 0) {
        this.emit('fileselect', {fileList: parsedFiles});
      }
    },

    _updateFileList: function (e) {
      this._fileselect(e.target.files);
    },

    fileFilterFunction: function (file) {
      var r = false;
      if (/^image\/(png|gif|bmp|jpg|jpeg)$/.test(file._type)) {
        r = true;
      }
      return r;
    }

  },
  {
    HTML5FILEFIELD_TEMPLATE: '<input type="file" style="visibility:hidden; width:0px; height:0px;" />'
  }
  );

  var uploadSettings = {

    options: {
      limit: 1,

      onDrop: function (e) {
        console.log('drop');
      },

      onFileselect: function (data) {
        var filehtml5 = data.fileList[0];

        this.original = filehtml5._file;

        this.$('.overlay').addClass('hide');
        this.$('.photozone').find('.back, .rotate').removeClass('hide');
        var canvas240 = document.getElementById('avatar240');
        var ctx240 = canvas240.getContext('2d');
        ctx240.clearRect(0, 0, 240, 240);

        var canvas80 = document.getElementById('avatar80');
        var ctx80 = canvas80.getContext('2d');
        ctx80.clearRect(0, 0, 80, 80);

        var img240 = document.createElement('img');
        var imgx = 0, imgy = 0, drag = false, startX = 0, startY = 0;

        clearInterval(this.__t);
        var self = this;
        this.__t = setInterval(function () {
          if (self.__mousepresed) {
            var left = imgx;
            var right = imgx + img240.width;
            var top = imgy;
            var bottom = imgy + img240.height;
            if (!drag) {
              startX = self.__x - imgx;
              startY = self.__y - imgy;
            }
            if (self.__x < right && self.__x > left && self.__y < bottom && self.__y > top) {
              if (!self.__dragging) {
                self.__dragging = true;
                drag = true;
              }
            }
          } else {
            drag = false;
          }

          if (drag) {
            imgx = self.__x - startX;
            imgy = self.__y - startY;
            ctx240.clearRect(0, 0, 240, 240);
            ctx240.drawImage(img240, imgx, imgy);
            ctx80.clearRect(0, 0, 80, 80);
            //ctx80.drawImage(img240, imgx/3, imgy/3, img240.width, img240.height, imgx/3, imgy/3, img240.width*80/240, img240.height*80/240);
            ctx80.drawImage(img240, imgx/3, imgy/3, img240.width*80/240, img240.height*80/240);
          }
        }, 10);

        img240.onload = function (e) {
          ctx240.translate(120, 120)
          //ctx240.drawImage(img240, imgx, imgy);
          console.log(img240.width, img240.height);
          ctx240.drawImage(img240, -img240.width/2, -img240.height/2);
          //ctx80.drawImage(img240, 0, 0, img240.width, img240.height, 0, 0, img240.width*80/240, img240.height*80/240);
          ctx80.translate(40, 40);
          //ctx80.drawImage(img240, 0, 0, img240.width*80/240, img240.height*80/240);
          var i = 80 / 240;
          //ctx80.drawImage(img240, -(img240.width * i)/2, -(img240.height * i)/2);
          ctx80.drawImage(img240, -(img240.width * i)/2, -(img240.height * i)/2, img240.width * i, img240.height * i);
        };

        this.img240 = img240;

        if (window.URL && window.URL.createObjectURL) {
          img240.src = window.URL.createObjectURL(filehtml5._file);
        }
        else if (window.webkitURL.createObjectURL) {
          img240.src = window.webkitURL.createObjectURL(filehtml5._file);
        }
        else {
          var reader = new FileReader();
          reader.onload = function () {
            img240.src = this.result;
          };
          reader.readAsDataURL(filehtml5._file);
        }
      },

      backdrop: false,

      // bind events
      events: {
        'click .selectfile': function (e) {
          e.stopPropagation();
          e.preventDefault();
          this._bindSelectFile(e);
        },
        'mousedown #avatar240': function (e) {
          this.__mousepresed = true;
        },
        'mousemove #avatar240': function (e) {
          this.__x = e.offsetX;
          this.__y = e.offsetY;
        },
        'mouseup #avatar240': function (e) {
          this.__mousepresed = false;
          this.__dragging = false;
          this.__t && clearInterval(this.__t);
        },
        'click .rotate': function (e) {
          if (!this._ii_) this._ii_ = 0;
          if (this._ii_ === 3) this._ii_ = 0;
          e.preventDefault();
          var canvas240 = document.getElementById('avatar240');
          var ctx240 = canvas240.getContext('2d');
          //ctx240.save();
          ctx240.clearRect(0, 0, 240, 240);
          ctx240.translate(0, 0);
          //ctx240.translate(this.img240.width / 2, this.img240.height/2);
          ctx240.translate(120, 120);
          ctx240.rotate(90 * ++this._ii_ * Math.PI / 180);
          ctx240.drawImage(this.img240, -(this.img240.width/2), -(this.img240.height/2));
          ctx240.drawImage(this.img240, 0, 0);
          ctx240.restore();
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
              + '<div class="avatar80">'
                + '<canvas id="avatar80" width="80" height="80"></canvas>'
              + '</div>'
              + '<div class="overlay"></div>'
            + '</div>'

            + '<div class="uploader-form">'
              + '<button class="pull-right xbtn xbtn-blue uploader-button">Done</button>'
            + '</div>'
          + '</div>'

          + '<div class="photozone">'
            + '<i class="icon20-back back hide" style="width: 20px; height: 20px; background: red;"></i>'
            + '<i class="icon20-rotate rotate hide" style="width: 20px; height: 20px; background: black;"></i>'
            + '<div class="avatar240">'
              + '<canvas id="avatar240" width="240" height="240"></canvas>'
            + '</div>'

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
