<?php

/**
 * Используется для специальных разговоров
 * @property integer $idMessage
 * @property integer $idConversation
 * @property integer $idObject
 */
class ObjectConversation extends IMActiveRecord
{

    /**
     * Соответствующий объект
     * @var array
     */
    public $item = null;

    /**
     * Владелец
     * @var array
     */
    public $user = null;

    public $total = null;

    public function relations()
    {
        return array(
            'Conversation' => array(self::BELONGS_TO, 'Conversation', 'idConversation',
                'together'=>true,
                'with'=>array(
                    'Receiver',
                    'Message',
                ),
            ),
//            'conversation' => array(self::BELONGS_TO, 'Conversation', 'idConversation',
//                'with'=>array('my','ConversationMessages','users'),
//            ),
        );
    }

    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return MessageConversation the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'ObjectConversation';
    }

    public function rules()
    {
        return array(
            array('idObject, idConversation', 'required'),
            array('idObject, idConversation', 'numerical', 'integerOnly'=>true),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('idObject, idConversation', 'safe', 'on'=>'search'),
        );
    }

    public function attributeLabels()
    {
        return array(
            'idObject' => 'Id Object',
            'idConversation' => 'Id Conversation',
        );
    }

    public function search()
    {
        $criteria=new CDbCriteria;
        $criteria->compare('idObject',$this->idMessage);
        $criteria->compare('idConversation',$this->idConversation);
        return new CActiveDataProvider($this, array('criteria'=>$criteria,));
    }

    public function isMine() {
        return $this->item->user_id == Yii::app()->user->getId();
    }

    public function getTotal() {
        return (int)$this
            ->with('Conversation')
            ->mine()
            ->count();
    }

    public function getByConversationId($idConversation){
        return $this->findByAttributes(array('idConversation'=>$idConversation));
    }

    /**
     * Листаем объекты в хронологическом порядке
     * @param int $total уже загружено объектов
     * @return $this
     */
    public function get($total = 0,$limit = 3){
        $this->mine()->getDbCriteria()->mergeWith(array(
            'select'=>'*,count(*) as total',
            'together'=>true,
            'with'=>'Conversation',
            'group'=>'t.idConversation',
            'order'=>'max(Message.idMessage) DESC',
            'limit'=>$limit,
            'offset'=>$total,
        ));

        return $this;
    }

    public function mine(){
        $this->getDbCriteria()->mergeWith(array(
            'condition'=>'Receiver.idUser = :myself',
            'params'=>array(':myself'=>Yii::app()->user->getTicnoId())
        ));
        return $this;
    }

    public function getOwnerId(){
        return $this->item->getTicnoId();
    }

    /**
     * Список тех, с кем можно переписываться
     */
    public function getObjectReceivers(){
        // формируем список пользователей с которыми переписываемся
        $users = [];
        $ticnoId = $this->item->ticnoId; // ticnoId владельца
        $id = Yii::app()->user->model->getTicnoId(); // мой ticnoId

        // если объект мой, то можно писать всем, если нет - только собственнику
        if ($this->isMine()) {
            foreach ($this->users as $u)
                $users[] = $u->attributes;
        } else {
            foreach ($this->users as $u) {
                $list = array($ticnoId,$id);
                if (in_array($u->idUser,$list))
                    $users[] = $u->toArray();
            }
        }
    }

    /**
     * Число сообщений по объекту
     * @param bool $unread прочитанных/всего
     * @return int
     */
    public function getMessagesTotal($unread = false) {
        $idSender = null;
        if (!$this->isMine())
            $idSender = $this->user->getTicnoid();
        return Message::model()->getMessagesTotal($unread,$this->idConversation,$idSender);
    }

    /**
     * Сохраняем дополнительную информацию по объекту
     * @param User $user владелец
     * @param Class $item объект
     * @return string
     */
    public function saveInfo($user, $item) {
        $this->info = serialize(array(
            'user'=>$user->getAttributes(),
            'item'=>$item->getAttributes(),
        ));
        return $this->save(false);
    }

    /**
     * Устанавливаем модель объекта для свойства item
     */
    public function populateItem() {
        $module = ImModule::get();
        $class = $module->classes['object'];
        $model = new $class();
        $user = new User();

        if (!$this->info) {
            $this->item = $class::model()->findByPk($this->idObject);
            $this->user = User::model()->findByPk($this->item->user_id);
            $this->info = $this->saveInfo($this->user,$this->item);
        } else {
            // теперь можно полноценно использовать $this->item
            $info = unserialize($this->info);
            $model->setAttributes($info['item'],false);
            $user->setAttributes($info['user'],false);
            $this->item = $model;
            $this->user = $user;
        }
    }

    public function afterFind() {
        $this->populateItem();
        return parent::afterFind();
    }

    public function addConversation($idObject) {
        $conversation = new Conversation();
        $conversation->appId = Yii::app()->params['ticno']['appId'];
        $conversation->type = 2;
        if (!$conversation->save())
            throw new CException('Не удалось сохранить разговор');

        $this->idObject = $idObject;
        $this->idConversation = $conversation->idConversation;
        if (!$this->save())
            throw new CException('Не удалось сохранить разговор для объекта');

        return $this;
    }

    public function toJSON(){
        return array(
            'idConversation'=>$this->idConversation,
            'idObject'=>$this->idObject,
            'object'=>!empty($this->item) ? $this->item->toObject() : [],
            'owner'=>!empty($this->user) ? $this->user->toObject() : [],
            // отдельный запрос на число непрочитанных по объекту
            'unread'=>$this->getMessagesTotal(true),
            'total'=>$this->getMessagesTotal(),
        );
    }
}