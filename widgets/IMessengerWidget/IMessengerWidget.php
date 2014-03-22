<?php
/*
 * Виджет для сообщений
 * @author: terrasoff
 */
class IMessengerWidget extends CWidget
{
    /**
     * идентификатор контейнера по-умолчанию
     * @var string
     */
    public $id = 'im';

    /**
     * данные разговоров (JSON)
     * @var string
     */
    public $conversations = null;

    /**
     * html-шаблоны (см.файл templates.php)
     * @var string
     */
    public $templates = null;

    /**
     * Пользователь (js)
     * @var string
     */
    public $user = '';

    /**
     * Имя js-переменной для сообщений
     * @var string
     */
    public $jsObject = 'im';

    /**
     * Используемые классы для сообщений
     * Например, если мы хотим использовать своё вью для модели Conversation, то нужно указать:
     * array('ConversationView'=>'MyConversationView')
     * @var array
     */
    public $classes = array(
    );

    const DEFAULT_CLASS = 'IMessenger';

    public $messengerClass = self::DEFAULT_CLASS;
    public $userClass = 'User';

    /**
     * Адрес api
     * Например, "/im/api"
     * @var string
     */
    public $server = null;

    public function init()
    {
        // публикуем всю папку
        $am = Yii::app()->getAssetManager();
        $cs = Yii::app()->clientScript;
        // публикуем все, что в папке
        $path1 = dirname(__FILE__).'/assets/';
        $path2 = dirname(__FILE__).'/assets/BB.Autocomplete';
        $path3 = dirname(__FILE__).'/assets/BB.ModelList';
        $am->publish($path1);
        $am->publish($path2);
        $am->publish($path3);
        // получаем url с опубликованными файлами
        $assetsPath1 = $am->getPublishedUrl($path1);
        $assetsPath2 = $am->getPublishedUrl($path2);
        $assetsPath3 = $am->getPublishedUrl($path3);

        $cs->registerScriptFile($assetsPath1.'/IMessenger.js',CClientScript::POS_END);
        $cs->registerScriptFile($assetsPath1.'/Receiver.js',CClientScript::POS_END);
        $cs->registerScriptFile($assetsPath1.'/Command.js',CClientScript::POS_END);
        $cs->registerScriptFile($assetsPath1.'/Dispatcher.js',CClientScript::POS_END);
        $cs->registerScriptFile($assetsPath1.'/Message.js',CClientScript::POS_END);
        $cs->registerScriptFile($assetsPath1.'/Conversation.js',CClientScript::POS_END);
        $cs->registerScriptFile($assetsPath1.'/ConversationView.js',CClientScript::POS_END);
        $cs->registerScriptFile($assetsPath1.'/PeerView.js',CClientScript::POS_END);
        $cs->registerScriptFile($assetsPath1.'/PeerMessageView.js',CClientScript::POS_END);

        // класс сообщений по-умолчанию
        $messenger = empty($this->classes['IMessenger'])
            ? 'IMessenger'
            : $this->classes['IMessenger'];

        // custom messenger
        if ($this->messengerClass !== self::DEFAULT_CLASS) {
            $path4 = dirname(__FILE__).'/assets/Classes/'.$this->messengerClass;

            $assetsPath4 = $am->getPublishedUrl($path4);
            if ($this->classes) {
                $am->publish($path4);

                foreach ($this->classes as $i=>$c) {
                    if (file_exists($path4.'/'.$c.'.js'))
                        $cs->registerScriptFile($assetsPath4.'/'.$c.'.js',CClientScript::POS_END);
                }
            }
        }

        // js-инициализация
        $cs->registerScript('var '.$this->jsObject.';', CClientScript::POS_BEGIN);
        $cs->registerScript(
            'IMessenger.init',
            $this->jsObject.' = new '.$this->messengerClass.'({'.
                'id:"'.$this->id.'",'.
                'server:"'.$this->server.'",'.
                'classes: '.CJSON::encode($this->classes).','.
                'user: '.$this->user.
            '});'
            //conversations:'.CJSON::encode($this->conversations).
            ,CClientScript::POS_READY
        );

        if (!$this->server)
            throw new Exception('Не задан адрес сервера обмена сообщениями');

        // шаблоны по-умолчанию
        if (!$this->templates) {
            Yii::setPathOfAlias('IMessenger',dirname(__FILE__));
            $this->templates = 'IMessenger.templates';
        }

        $this->render($this->templates,array(
            'id'=>$this->id,
        ));
    }
}