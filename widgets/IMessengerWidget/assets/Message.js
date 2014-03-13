/**
 * Сообщение
 * @type {Backbone.Model}
 */
IMessenger.Message = Backbone.Model.extend({

    idAttribute: 'idMessage',

//    comparator:function() {
//        return this.get('idMessage');
//    },

    defaults: {
        read: false,
        checked: false
    },

    getId: function() {
        return this.get('idMessage');
    },

    /**
     * используется для ajax-сохранения сообщения
     * @returns {Object}
     */
    toObject: function() {
        var data = {
            idConversation: this.get('idConversation'),
            title: this.get('title'),
            body: this.get('body'),
            idUser: this.get('idUser')
        };

        return {message:data};
    },

    read: function() {
        this.set({read:true},{silent:true});
        console.log("reading message: "+messages[i]);
        this.trigger(IMessenger.Events.Message.READ);
    },

    isRead: function() {
        return this.get('read');
    },

    // прочитали сообщение
    read: function() {
        // перекрываем событие
        this.set({read: true},{silence: true});
        this.trigger(IMessenger.Events.Message.READ);
    },

    getUserId: function() {
        return this.get('idUser');
    },

    getUser: function() {
        return this.get('user');
    },

    getTitle: function() {
        return this.get('title');
    },

    getBody: function() {
        return this.get('body');
    },

    check: function() {
        this.set({checked: !this.get('checked')})
    },

    setReceivers: function(value) {
    	this.set({receivers:value});
        return this;
    },

    setTitle: function(value) {
        this.set({title:value});
        return this;
    },

    setBody: function(value) {
    	this.set({body:value});
        return this;
    }
});