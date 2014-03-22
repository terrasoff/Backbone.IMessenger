/**
 * Представление разговора
 * @type {Backbone.View}
 */
IMessenger.ConversationView = Backbone.View.extend({
    template: _.template($("#IMessengerConversation").html()),
    className: "messenger-conversation",
    tagName: "div",

    events: {
        'click': 'changeActive'
    },

    initialize: function(params) {
        this.model.on('change:active', this.onChangeActive.bind(this));
        this.model.on(IMessenger.Events.Conversation.UPDATE, this.onUpdate, this);
        return this.render();
    },

    changeActive: function() {
        this.model.set({active: true});
    },

    // Выбрали разговор
    onChangeActive: function() {
        var state = this.model.get('active');
        this.$el.toggleClass('selected', state);
        return this;
    },

    onUpdate: function() {
        this.model.trigger(IMessenger.Events.Conversation.UP, this.$el);
    },

    render: function() {
        this.$el.html(this.template({model: this.model}));
        return this;
    }
});