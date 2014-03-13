/**
 * Список объектов с возможностью добавления через автодополнение
 * @user: terrasoff
 */
BB.ModelsList = Backbone.View.extend({
    tagName: 'div',
    className: 'models-select-list',
    template: _.template('#templateModelsList'),
    template_item: _.template('#templateModelsListItem'),
    models: [],

    /**
     * Объект, который сможет предоставить данные для списка с автодополнением
     * Данные предоставляются по событию BB.Autocomplete.Events.DATA
     * Если объект не задан, то будет сгенерированно событие в контексте объекта BB.ModelList
     */
    DataProvider: null,

    /**
     * Нажали добавить - выбираем текущую модель
     */
    currentModel: null,

    classes: {
        'list':'.filter-list',
        'filter':'.filter-list-value',
        'filter_items':'.filter-list-items',
        'items':'.items'
    },

    initialize: function(options) {
        _.extend(this,options);
        this.render();

        // добавляем объекты
        this.on(BB.ModelsList.Events.SELECTED,this.addNextModel,this);
        // удаляем объекты
        this.on(BB.ModelsList.Events.REMOVED,this.onDeleteModel,this);

        if (this.items == undefined)
            this.items = [];

        _.each(this.items, $.proxy(function(attributes) {
            var item = new BB.ModelsList.Model(attributes);
            this.addModel(item);
        },this));

        this.addNextModel();

        return this;
    },

    onDeleteModel: function(item) {
        console.log("remove from collection");
        var index = this.models.indexOf(item);
        this.models.remove(index);
        return this;
    },

    showFilter: function() {
        this.$filterlist.show();
        this.$filter.focus();
    },

    /**
     * Продолжаем добавлять
     */
    addNextModel: function() {
        var model = new BB.ModelsList.Model();
        this.addModelView(model);
    },

    /**
     * Продолжаем добавлять модели
     * @param model
     * @returns {*}
     */
    addModelView: function(model) {
        // добавляем модель в список
        var item = new BB.ModelsList.ModelView({
            parent: this,
            DataProvider: (this.DataProvider ? this.DataProvider : this),
            model: model,
            template: this.template_item
        });
        this.$models.append(item.$el);
        this.models.push(item);

        return this;
    },

    /**
     * Текущие выбранные модели
     * @returns {Array}
     */
    getModels: function() {
        var models = this.models;
        var _models = [];

        for(var i=0;i<models.length;i++) {
            if (!models[i].model.isNew())
                _models.push(models[i].model);
        }
        return _models;
    },

    render: function () {
        this.$el.html(this.template());
        this.$models = this.$el.find(this.classes.items);

        this.$filterlist = this.$el.find(this.classes.filterlist);
        this.$filter = this.$el.find(this.classes.filter);
        this.$filter.on('keypress',this.filter);

        return this;
    }

});

BB.ModelsList.Events = {
    SELECTED: 'ModelsList:added',
    REMOVED: 'ModelsList:removed'
};

_.extend(BB.ModelsList, Backbone.Events); // подмешиваем событийность Backbone
