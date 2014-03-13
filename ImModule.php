<?php

/**
 * @desc модуль управления рубрикатором
 * @author Тарасов Константин <kosta@onego.ru>
 */

class ImModule extends CWebModule
{

    static $id = 'im';

    public $appId = null;

    public $messageLimit = 10;

    /**
     * Классы, используемые модулем
     * @var array
     */
    public $classes = array(
        'object'=>'Items',
        'user'=>'User',
    );

    /**
     * @var CDbConnection соединение с БД для рубрикатора по-умолчанию
     */
    private $connection = null;

    /**
     * База данных
     * @var CDbConnection
     */
    public $db = null;

    /**
     * адрес api
     * @var string
     */
    public $server = null;

    /**
     * Ссылка на модуль
     * @return ImModule
     * @throws TException
     */
    public static function get() {
        $module = Yii::app()->getModule(self::$id);
        // модуль загружен?
        if (!$module)
            throw new TException('Модуль "'.self::$id.'" не найден');
        else
            return $module;
    }

    public function init() {
        $this->setImport(array(
            'application.modules.im.components.*',
            'application.modules.im.extensions.*',
            'application.modules.im.controllers.*',
            'application.modules.im.models.*',
        ));

        // соединение с БД рубрикатора
        $this->connection = Yii::app()->getComponent($this->db);

        if (!$this->connection || !($this->connection instanceof CDbConnection))
            throw new TException('База данных для сообщений не определена');

        if ($this->getName() !== self::$id)
            throw new TException('Идентификатор модуля "'.self::$id.'" и его имя не совпадают!');
    }

    // соединение с базой для рубрикатора
    public function getDbConnection() {
        return $this->connection;
    }

    public function getServer() {
        return $this->server;
    }

    public function getAppId() {
        return $this->appId
            ? $this->appId
            : Yii::app()->id;
    }

    /**
     * Число сообщений (новых) по объекту
     * @param int $idObject объект
     * @param bool $unread новые/всего
     * @return int
     */
    public function getObjectMessagesTotal($idObject,$unread = true) {
        /** @var $object ObjectConversation */
        $object = ObjectConversation::model()->findByPk($idObject);
        return $object
            ? $object->getMessagesTotal($unread)
            : 0;
    }

    /**
     * Число сообщений (новых) по объекту
     * @param int $idObject объект
     * @return int
     */
    public function getObjectMessagesCount($idObject = null) {

        $result = array(
            'unread'=>0,
            'total'=>0
        );

        if ($idObject === null)
            return $result;

        /** @var $object ObjectConversation */
        $object = ObjectConversation::model()->findByPk($idObject);

        if ($object) {
            $result['unread'] = $object->getMessagesTotal(true);
            $result['total'] = $object->getMessagesTotal(false);
        }

        return $result;
    }

}