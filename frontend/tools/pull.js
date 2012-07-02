var Fs = require('fs');
var Url = require('url');
var Path = require('path');
// npm install -g request
var Request = require('request');

var jslib = Path.resolve(__dirname, '../jslib');

function main(args, argv) {
  var module, package, pull, config, path;

  switch (args) {
    case 1:
      module = argv[0];
      path = jslib + '/' + module;
      config = path + '/package.json';
      if (Path.existsSync(config)) {
        package = JSON.parse(Fs.readFileSync(config, 'utf-8'));
        pull = package.pull;
        console.log('Download ' + module, path + '/lib/' + module + '.js');
        var file = Fs.createWriteStream(path + '/lib/' + module + '.js');
        if (package.module) {
          file.write(package.module.header);
        }
        Request(pull.url, function (err, response, body) {
          if (!err && response.statusCode == 200) {
            file.write(body);
            if (package.module) {
              file.write(package.module.footer);
            }
          }
        });
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
