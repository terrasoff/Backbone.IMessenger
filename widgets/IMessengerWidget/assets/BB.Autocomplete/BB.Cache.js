/**
 * Локальный кеш
 * User: terrasoff
 * Date: 5/30/13
 * Time: 1:37 PM
 */

getGlobalNamespace('BB').Cache = function(options) {

    this.data = {};

    this.init = function(options) {

    };

    // пишем в кеш
    this.set = function(key,value) {
        this.data[key] = value;
    };

    // читаем из кеша
    this.get = function(key) {
        return this.data[key] != undefined
            ? this.data[key]
            : false;
    };

    // сбрасываем кеш
    this.flush = function() {
        this.data = {};
    };

    this.init(options);
};