/**
 * Представление объекта и разговоров по нему.
 * Разговоры с разными пользователями группируются по данному объекту
 * @type {Backbone.ConversationView}
 */
IMessenger.ObjectView = Backbone.View.extend({
    template: _.template($('#template_messenger_object').html()),
    className: "conversation",
    tagName: "div",

    // текущий активный разговор
    currentPeer: null,

    /**
     * @type {IMessenger.Object}
     */
    model: null,

    initialize: function(params) {
        _.extend(this,params);
        this.model.on(IMessenger.Events.Peer.SELECTED,this.onPeerSelected,this);
        this.model.on('change:unread',this.setUnread,this);

        this.model.receivers.on('add',this.onAddRectiver,this);
        this.model.receivers.on('change:active',this.onPeerSelect,this);
        this.model.on(IMessenger.Events.Object.UPDATED,this.onUpdated,this);

        return this.render();
    },


    // есть новые сообщения в переписке
    onUpdated: function() {
        this.model.trigger(IMessenger.Events.Object.UP,this);
    },

    events: {
        'click .head h4': 'onSelect' // выбрали пользователя из списка
    },

    setUnread: function() {
        console.log("changed unread");
        var unread = this.model.getUnread();
        this.$total.toggleClass('red',unread>0);
        this.$total.text(unread ? unread : this.model.getTotal());
    },

    toggle: function() {
        this.$header.toggle();
    },

    onSelect: function() {
        this.model.select();
        this.toggle();

        // просматриваем разговор, а пользователь не выбран - выбираем первого
        if (this.$el.is(':visible') && !this.model.receivers.where({active:true}).length)
            this.selectFirstReceiver();
    },

    /**
     * Добавлен новый разговор
     * @param conversation {IMessenger.Receiver}
     */
    onAddRectiver: function(receiver) {
        // с самим собой не переписываемся
        if (this.model.myself.getId() == receiver.getId()) return;

        var item = new IMessenger.Classes.PeerTabView({
            model: receiver,
            object: this.model
        });
        this.$userlist.append(item.$el);

        // для каждого разговора создаем активный разговор
        peer = new IMessenger.Classes.PeerView({
            model:receiver,
            object: this.model
        });
        this.$peers.append(peer.$el);

        // при добавлении первого пользователя сразу же подгружаем его сообщения
        if (this.model.receivers.length == 1) {
            this.selectFirstReceiver();
        }
    },

    // выбираем первого пользователя из списка
    selectFirstReceiver: function() {
        console.log("click first user");
        this.$userlist.find('>:first').click();
    },

    /**
     * Кликом по закладке выбрали новый активный разговор
     * @param peer IMessenger.Conversation
     */
    onPeerSelect: function(peer) {
        console.log("new peer:"); console.dir(this.currentPeer); console.dir(peer);
        // старый активный разговор закончен
        if (this.currentPeer)
            this.currentPeer.active(false);

        // новый активный разговор
    	this.currentPeer = peer;
    },

    render: function() {
        this.$el.html(this.template({model: this.model}));
        this.$header = this.$el.find('.main-messages'); // заголовок
        this.$userlist = this.$el.find('.userlist'); // список пользователей
        this.$peers = this.$el.find('.peers'); // активные разговоры для соотв.пользователей
        this.$total = this.$el.find('.total'); // число непрочитанных

        // TODO тест для подгрузки пользователей
        this.$el.find('.btn_more').on('click', $.proxy(function() {
            console.log("more receivers");
            this.model.loadReceivers();
        },this));


        this.setUnread()
    }
});