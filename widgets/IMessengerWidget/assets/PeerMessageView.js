/**
 * Представление сообщения в переписке
 */
IMessenger.PeerMessageView = Backbone.View.extend({
    className: "messenger-peer-message",
    tagName: "div",

    template: _.template($('#IMessengerMessage').html()),

    events: {
        'click .peer-message-body': 'onSelect'
    },

    /**
     * @type {IMessenger.Receiver}
     */
    user: null,

    initialize: function(params) {
        _.extend(this,params);

        this.model.on(IMessenger.Events.Message.READ,this.onRead,this);
        this.render();
        this.onRead();
        return this;
    },

    onRead: function() {
        this.$el.toggleClass('unread',!this.model.isRead());
        return this;
    },

    onSelect: function() {
        this.model.trigger(IMessenger.Events.Message.SELECTED,this.model);
        this.model.check();
        this.$el.toggleClass('checked',this.model.get('checked'));
        this.model.read();
    },

    render: function() {
        this.$el.html(this.template({
            model: this.model,
            user: this.user
        }));
        return this;
    }
});