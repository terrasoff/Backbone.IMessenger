/**
 * Участник разговора
 */
IMessenger.Receiver = Backbone.Model.extend({

    idAttribute: 'idUser',

    getId: function() {
        return this.get('idUser');
    },

    getAvatar: function() {
        return this.get('avatar')
            ? this.get('avatar')
            : null;
    },

    getName: function() {
        var name = this.get('name');

        if (!name) {
            name = this.get('email');
            if (!name) {
                name = this.get('email');
                if (!name) {
                    name = 'user'+this.get('id');
                }
            }
        }


        return name;
    }

});