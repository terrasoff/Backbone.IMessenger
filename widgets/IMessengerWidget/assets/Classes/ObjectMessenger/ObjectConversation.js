/**
 * Модель активного разговора
 * @type {IMessenger.Conversation}
 */
IMessenger.ObjectConversation = IMessenger.Conversation.extend({

    initialize: function(params) {
        // если разговор стал активным (открыли для просмотра), то будем считать, что сообщения прочитаны
        this.on('change:active',this.onState,this);
    },

    /**
     * Открыли-закрыли разговор для просмотра
     */
    onState: function() {
        // если разговор стал активным
        if (this.get('active')) {
            // то будем считать, что сообщения прочитаны - отправляем запрос на чтение
            this.trigger(IMessenger.Events.Conversation.READ,this);
        }
    }

});