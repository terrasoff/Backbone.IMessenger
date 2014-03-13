/**
 * Модель активного разговора
 * @type {Backbone.Model}
 */
IMessenger.Conversation = Backbone.Model.extend({

    idAttribute: 'idConversation',

    /**
     * участники текущего разговора
     * type {Object}
     */
    receivers: null,

    /**
     * сообщения в текущем разговоре
     * type {Backbone.Collection}
     */
    messages: null,

    myself: null, // это я

    defaults: {
        /**
         * Заголовок разговора
         * type {String}
         */
        title: '',

        /**
         * При запросах на обновление надобно знать идентификатор посл.загруженного сообщения
         * type {Number}
         */
        maxId: 0,

        /**
         * Число непрочитанных
         * type {Number}
         */
        unread: 0,

        active: false
    },

    initialize: function(attributes, options)
    {
        this.set(attributes);

        this.user = options.user;

        // участники текущего разговора
        this.receivers = new Backbone.Collection();
        if (options.users != undefined) {
            _.each(options.users, function(attributes) {
                var model = new IMessenger.Receiver(attributes);
                this.receivers.add(model);
            }.bind(this));
        };

        this.messages = new Backbone.Collection();
        // прочитали определенное сообщение
        this.messages.on(IMessenger.Events.Message.READ,this.onRead,this);

        if (options.messages != undefined)
            this.addMessages(options.messages);

        // заголовок разговора
        this.setTitle();
    },

    addMessages: function(messages, history)
    {
        if (history) {
            for(i=0;i<messages.length;i++) {
                item = new IMessenger.Message(messages[i]);
                this.messages.unshift(item, {history: history});
            };
        } else {
            for(i=messages.length-1;i>=0;i--) {
                item = new IMessenger.Message(messages[i]);
                this.messages.add(item, {history: history});
            };
            this.trigger(IMessenger.Events.Conversation.UPDATE);
        }
        return this;
    },

    addHistory: function(messages) {
    	this.addMessages(messages, true);
    },

    setUnread: function(total) {
    	this.unread = total;
        console.log("setUnread: "+total);
        this.trigger(IMessenger.Events.Conversation.UNREAD,this);
    },

    getUnread: function() {
    	return this.get('unread')-0;
    },

    /**
     * прочитали сообщение в разговоре
     * @param model {IMessenger.Message}
     */
    onRead: function(message) {
        this.trigger(IMessenger.Events.Message.READ,message);
    },

    /**
     * Название разговора
     * @returns {string}
     */
    setTitle: function() {
        var receivers = this.receivers.filter(function(model) {
            return model.getId() != this.user.getId();
        }.bind(this));

        var title = '';
        for (var i=0; i<receivers.length; i++) {
            title = receivers[i].get('name') + ', ';
        }
        this.title = title.trim('\\,\\s+');
        return this;
    },

    getTitle: function() {
    	return this.title;
    },

    /**
     * Ищем сообщения с заданным идентификатором в разговоре
     * @param list {Array} список идентификаторо сообщений
     * @returns {Array}
     */
    getMessagesById: function(list) {
        var i, j,message,idMessage;
        var result = [];

        var id;
        var messages = this.getMessages();
        for(i in list) {
            message = messages.get(list[i]);
            if (message) {
                result.push(message);
            }

        }

        return result;

    },

    select: function() {
        this.setActive(true);
    },

    close: function(state) {
        this.setActive(false);
    },

    setActive: function(status) {
        this.set({active:status})
    },

    getMessages: function() {
        return this.messages;
    },

    getLastMessage: function() {
        return this.getMessages().last();
    },

    getId: function() {
        return this.get('idConversation')
    }
});