<?php

namespace app\models;

use Yii;
use \app\models\base\Country as BaseCountry;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "tblCountry".
 */
class Country extends BaseCountry
{

    public function behaviors()
    {
        return ArrayHelper::merge(
            parent::behaviors(),
            [
                # custom behaviors
            ]
        );
    }

    public function rules()
    {
        return ArrayHelper::merge(
            parent::rules(),
            [
                # custom validation rules
            ]
        );
    }
}
