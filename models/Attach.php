<?php

/**
 * This is the model class for table "attach".
 *
 * The followings are the available columns in table 'attach':
 * @property integer $idAttach
 * @property integer $idMessage
 * @property integer $idMediaFile
 *
 * The followings are the available model relations:
 * @property Message $idMessage0
 * @property Media $idMediaFile0
 */
class Attach extends IMActiveRecord
{
    public static $test;
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return Attach the static model class
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
		return 'Attach';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('idMessage, idMediaFile', 'required'),
			array('idMessage, idMediaFile', 'numerical', 'integerOnly'=>true),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('idAttach, idMessage, idMediaFile', 'safe', 'on'=>'search'),
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
			'message' => array(self::BELONGS_TO, 'Message', 'idMessage'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'idAttach' => 'Id Attach',
			'idMessage' => 'Id Message',
			'idMediaFile' => 'Id Media File',
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

		$criteria->compare('idAttach',$this->idAttach);
		$criteria->compare('idMessage',$this->idMessage);
		$criteria->compare('idMediaFile',$this->idMediaFile);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}