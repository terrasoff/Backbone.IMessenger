/**
 * Модель активного разговора
 * @type {Backbone.Model}
 */
DynamicCollection = Backbone.Collection.extend({
    // всего элементов в коллекции
    total: null,

    // генерируем событие, чтоб подгрузить еще моделей
    event: 'DynamicCollection:get',

    initialize: function(models,options) {
        _.extend(this,options);
    },

    load: function() {
        if (this.length < this.total || this.total === null) {
            console.log(this.event);
            this.trigger(this.event);
        }
    },

    setTotal: function(total) {
    	this.total = total
    }

});