/**
 * Представление активного окна сообщений
 * @type {Backbone.View}
 */
IMessenger.ObjectPeerView = IMessenger.PeerView.extend({
    template: _.template($('#template_messenger_peer').html()),

    classes: {
        wrap: '.messagewrap.edit-type',
        send: '.peer-send'
    },

    object: null,

    initialize: function(params) {
        _.extend(this,params);

        // перегружаем классы
        _.extend(this.classes,IMessenger.ObjectPeerView.__super__.classes);
        this.$el.on('focus',this.classes.wrap, $.proxy(this.onStartType,this));
        this.$el.on('blur',this.classes.wrap, $.proxy(this.onCompleteType,this));

        // изменилось число непрочитанных в разговоре
        this.model.on(IMessenger.Events.Receiver.UNREAD,this.onUnreadMessages,this);
        this.model.on('change:active',this.onSelectUser,this);
        this.model.messages.on('add',this.onAddMessage,this);

        // наследуем
        return IMessenger.ObjectPeerView.__super__.initialize.call(this);
    },

    onSelectUser: function() {
//        console.log("user selected: "); console.dir(this.model);
    	this.$el.toggle(this.model.get('active'));
    },

    /**
     * Отправляем сообщение
     */
    onSend: function() {
        console.log("send to conversation:"+this.model.getId());
        console.log(this.getBody());
        this.model.trigger(IMessenger.Events.Message.SEND,{
            toUserId: this.model.getId(),
            body: this.getBody()
        });
        this.$input.html('');
    },

    /**
     * Пробуем прочитать список сообщений
     * @param messages {Array} список сообщений (IMessenger.Message)
     */
    onUnreadMessages: function(messages) {
        // в упрощенной форме считаем, что пользователь сразу прочитал сообщения
    	console.log("reading...");
        // теперь эти сообщения прочитаны (Object отправит команду)
        this.model.trigger(IMessenger.Events.Receiver.READ,messages)
    },

    onStartType: function(e) {
        if (!this.$wrap.hasClass('show'))
            this.$wrap.addClass('show');
    },

    onCompleteType: function(e) {
        this.$wrap.removeClass('show');
    },

    onClose: function(model) {
        // не забываем убрать события с модели
        this.model.off(IMessenger.Events.Tab.CLOSED,this.onClose);
        this.model.off('change:active',this.onState);
        this.model.getMessages().off('add',this.onAddMessage);
        // закрываем активный разговор
        this.model.trigger(IMessenger.Events.Peer.CLOSED,this.model);
        // удаляем активный разговор
        this.remove();
    },

    /**
     * Добавляем сообщение в коллекцию (дальше срабатывает триггер add и запускается onAddMessage)
     * @param message {IMessenger.Message}
     */
    addMessage: function(message) {
        this.model.getMessages().add(message);
    },

    /**
     * Добавили новое сообщение в разговор
     * @param message {IMessenger.Message}
     */
    onAddMessage: function(message,collection,options) {
//        console.log("peer message:");
//        console.dir(message.get('idUser'));
//        console.dir(this);

        /**
         * автор сообщения
         * @type {IMessenger.Receiver}
         */
        var id = message.get('idUser');
        var user = id == this.object.myself.getId()
            ? this.object.myself
            : this.model

        item = new IMessenger.Classes.PeerMessageView({
            model: message,
            user: user
        });

//        console.log("history: "+(options.at != undefined && options.at == 0));
        options.at != undefined && options.at == 0
            ? this.$messages.append(item.$el) // история
            : this.$messages.prepend(item.$el); // обновление

        var height = this.$el.find('.peer-messages').height();
    },

    getBody: function() {
        return this.$input.html();
    },

    render: function() {
        IMessenger.ObjectPeerView.__super__.render.call(this);
        return this;
    },

    render: function() {
        this.$el.html(this.template({model: this.model}));

        this.$wrap = this.$el.find(this.classes.wrap); // поле ввода нового сообщения
        this.$messages = this.$el.find(this.classes.messages); // сообщения
        this.$input = this.$el.find(this.classes.input); // поле ввода нового сообщения
        this.$btn_send = this.$el.find(this.classes.send); // поле ввода нового сообщения

        // TODO тест для подгрузки пользователей
        this.$el.find('.btn_more').on('click', $.proxy(function() {
            console.log("more receivers");
            this.model.getHistory();
        },this));

        this.$btn_send.on('click',this.send);

        return this;
    }

});