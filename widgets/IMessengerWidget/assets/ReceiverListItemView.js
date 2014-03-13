/**
 * Участник разговора
 */
IMessenger.ReceiverListItemView = Backbone.View.extend({
    tagName: "li",
    template: _.template(''),

    events: {
        "click": "select"
    },

    render: function () {
        this.$el.html(this.template({model: this.model}));
        return this;
    },

    select: function () {
        this.options.parent.hide().select(this.model);
        return false;
    }

});