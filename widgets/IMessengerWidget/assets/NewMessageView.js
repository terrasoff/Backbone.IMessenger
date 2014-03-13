/**
 * Представление нового сообщения
 */
IMessenger.NewMessageView = Backbone.View.extend({
    className: "new-message",
    tagName: "div",
    ItemModel: IMessenger.Message,

    /**
     * Ссылка на модуль сообщений {IMessenger}
     */
    messenger: null,

    /**
     * список получателей
     * type {BB.ModelsList}
     */
    receivers: null,

    template: _.template($('#IMessengerMessage').html()),

    events: {
        'click .new-message-send': 'onSend'
    },

    initialize: function(options) {
        _.extend(this,options);

        this.model = new this.ItemModel();
        this.render();
    },

    onSend: function() {
        var receivers = _.map(this.receivers.getModels(),function(model){
            return model.getId();
        });

        console.log("try to send");
        console.dir(this.messenger);

        this.model.setReceivers(receivers);
        this.model.setTitle(this.$title.val());
        this.model.setBody(this.$body.val());
        this.messenger.trigger(IMessenger.Events.Message.SEND,this.model);
    },

    render: function() {
        this.$el.html(this.template({
            model: this.model
        }));

        // список получателей
        this.receivers = new BB.ModelsList({
            DataProvider: this.messenger,
            template: _.template($('#template_messenger_receiver_list').html()),
            template_item: _.template($('#template_messenger_receiver').html())
        });

        this.$el.find('.new-message-receivers').append(this.receivers.$el);
        this.$body = this.$el.find('.new-message-body textarea');
        this.$title = this.$el.find('.new-message-title input');
        return this;
    }
});