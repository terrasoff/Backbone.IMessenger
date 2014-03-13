/**
 * Представление закладки разговора с пользователем
 * @type {Backbone.View}
 */
IMessenger.PeerTabView = Backbone.View.extend({
    className: "tabs-conversation",
    tagName: "div",
    template: _.template($('#template_messenger_conversation_tab').html()),

    /**
     * @type {IMessenger.Rectiver}s
     */
    model: null,

    events: {
        'click .tabs-name': 'onSelect',
        'click .tabs-close': 'onClose'
    },

    initialize: function(params) {
        _.extend(this,params);
        this.model.on(IMessenger.Events.Conversation.NEW_MESSAGE,this.onMessage,this);
        this.model.on('change:active',this.onState,this);
        this.render();
        return this;
    },

    onMessage: function() {
        this.render();
    },

    onSelect: function(e) {
        // закладка уже активная - не надо кликать по сто раз
        if (this.model.get('active')) return;
        this.model.trigger(IMessenger.Events.Tab.SELECTED,this.model);
    },

    onClose: function(e) {
        // не забываем снять события с модели
        this.model.off('change:active',this.onState);
        this.remove();
        this.model.trigger(IMessenger.Events.Tab.CLOSED,this.model);
    },

    /**
     * Показываем/прячем Активный разговор
     * @param model {IMessenger.Conversation}
     * @returns {IMessenger.PeerTabView}
     */
    onState: function(model) {
        console.log("tab state: "+model.get('active'));
        model.get('active')
            ? this.$el.addClass('active')
            : this.$el.removeClass('active');

        return this;
    },

    render: function() {
        this.$el.html(this.template({model: this.model}));
        return this;
    }
});