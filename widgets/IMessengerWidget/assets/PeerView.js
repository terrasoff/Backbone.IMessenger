/**
 * Представление активного окна сообщений
 * @type {Backbone.View}
 */
IMessenger.PeerView = Backbone.View.extend({
    className: "messenger-peer",
    template: _.template($('#IMessengerPeer').html()),

    isActive: false,

    classes: {
        messages: '.messenger-peer-messages',
        input: '.messenger-peer-input',
        btn: '.messenger-peer-send'
    },

    events: {
        'click .messenger-peer-send': 'sendMessage'
    },

    initialize: function(params) {
        this.model.on('change:active',this.onState,this);
        this.model.messages.on('add', this.addMessage, this);

        return this.render();
    },

    /**
     * Показываем/прячем Активный разговор
     * @returns {IMessenger.PeerView}
     */
    onState: function() {
        var state = this.model.get('active');
        this.$el.toggle(state);
        if (state && !this.isActive) {
            this.$messages.scrollTop(999999); // force scroll bottom
            this.isActive = true;
            this.$messages.scroll(this.onScroll.bind(this));
        }

        return this;
    },

    onScroll: function(e) {
    	if ($(e.currentTarget).scrollTop() - 300 < 0)
            this.getHistory();
    },

    /**
     * Отправляем сообщение
     */
    getHistory: function() {
        console.log("history conversation:"+this.model.getId());
        this.model.trigger(IMessenger.Events.Peer.HISTORY, this.model);
    },

    /**
     * Отправляем сообщение
     */
    sendMessage: function() {
        var message = new IMessenger.Message({
            idConversation: this.model.getId(),
            body: this.$input.val()
        })
        this.model.trigger(IMessenger.Events.Message.SEND, message);
    },

    /**
     * Добавили новое сообщение в разговор
     * @param message {IMessenger.Message}
     * @param options Obect
     *  history Boolean новое или история?
     */
    addMessage: function(message, collection, options)
    {
        console.dir(options);
        if (options == undefined) options = {}
        if (options.history == undefined) options.history = false;
        history = options.history

        var user = this.model.receivers.get(message.getUserId());
        var item = new IMessenger.PeerMessageView({model: message, user: user});
        var process = history ? 'prepend' : 'append';
        this.$messages[process](item.$el);

        var top = this.$messages.scrollTop() + item.$el.outerHeight(true)
        this.$messages.scrollTop(top);
    },

    render: function() {
        this.$el.html(this.template({model: this.model}));

        this.$messages = this.$el.find(this.classes.messages); // сообщения
        this.$input = this.$el.find(this.classes.input); // поле ввода нового сообщения

        this.model.messages.each(function(message) {
            this.addMessage(message);
        }.bind(this));

        return this;
    }

});