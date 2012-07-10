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

      this.element.on('drop', '.modal-main'/*'.dropbox'*/, _ddEventhandler);
      this.element.on('dragenter', '.modal-main'/*'.dropbox'*/, _ddEventhandler);
      this.element.on('dragover', '.modal-main'/*'.dropbox'*/, _ddEventhandler);
      this.element.on('dragleave', '.modal-main'/*'.dropbox'*/, _ddEventhandler);
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
          //var dropbox = this.$('.photozone')[0];
          var modalmain = this.$('.modal-main')[0];

          //if (!$.contains(dropbox, e.target) && (e.target !== dropbox)) {
          if (!$.contains(modalmain, e.target) && (e.target !== modalmain)) {
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

      if (false) {
      //if (filterFunc) {
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

    errors: {
      'server': 'Failed to open, please try again.',
      'format': 'File format not supported.',
      'size': 'File is too large.'
    },

    checkFile: function (file) {

      if (this.checkFileFormat(file)) { return false; }

      if (this.checkFileSize(file)) { return false; }

      return true;
    },

    checkFileSize: function (file) {
      var maxSize = 1024 * 1024 * 3, b = false;

      this.emit('toggleError', (b = file._size > maxSize), 'size');

      return b;
    },

    checkFileFormat: function (file) {
      var b = !this.fileFilterFunction(file);

      this.emit('toggleError', b, 'format');

      return b;
    },

    // 缩放比例
    sss: 1,

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

    filehtml5Bind: function () {
      var that = this;

      this.filehtml5._xhrHeaders = {
        'Accept': 'application/json, text/javascript, */*; q=0.01'
      };

      this.filehtml5.on('uploadcomplete', function (e) {
        var b = true;
        that.$('.loading').addClass('hide');
        data = JSON.parse(e.data);
        if (data) {
          if (data.meta.code === 200) {
            b = false;
            if (data.response.type === 'user') {
              $('.user-avatar .avatar, .user-panel .avatar').find('img').attr('src', data.response.avatars['80_80']);
            } else {
              $('.identity-list li[data-identity-id="' + data.response.identity_id + '"] .avatar').find('img').attr('src', data.response.avatars['80_80']);
            }
          }
        }

        that.emit('toggleError', b, 'server');

        that.hide();
      });

      this.filehtml5.on('uploadstart', function (e) {
        that.$('.loading').removeClass('hide');
      });
    },

    options: {
      limit: 1,

      onShowBefore: function () {
        docBind(this);
      },

      onShowAfter: function (data) {
        this._data = data;

        this._canvasOffset = this.$('#avatar240').offset();

        if (data.original) {

          var input = document.createElement('input');
          input.type = 'file';
          this.filehtml5 = new FileHTML5(input.files);
          this.filehtml5Bind();

          this.$('.overlay').addClass('hide');
          this.$('.resizeable').removeClass('hide');
          this.$('.upload-done').show();
          this.$('.upload-clear').hide();
          this.$('.zoom').show();

          var self = this;
          self.ri = 0;
          self.R = [0, 0];
          var canvas = document.getElementById('avatar240')
            , canvas80 = document.getElementById('avatar80')
            , r = self.r
            , stage = new Stage(canvas)
            , bitmap
            , stage80 = new Stage(canvas80)
            , bitmap80
            , originalImage = document.createElement('img');

          originalImage.onload = function () {
            var min = Math.min(originalImage.width, originalImage.height);
            self.sss = 1;

            if (min > 240) {
              self.sss = 240 / min;
            }

            bitmap = new Bitmap(originalImage);

            self.psx = bitmap.scaleX = self.sss;
            self.psy = bitmap.scaleY = self.sss;

            bitmap.setPosition(canvas.width / 2 - (bitmap.regX *= bitmap.scaleX), canvas.height / 2 - (bitmap.regY *= bitmap.scaleY));
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

          // CORS: cross origin
          originalImage.crossOrigin = 'anonymous';
          originalImage.src = data.original;
        }

      },

      onHideAfter: function () {
        var $e = this.element;
        this.offSrcNode();
        this.destory();
        $e.remove();
        docUnBind();
      },

      onToggleError: function (b, errorType) {
        if (b) {
          this.$('.xalert-error').html(this.errors[errorType]).removeClass('hide');
        }
        else {
          this.$('.xalert-error').addClass('hide');
        }
      },

      onDrop: function (e) {
      },

      onFileselect: function (data) {
        var files = data.fileList, filehtml5;
        if (files.length) {
          filehtml5 = this.filehtml5 = files[0];

          if (!this.checkFile(filehtml5)) { return false; }

          this.filehtml5Bind();

          this.$('.overlay').addClass('hide');
          this.$('.resizeable').removeClass('hide');
          this.$('.upload-done').show();
          this.$('.upload-clear').hide();

          var self = this;
          self.ri = 0;
          self.R = [0, 0];
          var canvas = document.getElementById('avatar240')
            , canvas80 = document.getElementById('avatar80')
            , r = self.r
            , stage = new Stage(canvas)
            , bitmap
            , stage80 = new Stage(canvas80)
            , bitmap80
            , originalImage = document.createElement('img');

          originalImage.onload = function () {
            var image = originalImage;
            var min = Math.min(originalImage.width, originalImage.height);
            self.sss = 1;

            if (min > 240) {
              self.sss = 240 / min;
            }

            // gif, get first frame
            if (self.filehtml5._type === 'image/gif') {
              var ccc = document.createElement('canvas'),
                  ccctx = ccc.getContext('2d');
              ccc.width = image.width;
              ccc.height = image.height;
              ccctx.drawImage(image, 0, 0, ccc.width, ccc.height);
              image = ccc;
            }

            bitmap = new Bitmap(image);

            self.psx = bitmap.scaleX = self.sss;
            self.psy = bitmap.scaleY = self.sss;

            bitmap.setPosition(canvas.width / 2 - (bitmap.regX *= bitmap.scaleX), canvas.height / 2 - (bitmap.regY *= bitmap.scaleY));
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

          // CORS: cross origin
          originalImage.crossOrigin = 'anonymous';
          readFileToImage(originalImage, filehtml5._file);

        }
      },

      backdrop: false,

      // bind events
      events: {

        'click .dropbox': function (e) {
          e.preventDefault();
          this._fileInputField[0].value = null;
          this._bindSelectFile();
        },

        'mousedown #avatar240': function (e) {
          e.preventDefault();
          this.dragging = true;
          this.offset = [e.pageX, e.pageY];
          return false;
        },

        // upload
        'click .upload': function (e) {
          e.preventDefault();
          this.$('.overlay').removeClass('hide');
          this.$('.resizeable').addClass('hide');
          this.$('.upload, .rotate, .upload-done').hide();
          this.$('.back, .upload-clear').show();
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

        'hover .avatar240': function (e) {
          if (e.type === 'mouseenter') {
            this.$('.upload, .rotate').show();
          }
          else {
            this.$('.upload, .rotate').hide();
          }
        },

        'hover .overlay': function (e) {
          var enter = e.type === 'mouseenter';
          if ($(e.currentTarget).hasClass('dropbox')) {
            this.$('.back')[enter ? 'show' : 'hide']();
          }
          else {
            //this.$('.zoom')[enter ? 'show' : 'hide']();
          }
        },

        // Back
        'click .back': function (e) {
          this.$('.overlay').addClass('hide');
          this.$('.resizeable').removeClass('hide');
          this.$('.upload, .rotate, .upload-done').show();
          this.$('.back, .upload-clear').hide();
          return false;
        },

        'click /*#avatar240,*/.smallphoto': function (e) {
          e.preventDefault();
          var src = '';
          if (!this.bitmap) { return false; }
          if ($.browser.safari && !/chrome/.test(navigator.userAgent.toLowerCase())) {
            var canvas = document.createElement('canvas'),
                ctx = canvas.getContext('2d');
            canvas.width = this.bitmap.originalImage.width;
            canvas.height = this.bitmap.originalImage.height;
            ctx.drawImage(this.bitmap.originalImage, 0, 0, canvas.width, canvas.height);
            src = canvas.toDataURL('image/png');
          } else {
            src = this.bitmap.originalImage.src;
          }
          return window.open(src);
        },

        // Resize
        'mousedown .resizeable': function (e) {
          e.preventDefault();
          var $e = $(e.target);
          var anchor = this.anchor = $e.data('anchor');
          this.resizing = true;
          this.aoffset = [e.pageX, e.pageY];

          var offset = this._canvasOffset;

          if (anchor === 7) {
            this.dr = Math.sqrt(Math.pow(e.pageX - offset.left, 2) + Math.pow(e.pageY - offset.top, 2));
          } else if (anchor === 5) {
            this.dr = Math.sqrt(Math.pow(e.pageX - offset.left - 240, 2) + Math.pow(e.pageY - offset.top, 2));
          } else if (anchor === 0) {
            this.dr = Math.sqrt(Math.pow(e.pageX - offset.left - 240, 2) + Math.pow(e.pageY - offset.top - 240, 2));
          } else if (anchor === 2) {
            this.dr = Math.sqrt(Math.pow(e.pageX - offset.left, 2) + Math.pow(e.pageY - offset.top - 240, 2));
          }

          return false;
        },

        'click .upload-clear': function (e) {
          this.$('.help-portrait').removeClass('hide');
          this.$('.xbtn-yes, .xbtn-no').removeClass('hide');
          return false;
        },

        'click .xbtn-no': function (e) {
          this.$('.help-portrait').addClass('hide');
          this.$('.xbtn-yes, .xbtn-no').addClass('hide');
          return false;
        },

        'click .xbtn-yes': function (e) {
          var data = {};

          if (this._data.identity_id) {
            data.identity_id = this._data.identity_id;
          }
          this.filehtml5.startUpload(Config.api_url + '/avatar/update?token=' + Api.getToken(), data);
          return false;
        },

        'click .upload-done': function (e) {
          var self = this;
          var bitmap = this.bitmap;
          var originalImage = bitmap.originalImage;
          var stage = this.stage;
          var stage80 = this.stage80;

          // crop origin image {{{
          var min = 240 / this.sss;
          var x = bitmap.x / this.sss;
          var y = bitmap.y / this.sss;

          var oc = document.createElement('canvas');
          oc.width = oc.height = min;
          var os = new Stage(oc);
          var ob = new Bitmap(originalImage);

          ob.setPosition(x, y);
          ob.rotation = 90 * this.ri;
          ob.scaleX = bitmap.scaleX / this.sss;
          ob.scaleY = bitmap.scaleY / this.sss;

          ob.updateContext = function (ctx) {
            ctx.translate(oc.width * self.R[0], oc.height * self.R[1]);
            ctx.rotate(this.rotation * Stage.DEG_TO_RAD);
          };

          os.addChild(ob);
          os.update();
          // }}}

          var img0 = saveCanvasAsFile(os.canvas, 'original.png');
          var img1 =  saveCanvasAsFile(stage80.canvas, '80_80.png');

          var that = this;
          setTimeout(function () {

            var data = {
              'original': img0,
              '80_80': img1
            };

            if (that._data.identity_id) {
              data.identity_id = that._data.identity_id;
            }

            // 头像上传
            that.filehtml5.startUpload(Config.api_url + '/avatar/update?token=' + Api.getToken(), data);
          }, 15.6);
        }
      },

      viewData: {

        cls: 'mblack modal-uploader',

        title: 'Portrait',

        body: ''
          + '<div class="pull-right sider">'
            + '<div class="pull-right smallphoto">'
              + '<i class="icon20-zoom zoom"></i>'
              + '<div class="avatar80">'
                + '<canvas id="avatar80" width="80" height="80"></canvas>'
              + '</div>'
              + '<div class="overlay">'
              + '</div>'
              + '<div class="loading hide"></div>'
            + '</div>'

            + '<div class="uploader-form">'
              + '<div class="xalert-error hide"></div>'
              + '<button class="pull-right xbtn xbtn-blue upload-done hide">Done</button>'
              + '<button class="pull-right xbtn xbtn-white upload-clear hide">Clear</button>'
            + '</div>'
          + '</div>'

          + '<div class="photozone">'
            + '<div class="anchor-n resizeable hide" data-anchor="1"></div>'
            + '<div class="anchor-w resizeable hide" data-anchor="3"></div>'
            + '<div class="anchor-e resizeable hide" data-anchor="4"></div>'
            + '<div class="anchor-s resizeable hide" data-anchor="6"></div>'
            + '<div class="anchor-nw resizeable hide" data-anchor="0"></div>'
            + '<div class="anchor-ne resizeable hide" data-anchor="2"></div>'
            + '<div class="anchor-sw resizeable hide" data-anchor="5"></div>'
            + '<div class="anchor-se resizeable hide" data-anchor="7"></div>'
            + '<div class="avatar240">'
              + '<i class="icon20-upload upload"></i>'
              + '<i class="icon20-rotate rotate"></i>'
              + '<canvas id="avatar240" width="240" height="240"></canvas>'
            + '</div>'

            + '<div class="loading hide">'
              + '<img src="/static/img/loading.gif" alt="" width="36" height="39" />'
              + '<p>Uploading...</p>'
            + '</div>'

            + '<div class="overlay dropbox">'
              + '<i class="icon20-back back"></i>'
              + '<img class="bigupload" src="/static/img/upload_128.png" alt="" width="128" height="128" />'
              + '<div class="droptips">Drop your photo <span class="hide">or URL</span> here.<br />'
                + 'Alternatively, <span class="underline">open</span> local file.'
                // TODO: 第二版再弄摄像头
                //+ 'Alternatively, <span class="underline">open</span> local file, <br />'
                //+ '<span class="underline">take</span> one from your camera.'
              + '</div>'
            + '</div>'
          + '</div>',

        footer: ''
          + '<button class="pull-right xbtn xbtn-white xbtn-yes hide">Yes</button>'
          + '<button class="pull-right xbtn xbtn-blue xbtn-no hide">No</button>',

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
    var res;
    if (BlobBuilder) {
      var bb = new BlobBuilder();
      bb.append(ab);
      res = bb.getBlob(mimeString);
    } else {
      // for safari Blob  算法不一样，导致 bytelength 也不一样
      // wtf? // 对于大文件不稳定哦
      res = new Blob([ab], {"type": mimeString});
    }
    return res;
  }

  function getAsPNGBlob(canvas, filename) {
    if(canvas.mozGetAsFile) {
      return canvas.mozGetAsFile(filename, "image/png");
    } else {
      var data = canvas.toDataURL('image/png');
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

  // 等比例
  function geometricProportion(x, y, h, w) {
    var a = [];
    a[0] = Math.sqrt(Math.pow(x, 2) + Math.pow(y, 2));
    if (x < 0 || y < 0) {
      a[0] *= -1;
    }
    a[1] = a[0] / Math.sqrt(Math.pow(h, 2) + Math.pow(w, 2));
    return a;
  }

  function docUnBind() {
    $(document)
      .off('mousemove.photozone')
      .off('mouseup.photozone');
  }

  function docBind(_uploader) {
    $(document)
      .on('mousemove.photozone', function (e) {
        e.preventDefault();
        var _u_ = _uploader;
        if (_u_ && _u_.dragging) {
          var dx = e.pageX - _u_.offset[0];
          var dy = e.pageY - _u_.offset[1];

          var bitmap = _u_.bitmap;

          switch (_u_.ri) {
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

          _u_.offset[0] = e.pageX;
          _u_.offset[1] = e.pageY;

          _u_.stage.update();
          _u_.bitmap80.updateImage(_u_.stage.canvas);
          _u_.stage80.update();
          return false;
        }
        if (_u_ && _u_.resizing) {
          var dx = e.pageX - _u_.aoffset[0]
            , dy = e.pageY - _u_.aoffset[1]
            , dzx, dzy, sbx, sby
            , w = _u_.stage.canvas.width
            , h = _u_.stage.canvas.height
            , bitmap = _u_.bitmap
            , img = bitmap.originalImage
            , psx = _u_.psx
            , psy = _u_.psy
            , i = _u_.ri
            , ao = _u_.aoffset
            , sss = _u_.sss
            , canvasOffset = _u_._canvasOffset;

          function a1(sby) {
            //dzy = ao[1] - e.pageY; sby = dzy / h;

            if (i === 0) {
              bitmap.scaleY = psy + sby;
              if (bitmap.scaleY < 0) bitmap.scaleY = sss / img.height;
              bitmap.y -= bitmap.scaleY * img.height - bitmap.height;
            } else if (i === 1) {
              bitmap.scaleX = psx + sby;
              if (bitmap.scaleX < 0) bitmap.scaleX = sss / img.width;
              bitmap.x -= bitmap.scaleX * img.width - bitmap.width;
            } else if (i === 2) {
              bitmap.scaleY = psy + sby;
              if (bitmap.scaleY < 0) bitmap.scaleY = sss / img.height;
            } else {
              bitmap.scaleX = psx + sby;
              if (bitmap.scaleX < 0) bitmap.scaleX = sss / img.width;
            }
          }

          function a3(sbx) {
            //dzx = ao[0] - e.pageX; sbx = dzx / w;

            if (i === 0) {
              bitmap.scaleX = psx + sbx;
              if (bitmap.scaleX < 0) bitmap.scaleX = sss / img.width;
              bitmap.x -= bitmap.scaleX * img.width - bitmap.width;
            } else if (i === 1) {
              bitmap.scaleY = psy + sbx;
              if (bitmap.scaleY < 0) bitmap.scaleY = sss / img.height;
            } else if (i === 2) {
              bitmap.scaleX = psx + sbx;
              if (bitmap.scaleX < 0) bitmap.scaleX = sss / img.width;
            } else {
              bitmap.scaleY = psy + sbx;
              if (bitmap.scaleY < 0) bitmap.scaleY = sss / img.height;
              bitmap.y -= bitmap.scaleY * img.height - bitmap.height;
            }
          }

          function a4(sbx) {
            //dzx = e.pageX - ao[0]; sbx = dzx / w;

            if (i === 0) {
              bitmap.scaleX = psx + sbx;
              if (bitmap.scaleX < 0) bitmap.scaleX = sss / img.width;
            } else if (i === 1) {
              bitmap.scaleY = psy + sbx;
              if (bitmap.scaleY < 0) bitmap.scaleY = sss / img.height;
              bitmap.y -= bitmap.scaleY * img.height - bitmap.height;
            } else if (i === 2) {
              bitmap.scaleX = psx + sbx;
              if (bitmap.scaleX < 0) bitmap.scaleX = sss / img.width;
              bitmap.x -= bitmap.scaleX * img.width - bitmap.width;
            } else {
              bitmap.scaleY = psy + sbx;
              if (bitmap.scaleY < 0) bitmap.scaleY = sss / img.height;
            }
          }

          function a6(sby) {
            //dzy = e.pageY - ao[1]; sby = dzy / h;

            if (i === 0) {
              bitmap.scaleY = psy + sby;
              if (bitmap.scaleY < 0) bitmap.scaleY = sss / img.height;
            } else if (i === 1) {
              bitmap.scaleX = psx + sby;
              if (bitmap.scaleY < 0) bitmap.scaleY = sss / img.height;
            } else if (i === 2) {
              bitmap.scaleY = psy + sby;
              if (bitmap.scaleY < 0) bitmap.scaleY = sss / img.height;
              bitmap.y -= bitmap.scaleY * img.height - bitmap.height;
            } else {
              bitmap.scaleX = psx + sby;
              if (bitmap.scaleX < 0) bitmap.scaleX = sss / img.width;
              bitmap.x -= bitmap.scaleX * img.width - bitmap.width;
            }
          }

          if (dx || dy) {
            var a;

            switch (_u_.anchor) {
              case 0:
                //dzy = ao[1] - e.pageY; dzx = ao[0] - e.pageX;

                var dzr = Math.sqrt(Math.pow(e.pageX - w - canvasOffset.left, 2) + Math.pow(e.pageY - canvasOffset.top - h, 2));
                var r = Math.sqrt(Math.pow(w, 2) + Math.pow(h, 2));
                dzr -= _u_.dr;
                a1(dzr / r * sss);
                a3(dzr / r * sss);
                break;

              case 1:
                dzy = ao[1] - e.pageY;
                sby = dzy / h;
                a1(sby * sss);
                break;

              case 2:
                //dzy = ao[1] - e.pageY; dzx = e.pageX - ao[0];

                var dzr = Math.sqrt(Math.pow(e.pageX - canvasOffset.left, 2) + Math.pow(e.pageY - canvasOffset.top - h, 2));
                var r = Math.sqrt(Math.pow(w, 2) + Math.pow(h, 2));
                dzr -= _u_.dr;
                a1(dzr / r * sss);
                a4(dzr / r * sss);
                break;

              case 3:
                dzx = ao[0] - e.pageX;
                sbx = dzx / w;
                a3(sbx * sss);
                break;

              case 4:
                dzx = e.pageX - ao[0];
                sbx = dzx / w;
                a4(sbx * sss);
                break;

              case 5:
                //dzx = ao[0] - e.pageX; dzy = e.pageY - ao[1];

                var dzr = Math.sqrt(Math.pow(e.pageX - w - canvasOffset.left, 2) + Math.pow(e.pageY - canvasOffset.top, 2));
                var r = Math.sqrt(Math.pow(w, 2) + Math.pow(h, 2));
                dzr -= _u_.dr;
                a3(dzr / r * sss);
                a6(dzr / r * sss);
                break;

              case 6:
                dzy = e.pageY - ao[1];
                sby = dzy / h;
                a6(sby * sss);
                break;

              case 7:
                //dzx = e.pageX - ao[0]; dzy = e.pageY - ao[1];

                var dzr = Math.sqrt(Math.pow(e.pageX - canvasOffset.left, 2) + Math.pow(e.pageY - canvasOffset.top, 2));
                var r = Math.sqrt(Math.pow(w, 2) + Math.pow(h, 2));
                dzr -= _u_.dr;
                a4(dzr / r * sss);
                a6(dzr / r * sss);
                break;
            }

            _u_.stage.update();
            _u_.bitmap80.updateImage(_u_.stage.canvas);
            _u_.stage80.update();

          }

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

  exports.Uploader = Uploader;
  exports.uploadSettings = uploadSettings;

});
