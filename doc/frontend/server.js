#!/usr/bin/env node
var http = require('http');
var h5bp = require('h5bp');

/*
var express = require('express');
var server = h5bp.server(express, {
    root: __dirname
});
server.listen(8080);
*/

var connect = require('connect');
var app = connect()
  .use(connect.static(__dirname))
  .listen(8080);

console.log('ok - 127.0.0.1:8080');
