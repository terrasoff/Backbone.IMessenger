/**
 * Диспечер запросов
 * Номинально создан для обновления данных для разговоров
 * @constructor
 */
IMessenger.Dispatcher = function(params) {

    /**
     * Набор команд, которые будут отправлены на обработку
     * @type {Object}
     */
    this.commands = null;

    this.timer = null;

    this.isActive = true;

    this.init = function(params) {
        _.extend(this,params);
        this.on(IMessenger.Events.Dispatcher.DISPATCH,this.onSendCommand);
        if (this.isActive)
            this.reset();
    };

    this.onSendCommand = function(data) {
        $this.sendCommand(data)
    };

    this.dispatch = function() {
        console.log("dispatch");
        $this.trigger(IMessenger.Events.Dispatcher.UPDATE);
    };

    this.sendCommand = function(command)
    {
        var $this = this;
        var data = command.toObject();
        if (command.getMaxId()) data.data.maxId = data.maxId;
        
        $.ajax({
            url: this.server,
            type: 'POST',
            data: data,
            success: function(re) {
                $this.onData.call($this,re,command)
            },
            error: $this.onError
        })
    };

    /**
     * Пришли данные с сервера
     * @param data {Array}
     */
    this.onData = function(data,command) {
//        console.log("onData:"); console.dir(data);
        // обрабатываем все команды, которые пришли с сервера
        _.each(data, function(item) {
            this.onCommand.call(this, item.command, item.data, command);
        }.bind(this));

        this.reset();
    };

    /**
     * Сообщаем, что пришла очередная команда с сервера
     * @param command
     * @param data
     */
    this.onCommand = function(command,data,_command) {
        this.trigger(IMessenger.Events.Dispatcher.COMMAND,{
            _command: _command,
            command: command,
            data:data
        })
    };

    /**
     * Ошибка при обращении к серверу
     * @param data
     */
    this.onError = function(data) {
        console.log("error while dispatch");
        $this.reset();
    }

    /**
     * перезапускаем таймер диспетчера
     */
    this.reset = function() {
        if (this.isActive) {
            if (this.timer) clearTimeout(this.timer);
            this.timer = setTimeout(this.dispatch, IMessenger.Dispatcher.PERIOD);
        }
    };

    this.stop = function() {
        if (this.timer) clearTimeout(this.timer);
        this.isActive = false;
    };

    this.getMessenger = function() {
        return this.messenger;
    };

    $this = this;
    this.init(params);

};

_.extend(IMessenger.Dispatcher.prototype, Backbone.Events); // подмешиваем событийность Backbone

IMessenger.Dispatcher.PERIOD = 1000*2; // промежуток между запросами