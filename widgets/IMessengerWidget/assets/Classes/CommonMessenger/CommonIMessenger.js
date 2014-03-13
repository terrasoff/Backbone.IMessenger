/**
 * Разговор (conversation) - диалог с опр.пользователем или разговор несколькими пользователями
 * Активный разговор (peer) - открытый разговор с опр.пользователем
 * @param params
 * @constructor
 */
IMessenger = function(params) {

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
    this.maxId = null;

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

    this.classes = {
        ConversationView: IMessenger.ConversationView
    };

    // запустить init()
    this.run = true;

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
        _.extend(this,Backbone.Events);

        if (params.id == undefined)
            throw "Ошибка при инициализации мессенджера: не определен контейнер";
        if (this.server == undefined)
            throw "Не задан адрес сервера обмена сообщениями";
        if (this.user == undefined)
            throw "Класс пользователя не определен";
        else {
            if (this.user.getId == undefined)
                throw "В классе пользователя должен быть определен метод getId()";
        }

        IMessenger.User = this.user;

        $messenger = $(document.getElementById(params.id));
        if (!$messenger.length)
            throw "Ошибка при инициализации мессенджера: контейнер не найден";

        $messenger.show();

        $tabs = $messenger.find('.tabs .last');
        $peers = $messenger.find('.peers');
        $peers_items = $messenger.find('.peers .items');
        $conversations = $messenger.find('.conversations');
        $btn_conversations = $messenger.find('.control-btn-conversations');
        $btn_peers = $messenger.find('.control-btn-peers');
        $btn_new = $messenger.find('.control-btn-new');
        $new_messages = $messenger.find('.new-messages');

        this.conversations.on('add',this.onAddConversation,this); // добавлен новый разговор
        this.conversations.on(IMessenger.Events.Conversation.NEW_MESSAGE,this.onNewMessage); // новое сообщение в разговорах
        this.conversations.on(IMessenger.Events.Conversation.SELECTED,this.onSelectConversation); // выбрали разговор - показываем форму активного разговора
        this.conversations.on(IMessenger.Events.Peer.CLOSED,this.onClosePeer); // закрыли форму активного разговора
        this.conversations.on(IMessenger.Events.Message.SEND,this.onSendMessage); // закрыли форму активного разговора

        $btn_conversations.on('click',this.showConversations); // переход к разговорам
        $btn_peers.on('click',this.showPeers); // переход к активным разговорам
        $btn_new.on('click',this.createNewMessage); // переход к активным разговорам

        // есть разговоры - инициализируем
        if (params.conversations != undefined) {
            var items = params.conversations;
            for(var i=0; i<items.length; i++)
                this.createConversation(items[i]);
        }

        this.dispatcher = new IMessenger.Dispatcher({
            messenger:this,
            server:this.server
        });
        this.dispatcher.on(IMessenger.Events.Dispatcher.UPDATE,this.update);
        this.dispatcher.on(IMessenger.Events.Dispatcher.COMMAND,this.onCommand);

        // обрабатываем запросы от автодополнений
        this.on(BB.Autocomplete.Events.DATA,this.getAutocomplete,this);

        // сразу обновляемся

        this.sendCommand(new IMessenger.Command('conversations'));
        this.dispatcher.stop();
        return;
    };

    /**
     * Выполняем очередную команду, которая пришла с сервера
     * @param data {Object}
     */
    this.onCommand = function(data) {
        // отправленная команда
        var _command = data._command;
        console.log("onCommand:"); console.dir(data);

        // данные должны содержать название соотв.команды, которая будет вызвана на клиенте
        var command = 'on' + data.command.charAt(0).toUpperCase() + data.command.slice(1); // первая буква - заглавная
            $this[command].call($this,data.data,_command);
        try {
        } catch(e) {
            console.log("Ошибка при выполнении команды");
            console.dir(command,data);
        }
    };

    /**
     * Обновляем разговоры
     * @param ts {String} время посл.обновления
     */
    this.update = function() {
        var command = new IMessenger.Command('update',{
            maxId: $this.getMaxId()
        });
        this.sendCommand(command);
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
        this.sendCommand(command);
    };

    /**
     * Пришли данные для обновления разговоров
     * @param conversations {Array}
     */
    this.onUpdate = function(conversations) {
        if (conversations.length) {
            var item;
            for(var i=0;i<conversations.length;i++) {
                item = conversations[i];
                var conversation = this.getConversationById(item.idConversation);
//                console.log("Dispatcher.onMessage:"); console.dir(item);

                // обновляем существующий разговор
                if (conversation != undefined) {
                    conversation.addMessages(item.messages,$this.maxId);
                // создаем новый рзаговор
                } else {
                    this.createConversation(item);
                }
            }
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
            var conversation = this.getConversationById(data.idConversation);
            if (conversation) {
                var messages = conversation.getMessagesById(data.messages);
                for(var i=0; i<messages.length; i++) {
                    messages[i].read();
                }
            }

        }
    };

    this.onUser = function(data,command) {
        console.log("onUser");
        var autocomplete = command.getContext();
        autocomplete.update(data);
    };

    /**
     * Новое сообщение в разговорах
     * @param message {IMessenger.Message}
     */
    this.onNewMessage = function(message) {
        if (message.getId() > $this.getMaxId())
            $this.setMaxId(message.getId());
    };

    this.getMaxId = function() {
        return this.maxId;
    };

    this.setMaxId = function(value) {
        return this.maxId = value;
    };

    this.sendMessage = function(message) {
        console.log("send command send");
        var command = new IMessenger.Command('send',message.toObject());
        this.sendCommand(command);
    };

    /**
     * Отправляем команду на сервер
     * @param command {IMessenger.Command
     */
    this.sendCommand = function(command) {
        console.log("sendCommand:"); console.dir(command);
        if (!command.getParam('maxId'))
            command.setParam('maxId',$this.getMaxId());

        this.dispatcher.sendCommand(command);
    };

    /**
     * Создаем новый разговор
     * @param data
     */
    this.createConversation = function(data) {
//        console.log("new conversation");
        var conversation = new IMessenger.Conversation(data);
        this.conversations.add(conversation);
    }
    
    this.onAddConversation = function(conversation)
    {
        this.addConversation(conversation);
    };

    /**
     * Добавляем форму очередного разговор в общий список разговоров
     * @param conversation {IMessenger.Conversation}
     */
    this.addConversation = function(conversation)
    {
//        console.log("add conversation"); console.dir(conversation);
        // TODO: пока так :(
        var itemClass = eval(this.classes['ConversationView']);
        var item = new itemClass({model: conversation});
        $conversations.append(item.$el);
    };

    /**
     * Выбрали активный разговор. Можно выбирать по-разному:
     *  - кликнув по разговору
     *  - кликнув по закладке
     *  ...
     * Главное, при этом генерировать событие IMessenger.Events.Conversation.SELECTED и разговор будет выбран
     * @param conversation
     */
    this.onSelectConversation = function(conversation) {
        console.log("select conversation: "+conversation.getId()); console.dir(conversation);
        return;
        // предыдущий активный разговор закрываем
        if ($this.currentConversation &&
            $this.currentConversation.getId() != conversation.getId())
            $this.currentConversation.setActive(false);

        // новый активный разговор
        if ($this.peers[conversation.getId()] == undefined) {
            $this.peers[conversation.getId()] = conversation;
            var item = new IMessenger.PeerView({model: conversation});
            var tab = new IMessenger.PeerTabView({model: item.model});
            $tabs.before(tab.$el);
            $peers_items.append(item.$el);
            conversation.setActive(true);
        // уже открытый
        } else {
            if ($this.currentConversation == undefined ||
                $this.currentConversation.getId() != conversation.getId())
                conversation.setActive(true);
        }

        // считаем, что сообщения прочитали
        $this.read(conversation.getId());

        $this.currentConversation = conversation;
        $this.showPeers();
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

    /**
     * Закрыли очередной активный разговор
     * @param conversation {IMessenger.Conversation}
     */
    this.onClosePeer = function(conversation) {
        console.log("close peer: "+conversation.getId());
        var _conversation = $this.peers[conversation.getId()];
        var isActive = _conversation.get('active');
        _conversation.setActive(false);
        delete $this.peers[conversation.getId()];
        $this.currentConversation = null;

        if (!$this.getPeersTotal()) {
            $this.showConversations();
        } else if (isActive) {
            // если удалили текущий активный разговор, то нужно выбрать новый
            var keys = Object.keys($this.peers);
            // выбираем последний выбранный
            var index = keys[keys.length-1];
            console.log("select new peer: "+index);
            var conversation = $this.peers[index];
            conversation.select();
        }
    };

    this.getConversationById = function(id)
    {
        return this.conversations.where({idConversation:""+id})[0];
    };

    this.getConversation = function()
    {
        return this.currentConversation
    };

    this.removeConversation = function()
    {

    };

    this.addMessage = function()
    {
        
    };

    this.getPeersTotal = function() {
        return Object.keys(this.peers).length;
    };

    /**
     * Получение списка пользователей
     * @returns {Backbone.Collection}
     */
    this.getUsers = function() {
        return list;
    };


    this.render = function() {
        $el = $(this.form);
        $el.append(_.template('#template_messenger_conversation').html());
        var autocomplete = new BB.Autocomplete();
        $el.append('<div>hello</div>');
    };

    var $this = this;
    _.extend(this,params);
    console.dir(params);

    if (this.run)
        this.init(params);

    return this;
};

IMessenger.Events = {
    Conversation: {
        SELECTED: 'conversation:selected', // выбрали разговор - открываем окно с активным разговором
        NEW_MESSAGE: 'conversation:message', // новое сообщение
        MAX_ID:'message:maxid' // идентификатор посл.полученного сообщения изменился
    },
    Peer: {
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
    }
};