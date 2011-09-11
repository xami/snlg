<?php

/**
 * This is the model class for table "wp_users".
 *
 * The followings are the available columns in table 'wp_users':
 * @property string $ID
 * @property string $user_login
 * @property string $user_pass
 * @property string $user_nicename
 * @property string $user_email
 * @property string $user_url
 * @property string $user_registered
 * @property string $user_activation_key
 * @property integer $user_status
 * @property string $display_name
 */
class WpUsers extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return WpUsers the static model class
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
		return 'wp_users';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('user_status', 'numerical', 'integerOnly'=>true),
			array('user_login, user_activation_key', 'length', 'max'=>60),
			array('user_pass', 'length', 'max'=>64),
			array('user_nicename', 'length', 'max'=>50),
			array('user_email, user_url', 'length', 'max'=>100),
			array('display_name', 'length', 'max'=>250),
			array('user_registered', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('ID, user_login, user_pass, user_nicename, user_email, user_url, user_registered, user_activation_key, user_status, display_name', 'safe', 'on'=>'search'),
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
			'ID' => 'ID',
			'user_login' => 'User Login',
			'user_pass' => 'User Pass',
			'user_nicename' => 'User Nicename',
			'user_email' => 'User Email',
			'user_url' => 'User Url',
			'user_registered' => 'User Registered',
			'user_activation_key' => 'User Activation Key',
			'user_status' => 'User Status',
			'display_name' => 'Display Name',
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

		$criteria->compare('ID',$this->ID,true);
		$criteria->compare('user_login',$this->user_login,true);
		$criteria->compare('user_pass',$this->user_pass,true);
		$criteria->compare('user_nicename',$this->user_nicename,true);
		$criteria->compare('user_email',$this->user_email,true);
		$criteria->compare('user_url',$this->user_url,true);
		$criteria->compare('user_registered',$this->user_registered,true);
		$criteria->compare('user_activation_key',$this->user_activation_key,true);
		$criteria->compare('user_status',$this->user_status);
		$criteria->compare('display_name',$this->display_name,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}