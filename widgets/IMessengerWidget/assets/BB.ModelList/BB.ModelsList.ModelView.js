BB.ModelsList.ModelView = Backbone.View.extend({
    tagName: 'div',
    className: 'item',

    model: BB.ModelsList.Model,
    DataProvider: null,
    ItemModel: null,

    events: {
        'click .item-add': 'onStartAdd',
        'click .item-delete': 'onDelete'
    },

    initialize: function(options) {
        _.extend(this,options);

        // класс модели по-умолчанию
        if (!this.ItemModel)
            this.ItemModel = BB.ModelsList.Model;

        if (!this.model)
            this.model = new this.ItemModel();

        this.render();
    },

    /**
     * выбрали модель из списка автодополнения
     * @param model {BB.Autocomplete.ItemModel}
     */
    onStartAdd: function(model) {
        if (this.model.isNew()) {
            this.autocomplete = new BB.Autocomplete({
                target: this,
                ItemModel: this.ItemModel,
                DataProvider: (this.DataProvider ? this.DataProvider : this)
            });
            this.autocomplete.on(BB.Autocomplete.Events.COMPLETED,this.onSelect,this);
            this.$el.html(this.autocomplete.$el);
            this.autocomplete.focus();
        }
        return false;
    },

    /**
     * Выбрали автодополнение из списка
     * @param model {BB.Autocomplete.ItemModel}
     */
    onSelect: function(model) {
        this.model = model;
        this.render();
        this.parent.trigger(BB.ModelsList.Events.SELECTED,model);
    },

    onDelete: function() {
        console.log("delete model");
        console.dir(this.model);
        this.parent.trigger(BB.ModelsList.Events.REMOVED,this.model);
    },

    render: function() {
        if (this.model.isNew()) {
            this.on(BB.Autocomplete.Events.COMPLETED,this.onSelect,this);
            this.$el.html(this.template({model: this.model}));
        } else {
            console.log("re-render");
            this.$el.html(this.template({model: this.model}));
        }
        return this;
    }
});

_.extend(BB.ModelsList.ModelView, Backbone.Events); // подмешиваем событийность Backbone