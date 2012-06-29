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

      this.element.on('drop', '.dropbox', _ddEventhandler);
      this.element.on('dragenter', '.dropbox', _ddEventhandler);
      this.element.on('dragover', '.dropbox', _ddEventhandler);
      this.element.on('dragleave', '.dropbox', _ddEventhandler);

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
      //if (e.target !== this.find('#avatar240')[0]) return false;

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
      //return false;
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

    radian: Math.PI * 90 / 180,

    ri: 0,

    coord: [0, 0],

    // zoom 240
    z240: 1,
    // zoom 80
    z80: 1,

    // origin image.width
    IW: 0,

    // origin image.height
    IH: 0,

    SCALE: 80 / 240,

    options: {
      limit: 1,

      onDrop: function (e) {
        //console.log('drop');
      },

      onImageRotate: function (c, ctx, img, ri, x, y, z, s) {
        //s && ctx.scale(1/s, 1/s);
        ctx.translate(-c.width / 2, -c.height / 2);

        ctx.clearRect(0, 0, c.width, c.height);

        ctx.translate(c.width / 2, c.height / 2);
        //s && ctx.scale(s, s);

        ctx.save();
        ctx.rotate(ri * this.radian);
        if (s) {
          ctx.drawImage(img, x * s, y * s, this.IW * s * z, this.IH * s * z);
        } else {
          ctx.drawImage(img, x, y, this.IW * z, this.IH * z);
        }
        ctx.restore();
        ctx.rotate(0);
      },

      onFileselect: function (data) {
        var that = this;
        var filehtml5 = this.filehtml5 = data.fileList[0];

        filehtml5.on('uploadcomplete', function (data) {
        });

        this.original = filehtml5._file;

        this.$('.overlay').addClass('hide');
        this.$('.photozone').find('.back, .rotate').removeClass('hide');
        this.$('.resizeable').removeClass('hide');
        var c240 = document.getElementById('avatar240');
        var ctx240 = c240.getContext('2d');

        var c80 = document.getElementById('avatar80');
        var ctx80 = c80.getContext('2d');

        var img240 = this.img240 = document.createElement('img');
        this.img240.onload = function () {
          that.ri = 0;
          that.IW = img240.width;
          that.IH = img240.height;
          that.coord = [-that.IW/2, -that.IH/2];
          that.__dx = that.__dy = 0;
          that.isDrag = false;
          isResize = false;

          ctx240.translate(c240.width / 2, c240.height / 2);
          //ctx240.scale(1, 1);
          ctx240.drawImage(img240, that.coord[0], that.coord[1]);
          ctx240.save();

          ctx80.translate(c80.width / 2, c80.height / 2);
          //ctx80.scale(SCALE, SCALE);
          //ctx80.drawImage(img, x, y);
          ctx80.drawImage(img240, that.coord[0] * that.SCALE, that.coord[1] * that.SCALE, that.IW * that.SCALE, that.IH * that.SCALE);
        };

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

        that.timer && clearInterval(that.timer);
        that.timer = setInterval(function () {
          if (that.isDrag || isResize) {
            if (isResize) {
                gdz = Math.min(gmx - gsx, gmy - gsy);
                gdz *= 0.0005 * arrow;
                that.z240 += gdz;
                that.z80 += gdz;
                if (that.z240 < 0) {
                  that.z240 = 0;
                  that.z80 = 0;
                }
                that.emit('imageRotate', c240, ctx240, img240, that.ri, that.coord[0], that.coord[1], that.z240);
                that.emit('imageRotate', c80, ctx80, img240, that.ri, that.coord[0], that.coord[1], that.z80, that.SCALE);
                if (!that.z240) {
                  that.z240  = 1;
                  that.z80 = 1;
                }
              return;
            }
            var px, py;
            that.__dx = that.__mx - that.__sx;
            that.__dy = that.__my - that.__sy;
            if (that.__dx || that.__dy) {
              switch (that.ri) {
                case 0:
                  px = that.coord[0] + that.__dx; py = that.coord[1] + that.__dy;
                  break;

                case -1:
                  px = that.coord[0] - that.__dy; py = that.coord[1] + that.__dx;
                  break;

                case -2:
                  px = that.coord[0] - that.__dx; py = that.coord[1] - that.__dy;
                  break;

                case -3:
                  px = that.coord[0] + that.__dy; py = that.coord[1] - that.__dx;
                  break;
              }
              that.emit('imageRotate', c240, ctx240, img240, that.ri, px, py, that.z240);
              that.emit('imageRotate', c80, ctx80, img240, that.ri, px, py, that.z80, that.SCALE);
            }
          }
        }, 20);
      },

      backdrop: false,

      // bind events
      events: {
        'click .dropbox': function (e) {
          console.log(1);
          e.stopPropagation();
          e.preventDefault();
          this._bindSelectFile(e);
          return false;
        },
        'mousedown #avatar240': function (e) {
          e.preventDefault();
          this.isDrag = true;
          this.__mx = this.__sx = e.offsetX;
          this.__my = this.__sy = e.offsetY;
        },
        'mouseleave #avatar240': function (e) {
          e.preventDefault();
          this.isDrag = false;
        },
        'mousemove #avatar240': function (e) {
          e.preventDefault();
          if (this.isDrag) {
            this.__mx = e.offsetX;
            this.__my = e.offsetY;
          }
        },
        'mouseup #avatar240': function (e) {
          e.preventDefault();
          if (this.isDrag) {
            if (this.ri === -3) {
              this.coord[0] += this.__dy; this.coord[1] -= this.__dx;
            } else if (this.ri === -2) {
              this.coord[0] -= this.__dx; this.coord[1] -= this.__dy;
            } else if (this.ri === -1) {
              this.coord[0] -= this.__dy; this.coord[1] += this.__dx;
            } else {
              this.coord[0] += this.__dx; this.coord[1] += this.__dy;
            }
            this.__dx = this.__dy = 0;
            this.isDrag = false;
          }
        },
        'click .rotate': function (e) {
          e.preventDefault();
          this.ri--;
          (this.ri === -4) && (this.ri = 0);
          var c240 = document.getElementById('avatar240');
          var ctx240 = c240.getContext('2d');
          this.emit('imageRotate', c240, ctx240, this.img240, this.ri, this.coord[0], this.coord[1], this.z240);

          var c80 = document.getElementById('avatar80');
          var ctx80 = c80.getContext('2d');
          this.emit('imageRotate', c80, ctx80, this.img240, this.ri, this.coord[0], this.coord[1], this.z80, this.SCALE);
        },
        'click .uploader-button': function (e) {
          var c80 = document.getElementById('avatar80');
          var originalCanvas = document.createElement('canvas');

          originalCanvas.width = originalCanvas.height = Math.min(this.img240.width, this.img240.height);
          var originalCtx = originalCanvas.getContext('2d');
          originalCtx.translate(originalCanvas.width / 2, originalCanvas.height / 2);
          originalCtx.save();
          originalCtx.rotate(this.ri * this.radian);
          originalCtx.drawImage(this.img240, -originalCanvas.width / 2, -originalCanvas.height / 2);
          originalCtx.restore();
          originalCtx.save();

          this.filehtml5.startUpload(Config.api_url + '/avatar/update?token=' + Api.getToken(), {
            'original': saveCanvasAsFile(originalCanvas, 'original.png'),
            '80_80': saveCanvasAsFile(c80, '80_80.png')
          });
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
            + '<i class="icon20-back back hide"></i>'
            + '<i class="icon20-rotate rotate hide"></i>'
            + '<div class="bdt resizeable hide"></div>'
            + '<div class="bdr resizeable hide"></div>'
            + '<div class="bdb resizeable hide"></div>'
            + '<div class="bdl resizeable hide"></div>'
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

  var isResize = false, gmx, gmy, gsx, gsy, gdx, gdy, gdz, arrow = 1;
  $(function () {
    var $DOC = $(document);
    $DOC.on('mousedown', '.resizeable', function (e) {
      e.preventDefault();
      if ($(this).is('.resizeable')) {
        isResize = true;
        gmx = gsx = e.pageX;
        gmy = gsy = e.pageY;
        if ($(e.target).hasClass('bdl') || $(e.target).hasClass('bdt')) {
          arrow = -1;
        } else {
          arrow = 1;
        }
      }
    });
    $DOC.mouseup(function (e) {
      e.stopPropagation();
      e.preventDefault();
      if (isResize) {
        isResize = false;
        gmx = gsx = gmy = gsy = gdz = 0;
      }
    })
    .mousemove(function (e) {
      e.preventDefault();
      if (isResize) {
        gmx = e.pageX;
        gmy = e.pageY;
      }
    });
  });

  return function () {
    return new Uploader(uploadSettings);
  };

});
