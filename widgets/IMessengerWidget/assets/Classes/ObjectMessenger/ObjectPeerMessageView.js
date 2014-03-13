/**
 * Представление сообщения в переписке
 */
IMessenger.ObjectPeerMessageView = IMessenger.PeerMessageView.extend({

    events: {
        'click .commentbox': 'onSelect'
    },

    initialize: function(params) {
        IMessenger.ObjectPeerMessageView.__super__.initialize.call(this,params);
        return this;
    },

    onRead: function() {
        this.$wrap.toggleClass('read',this.model.isRead());

    },

    onSelect: function() {
        this.show();
    },

    // раскрываем сообщение
    show: function() {
        $this = this;
        this.$message.animate({
            overflow: 'visible',
            height: this.$height,
            minHeight:85
        },{queue:false, complete:function(){
            $this.$avatar.show(500);
        }});
    },

    render: function() {
        IMessenger.ObjectPeerMessageView.__super__.render.call(this);

        this.$avatar = this.$el.find('.avatar');
        this.$height = this.$el.height();
        this.$message = this.$el.find('.commentbox p');
        this.$wrap = this.$el.find('.my-message')

        return this;
    }

});