define('filehtml5', [], function (require, exports, module) {

  // File HTML5 Uploader
  var Class = require('class');
  var Emitter = require('emitter');

  var Win = window;

  var FileHTML5 = Class.create(Emitter, {

    initialize: function (o) {
      var file = null;

      if (FileHTML5.isValidFile(o)) {
        file = o;
      }
      else if (FileHTML5.isValidFile(o.file)) {
        file = o.file;
      }
      else {
        file = false;
      }

      if (file && FileHTML5.canUpload()) {
        this._file = file;
        this._name = file.name || file.fileName;
        this._size = file.size || file.fileSize;
        this._type = file.type;

        if (file.hasOwnProperty('lastModifiedDate')) {
          this._dateModified = file.lastModifiedDate;
        }
      }
      this._id = guid();
    },

    _uploadEventHandler: function (event) {
      var xhr = this._xhr,
          type = event.type;

      switch (type) {

        case 'progress':

          this.emit('uploadprogress', {
            originEvent: event,
            bytesLoaded: event.loaded,
            bytesTotal: this._size,
            percentLoaded: Math.min(100, Math.round(10000*event.loaded/this.get("size"))/100)
          });

          this._bytesUploaded = event.loaded;
          break;

        case 'load':

          if (xhr.status >= 200 && xhr.status <= 299) {
            this.emit('uploadcomplete', {
              originEvent: event,
              data: event.target.responseText
            });

            var xhrupload = xhr.upload,
                boundEventHandler = this._boundEventHandler;

              xhrupload.removeEventListener('progress', boundEventHandler);
              xhrupload.removeEventListener('crror', boundEventHandler);
              xhrupload.removeEventListener('abort', boundEventHandler);
              xhr.removeEventListener('load', boundEventHandler);
              xhr.removeEventListener('error', boundEventHandler);
              xhr.removeEventListener('readystatechange', boundEventHandler);

              this._xhr = null;
          } else {
            this.emit('uploaderror', {
              originEvent: event,
              status: xhr.status,
              statusText: xhr.statusText,
              source: 'http'
            });
          }

          break;

        case 'error':
          this.emit('uploaderror', {
            originEvent: event,
            status: xhr.status,
            statusText: xhr.statusText,
            source: 'FileHTML5'
          });

          break;

        case 'abort':
          this.emit('uploadcancel', {originEvent: event});
          break;

        case 'readystatechange':
          this.emit('readystatechange', {
            originEvent: event,
            readyState: event.target.readyState
          });
          break;
      }
    },

    startUpload: function (url, parameters, fileFieldName) {

      this._bytesUploaded = 0;

      this._xhr = new XMLHttpRequest();
      this._boundEventHandler = proxy(this._uploadEventHandler, this);

      var uploadData = new FormData(),
          fileField = fileFieldName || 'Filedata',
          xhr = this._xhr,
          xhrupload = xhr.upload,
          boundEventHandler = this._boundEventHandler,
          key;

      for (key in parameters) {
        uploadData.append(key, parameters[key]);
      }
      uploadData.append(fileField, this._file);

      xhr.addEventListener('loadstart', boundEventHandler,false);
      xhr.addEventListener('load', boundEventHandler, false);
      xhr.addEventListener('error', boundEventHandler, false);
      xhr.addEventListener('abort', boundEventHandler, false);
      xhr.addEventListener('loadend', boundEventHandler, false);
      xhr.addEventListener('readystatechange', boundEventHandler, false);
      xhrupload.addEventListener('progress', boundEventHandler,false);
      xhrupload.addEventListener('error', boundEventHandler, false);
      xhrupload.addEventListener('abort', boundEventHandler, false);

      xhr.open('POST', url, true);
      xhr.withCredentials = true;

      if (this._xhrHeaders) {
        for (key in this._xhrHeaders) {
          xhr.setRequestHeader(key, this._xhrHeaders[key]);
        }
      }

      xhr.send(uploadData);

      this.emit('uploadstart', {xhr: xhr});
    },

    cancelUpload: function () {
      this._xhr.abort();
    }

  });

  // 检查是否是原生的 File() 实例
  FileHTML5.isValidFile = function (file) {
    return (Win && Win.File && file instanceof Win.File);
  };

  // 检测浏览器原生上传的兼容性，XMLHttpRequest Leve 2
  FileHTML5.canUpload = function () {
    return (Win && Win.FormData && Win.XMLHttpRequest);
  };

  // Helper
  var uuid = 0;
  function guid() {
    return 'file-' + uuid++;
  }

  function proxy(f, c) {
    return function cb(e) {
      return f.call(c, e);
    };
  }

  return FileHTML5;

});
