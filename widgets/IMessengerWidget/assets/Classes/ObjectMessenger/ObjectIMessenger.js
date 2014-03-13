/**
 * Клиент сообщений (под RealtyImApi)
 * Представляем переписку в виде объектов. Каждый объект - это разговор с несколькими пользователями.
 * Владелец объекта может читать разговоры со всеми.
 * Участник разговора может читать сообщения только от владельца.
 * Т.о. каждый объект имеет набор пользователей: Object.receivers
 * Переписка закреплена за опр.пользователем: Receiver.messages
 *
 * Иерархия моделей и вьюх схематично:
 * ---
 * ObjectImMessenger(Objects)
 *    -> ObjectView(Object)::Objects
 *       -> ObjectPeerTabView(ObjectReceiver)::Object.receivers(ObjectReceiver)
 *          -> ObjectPeerView(ObjectReceiver)
 *             -> ObjectPeerMessageView(Message)::ObjectReceiver.messages
 * ---
 */
ObjectIMessenger = function(params) {

    // расширяем класс
    _.extend(params,{
        run: false,
        classes: {
            Conversation: IMessenger.ObjectConversation,
            Receiver: IMessenger.ObjectReceiver,
            ObjectView: IMessenger.ObjectView,
            Object: IMessenger.Object,
            PeerTabView: IMessenger.ObjectPeerTabView,
            PeerView: IMessenger.ObjectPeerView,
            PeerMessageView: IMessenger.ObjectPeerMessageView
        }
    });

    // наследуем
    var _class = IMessenger.call(this,params);
    _class.constructor = arguments.callee;
    
    // методы родителя, которые будут перегружены и к которым будем обращаться
    var _super = {
        init: _class.init
    };

    /**
     * Объекты
     * @type {DynamicCollection}
     */
    this.objects = null;

    this.init = function(params) {
        _super.init.call(this,params);

        // пользователь прочитал сообщения в разговоре
        this.conversations.on(IMessenger.Events.Conversation.READ,this.onReadConversation,this);

        $conversations = $messenger.find('.objects');
        $btn_more = $messenger.find('>button');

        // подгружаем еще объектов при нажатии на кнопку
        $btn_more.on('click', $.proxy(function() {
            this.objects.load();
        },this));

        this.objects = new DynamicCollection([],{
            event: IMessenger.Events.Object.LOAD
        });

        this.objects.on('add',this.addObject,this); // новый объект в списке
        this.objects.on(IMessenger.Events.Object.RECEIVERS,this.getReceivers,this); // нужно подгрузить пользователей
        this.objects.on(IMessenger.Events.Object.MESSAGES,this.getMessages,this); // нужно подгрузить сообщения
        this.objects.on(IMessenger.Events.Object.READ,this.read,this); // читаем сообщения
        this.objects.on(IMessenger.Events.Object.LOAD,this.getObjects,this); // подгружаем объекты
        this.objects.on(IMessenger.Events.Receiver.LOAD,this.getReceivers,this); // погружаем пользователей
        this.objects.on(IMessenger.Events.Peer.SEND,this.sendMessage,this); // погружаем пользователей
        this.objects.on(IMessenger.Events.Object.UP,this.onObjectUpdated,this); // объект обновился

        // сразу обновляемся
        var idObject = params.idObject == undefined
            ? null
            : params.idObject;

        this.getObjects(idObject);
    };

    // новые сообщения по объекту
    this.onObjectUpdated = function(objectView) {
        // поднимаем элемент на самый верх
        $conversations.prepend(objectView.$el)
    };

    /**
     * При добавлении нового разговора добавляем его в общую коллекцию и к объекту
     * @param conversation {IMessenger.Conversation}
     */
    this.addConversation = function(conversation)
    {
//        console.log("ObjectIMessenger::addConversation: " + conversation.getId());
        conversation.on(IMessenger.Events.Conversation.SELECTED,this.onSelectConversation);
        // ищем (или создаем) объект
        var model = this.getObject(conversation.get('item'));
        // добавляем разговор к объекту
        model.conversations.add(conversation);
    };

    this.onReadConversation = function(conversation) {
    	console.log("Сообщения в разговоре прочитаны: "+conversation.getId());
        this.read(conversation.getId());
    };

    /**
     *
     * @param data
     * @returns {IMessenger.Object}
     */
    this.getObject = function(data) {
        console.dir(data);
        var object = this.objects.get(data.idConversation);
        return object
            ? object
            : this.createObject(data);
    };

    this.createObject = function(data) {
//        console.log("ObjectIMessenger::createObject"); console.dir(data);
        var model = new IMessenger.Classes.Object(data);
        // подшиваем себя в каждый объект
        model.myself = this.myself;
        this.objects.add(model);
        return model;
    };

    /**
     * Новый объект с разговорами в коллекции
     * Добавляем форму очередного разговор в общий список разговоров
     * @type conversation {IMessenger.Object}
     */
    this.addObject = function(model) {
        var item = new IMessenger.Classes.ObjectView({model: model});
        // когда выбрали разговор в рамках объекта
        $conversations.append(item.$el)
    };


    /**
     * API: подгружаем объекты
     */
    this.getObjects = function(idObject) {
        var command = new IMessenger.Command('objects',{
            idObject: idObject,
            total: this.objects.length
        });
        this.sendCommand.call(this,command);
    };

    /**
     * Подгружаем/Листаем объекты
     * @param data
     */
    this.onObjects = function(data) {
        var objects = data.objects;
        var total = data.total;
        this.objects.setTotal(total);

        for(var i=0;i<objects.length;i++) {
            o = this.getObject(objects[i]);
        };
    };

    /**
     * API: подгружаем/листаем собеседников для текущего объекта
     * @type object {IMessenger.Object}
     */
    this.getReceivers = function(object) {
        console.log("get receivers command:"); console.dir(object);
        var command = new IMessenger.Command('receivers',{
            idConversation: object.getId(),
            total: object.getReceiversTotal()
        });
        this.sendCommand.call(this,command);
        return this;
    };

    /**
     * Подгружаем/Листаем пользователей
     * @param data
     */
    this.onReceivers = function(data) {
        var object = this.getObject(data);
//        console.log("onReceivers->getObject"); console.dir(object);
        var receivers = data.receivers;
        if (object && receivers.length) {
            var receiver;
            for(var i=0;i<receivers.length;i++) {
//                console.log("add new receiver: "); console.dir(receiver);
                receiver = new IMessenger.Classes.Receiver(receivers[i]);
                object.receivers.add(receiver)
            };
        }
    };

    /**
     * API: подгружаем/листаем собеседников для текущего объекта
     * @type object {IMessenger.Object}
     */
    this.getMessages = function(data) {
        console.log("get receivers command:"); console.dir(data);
        var command = new IMessenger.Command('messages',data);
        this.sendCommand.call(this,command);
        return this;
    };

    /**
     * Подгружаем сообщения
     * @param data
     */
    this.onMessages = function(data) {
//        console.dir(data);
        if (data && data.messages.length) {
            var object = this.objects.get(data.idConversation);
            if (object) {
                var receiver = object.receivers.get(data.idUser);
                if (receiver) {
                    if (!receiver.messages.length) {
                        var maxId = data.messages[0].idMessage;
                        console.log('maxId: '+maxId);
                        this.setMaxId(maxId);
                    }
                    receiver.addMessages(data.messages)
                }
            }
        }
    };

    this.read = function(data) {
        console.log("read:"); console.dir(data);
        var command = new IMessenger.Command('read',data);
        this.sendCommand.call(this,command);
        return this;
    };

    this.onRead = function(data) {
        console.dir(data);

        if (data && data.messages.length) {
            var object = this.objects.get(data.idConversation);
            if (object) {
                object.set({unread: data.unread.object})
                var receiver = object.receivers.get(data.idUser);
                if (receiver) {
                    receiver.read(data);
                }
            }
        }
    };

    /**
     * Пришли данные для обновления разговоров
     * @param conversations {Array}
     */
    this.onUpdate = function(data) {
        console.log("update"); console.dir(data);
        if (data.length) { // есть чо?
            for(var i=data.length-1; i>=0; i--) {
                if(!data.hasOwnProperty(i)) continue;
                var obj = this.getObject(data[i]);
                obj.set({
                    total: data[i].total,
                    unread: data[i].unread
                });

//                console.log("users:"); console.dir(data[i].users);
                var user;
                var maxId = null;
                for(var j in data[i].users) {
                    if(!data[i].users.hasOwnProperty(j)) continue;

                    // пользователь, с которым переписываемся
//                    console.log("пользователь, с которым переписываемся:"); console.dir(data[i].users[j].idUser);
                    user = obj.receivers.get(data[i].users[j].idUser);
                    if (!user) {
                        console.log("create new user:");
                        user = new IMessenger.Classes.Receiver(data[i].users[j]);
                        obj.receivers.add(user);
                    }
                    // сообщения
                    var messages = [];
//                    console.log("messages:"); console.dir(data[i].messages);
                    for(var k=0;k<data[i].messages.length;k++) {
                        // выбираем нужные сообщения
//                        console.log("messae from this chat?: "+data[i].messages[k].toUserId +'/'+data[i].messages[k].idUser+' = '+ user.get('idUser'));
                        if (data[i].messages[k].toUserId == user.get('idUser') ||
                            data[i].messages[k].idUser == user.get('idUser')) {
                            messages.unshift(data[i].messages[k]);
                            // т.к. сообщения упорядочены, то первое сообщение в первом разговоре будет иметь максимальный ID
                            if (!maxId) {
                                maxId = data[i].messages[k].idMessage;
                                this.setMaxId(maxId);
//                            console.log('maxId: '+maxId);
                            }
                        }
                    }
                    // новые сообщения пользователя
                    if (messages.length) {
//                    console.log("messages at "+obj.getId()+" -> "+user.getId()); console.dir(messages);
                        user.addMessages(messages,false);
                        // есть новые сообщения в разговоре - разговор поднимаем наверх
                        // обрабатывается в ObjectView
                        obj.trigger(IMessenger.Events.Object.UPDATED,this);
                    }
                }
            }
        }
    };

    this.init(params);

    return _class;
}

_.extend(IMessenger.Events,{
    Object: {
        UP: 'IMessenger.Object.UP', // поднимаем объект вверх
        UPDATED: 'IMessenger.Object.UPDATED', // есть обновления в разговорах
        INIT: 'IMessenger.Object.INIT', // первоначальная подгрузка объекта
        LOAD: 'IMessenger.Object.LOAD', // подгрузить еще объектов
        COUNTERS: 'IMessenger.Object.COUNTERS', // изменились счетчики сообщений по объекту
        RECEIVER: 'IMessenger.Object.RECEIVER', // выбрали пользователя в рамках объекта
        MESSAGES: 'IMessenger.Object.MESSAGES', // сообщения пользователя
        SELECT: 'IMessenger.Object.SELECT', // выбрали объект - подгружаем информацию
        ADD_RECEIVER: 'IMessenger.Object.ADD_RECEIVER', // к объекту добавлен новый участник
        READ: 'IMessenger.Object.READ'
    }
})