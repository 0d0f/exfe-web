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

## package.json
    例子, name description version author hint 必填项
    {
      "name": "blamo",
      "description": "a thing that blams the o's",
      "version": "1.0.0",
      "keywords": ['blamo', 'ender'],
      "homepage": "http://example.com",
      "authors": ["Mr. Blam", "Miss O"],
      "repository": {
        "type": "git",
        "url": "https://github.com/fake-account/blamo.git"
      },
      "dependencies": {
        "klass": "*"
      },
      "pull": {
        "url": "",
        "minurl": ""
      }
      "hint": true
    }
