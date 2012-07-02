var rex = require('../lib/rex.js');
//console.dir(rex);
console.dir(rex([1,2,3]).chain().map(function (v) {
  return v += 4;
}).filter(function (v) {
  return v === 5;
}));

console.dir(rex.map([1,2,3], function (v) {
  return v += 4;
}));

console.dir(rex.chain([1,2,3]).map(function (v) {
  return v += 4;
}));
