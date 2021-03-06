<?php
// This class was automatically generated by a giiant build task
// You should not change it manually as it will be overwritten on next build

namespace app\models\base;

use Yii;

/**
 * This is the base-model class for table "tblCustomer".
 *
 * @property integer $customerSID
 * @property string $customerCode
 * @property string $customerName
 * @property string $customerAddress1
 * @property string $customerAddress2
 * @property string $customerAddress3
 * @property integer $customerAddressCountrySID
 * @property integer $customerLocationSID
 * @property string $aliasModel
 */
abstract class Customer extends \yii\db\ActiveRecord
{



    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'customer';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['customerCode', 'customerName', 'customerAddress1', 'customerAddress2', 'customerAddress3', 'customerAddressCountrySID', 'customerLocationSID'], 'required'],
            [['customerCode', 'customerName', 'customerAddress1', 'customerAddress2', 'customerAddress3'], 'string'],
            [['customerAddressCountrySID', 'customerLocationSID'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'customerSID' => 'Customer Sid',
            'customerCode' => 'Customer Code',
            'customerName' => 'Customer Name',
            'customerAddress1' => 'Customer Address1',
            'customerAddress2' => 'Customer Address2',
            'customerAddress3' => 'Customer Address3',
            'customerAddressCountrySID' => 'Customer Address Country Sid',
            'customerLocationSID' => 'Customer Location Sid',
            'location.locationName' => 'Customer Location Name',
            'country.countryName' => 'Customer Country Name'
        ];
    }

}
