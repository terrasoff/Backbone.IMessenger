/**
 * Разговор (conversation) - диалог с опр.пользователем или разговор несколькими пользователями
 * Активный разговор (peer) - открытый разговор с опр.пользователем
 * @param params
 * @constructor
 */
IMessenger = function(params) {

    /**
     * А это будущий я! Ссылка на глобальный экземпляр пользователя
     * @type {IMessenger.Receiver}
     */
    this.user = null;

    /**
     * Адрес для работы с серваком
     * @type {string}
     */
    this.server = null;

    /**
     * Разговоры с пользователями
     * @type {Backbone.Collection}
     */
    this.conversations = new Backbone.Collection();

    /**
     * Идентификатор послденего полученного сообщения
     * @type {Number}
     */
    this.maxId = 0;

    /**
     * Текущий активный разговор
     * @type {IMessenger.Conversation}
     */
    this.currentConversation = null;

    /**
     * Диспечер запросов
     * @type {IMessenger.Dispatcher}
     */
    this.dispatcher = null;

    // активные разговоры
    this.peers = {};

    // запустить init()
    this.run = true;

    // можно подгрузить еще разговоры
    this.total = true;

    // кешируем JQuery элементы
    $messenger = null; // сам мессенджер
    $tabs = null; // закладки активных разговоров
    $peers = null; // активные разговоры
    $peers_items = null; // элементы активных разговоров
    $conversations = null; // разговоры
    $btn_conversations = null; // кнопка "Разговоры"
    $btn_peers = null; // кнопка "Активные разговоры"

    this.init = function(params)
    {
        _.extend(this,
            params,
            Backbone.Events
        );
        // Определяем сначала кастмные классы. Остальные (непереопределенные) = дефолтные
        _.defaults(this.classes, this.defaultClasses);
//        console.dir(this.classes); return;

        if (params.id == undefined) {
            throw "Ошибка при инициализации мессенджера: не определен контейнер";
        }
        if (this.server == undefined)
            throw "Не задан адрес сервера обмена сообщениями";

        // пробуем проинициализировать себя
        if (this.user == undefined)
            throw "Пользователь задан неверно";
        if (this.user.getId == undefined)
            throw "Не определена функция getId для определения идентификатора пользователя";

        IMessenger.User = this.user;

        $messenger = $(document.getElementById(params.id));
        if (!$messenger.length)
            throw "Ошибка при инициализации мессенджера: контейнер не найден";

        this.conversations.on('add',this.onAddConversation,this); // добавлен новый разговор
        this.conversations.on(IMessenger.Events.Conversation.NEW_MESSAGE,this.onNewMessage,this); // закрыли форму активного разговора
        this.conversations.on(IMessenger.Events.Message.SEND, this.sendMessage, this); // закрыли форму активного разговора
        this.conversations.on(IMessenger.Events.Peer.HISTORY, this.getHistory, this); // закрыли форму активного разговора
        this.conversations.on('change:active', this.onSelectConversation);

        // есть разговоры - инициализируем
        if (params.conversations != undefined) {
            var items = params.conversations;
            for(var i=0; i<items.length; i++)
                this.createConversation(items[i]);
        }

        this.dispatcher = new IMessenger.Dispatcher({
            server:this.server,
            isActive: false
        });
        this.dispatcher.on(IMessenger.Events.Dispatcher.UPDATE,this.update);
        this.dispatcher.on(IMessenger.Events.Dispatcher.COMMAND,this.onCommand);

        $messenger.show();

        $conversations = $messenger.find('.messenger-conversations');
        $peers = $messenger.find('.messenger-peers');

        $tabs = $messenger.find('.tabs .last');
        $peers_items = $messenger.find('.peers .items');
        $btn_conversations = $messenger.find('.control-btn-conversations');
        $btn_peers = $messenger.find('.control-btn-peers');
        $btn_new = $messenger.find('.control-btn-new');
        $new_messages = $messenger.find('.new-messages');

        // обрабатываем запросы от автодополнений
        this.on(BB.Autocomplete.Events.DATA,this.getAutocomplete,this);
        $btn_new.on('click',this.createNewMessage);

        this.sendCommand(new IMessenger.Command('conversations'));
    };

    /**
     * Выполняем очередную команду, которая пришла с сервера
     * @param data {Object}
     */
    this.onCommand = function(data) {
        // отправленная команда
        var _command = data._command;
        console.log("onCommand:"); console.dir(data);

        // WARNING: тут часто бывает непонятная ошибка если CommonApiController не наследовать от JsonController
        // данные должны содержать название соотв.команды, которая будет вызвана на клиенте
        var command = 'on' + data.command.charAt(0).toUpperCase() + data.command.slice(1); // первая буква - заглавная
            $this[command].call($this,data.data,_command);
        try {
        } catch(e) {
            console.log("Ошибка при выполнении команды");
            console.dir(command,data);
        }
    };

    this.sendMessage = function(message) {
        var data = message.toObject();
        var command = new IMessenger.Command('send',data);
        this.sendCommand(command);
    };

    /**
     * Обновляем разговоры
     */
    this.getHistory = function(model)
    {
        var command = new IMessenger.Command('history',{
            since: model.messages.first().getId(),
            idConversation: model.getId()
        }, model);
        this.sendCommand.call(this, command);
    };

    /**
     * Пришли данные для обновления разговоров
     * @param conversations {Array}
     */
    this.onHistory = function(data, command) {
//        console.log("onHistory"); console.dir(data);
        var model = command.context;
        model.addHistory(data.data.messages);
    };

    /**
     * Обновляем разговоры
     */
    this.update = function() {
        var command = new IMessenger.Command('update',{
            maxId: $this.getMaxId()
        });
        this.sendCommand.call(this,command);
    };

    /**
     * Читаем сообщения в разговоре
     * @param idConversation
     * @param messages
     */
    this.read = function(idConversation,messages) {
        var params = {};
        if (idConversation != undefined) {
            params.idConversation = idConversation;
            if (messages != undefined)
                params.messages = messages;
        }
        var command = new IMessenger.Command('read',params);
        this.sendCommand(command);
    };

    /**
     * Получение списка пользователей для автодополнения
     * @param query
     * @param context
     */
    this.getAutocomplete = function(query,context) {
        console.log("getAutocomplete:"); console.dir(context);
        var command = new IMessenger.Command('user',{query:query},context);
        this.sendCommand.call(this,command);
    };

    /**
     * Пришли данные для обновления разговоров
     * @param conversations {Array}
     */
    this.onConversations = function(data) {
        _.each(data,function(data) {
            this.createConversation(data);
        }.bind(this));
    };

    /**
     * Пришли данные для обновления разговоров
     * @param conversations {Array}
     */
    this.onUpdate = function(conversations) {
        if (conversations.length) {
            console.log("onUpdate");
            // т.к. сообщения упорядочены, то первое сообщение в первом разговоре будет иметь максимальный ID
            if (conversations[0].data != undefined
                && conversations[0].data.messages != undefined
                && conversations[0].data.messages.length)
                this.maxId = conversations[0].data.messages[0].idMessage

            _.each(conversations, function(item) {
                var conversation = this.conversations.get(item.attributes.idConversation);

                // обновляем существующий разговор
                if (conversation != undefined) {
                    conversation.addMessages(item.data.messages, false);
                // создаем новый рзаговор
                } else {
                    this.createConversation(item);
                }
            }.bind(this));
        }
    };

    /**
     * Сообщения в разговоре прочитаны
     * @param data {Object}
     *      data.idConversation - идентификатор раговора
     *      data.messages - список идентификаторов прочитанных сообщений
     */
    this.onRead = function(data) {
        if (data.idConversation != undefined) {
            console.log("reading...");
            var conversation = this.conversations.get(data.idConversation);
            if (conversation) {
                for(var i=0; i<data.messages.length; i++) {
                    var message = conversation.messages.get(data.messages[i]);
                    message.read();
                };
                // изменилось число прочитанных
                if (data.unread != undefined) {
                    console.log("set unread: "+data.unread)
                    conversation.setUnread(data.unread);
                }
            };

        }
    };

    this.onUser = function(data,command) {
        console.log("onUser");
        var autocomplete = command.getContext();
        autocomplete.update(data);
    };

    this.setMaxId = function(maxId) {
        if (this.maxId < maxId)
            this.maxId = maxId;
    };

    this.getMaxId = function() {
        return this.maxId;
    };

    this.send = function(message) {
        console.log("sending: "); console.dir(message.toObject()); return;

        var command = new IMessenger.Command('send',message.toObject());
        this.sendCommand.call(this,command);
    };

    /**
     * Отправляем команду на сервер
     * @param command {IMessenger.Command
     */
    this.sendCommand = function(command) {
//        console.log("sendCommand:"); console.dir(command);
        command.setParam('maxId',this.getMaxId());
        this.dispatcher.sendCommand(command);
    };

    /**
     * Создаем новый разговор
     * @param data
     */
    this.createConversation = function(data) {
        data.data.user = this.user;
        var idConversation = data.attributes.idConversation;
        if (!this.conversations.get(idConversation)) {
            var conversation = new IMessenger.Conversation(data.attributes, data.data);
            //console.log("new conversation: "+conversation.getId()); console.dir(data);
            this.conversations.add(conversation);
        }
        return this;
    }
    
    this.onAddConversation = function(conversation)
    {
        this.addConversation(conversation);
    };

    /**
     * Добавляем форму очередного разговор в общий список разговоров
     * @param conversation {IMessenger.Conversation}
     */
    this.addConversation = function(model)
    {
//        console.dir(conversation.getId());
        var conversation = new IMessenger.ConversationView({model: model});
        $conversations.append(conversation.$el);

        var peer = new IMessenger.PeerView({model: model});
        $peers.append(peer.$el);
    };

    /**
     * Выбрали активный разговор. Можно выбирать по-разному:
     *  - кликнув по разговору
     *  - кликнув по закладке
     *  ...
     * Главное, при этом генерировать событие IMessenger.Events.Conversation.SELECTED и разговор будет выбран
     * @param conversation
     */
    this.onSelectConversation = function(conversation)
    {
        if (this.currentConversation && this.currentConversation.getId() == conversation.getId())
            return false;

        if (this.currentConversation)
            this.currentConversation.close();

        conversation.select();
        this.currentConversation = conversation;
    };

    this.showConversations = function(e) {
        console.log("show conversations");
        $peers.hide();
        $conversations.show();
        console.dir($btn_peers);
        if ($this.getPeersTotal()) $btn_peers.show();
        $btn_conversations.hide();
    };

    this.showPeers = function(e) {
        $conversations.hide();
        $peers.show();
        $btn_peers.hide();
        $btn_conversations.show();
    };

    this.createNewMessage = function(e) {
        var form = new IMessenger.NewMessageView({
            messenger: $this
        });
        $new_messages.append(form.$el);
    }

    this.getConversation = function()
    {
        return this.currentConversation
    };

    this.getPeersTotal = function() {
        return Object.keys(this.peers).length;
    };


    this.render = function() {
        $el = $(this.form);
        $el.append(_.template('#template_messenger_conversation').html());
        var autocomplete = new BB.Autocomplete();
    };

    var $this = this;
    _.extend(this,params);

    if (this.run)
        this.init(params);

    return this;
};

