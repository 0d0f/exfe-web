var Fs = require('fs');
var Path = require('path');
var Util = require('util');

var mkdirp = require('mkdirp').sync;
var jshint = require('jshint').JSHINT;
var jshintrc = JSON.parse(Fs.readFileSync(__dirname + '/jshintrc', 'utf8').replace(/\/{2}[^\n]+\n/g, ''));
var jsp = require("uglify-js").parser;
var pro = require("uglify-js").uglify;

//var jsdist = Path.resolve(__dirname, '../js');
var jsdist = Path.resolve(__dirname, '../../static/js');
var jslib = Path.resolve(__dirname, '../jslib');


function main(args, argv) {
  var module, package, path, config, newpath, source, dist, mindist, jscheck = true;

  switch (args) {
    case 1:
      module = argv[0];
      path = Path.join(jslib, module);
      config = Path.join(path, 'package.json');
      if (Fs.existsSync(config)) {
        source = Fs.readFileSync(Path.join(path, 'lib', module + '.js'), 'utf8');
        package = JSON.parse(Fs.readFileSync(config, 'utf8'));
        newpath = Path.join(jsdist, module, package.version);

        if ('hint' in package) jscheck = package.hint;
        if (jscheck) {
          if (hint(source, jshintrc)) {
            console.log('JSHint check passed.');
          } else {
            console.log('JSHint found errors.');
            hintErrors();
          }
        }

        mkdirp(newpath);

        dist = Path.join(newpath, module + '.js');
        mindist = Path.join(newpath, module + '.min.js');

        Fs.writeFileSync(dist, source, 'utf8');
        Fs.writeFileSync(mindist, compress(source), 'utf8');
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

function hint(src, cfg) {
  return jshint(src, cfg);
}

function hintErrors() {
  jshint.errors.forEach(function( e ) {
    if ( !e ) { return; }

    var str = e.evidence ? e.evidence : "",
    character = e.character === true ? "EOL" : "C" + e.character;

    if ( str ) {
      str = str.replace( /\t/g, " " ).trim();
      console.log( " [L" + e.line + ":" + character + "] " + e.reason + "\n  " + str + "\n");
    }
  });
}

function compress(code) {
  var ast = jsp.parse(code);
  ast = pro.ast_mangle(ast);
  ast = pro.ast_squeeze(ast);
  return pro.gen_code(ast) + ';';
}
