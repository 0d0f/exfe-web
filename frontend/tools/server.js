#!/usr/bin/env node
var Http = require('http');
var Path = require('path');

/*
var express = require('express');
var server = h5bp.server(express, {
    root: __dirname
});
server.listen(8080);
*/

var connect = require('connect');
var app = connect()
  .use(connect.static(Path.join(__dirname, '..')))
  .listen(8081);

console.log('ok - 127.0.0.1:8080');
