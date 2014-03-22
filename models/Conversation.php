<?php

/**
 * This is the model class for table "Conversation".
 *
 * The followings are the available columns in table 'Conversation':
 * @property integer $idConversation
 * @property string $title
 * @property integer $isClosed
 * @property integer $inviteRules
 * @property string $appId
 * @property integer $type
 *
 * The followings are the available model unres:
 * @property Message[] $messages
 * @property User[] $users
 */
class Conversation extends IMActiveRecord
{

    const TYPE_SINGLE = 1; // conversation of two
    const TYPE_MULTIPLY = 2; // conversation of three and more

    public function beforeSave() {
        $module = ImModule::get();
        // идентификатор приложения из конфига
        $this->appId = $module->getAppId();
        return parent::beforeSave();
    }

    public $unread = 0;

    public function relations()
    {
        return array(
            'Message'=>array(self::MANY_MANY, 'Message', 'MessageConversation(idConversation, idMessage)',
                'with'=>'ReadMessage',
                'together'=>true,
            ),
            'Receiver'=>array(self::HAS_MANY, 'Receiver', 'idConversation'),
            'lastMessage' => array(self::MANY_MANY, 'Message', 'MessageConversation(idConversation, idMessage)',
                'alias'=>'Message',
                'order'=>'Message.idMessage DESC',
                'together'=>true,
            ),
            'ConversationObject' => array(self::HAS_ONE, 'ObjectConversation', 'idConversation'),
        );
    }

    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return Conversation the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function tableName() {return '{{Conversation}}';}

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('isClosed, inviteRules', 'numerical', 'integerOnly'=>true),
            array('title, appId', 'length', 'max'=>255),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('idConversation, title, isClosed, inviteRules, appId', 'safe', 'on'=>'search'),
        );
    }

    public function scopes(){
        return array(
            'single'=>array(
                'condition'=>'type='.self::TYPE_SINGLE,
            ),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'idConversation' => 'Id Conversation',
            'title' => 'Title',
            'isClosed' => 'Is Closed',
            'inviteRules' => 'Invite Rules',
            'appId' => 'App',
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
        $criteria->compare('title',$this->title,true);
        $criteria->compare('isClosed',$this->isClosed);
        $criteria->compare('inviteRules',$this->inviteRules);
        $criteria->compare('appId',$this->appId,true);

        return new CActiveDataProvider($this, array('criteria'=>$criteria));
    }

    public function addReceivers(array $userIds){
        $result = 0;
        $count_receivers = count($userIds);

        $users = $this->getRelated('Receiver');
        $receivers = array();
        foreach($users as $receiver) {
            $receivers[] = $receiver->idUser;
        }

        foreach ($userIds as $id) {
            if(in_array($id,$receivers)){
                $result++;
            }
            else{
                $receiver = new Receiver;
                $receiver->idUser = $id;
                $receiver->idConversation = $this->idConversation;

                if($receiver->save()){
                    $result++;
                }
            }
        }

        return ($result == $count_receivers) ? true : false;
    }

    /**
     * Normalize AR after cloning.
     */
    public function __clone()
    {
        $this->primaryKey = null;
        $this->oldPrimaryKey = null;
        $this->isNewRecord = true;
    }

    public function fork($receivers = array()){
        $idUser = Yii::app()->user->getId();
        if($this->isMember($idUser)){
            $conversation = clone $this;
            $conversation->save();
            // clone message conversation
            $messages = $this->messages;
            $query = '';
            foreach ($messages as $message) {
                $query .= 'INSERT INTO '.MessageConversation::model()->tableName().' (`idMessage`, `idConversation`) VALUES ('.$message->idMessage.', '.$conversation->idConversation.');';
            }
            $command = $this->getDbConnection()->createCommand($query);
            $command->execute();
            $command->reset();

            if(!$receivers){
                $receivers = [];
            }
            if(!in_array($idUser,$receivers)){
                $receivers[] = $idUser;
            }
            $conversation->addReceivers($receivers);
            return $conversation;
        }
        return false;
    }

    public function getUnique($id) {
        $conversation = $this->unique()->findByAttributes(array('title'=>$id,'appId'=>'domva'));
        if (!$conversation) {
            $conversation = new Conversation();
            $conversation->type = self::TYPE_UNIQUE;
            $conversation->title = $id;
            $conversation->save();
        }
        return $conversation;
    }

    public function getSingle($toId){

        $conversation = Conversation::model()->with('users')->single()->find('users.idUser ='.$toId);
        if(!$conversation){
            $conversation = new Conversation();
            $conversation->type = self::TYPE_SINGLE;
            $conversation->save();
        }

        return $conversation;
    }

    public function isPrivate(){
        if($this->type==self::TYPE_SINGLE || $this->type == self::TYPE_SPECIAL){
            return true;
        }

        return false;
    }

    public function isMember($idUser=null){
        if(!$idUser){
            $idUser = Yii::app()->user->getId();
        }
        $findReceiver = Receiver::model()->findByPk(array('idUser'=>$idUser,'idConversation'=>$this->idConversation));
        if($findReceiver)
            return true;

        return false;
    }

    public function allowInvite(){
        if($this->isPrivate())
            return false;
        if($this->inviteRules==self::INVITE_ONLY_MEMBER_CAN && !$this->isMember(Yii::app()->user->getId()))
            return false;

        return true;
    }

    public function getLink(){
        return '/im/conversation/'.$this->idConversation;
    }

    public function getLastPost(){
        return (isset($this->lastMessage[0])) ? $this->lastMessage[0] : false;
    }

    public function my(){
        $criteria = $this->getDbCriteria();
        $criteria->mergeWith(
            array(
                'select'=>'max(Message.idMessage) as maxMessageId',
                'with'=>array('Receiver','Message'),
                'together'=>true,
                'condition'=>'Receiver.idUser = :idUser',
                'params'=>array(':idUser'=>Yii::app()->user->getId()),
                'group'=>'t.idconversation',
            )
        );
        $criteria->order = 'maxMessageId DESC';
        return $this;
    }

    public function scopePage($total = 0, $limit=10)
    {
        $this->getDbCriteria()->mergeWith(array(
            'limit'=>$limit,
            'offset'=>$total,
        ));
        return $this;
    }

    public function messages($limit=10, $sinceId=null, $maxId=null)
    {
        $criteria =array(
            'with'=>'Message:ordered',
            'limit'=>$limit,
        );

        if($maxId){
            $criteria['condition']='Message.idMessage > :maxId';
            $criteria['params'] = array(':maxId'=>$maxId);
        }

        if($sinceId){
            $criteria['condition']='Message.idMessage < :sinceId';
            $criteria['params'] = array(':sinceId'=>$sinceId);
        }

        $this->getDbCriteria()->mergeWith($criteria);

        return $this;
    }

    public function toJSON($with=array())
    {
        $data = [];
        $attributes = $this->getAttributes();

        if(in_array('unread',$with)){
            $attributes['unread'] = $this->ConversationObject->getMessagesTotal(true);
            $attributes['total'] = $this->ConversationObject->getMessagesTotal();
        }

        if(isset($with['users'])){
            $data['users'] = [];
            $users = Receiver::model()->findAllByAttributes(array('idConversation'=>$this->idConversation));
            foreach ($users as $user) {
                $data['users'][] = $user->getUser();
            }
        }

        if(isset($with['messages']))
        {
            $data['messages'] = [];
            $criteria = $with['messages'];

            $params = array(
                'order'=>'Message.idMessage DESC',
                'limit'=>10,
            );

            if ($with['messages'] instanceof CDbCriteria) {
                $criteria->mergeWith($params);
            } else
            if (is_array($with['messages'])) {
                $criteria = array_merge($with['messages'], $params);
            } else {
                $criteria = $params;
            }

            $messages = $this->getRelated('Message', false, $criteria);
            foreach ($messages as $message) {
                $data['messages'][] = $message->toJSON();
            }
        }

        return [
            'attributes'=>$attributes,
            'data'=>$data,
        ];
    }

    public function defaultScope()
    {
        $module = ImModule::get();
        return array(
            // разговоры в рамках приложения
            'condition'=>"appId = '".ImModule::get()->getAppId()."'",
        );
    }

    public function getId() {
        return $this->idConversation;
    }

    public function getTitle() {
        return $this->title;
    }

    public function range($fromMessageId = null,$toMessageId = null)
    {
        $criteria = new CDbCriteria();

        if ($fromMessageId) {
            $criteria->addCondition('Message.idMessage > :fromMessageId');
            $criteria->params[':fromMessageId'] = $fromMessageId;
        }
        if ($toMessageId) {
            $criteria->addCondition('Message.idMessage > :toMessageId');
            $criteria->params[':toMessageId'] = $toMessageId;
        }

        $this->getDbCriteria()->mergeWith($criteria);
        return $this;
    }

    public function tail($maxId,$limit = 10) {
        $this->range($maxId)->getDbCriteria()->mergeWith(array(
            'order'=>'Message.idMessage DESC',
//            'limit'=>$limit,
        ));
        return $this;
    }

    /**
     * Формируем список пользователей
     * @param $receivers
     * @return array $list
     */
    public function getReceivers(array $receivers) {
        if (empty($this->ConversationObject))
            $this->getRelated('ConversationObject');
        $list = [];
        /**@var Receiver $r */
        foreach ($receivers as $r) {
            // если я не владелец, то мне можно переписываться только с владельцем
            if (!$this->ConversationObject->isMine()) {
                if ($r->idUser != Yii::app()->user->getId() &&
                    $r->idUser != $this->ConversationObject->user->getId())
                    continue;
            }
            $list[] = $r->toJSON();
        }

        return $list;
    }

    public static function add($receivers) {
        try {
            $conversation = new Conversation();
            $conversation->type = count($receivers)>2
                ? self::TYPE_MULTIPLY
                : self::TYPE_SINGLE;
            $conversation->save();
            $conversation->addReceivers($receivers);
        } catch (Exception $e) {
            throw new CException('Error while creating conversation');
        }
        return $conversation;
    }

    public static function get($idUser)
    {
        $data = Receiver::model()->find(array(
            'select'=>'idConversation',
            'with'=>array('receiver'),
            'condition'=>'t.idUser=:myself AND receiver.idUser=:idUser',
            'params'=>array(
                ':myself'=>Yii::app()->user->id,
                ':idUser'=>$idUser
            ),
        ));
        if (!$data) {
            $conversation = self::add(array(Yii::app()->user->id, $idUser));
        } else {
            $conversation = self::model()->findByPk($data->idConversation);
        }
        return $conversation;
    }

}