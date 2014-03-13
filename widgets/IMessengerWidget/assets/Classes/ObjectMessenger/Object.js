/**
 * Объект
 * @type {Backbone.Model}
 */
IMessenger.Object = Backbone.Model.extend({

    idAttribute: 'idConversation',

    // участники разговора
    receivers: null,

    /**
     * это я
     * @type {Object}
     */
    myself: null,

    defaults: {
        maxId: 0, // идентификатор посл.загруженного сообщения
        total: 0, // общее число сообщений
        unread: 0 // Общее число непрочитанных
    },

    initialize: function() {
        this.receivers = new DynamicCollection([],{
            event: IMessenger.Events.Receiver.LOAD
        });
        console.log("receivers total: "+this.receivers.length);

        this.receivers.on('add',this.onAdd,this);
        this.receivers.on(IMessenger.Events.Receiver.SELECTED,this.onSelectReceiver,this);
        this.receivers.on(IMessenger.Events.Receiver.READ,this.onReadMessages,this);
        // листаем список собеседников (подгрузка)
        this.receivers.on(IMessenger.Events.Receiver.LOAD,this.onLoadReceivers,this);
        this.receivers.on(IMessenger.Events.Receiver.HISTORY,this.getUserHistory,this);
        this.receivers.on(IMessenger.Events.Receiver.MAX_ID,this.onMaxId,this);
        this.receivers.on(IMessenger.Events.Message.SEND,this.onSend,this);

        return this;
    },

    onSend: function(data) {
        // добавляем идентификатор разговора
        data.idConversation = this.getId();
        var message = new IMessenger.Message(data);
        this.trigger(IMessenger.Events.Peer.SEND,message);
    },

    // выбрали объект
    select: function(object) {
        console.log("select object: "+this.getId());
        console.log(this.getReceiversTotal());
        console.dir(this.receivers);
        if (!this.getReceiversTotal()) {
            this.loadReceivers();
        }
    },

    // надо подгрузить пользователей
    loadReceivers: function() {
        this.receivers.load();
    },

    // стартуем загрузку пользователей
    onLoadReceivers: function() {
        this.trigger(IMessenger.Events.Receiver.LOAD,this)
    },

    onAdd: function(receiver) {
        this.trigger(IMessenger.Events.Object.ADD_RECEIVER,receiver);
    },

    /**
     * Выбрали пользователя в списке в рамках объекта - подгружаем сообщения
     * @param model {IMessenger.ObjectReceiver}
     */
    onSelectReceiver: function(model) {
        if (!model.messages.length)
            this.getReceiverMessages(model);
    },

    /**
     * Нужно подгрузить историю сообщений
     * @param model {IMessenger.ObjectReceiver}
     */
    getUserHistory: function(model) {
        this.getReceiverMessages(model,true);
    },

    /**
     * Получаем сообщения пользователя
     * @param model {IMessenger.ObjectReceiver}
     */
    getReceiverMessages: function(model,history) {
        var data = {
            idConversation: this.getId(),
            idUser: model.getId()
        };

        // листаем сообщения назад по истории
        if (history != undefined) data.sinceMessageId = model.getFirstMessageId();
        // листаем сообщения вперед
        else data.maxMessageId = model.getLastMessageId();


        this.trigger(IMessenger.Events.Object.MESSAGES,data);
    },

    /**
     * Прочитали сообщения
     * @param receiver
     */
    onReadMessages: function(data) {
        console.log("read messages");
        var receiver = data.receiver;
        var messages = data.messages;
        var list = [];
        for(var i=0; i<messages.length; i++) {
            list.push(messages[i].getId());
        };
        // формируем команду для API и отправляем
    	this.trigger(IMessenger.Events.Object.READ,{
            idConversation: this.getId(),
            idUser: receiver.getId(),
            idMessages: list
        });
    },

    getObject: function(field) {
        return field == undefined
            ? this.get('object')
            : this.get('object')[field];
    },

    getReceivers: function() {
        this.trigger(IMessenger.Events.Object.RECEIVERS,this);
    },

    getUnread: function() {
        return this.get('unread')-0;
    },

    getTotal: function() {
        return this.get('total')-0;
    },

    getReceiversTotal: function() {
    	return this.receivers.length;
    },

    getId: function() {
        return this.get('idConversation');
    }
});