/**
 * Классы, используемые для управления сообщениями по-умолчанию
 * Если используются другие, то переопределяем:
 *      1) Добавить класс сюды (для того, чтобы можно было перегружать)
 *      2) Добавить класс в наследника IMessenger. Например, в ObjectIMessenger (для переопределения класса)
 *      3) Добавить класс в инициализацию php-виджета (для загрузки соотв.класса)
 *
 */
IMessenger.Classes = {
    Conversation: IMessenger.Conversation,
    Receiver: IMessenger.Receiver,
    ConversationView: IMessenger.ConversationView,
    PeerMessageView: IMessenger.PeerMessageView,
    PeerTabView: IMessenger.PeerTabView,
    PeerView: IMessenger.PeerView
};

IMessenger.Events = {
    Conversation: {
        UNREAD: 'conversation:unread', // изменилось число прочитанных
        READ: 'conversation:read', // юзер прочитал сообщения в разговоре
        ADDED: 'conversation:added', // добавлен новый разговор
        SELECTED: 'conversation:selected', // выбрали разговор - открываем окно с активным разговором
        NEW_MESSAGE: 'conversation:message', // новое сообщение
        UPDATE: 'conversation:update', // новое сообщение
        MAX_ID:'message:maxid' // идентификатор посл.полученного сообщения изменился
    },
    Peer: {
        HISTORY: 'IMessenger.Peer.HISTORY', // отправляем сообщение
        SEND: 'IMessenger.Peer.SEND', // отправляем сообщение
        SELECTED: 'peer:selected', // открыли активный разговор
        CLOSED: 'peer:closed' // закрыли активный разговор
    },
    Message: {
        SEND:'message:send', // сообщение отправлено
        READ:'message:selected', // прочитали сообщение
        SELECTED:'message:selected' // выбрали сообщение
    },
    Tab: {
        SELECTED: 'tab:selected', // открыли активный разговор
        CLOSED: 'tab:closed' // закрыли закладку активного разговора
    },
    Dispatcher: {
        UPDATE: 'dispatcher:update', // обновляемся с сервера
        DISPATCH: 'dispatcher:dispatch', // отправляем запрос на сервер
        COMMAND: 'dispatcher:command' // пришли данные
    },
    Receiver: {
        NEW_MESSAGES: 'IMessenger.Receiver.NEW_MESSAGES', // новые сообщения в переписке
        MAX_ID: 'IMessenger.Receiver.MAX_ID', // получено сообщение с максимальным идентификатором
        LOAD: 'IMessenger.Receiver.LOAD', // подгрузить еще пользователей
        UNREAD: 'IMessenger.Receiver.UNREAD', // обнаружены новые непрочитанные сообщения пользователя
        READ: 'IMessenger.Receiver.READ', // пользователь прочитал сообщения
        HISTORY: 'IMessenger.Receiver.HISTORY', // подгрузить историю сообщений
        SELECTED: 'IMessenger.Receiver.SELECTED' // выбрали конкретного пользователя
    }
};