/**
 * Представление разговора
 * @type {Backbone.ConversationView}
 */
IMessenger.ObjectConversationView = IMessenger.ConversationView.extend({
    // текущий активный разговор
    currentPeer: null,

    initialize: function(params) {
        IMessenger.ObjectConversationView.__super__.initialize.call(this,params);
        this.on(IMessenger.Events.Conversation.SELECTED,this.toggle);
        this.model.on(IMessenger.Events.Peer.SELECTED,this.onPeerSelected);
        this.model.on(IMessenger.Events.Conversation.NEW_MESSAGE,this.onNewMessage); // новое сообщение в разговорах
    },

    events: {
        'click .head h4': 'onSelect' // выбрали пользователя из списка
    },

    toggle: function() {
        this.$conversations.toggle();
    },

    onSelect: function() {
        IMessenger.ObjectConversationView.__super__.onSelect.call(this);
        this.toggle();
    },

    onNewMessage: function() {
    	console.log("new message");
    },

    /**
     * Кликом по закладке выбрали новый активный разговор
     * @param peer
     */
    onPeerSelected: function(peer) {
        console.log("new peer:");
        console.dir(this.currentPeer);
        console.dir(peer);
        // старый активный разговор закончен
        if (this.currentPeer)
            this.currentPeer.set({active:false});

        // новый активный разговор
    	this.currentPeer = peer;
        this.currentPeer.set({active:true});
    },

    /**
     * Отправляем сообщение
     */
    onSend: function() {
        data = {
            idUser: IMessenger.User.getId(),
            idConversation: this.model.getId(),
            body: this.$input.val()
        };
        var item = new IMessenger.Message(data);
        this.model.trigger(IMessenger.Events.Message.SEND,item);
    },

    render: function() {
        IMessenger.ObjectConversationView.__super__.render.call(this);
        this.$conversations = this.$el.find('.main-messages'); // список пользователей
        this.$userlist = this.$el.find('.userlist'); // список пользователей
        this.$peers = this.$el.find('.peers'); // активные разговоры для соотв.пользователей

        // список юзеров для объекта
        var user;
        var peer;
        var users = this.model.users;
        for(var i in users) {
            user = new IMessenger.Classes.PeerTabView({
                model: this.model,
                user: users[i]
            });
            this.$userlist.append(user.$el)

            // для каждого юзера создаем активный разговор
            peer = new IMessenger.Classes.PeerView({
                model: this.model,
                // активный разговор завязан на пользователе
                user: users[i]
            });
            this.$peers.append(peer.$el)
        }
    }
});