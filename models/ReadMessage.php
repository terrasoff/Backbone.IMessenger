<?php

/**
 * This is the model class for table "readmessage".
 *
 * The followings are the available columns in table 'readmessage':
 * @property integer $idMessage
 * @property integer $idUser
 * @property integer $isDeleted
 * @property string $ts
 */
class ReadMessage extends IMActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return ReadMessage the static model class
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
		return 'ReadMessage';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('idMessage, idUser', 'required'),
			array('idMessage, idUser, isDeleted', 'numerical', 'integerOnly'=>true),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('idMessage, idUser, isDeleted, ts', 'safe', 'on'=>'search'),
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
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'idMessage' => 'Id Message',
			'idUser' => 'Id User',
			'isDeleted' => 'Is Deleted',
			'ts' => 'Ts',
		);
	}

    public function my()
    {
        $this->getDbCriteria()->mergeWith(array(
            'condition'=>'ReadMessage.idUser=:myself',
            'params'=>array(':myself'=>Yii::app()->user->getId())
        ));

        return $this;
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
		$criteria->compare('idUser',$this->idUser);
		$criteria->compare('isDeleted',$this->isDeleted);
		$criteria->compare('ts',$this->ts,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

}