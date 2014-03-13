/**
 * Участник разговора
 */
IMessenger.ObjectReceiver = IMessenger.Receiver.extend({

    idAttribute: 'idUser',

    /**
     * Моя переписка с данным пользователем в рамках объекта
     * @type {Backbone.Collection}
     */
    messages: null,

    defaults: {
        total: 0,
        unread: 0
    },

    initialize: function() {
        // сообщения из переписки с этим собеседником
        this.messages = new Backbone.Collection();
        this.on('change:active',this.onSelect,this);
    },

    /**
     * Добавляем сообщения от пользователя
     * Вызывается при update
     * @param messages {Array} сообщения {Object}
     * @param history {Boolean} это история или обновление?
     */
    addMessages: function(messages,history) {
        if (history == undefined) history = true; // по умолчанию обновляем
//        console.log("messages commin ("+history+"):"); console.dir(messages);

        var message;
        var list = [];
        for(var i=0;i<messages.length;i++) {
            message = new IMessenger.Message(messages[i]);
            history
                ? this.messages.unshift(message)
                : this.messages.add(message);
            // запоминаем непрочитанные сообщения, чтоб потом отправить запрос на чтение этих сообщений
            if (!message.isRead())
                list.push(message)
        }
        // есть непрочитанные - надо читать
        // политика чтения будет определяться вьюхой (ObjectPeerView)
        if (list.length) this.trigger(IMessenger.Events.Receiver.UNREAD,{
            receiver: this,
            messages: list
        });
    },

    read: function(data) {
        this.set({unread: data.unread.user})
        var messages = data.messages;
        for(var i=0;i<messages.length;i++) {
            var message = this.messages.get(messages[i].idMessage);
            if (message) message.read();
        };
    },

    onSelect: function() {
        this.trigger(IMessenger.Events.Receiver.SELECTED,this);
    },

    getHistory: function() {
        this.trigger(IMessenger.Events.Receiver.HISTORY,this);
    },

    getMessagesTotal: function() {
    	return this.messages.length;
    },

    getUnread: function() {
        return this.get('unread')-0;
    },

    getTotal: function() {
        return this.get('total')-0;
    },

    active: function(state) {
        this.set({active: state});
    },

    getLastMessageId: function() {
    	var message = this.messages.last();
        return message != undefined
            ? message.getId()
            : 0;
    },

    getFirstMessageId: function() {
        var message = this.messages.first();
        return message != undefined
            ? message.getId()
            : null;
    },

    getAvatar: function() {
        return this.get('avatar');
    }

});