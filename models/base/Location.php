<?php
// This class was automatically generated by a giiant build task
// You should not change it manually as it will be overwritten on next build

namespace app\models\base;

use Yii;

/**
 * This is the base-model class for table "tblLocation".
 *
 * @property integer $locationSID
 * @property integer $countrySID
 * @property string $locationName
 * @property string $aliasModel
 */
abstract class Location extends \yii\db\ActiveRecord
{



    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tblLocation';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['countrySID', 'locationName'], 'required'],
            [['countrySID'], 'integer'],
            [['locationName'], 'string']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'locationSID' => 'Location Sid',
            'countrySID' => 'Country Sid',
            'locationName' => 'Location Name',
        ];
    }

}
