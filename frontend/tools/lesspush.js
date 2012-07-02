var Fs = require('fs');
var Path = require('path');
var Util = require('util');
// npm install -g less
var less = require('less');

Util.puts('\n', stylize('LESS', 'underline'), '\n');

var argv = process.argv.slice(2);
var args = argv.length;

var v2 = 'v2';

main(args, argv);

function main(args, argv) {
  switch (args) {
    case 1:
 
      break;
    default:
      Util.puts('\n', stylize('暂时只能 compile v2 中的less 文件'), '\n');
  }
}

// Stylize a string
function stylize(str, style) {
  var styles = {
    'bold'      : [1,  22],
    'inverse'   : [7,  27],
    'underline' : [4,  24],
    'yellow'    : [33, 39],
    'green'     : [32, 39],
    'red'       : [31, 39]
  };
  return '\033[' + styles[style][0] + 'm' + str +
        '\033[' + styles[style][1] + 'm';
}
