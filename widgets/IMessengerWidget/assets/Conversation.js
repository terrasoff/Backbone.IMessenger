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
        var id = this.getId();
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
        var title = '';
        this.receivers.each(function(model) {
            if (model.getId() != this.user.getId()) {
                title += model.attributes.name + ', ';
            }
        }.bind(this));

        this.title = title.replace(/,\s+$/,'');
//        if (!this.title)
            this.title = 'conversation'+this.getId();
        return this;
    },

    getTitle: function() {
    	return this.title;
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

    getId: function() {
        return this.get('idConversation')
    }
});