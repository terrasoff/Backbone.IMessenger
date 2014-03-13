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

    render: function() {
        this.$el.html(this.template({model: this.model}));
        return this;
    }
});