var Fs = require('fs');
var Url = require('url');
var Util = require('util');
var Path = require('path');
// npm install -g request
var Request = require('request');

var less = Path.resolve(__dirname, '../less');

function main(args, argv) {
  var module, package, pull, config, path;

  switch (args) {
    case 1:
      module = argv[0];
      path = less + '/' + module;
      config = path + '/package.json';
      if (Path.existsSync(config)) {
        package = JSON.parse(Fs.readFileSync(config, 'utf-8'));
        pull = package.pull;
        muitl_down(pull.url, pull.baseurl, path);
      } else {
        console.log('Not found module ' + module + '\'s package.json.');
      }
      break;
    default:
      console.log('Please input module name.');
  }

}

var argv = process.argv.slice(2);
main(argv.length, argv);

function muitl_down(urls, baseurl, path) {
  var n;
  if (!Util.isArray(urls)) {
    urls = [urls];
  }

  urls.forEach(function (v) {
    console.log('Download ' + v, path + '/lib/' + v);
    Request(baseurl + v).pipe(Fs.createWriteStream(path + '/lib/' + v));
  });
}
