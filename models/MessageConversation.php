<?php

/**
 * This is the model class for table "messageconversation".
 *
 * The followings are the available columns in table 'messageconversation':
 * @property integer $idMessage
 * @property integer $idConversation
 */
class MessageConversation extends IMActiveRecord
{
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
        return 'MessageConversation';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('idMessage, idConversation', 'required'),
            array('idMessage, idConversation', 'numerical', 'integerOnly'=>true),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('idMessage, idConversation', 'safe', 'on'=>'search'),
        );
    }

    public function scopes(){
        return array(
            'my'=>array(
                'with'=>'users',
                'condition'=>'users.idUser='.Yii::app()->user->getId()
            ),
            'Message' => array(self::MANY_MANY, 'Message', 'MessageConversation(idConversation, idMessage)'),
        );
    }
    /**
     * @return array relational rules.
     */
    public function relations()
    {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'Message' => array(self::BELONGS_TO, 'Message', 'idMessage'),
            'message' => array(self::BELONGS_TO, 'Message', 'idMessage'),
            'conversation' => array(self::BELONGS_TO, 'Conversation', 'idConversation'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'idMessage' => 'Id Message',
            'idConversation' => 'Id Conversation',
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
        $criteria->compare('idConversation',$this->idConversation);

        return new CActiveDataProvider($this, array(
                                                   'criteria'=>$criteria,
                                              ));
    }
}