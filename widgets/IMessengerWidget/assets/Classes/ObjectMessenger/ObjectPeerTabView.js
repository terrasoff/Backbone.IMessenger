/**
 * Представление закладки разговора с пользователем
 * @type {IMessenger.PeerTabView}
 */
IMessenger.ObjectPeerTabView = IMessenger.PeerTabView.extend({
    tagName: "li",
    className: '',

    /**
     * @type {IMessenger.Conversation}
     */
    model: null,

    /**
     * @type {IMessenger.Object}
     */
    object: null,

    events: {
        'click': 'onSelect'
    },

    initialize: function(params) {
        IMessenger.ObjectPeerTabView.__super__.initialize.call(this,params);
        this.model.on('change:unread',this.setTotal,this);
        this.render();
        return this;
    },

    setTotal: function() {
        var unread = this.model.getUnread();
        this.$total.toggleClass('red',unread>0);
        this.$total.text(unread ? unread : this.model.getTotal());
    },


    onMessage: function() {
        this.render();
    },

    onSelect: function(e) {
        // Закладка уже активная. Зачем сто раз кликать? Так не надо!
        if (!this.model.get('active')) {
            console.log("select model");
            console.log("tab selected: "+this.model.getId());
            this.model.active(true);
        }
    },

    /**
     * Показываем/прячем Активный разговор
     * @param model {IMessenger.Conversation}
     * @returns {IMessenger.PeerTabView}
     */
    onState: function(model) {
//        console.log("tab state: "+model.get('active'));
        model.get('active')
            ? this.$el.addClass('active')
            : this.$el.removeClass('active');

        return this;
    },

    render: function() {
        var has_unread = true;
        var total = this.model.getUnread();
        if (!total) {
            has_unread = false;
            total = this.model.getMessagesTotal()
        }

        this.$el.html(this.template({
            name: this.model.getName()
        }));

        this.$total = this.$el.find('.total');
        this.setTotal();

        return this;
    }
});