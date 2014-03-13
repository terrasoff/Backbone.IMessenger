/**
 * User: terrasoff
 * Date: 5/30/13
 * Time: 1:37 PM
 */

BB.Autocomplete.ItemView = Backbone.View.extend({
    tagName: "div",
    template: _.template('<a href="#"><%= label %></a>'),

    events: {
        click: 'onSelect'
    },

    initialize: function() {
        this.render();
    },

    onSelect: function() {
        console.log("select autocomplete item");
        this.trigger(BB.Autocomplete.Events.SELECTED,this.model);
    },

    render: function () {
        console.log("render item");
        this.$el.html(this.template({
            label: this.model.getName()
        }));
        return this;
    }
});