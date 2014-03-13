<?php

/**
 * This is the model class for table "receiver".
 *
 * The followings are the available columns in table 'receiver':
 * @property integer $idConversation
 * @property integer $idUser
 * @property integer $ts
 */
class Receiver extends IMActiveRecord
{
    const LIMIT = 3;

    public $unread = 0;
    public $total = 0;

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'receiver' => array(self::BELONGS_TO, 'Receiver', 'idConversation'),
            'ReceiverUnread' => array(self::BELONGS_TO, 'Conversation', 'idConversation',
                'with'=>array('Messages'),
            ),
            'user' => array(self::BELONGS_TO, 'User', 'idUser'),
            'Conversation' => array(self::BELONGS_TO, 'Conversation', 'idUser',
                'with'=>array('Message'),
            ),
        );
    }

    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return Receiver the static model class
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
        return 'Receiver';
    }


    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('idConversation, idUser', 'required'),
            array('idConversation, idUser', 'numerical', 'integerOnly'=>true),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('idConversation, idUser', 'safe', 'on'=>'search'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'idConversation' => 'Id Conversation',
            'idUser' => 'Id User',
        );
    }

    /**
     * Retrieves a list of models based on the current search/filter conditions.
     * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
     */
    public function search()
    {
        // Warning: Please modify the following code to remove attributes that
        // should not be searched.

        $criteria=new CDbCriteria;

        $criteria->compare('idConversation',$this->idConversation);
        $criteria->compare('idUser',$this->idUser);

        return new CActiveDataProvider($this, array('criteria'=>$criteria));
    }

    public function getUser($fields = null)
    {
        // TODO: сделать поведением в общей форме. Возможно параметризировать имя класса User
        $user = User::model()->findByPk($this->idUser);
        if (!$user)
            throw new CException('User not found');
        return $user->toArray($fields);
    }

    public function conversation($conversation){
        // если я не-владелец, то я могу видеть только владельца
        if (!$conversation->ConversationObject->isMine()) {
            $this->getDbCriteria()->mergeWith(array(
                'condition'=>'t.idUser = :idOwner',
                'params'=>array(':idOwner'=>$conversation->ConversationObject->user->getTicnoId())
            ));
        }

        $this->getDbCriteria()->mergeWith(array(
            'condition'=>'t.idConversation = :idConversation',
            'params'=>array(':idConversation'=>$conversation->idConversation)
        ));

        return $this;
    }

    public function myself($condition = true){
        $this->getDbCriteria()->mergeWith(array(
            'condition'=>'t.idUser '.($condition ? '=' : '<>').' :idUser',
            'params'=>array(':idUser'=>Yii::app()->user->getTicnoId()),
        ));
        return $this;
    }

    /**
     * Участники разговора в хронологич.порядке
     * @param $idConversation
     * @param $total
     * @return $this
     */
    public function get($total,$limit = self::LIMIT){
        $this->myself(false)->getDbCriteria()->mergeWith(array(
            'with'=>array('Conversation'),
            'together'=>true,
            'order'=>'Message.idMessage DESC',
            'limit'=>$limit,
            'offset'=>$total,
        ));
        
        return $this;
    }

    /**
     * Число сообщений по объекту
     * @param bool $unread прочитанных/всего
     * @return int
     */
    public function getMessagesTotal($unread = false) {
        return Message::model()->getMessagesTotal($unread,$this->idConversation,$this->idUser);
    }

    public function toJSON() {
        // отдельный запрос на получение данных пользователя по ticnoId
        $module = ImModule::get();
        $class = $module->classes['user'];
        $user = $class::model()->findByTicnoId($this->idUser);

        if (!$user)
            throw new CException('user with ticnoID='.$this->idUser.' not found');

        return array_merge($user->toObject(),array(
            'idUser'=>$this->idUser,
            'unread'=>$this->getMessagesTotal(true),
            'total'=>$this->getMessagesTotal(),
        ));
    }

}