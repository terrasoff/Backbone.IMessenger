/**
 * Команды для обращения к серверу
 * User: terrasoff
 * Date: 5/23/13
 * Time: 4:06 PM
 *
 * @param name {String} имя команды
 * @param data {Object} параметры команды
 * @constructor
 */

IMessenger.Command = function(name,data,context) {

    this.command = null;
    this.data = new Object();

    /**
     * Контекст для конмады
     * Если результат выполнения важен для конкретного объекта
     * @type {Object}
     */
    this.context = null;

    this.commands = {
        default: 'update'
    };

    this.init = function(name,data,context) {
        this.command = name != undefined
            ? name
            : this.commands.default;
        // контекст команды
        if (context != undefined)
            this.setContext(context);

        this.setParams(data)
    };

    this.getName = function() {
        return this.command;
    };

    this.getMaxId = function() {
        return this.command.maxId == undefined
            ? 0
            : this.command.maxId;
    };

    this.getParam = function(attribute) {
        return this.data == undefined || this.data[attribute] == undefined
            ? null
            : this.data[attribute];
    };

    this.setParams = function(data) {
        if (data != undefined)
            this.data = data;
    };

    this.setParam = function(attribute,value) {
        this.data[attribute] = value;
    };

    this.toObject = function() {
        return {
            command: this.command,
            data: this.data
        }
    };

    this.setContext = function(context){
        this.context = context;
    };

    this.getContext = function(){
        return this.context;
    };

    this.init(name,data,context);

};