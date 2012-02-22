/**
 * @Description: X gather module
 * @Author:      Leask Huang <leask@exfe.com>
 * @createDate:  Feb 21, 2012
 * @CopyRights:  http://www.exfe.com
 */


var moduleNameSpace = 'odof.record',
    ns = odof.util.initNameSpace(moduleNameSpace);

(function(ns) {

    ns.maxItem    = 23; // 233

    ns.cbfunction = null;

    ns.arrStack   = null;

    ns.curItem    = null;


    ns.init = function(callback) {
        this.reset();
        this.cbfunction = callback;
        $(document).bind('keydown', this.keyEvent);
    };


    ns.reset = function() {
        this.arrStack = [];
        this.curItem  = -1;
    };


    ns.keyEvent = function(event) {
        var keyCode = event.which ? event.which : event.keyCode,
            keyMeta = event.metaKey || event.ctrlKey;
        if (keyMeta && event.shiftKey && keyCode === 90) {
            odof.record.redo(); // command/ctrl + shift + z
            event.preventDefault();
        } else if (keyMeta && keyCode === 90) {
            odof.record.undo(); // command/ctrl + z
            event.preventDefault();
        }
    };


    ns.trim = function() {
        while (this.arrStack.length > this.maxItem) {
            this.arrStack.shift();
            this.curItem--;
        }
    };


    ns.push = function(item) {
        this.arrStack.splice(this.curItem + 1);
        this.arrStack.push(odof.util.clone(item));
        this.trim();
        this.curItem = this.arrStack.length - 1;
    };


    ns.select = function(index) {
        this.cbfunction(index < 0 || index > this.arrStack.length - 1
                      ? null : odof.util.clone(this.arrStack[index]));
    };


    ns.last = function() {
        return this.arrStack.length
             ? odof.util.clone(this.arrStack[this.arrStack.length - 1]) : null;
    };


    ns.undo = function() {
        this.select(--this.curItem);
        if (this.curItem < 0) {
            this.curItem = 0;
        }
    };


    ns.redo = function() {
        this.select(++this.curItem);
        if (this.curItem > this.arrStack.length - 1) {
            this.curItem = this.arrStack.length - 1;
        }
    };

})(ns);
