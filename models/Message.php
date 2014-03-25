<?php

/**
 * This is the model class for table "Message".
 *
 * The followings are the available columns in table 'Message':
 * @property integer $idMessage
 * @property string $title
 * @property string $body
 * @property string $ts
 * @property integer $idUser
 * @property integer $replyIdMessage
 *
 * The followings are the available model relations:
 * @property Attach[] $attaches
 * @property User $author
 * @property Message $reply_for
 * @property Message[] $read
 * @property Conversation[] $conversations
 * @property User[] $users
 */
class Message extends IMActiveRecord
{
    public $idConversation = null;
    public $isRead = null;

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'attaches' => array(self::HAS_MANY, 'Attach', 'idMessage'),
            'author' => array(self::BELONGS_TO, 'User', 'idUser'),
            'reply_for' => array(self::BELONGS_TO, 'Message', 'replyIdMessage'),
            'conversations' => array(self::MANY_MANY, 'Conversation', 'MessageConversation(idMessage, idConversation)'),
            'ReadMessage' => array(self::HAS_MANY, 'ReadMessage', 'idMessage',
                'scopes'=>array('my')
            ),
            'ReplyMessage' => array(self::BELONGS_TO, 'Message', 'replyIdMessage'),
            'Receiver' => array(self::BELONGS_TO, 'Receiver', 'idUser'),
            'MessageConversation' => array(self::BELONGS_TO, 'MessageConversation', 'idMessage'),
            'Conversation'=>array(self::HAS_ONE,'Conversation',array('idConversation'=>'idConversation'),'through'=>'MessageConversation'),
        );
    }

    public function scopes(){
        return array(
            'ordered'=>array(
                'order'=>'Message.idMessage DESC',
            ),
        );
    }

    public function beforeSave(){
        if($this->getIsNewRecord()){
            $this->idUser = Yii::app()->user->getId();
        }
        return parent::beforeSave();
    }

    public function afterSave(){
        if(!$this->setMessageConversation())
            throw new IMException(400,'Разговор не определен');

        $this->markRead(Yii::app()->user->getId()); // для автора сообщение прочитано
        return parent::afterSave();
    }

    public function afterFind(){
        parent::afterFind();
        $this->setAttribute('read',$this->isRead());
        return true;
    }

    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return Message the static model class
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
        return 'Message';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('body', 'required'),
            array('idUser, replyIdMessage, toUserId', 'numerical', 'integerOnly'=>true),
            array('title', 'length', 'max'=>255),
            array('idConversation','checkMy'),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('idMessage, title, body, ts, idUser, replyIdMessage, toUserId', 'safe', 'on'=>'search'),
            array('idConversation','safe'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'idMessage' => 'Id Message',
            'title' => 'Title',
            'body' => 'Body',
            'ts' => 'Ts',
            'idUser' => 'Id User',
            'replyIdMessage' => 'Reply Id Message',
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

        $criteria->compare('idMessage',$this->idMessage);
        $criteria->compare('title',$this->title,true);
        $criteria->compare('body',$this->body,true);
        $criteria->compare('ts',$this->ts,true);
        $criteria->compare('idUser',$this->idUser);
        $criteria->compare('replyIdMessage',$this->replyIdMessage);

        return new CActiveDataProvider($this, array('criteria'=>$criteria,));
    }

    public  function setMessageConversation(){
        $messageConversation = new MessageConversation();

        $messageConversation->idConversation = $this->idConversation;
        $messageConversation->idMessage = $this->idMessage;

        return $messageConversation->save();

    }

    public function isRead(){
        return !empty($this->read) ? true : false;
    }

    public function isMine(){
        return $this->idUser == Yii::app()->user->getId();
    }

    public function markRead($idUser=null){
        if($idUser==null){
            $idUser = Yii::app()->user->getId();
        }
        $pk = array(
            'idMessage'=>$this->idMessage,
            'idUser'=> $idUser
        );
        $read_message = ReadMessage::model()->findByPk($pk);
        if(!$read_message){
            $read_message = new ReadMessage();
            $read_message->setAttributes($pk);
            $read_message->save();
        }
        return $read_message;
    }

    public function readUserMessages($idConversation=null,$idUser = null, $maxId=null){
        return $this->markAllRead($maxId,$idConversation,null,$idUser);
    }

    public function markAllRead($maxId=null,$idConversation=null,$idMessages=null,$idUser = null){
        $condition = new CDbCriteria;

        if($idMessages){
            $condition->addInCondition('t.idMessage',$idMessages);
        }

        if($maxId){
            $condition->addCondition('t.idMessage < :maxId');
            $condition->params = array(':maxId'=>$maxId);
        }

        if($idConversation){
            $condition->addCondition('my.idConversation = :idConversation');
            $condition->params[':idConversation'] = $idConversation;
        }

        if($idUser){
            $condition->addCondition('t.idUser = :idSender');
            $condition->params[':idSender'] = $idUser;
        }

        $messages = Message::model()->with(['conversations','conversations.my'])->findAll($condition);

        return $messages;
    }

    public function markDelete($idUser=null){
        $read_message = $this->markRead($idUser);
        $read_message->isDeleted = true;
        $read_message->ts = null;
        $read_message->save();
        return true;
    }

    // проверяем: сообщение, которые написал НЕ я
    public function notMine(){
        return $this->mine(false);
    }

    // сообщения, которые написал я
    public function mine($isMine = true,$and = true){
        $this->getDbCriteria()->mergeWith(array(
             'condition'=>'t.idUser '.($isMine ? '=' : '<>').' :myself',
             'params'=>array(':myself'=>Yii::app()->user->getId())
        ),false);
        return $this;
    }

    public function receiver($idReceiver = null) {
        if (!$idReceiver) $idReceiver = Yii::app()->user->getId();

        $this->getDbCriteria()->mergeWith(array(
            'condition'=>'Receiver.idUser = :idReceiver',
            'params'=>array(':idReceiver'=>$idReceiver)
        ));
        return $this;
    }

    public function sender($idUser = null){
        if (!$idUser)
            $idUser = Yii::app()->user->getId();
        $this->getDbCriteria()->mergeWith(array(
            'condition'=>'t.idUser = :idSender',
            'params'=>array(':idSender'=>$idUser)
        ));
        return $this;
    }

    public function conversation($idConversation = null,$idSender = null){
        // сообщения от всех в разговоре
        $this->getDbCriteria()->mergeWith(array(
            'with'=>array('Conversation'),
            'together'=>true,
        ));

        // сообщения только от определенного пользователя в разговоре
        if ($idConversation) {
            $this->getDbCriteria()->mergeWith(array(
                'condition'=>'Conversation.idConversation=:idConversation',
                'params'=>array(':idConversation'=>$idConversation),
            ));
        }

        // сообщения только от определенного пользователя в разговоре
        if ($idSender) {
            $this->getDbCriteria()->mergeWith(array(
                'with'=>array('Conversation'),
                'together'=>true,
                // принятые/полученные сообщения только между двумя пользователями
                'condition'=>'(t.idUser = :idSender or t.idUser = :myself) AND (t.toUserId = :idSender or t.toUserId = :myself)',
                'params'=>array(
                    ':idSender'=>$idSender,
                    ':myself'=>Yii::app()->user->model->getId(),
                ),
            ));
        }

        return $this;
    }

    public function page($limit = 10, $maxId = null, $sinceId = null) {
        $criteria = array(
            'order'=>'t.idMessage DESC',
            'limit'=>$limit,
        );

        if($maxId){
            $criteria['condition']='t.idMessage > :maxId';
            $criteria['params'] = array(':maxId'=>$maxId);
        }

        if($sinceId){
            $criteria['condition']='t.idMessage < :sinceId';
            $criteria['params'] = array(':sinceId'=>$sinceId);
        }
        
        $this->getDbCriteria()->mergeWith($criteria);
        return $this;
    }

    public function range($fromMessageId = null,$toMessageId = null) {
        $criteria = new CDbCriteria();
        if ($fromMessageId) {
            $criteria->addCondition('t.idMessage > :fromMessageId');
            $criteria->params[':fromMessageId'] = $fromMessageId;
        }
        if ($toMessageId) {
            $criteria->addCondition('t.idMessage > :toMessageId');
            $criteria->params[':toMessageId'] = $toMessageId;
        }

        $this->getDbCriteria()->mergeWith($criteria);
        return $this;
    }

    public function tail($maxId,$limit = 10) {
        // если НЕ задается maxId, то нужно упорядочивать по убыванию (иначе будет выборка посл.сообщений)
        // пока будем считать, что maxId задавать обязательно
        $this->range($maxId)->getDbCriteria()->mergeWith(array(
            'order'=>'t.idMessage DESC',
            'limit'=>$limit,
        ));
        return $this;
    }

    public function unread(){
        $this->getDbCriteria()->mergeWith(array(
            'with'=>'ReadMessage',
            // непрочитанные мной
            'condition'=>'ReadMessage.idMessage IS NULL',
        ));
        return $this;
    }

    public function checkMy($attribute){
        $idConversation = $this->$attribute;
        $conversation = Conversation::model()->findByPk($idConversation);
        if(!$conversation || !$conversation->isMember(Yii::app()->user->getId()))
            $this->addError($attribute, 'вы не можете писать в этот разговор');
    }

    /**
     * @param array $list список идентификаторов сообщений, которые надо прочитать
     * @return array список прочитанных сообщений
     */
    public function markReadById($list){
        if (is_int($list)) // а все равно массив ^^
            $idList = array($list);

        $messages = [];
        foreach ($list as $idMessage) {
            $message = $this->findByPk($idMessage);
            if ($message)
                $messages[] = $message->markRead();
        }
        return $messages;
    }

    /**
     * Число непрочитанных в разговоре
     * @param bool $unread считаем число непрочитанных или всех сообщений
     * @param int $idConversation
     * @param int $idSender сообщения от опр.пользователя
     * @return int
     */
    public function getMessagesTotal($unread=false, $idConversation = null,$idSender = null){
        $messages = Message::model()
            ->with(array('Conversation','Conversation.Receiver'))
            ->myConversation();

        if ($idConversation)
            $messages = $messages->conversation($idConversation);

        if ($unread)
            $messages = $messages->unread();

        if ($idSender)
            $messages = $messages->receivers(array($idSender,Yii::app()->user->getId()));

        return (int)$messages->count();
    }

    public function receivers($receivers) {
        $criteria = new CDbCriteria();
        foreach($receivers as $i=>$r) {
            $criteria->addCondition('t.idUser=:receiver'.$i,'OR');
            $criteria->params[':receiver'.$i] = $r;
        }
        $this->getDbCriteria()->mergeWith($criteria);
        return $this;
    }

    // Сообщения из разговоров, в которых есть я
    public function myConversation(){
        $this->getDbCriteria()->mergeWith(array(
            'condition'=>'Receiver.idUser = :myself',
            'params'=>array(':myself'=>Yii::app()->user->getId())
        ));
        return $this;
    }

    public function last($idUser){
        $this->sender($idUser)->getDbCriteria()->mergeWith(array(
            'limit'=>1,
            'order'=>'t.idMessage DESC',
        ));
        return $this;
    }

    public function toJSON(){
        // свои сообщения всегда прочитанны
        $isRead = $this->isMine()
            ? true
            : (bool)$this->isRead();

        return CMap::mergeArray($this->attributes,array(
            'read'=>$isRead,
        ));
    }
}