/**
 * User: terrasoff
 * Date: 5/30/13
 * Time: 1:37 PM
 *
 * Список с автодополнением
 * Для инициализации автодополнения нужно передать параметры:
 *      ItemModel - модель элемента списка
 *      ItemView - представление элемента списка
 * Так же необходимо организовать поставку данных по требованию автодополнения.
 * И обработать выбор элемента автодополнения.
 * Это реализованно (соответственно) через события
 * BB.Autocomplete.Events.DATA
 * BB.Autocomplete.Events.COMPLETED
 *
 * Например:
 * autocomplete = new BB.Autocomplete({
 *      ItemModel: IMessenger.Receiver,
 *      ItemView: CustomItemView
 * });
 * autocomplete.on(BB.Autocomplete.Events.DATA,this.getUserList,this);
 * autocomplete.on(BB.Autocomplete.Events.COMPLETED,this.onComplete,this);
 */

getGlobalNamespace('BB').Autocomplete = Backbone.View.extend({
    tagName: "div",
    className: "autocomplete",
    template: _.template($('#template_autocomplete').html()),

    /**
     * Объект, которому нужно сообщить о выборе элемента из списка
     */
    target: null,

    /**
     * Объект, который сможет предоставить данные для списка с автодополнением
     * Данные предоставляются по событию BB.Autocomplete.Events.DATA
     */
    DataProvider: null,

    /**
     * Модель для элемента в списке (обязательно указвыается)
     */
    ItemModel: null,

    /**
     * Представление элемента в списке
     */
    ItemView: null,

    /**
     * Кеш для хранения результатов запросов
     */
    cache: null,

    /**
     * Элементы в списке
     * type {Backbone.Collection}
     */
    items: null,

    /**
     * Модель текущего выбранного элемента
     * type {Object}
     */
    model: null,

    wait: 1000,
    regexp: {
        query: /\w{1,6}/i
    },
    queryParameter: "query",
    currentText: "",

    initialize: function (options) {
        $this = this;
        _.extend(this, options);

        // если не задан родит
        if (!this.target)
            this.target = this;

        // кеш по-умолчанию
        if (!this.cache)
            this.cache = new BB.Cache();

        // представление элемента по-умолчанию
        if (!this.ItemView)
            this.ItemView = BB.Autocomplete.ItemView;

        this.items = new Backbone.Collection();
        this.items.on('add',this.onAddItem,this);
        this.items.on('reset',this.onReset,this);

        this.$el.html(this.template());
        this.$input = this.$el.find('input');
        this.$list = this.$el.find('.autocomplete-list');
        this.$input.attr("autocomplete", "off");

        this.$input
            .keyup(_.bind(this.keyup, this))
            .keydown(_.bind(this.keydown, this));

        this.filter = _.debounce(this.filter,this.wait);

        return this;
    },

    keydown: function (event) {
        if (event.keyCode == 38) return this.move(-1);
        if (event.keyCode == 40) return this.move(+1);
        if (event.keyCode == 13) return this.onEnter();
        if (event.keyCode == 27) return this.hide();
    },

    keyup: function () {
        var keyword = this.$input.val();
        if (this.isChanged(keyword)) {
            if (this.isValid(keyword)) {
                this.items.reset();
                this.filter(keyword);
            }
        }
    },

    focus: function() {
        this.$input.focus();
        return this;
    },

    filter: function(query) {
        if (result = this.cache.get(query)) {
            console.log("cache:");
            console.dir(result);
        } else {
            object = (this.DataProvider ? this.DataProvider : this);
            console.log("ready:"); console.dir(object);
            object.trigger(BB.Autocomplete.Events.DATA,query,this)
        }
    },

    isValid: function (keyword) {
        return this.regexp['query'].test(keyword);
    },

    isChanged: function (keyword) {
        return this.currentText != keyword;
    },

    move: function (position) {
        var current = this.$el.children(".active"),
            siblings = this.$el.children(),
            index = current.index() + position;
        if (siblings.eq(index).length) {
            current.removeClass("active");
            siblings.eq(index).addClass("active");
        }
        return false;
    },

    onEnter: function () {
        this.$el.children(".active").click();
        return false;
    },

    update: function (data) {
        console.log("update autocomplete"); console.dir(data);
        if (data && data.length) {
            var item;
            for(var i=0;i<data.length;i++) {
                item = new this.ItemModel(data[i]);
                this.items.add(item);
            }
        }
    },

    onReset: function() {
        this.$list.empty();
    },

    onAddItem: function(model) {
        var item = new this.ItemView({model: model});
        item.on(BB.Autocomplete.Events.SELECTED,this.onSelect,this);
        this.$list.append(item.$el);
    },

    onSelect: function(model) {
        console.log("select:"); console.dir(model);
        this.model = model;
        this.$list.hide();
        this.target.trigger(BB.Autocomplete.Events.COMPLETED,model);
    },

    getModel: function() {
        return this.model;
    }
});

_.extend(BB.Autocomplete,Backbone.Events);

BB.Autocomplete.Events = {
    // элемент из списка автодополнения выбран
    COMPLETED:'autocomplete:completed',
    // выбрали элемент в списке автодополнения
    SELECTED:'autocomplete:selected',
    // запрос данных на построение автодополнения
    DATA:'autocomplete:data'
};

BB.Autocomplete.ItemView = BB.Autocomplete.ItemView