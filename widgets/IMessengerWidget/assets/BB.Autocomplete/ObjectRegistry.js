/**
 * Реализация паттерна Реестр
 * User: terrasoff
 */

ObjectRegistry = function() {

    this._registry = {};

    this.set = function(key,value) {
        this._registry[key] = value;
    };

    this.get = function(key) {
        return this._registry[key] != undefined
            ? this._registry[key]
            : false;
    };

    // сбрасываем кеш
    this.flush = function() {
        this._registry = {};
    };
};