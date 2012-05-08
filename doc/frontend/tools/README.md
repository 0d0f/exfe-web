工具使用帮助
===========

## pull.js
  * 根据 jslib/xxx/package.json 文件中的配置，下载 xxx 模块
  * ***`:node pull.js jquery`***

## push.js
  * 根据 jslib/xxx/package.json 文件中的配置，将 xxx 模块, 发布到 js 目录
  * ***`:node push.js jquery`***
  * ***`:tree js/jquery`***
    js/jquery/
          └── 1.7.2
                ├── jquery.js
                └── jquery.min.js
    1 directory, 2 files

## lesspull.js
  * 根据 less/xxx/package.json 文件中的配置，下载 xxx 模块
  * ***`:node lesspull.js bootstrap`***
