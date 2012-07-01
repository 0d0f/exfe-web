define('uploader', [], function (require, exports, module) {

  // Uploader Avatar Image
  // ---------------------

  var $ = require('jquery');
  var Api = require('api');
  var Config = require('config');
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
      var $dropbox = this.$('.dropbox');
      this._fileInputField = $(Uploader.HTML5FILEFIELD_TEMPLATE);
      $dropbox.after(this._fileInputField);

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

      this.element.on('drop', '.dropbox', _ddEventhandler);
      this.element.on('dragenter', '.dropbox', _ddEventhandler);
      this.element.on('dragover', '.dropbox', _ddEventhandler);
      this.element.on('dragleave', '.dropbox', _ddEventhandler);
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

    //
    aoffset: [0, 0],

    // 记录上传的缩放大小
    psx: 1,
    psy: 1,

    // anchor
    // 0  1  2
    // 3     4
    // 5  6  7
    anchor: null,

    // resize image
    resizing: false,

    // canvas dragging
    dragging: false,

    SCALE: 80 / 240,

    // 旋转角度计数器
    ri: 0,

    // 坐标旋转
    R: [0, 0],

    options: {
      limit: 1,

      onDrop: function (e) {
      },

      onFileselect: function (data) {
        var files = data.fileList, filehtml5;
        if (files.length) {
          filehtml5 = this.filehtml5 = files[0];

          this.filehtml5._xhrHeaders = {
            'Accept': 'application/json, text/javascript, */*; q=0.01'
          };

          this.filehtml5.on('uploadcomplete', function (e) {
            data = JSON.parse(e.data);
            if (data && data.meta.code === 200) {
              if (data.response.type === 'user') {
                $('.user-avatar .avatar, .user-panel .avatar').find('img').attr('src', data.response.avatars['80_80']);
              }
            }
          });

          this.$('.overlay').addClass('hide');
          this.$('.resizeable').removeClass('hide');
          this.$('.back, .rotate').show();

          var self = this;
          self.ri = 0;
          self.R = [0, 0];
          var canvas = document.getElementById('avatar240')
            , canvas80 = document.getElementById('avatar80')
            , R = self.R
            , stage = new Stage(canvas)
            , bitmap
            , stage80 = new Stage(canvas80)
            , bitmap80
            , originalImage = document.createElement('img');

          // 记录画布相对于DOM坐标系中的位置
          self.canvasOffset = $(canvas).offset();
          self.canvasOffset.right = canvas.width + self.canvasOffset.left;
          self.canvasOffset.bottom = canvas.height + self.canvasOffset.top;
          self.canvasOffset.width = canvas.width;
          self.canvasOffset.height = canvas.height;

          readFileToImage(originalImage, filehtml5._file);

          originalImage.onload = function () {

            bitmap = new Bitmap(originalImage);

            bitmap.setPosition(canvas.width / 2 - bitmap.regX, canvas.height / 2 - bitmap.regY);
            bitmap.rotation = self.ri;

            bitmap.updateContext = function (ctx) {
              ctx.translate(canvas.width * self.R[0], canvas.height * self.R[1]);
              ctx.rotate(this.rotation * Stage.DEG_TO_RAD);
            };

            // add to canvas
            stage.addChild(bitmap);

            // update canvas
            stage.update();

            self.bitmap = bitmap;
            self.stage = stage;

            // ---------------------- 80 * 80 --------------
            bitmap80 = new Bitmap(canvas);
            bitmap80.updateImage = function (canvas) {
              bitmap80.originalImage = canvas;
            };
            bitmap80.updateContext = function (ctx) {
              ctx.scale(self.SCALE, self.SCALE);
            };
            stage80.addChild(bitmap80);
            stage80.update();

            self.bitmap80 = bitmap80;
            self.stage80 = stage80;
          };

          docBind(this);

        }
      },

      backdrop: false,

      // bind events
      events: {

        'click .dropbox': function (e) {
          e.preventDefault();
          this._bindSelectFile();
        },

        'mousedown #avatar240': function (e) {
          this.dragging = true;
          this.offset = [e.offsetX, e.offsetY];
          return false;
        },

        'mousemove #avatar240': function (e) {
          e.preventDefault();
          if (this.dragging) {
            var dx = e.offsetX - this.offset[0];
            var dy = e.offsetY - this.offset[1];
            var bitmap = this.bitmap;

            switch (this.ri) {
              case 0:
                bitmap.x += dx;
                bitmap.y += dy;
                break;

              case 1:
                bitmap.x += dy;
                bitmap.y -= dx;
                break;

              case 2:
                bitmap.x -= dx;
                bitmap.y -= dy;
                break;

              case 3:
                bitmap.x -= dy;
                bitmap.y += dx;
                break;
            }

            this.offset[0] = e.offsetX;
            this.offset[1] = e.offsetY;

            this.stage.update();
            this.bitmap80.updateImage(this.stage.canvas);
            this.stage80.update();
          }
        },

        'mouseup #avatar240': function (e) {
          this.resizing = false;
          this.dragging = false;
          // 冒泡触发
          //return false;
        },

        // Rotate
        'click .rotate': function (e) {
          this.ri++;
          // 图片顶点在 canvs 坐标系中的相对位置
          // 90
          if (this.ri === 1) {
            this.R = [1, 0];
          }
          // 180
          else if (this.ri === 2) {
            this.R = [1, 1];
          // 270
          } else if (this.ri === 3) {
            this.R = [0, 1];
          // 360 / 0
          } else {
            this.ri = 0;
            this.R = [0, 0];
          }
          this.bitmap.rotation = 90 * this.ri;
          this.stage.update();
          this.bitmap80.updateImage(this.stage.canvas);
          this.stage80.update();
          return false;
        },

        // Back
        'click .back': function (e) {
          this.$('.overlay').removeClass('hide');
          this.$('.resizeable').addClass('hide');
          this.$('.back, .rotate').hide();

          this._fileInputField.val(null);
          this.stage.clear();
          this.stage80.clear();
          delete this.bitmap;
          delete this.stage;
          delete this.bitmap80;
          delete this.stage80;
          return false;
        },

        // Resize
        'mousedown .resizeable': function (e) {
          var $e = $(e.target);
          this.anchor = $e.data('anchor');
          this.resizing = true;
          // 在拖拽点中，相对DOM的坐标
          var o = $e.offset()
            , w = $e.width() / 2
            , h = $e.height() / 2;
          this.aoffset = [o + w, o.top + h];
        },

        'click .uploader-button': function (e) {
          var bitmap = this.bitmap;
          var originalImage = bitmap.originalImage;
          var stage = this.stage;
          var stage80 = this.stage80;

          var originalCanvas = document.createElement('canvas');

          originalCanvas.width = originalCanvas.height = Math.min(originalImage.width, originalImage.height);
          var originalCtx = originalCanvas.getContext('2d');
          originalCtx.translate(originalCanvas.width / 2, originalCanvas.height / 2);
          originalCtx.save();
          originalCtx.rotate(this.ri * Stage.DEG_TO_RAD);
          originalCtx.drawImage(originalImage, -originalCanvas.width / 2, -originalCanvas.height / 2);
          originalCtx.restore();
          originalCtx.save();

          // 头像上传
          this.filehtml5.startUpload(Config.api_url + '/avatar/update?token=' + Api.getToken(), {
            'original': saveCanvasAsFile(originalCanvas, 'original.png'),
            '80_80': saveCanvasAsFile(stage80.canvas, '80_80.png')
          });
        }
      },

      onHideAfter: function () {
        var $e = this.element;
        this.offSrcNode();
        this.destory();
        $e.remove();
        docUnBind();
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
            + '<i class="icon20-back back"></i>'
            + '<i class="icon20-rotate rotate"></i>'
            + '<div class="anchor-nw resizeable hide" data-anchor="0"></div>'
            + '<div class="anchor-n resizeable hide" data-anchor="1"></div>'
            + '<div class="anchor-ne resizeable hide" data-anchor="2"></div>'
            + '<div class="anchor-w resizeable hide" data-anchor="3"></div>'
            + '<div class="anchor-e resizeable hide" data-anchor="4"></div>'
            + '<div class="anchor-sw resizeable hide" data-anchor="5"></div>'
            + '<div class="anchor-s resizeable hide" data-anchor="6"></div>'
            + '<div class="anchor-se resizeable hide" data-anchor="7"></div>'
            + '<div class="avatar240">'
              + '<canvas id="avatar240" width="240" height="240"></canvas>'
            + '</div>'

            + '<div class="overlay dropbox">'
              + '<div class="droptips">Drop your photo <span class="hide">or URL</span> here.<br />'
                + 'Alternatively, <span class="underline">open</span> local file, <br />'
                + '<span class="underline">take</span> one from your camera.'
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

  // Helper

  function readFileToImage(image, file) {
    // 读取图像
    if (window.URL && window.URL.createObjectURL) {
      image.src = window.URL.createObjectURL(file);
    }
    else if (window.webkitURL.createObjectURL) {
      image.src = window.webkitURL.createObjectURL(file);
    }
    else {
      var reader = new FileReader();
      reader.onload = function () {
        image.src = this.result;
      };
      reader.readAsDataURL(file);
    }
    return image;
  }

  /**
   * http://stackoverflow.com/questions/4998908/convert-data-uri-to-file-then-append-to-formdata/5100158
   */
  function dataURItoBlob(dataURI, callback) {
    // convert base64 to raw binary data held in a string
    // doesn't handle URLEncoded DataURIs

    var byteString;
    if (dataURI.split(',')[0].indexOf('base64') >= 0) {
        byteString = atob(dataURI.split(',')[1]);
    } else {
        byteString = unescape(dataURI.split(',')[1]);
    }

    // separate out the mime component
    var mimeString = dataURI.split(',')[0].split(':')[1].split(';')[0];

    // write the bytes of the string to an ArrayBuffer
    var ab = new ArrayBuffer(byteString.length);
    var ia = new Uint8Array(ab);
    for (var i = 0; i < byteString.length; i++) {
      ia[i] = byteString.charCodeAt(i);
    }

    // write the ArrayBuffer to a blob, and you're done
    var BlobBuilder = window.WebKitBlobBuilder || window.MozBlobBuilder || window.BlobBuilder;
    var bb = new BlobBuilder();
    bb.append(ab);
    return bb.getBlob(mimeString);
  }

  function getAsPNGBlob(canvas, filename) {
    if(canvas.mozGetAsFile) {
      return canvas.mozGetAsFile(filename, "image/png");
    } else {
      var data = canvas.toDataURL();
      var blob = dataURItoBlob(data);
      return blob;
    }
  }

  function saveCanvasAsFile(canvas, filename) {
    var blob = getAsPNGBlob(canvas, filename);
    return blob;
  }

  // Point(x, y)
  function Point(x, y) {
    this.x = x || 0;
    this.y = y || 0;
  }

  // Image
  function Bitmap(image) {

    // original image
    this.originalImage = image;

    // init width height
    this.width = image.width;
    this.height = image.height;

    // reg
    this.regX = image.width / 2;
    this.regY = image.height / 2;

    // left-top point
    this.x = 0;
    this.y = 0;

    // opacity
    this.alpha = 1;

    this.visible = true;

    // radius
    this.rotation = 0;

    // x-axis
    this.scaleX = 1;
    // y-axis
    this.scaleY = 1;
  }

  Bitmap.prototype = {

    // set (x, y)
    setPosition: function (x, y) {
      this.x = x || 0;
      this.y = y || 0;
    },

    updateContext: function (ctx) {},

    updateRect: function () {
      this.width = this.originalImage.width * this.scaleX;
      if (this.width < 0) {
        this.width = 1;
        this.scaleX = this.width / this.originalImage.width;
      }

      this.height = this.originalImage.height * this.scaleY;
      if (this.height < 0) {
        this.height = 1;
        this.scaleY = this.height / this.originalImage.height;
      }
    },

    draw: function (ctx) {
      this.updateRect();
      ctx.drawImage(this.originalImage, this.x, this.y, this.width, this.height);
    }
  };

  // Stage
  function Stage(canvas) {
    this.id = ++Stage.UID;
    this.canvas = (canvas instanceof HTMLCanvasElement) ? canvas : document.getElementById(canvas);
  }

  Stage.UID = 0;

  Stage.DEG_TO_RAD = Math.PI / 180;

  Stage.prototype = {

    toDataURL: function () {},

    clear: function () {
      var ctx = this.canvas.getContext('2d');
      ctx.setTransform(1, 0, 0, 1, 0, 0);
      ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
    },

    addChild: function (child) {
      this._children || (this._children = []);
      this.parent = this;
      this._children.push(child);
      return child;
    },

    draw: function () {
      var i, l, ctx, list = this._children;

      if (!list) { return; }

      if ((l = this._children.length)) {
        ctx = this.canvas.getContext('2d');

        // clear canvas
        this.clear();
        // Y 轴向上，数学坐标系
        //ctx.scale(1, -1);

        for (i = 0; i < l; i++) {
          var child = list[i];
          ctx.save();
          child.updateContext(ctx);
          child.draw(ctx);
          ctx.restore();
        }
      }
    },

    update: function () {
      this.draw();
    }
  };

  function docUnBind() {
    $(document)
      .off('mousemove.photozone')
      .off('mouseup.photozone');
  }

  function docBind(_uploader) {
    $(document)
      .on('mousemove.photozone', function (e) {
        var _u_ = _uploader;
        if (_u_ && _u_.resizing) {
          var dx = e.pageX - _u_.aoffset[0]
            , dy = e.pageY - _u_.aoffset[1]
            , dzx, dzy, sbx, sby
            , cos = _u_.canvasOffset
            , w = _u_.stage.canvas.width
            , h = _u_.stage.canvas.height
            , bitmap = _u_.bitmap
            , img = bitmap.originalImage
            , psx = _u_.psx
            , psy = _u_.psy
            , i = _u_.ri;

          function a1() {
            dzy = cos.top - e.pageY;
            sby = dzy / h;

            if (i === 0) {
              bitmap.scaleY = psy + sby;
              if (bitmap.scaleY < 0) bitmap.scaleY = 1 / img.height;
              bitmap.y -= bitmap.scaleY * img.height - bitmap.height;
            } else if (i === 1) {
              bitmap.scaleX = psx + sby;
              if (bitmap.scaleX < 0) bitmap.scaleX = 1 / img.width;
              bitmap.x -= bitmap.scaleX * img.width - bitmap.width;
            } else if (i === 2) {
              bitmap.scaleY = psy + sby;
              if (bitmap.scaleY < 0) bitmap.scaleY = 1 / img.height;
            } else {
              bitmap.scaleX = psx + sby;
              if (bitmap.scaleX < 0) bitmap.scaleX = 1 / img.width;
            }
          }

          function a3() {
            dzx = cos.left - e.pageX;
            sbx = dzx / w;

            if (i === 0) {
              bitmap.scaleX = psx + sbx;
              if (bitmap.scaleX < 0) bitmap.scaleX = 1 / img.width;
              bitmap.x -= bitmap.scaleX * img.width - bitmap.width;
            } else if (i === 1) {
              bitmap.scaleY = psy + sbx;
              if (bitmap.scaleY < 0) bitmap.scaleY = 1 / img.height;
            } else if (i === 2) {
              bitmap.scaleX = psx + sbx;
              if (bitmap.scaleX < 0) bitmap.scaleX = 1 / img.width;
            } else {
              bitmap.scaleY = psy + sbx;
              if (bitmap.scaleY < 0) bitmap.scaleY = 1 / img.height;
              bitmap.y -= bitmap.scaleY * img.height - bitmap.height;
            }
          }

          function a4() {
            dzx = e.pageX - cos.right;
            sbx = dzx / w;

            if (i === 0) {
              bitmap.scaleX = psx + sbx;
              if (bitmap.scaleX < 0) bitmap.scaleX = 1 / img.width;
            } else if (i === 1) {
              bitmap.scaleY = psy + sbx;
              if (bitmap.scaleY < 0) bitmap.scaleY = 1 / img.height;
              bitmap.y -= bitmap.scaleY * img.height - bitmap.height;
            } else if (i === 2) {
              bitmap.scaleX = psx + sbx;
            } else {
              bitmap.scaleY = psy + sbx;
              if (bitmap.scaleY < 0) bitmap.scaleY = 1 / img.height;
            }
          }

          function a6() {
            dzy = e.pageY - cos.bottom;
            sby = dzy / h;

            if (i === 0) {
              bitmap.scaleY = psy + sby;
              if (bitmap.scaleY < 0) bitmap.scaleY = 1 / img.height;
            } else if (i === 1) {
              bitmap.scaleX = psx + sby;
              if (bitmap.scaleY < 0) bitmap.scaleY = 1 / img.height;
            } else if (i === 2) {
              bitmap.scaleY = psy + sby;
              if (bitmap.scaleY < 0) bitmap.scaleY = 1 / img.height;
              bitmap.y -= bitmap.scaleY * img.height - bitmap.height;
            } else {
              bitmap.scaleX = psx + sby;
              if (bitmap.scaleX < 0) bitmap.scaleX = 1 / img.width;
              bitmap.x -= bitmap.scaleX * img.width - bitmap.width;
            }
          }

          if (dx || dy) {

            switch (_u_.anchor) {
              case 0:
                a1();
                a3();
                break;

              case 1:
                a1();
                break;

              case 2:
                a1();
                a4();
                break;

              case 3:
                a3();
                break;

              case 4:
                a4();
                break;

              case 5:
                a3();
                a6();
                break;

              case 6:
                a6();
                break;

              case 7:
                a4();
                a6();
                break;
            }

            _u_.stage.update();
            _u_.bitmap80.updateImage(_u_.stage.canvas);
            _u_.stage80.update();

          }

          _u_.aoffset = [e.pageX, e.pageY];
          return false;
        }
      })
      .on('mouseup.photozone', function (e) {
        if (_uploader) {
          _uploader.resizing = false;
          _uploader.dragging = false;
          _uploader.anchor = null;
          // 记录上次缩放大小
          if (_uploader.bitmap) {
            _uploader.psx = _uploader.bitmap.scaleX;
            _uploader.psy = _uploader.bitmap.scaleY;
          }
        }
      });
  }

  return function () {
    return new Uploader(uploadSettings);
  };

});
