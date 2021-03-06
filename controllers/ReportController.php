<?php
/**
 * Created by PhpStorm.
 * User: Yudhi_G293
 * Date: 02/04/2018
 * Time: 10:54
 */

namespace app\controllers;

use app\models\Article;
use app\models\form\UploadForm;
use app\models\JSONResponse;
use app\models\Shipment;
use PHPUnit\Util\Log\JSON;
use Yii;
use app\models\FieldAlias;
use app\models\form\ReportWizardForm;
use app\models\ReportTemplate;
use app\models\search\ReportTemplateSearch;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\Response;
use yii\web\UploadedFile;

class ReportController extends Controller
{

    public function actionIndex()
    {
        $report = ReportTemplate::find()->all();

        return $this->render('index', [
            'report' => $report
        ]);
    }

    public function actionView($id)
    {
        $report = ReportTemplate::findOne($id);

        $field_alias = array();
        $field_alias_res = FieldAlias::find()->asArray()->all();
        foreach ($field_alias_res as $rows):
            $field_alias['k' . $rows['id']] = $rows;
        endforeach;

        $selectedField = $this->translateSelect($report->field_order, $field_alias);
        $filteredField = $this->translateFilter($report->filter, $field_alias);
        $orderedField = $this->translateOrder($report->sorting_order, $field_alias);
        $clientFilter = $this->translateClientFilter($report->client_filter, $field_alias);

        $query = Shipment::find()
            ->innerJoinWith('customer')
            ->orderBy($orderedField);

        foreach ($filteredField as $ff_temp):
            $query->andWhere($ff_temp);
        endforeach;

        $params = Yii::$app->request->queryParams;
        $applyClientFilter = $this->translateAppliedClientFilter($report->client_filter, $params, $field_alias);

        foreach ($applyClientFilter as $cf_temp):
            $query->andWhere($cf_temp);
        endforeach;

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $report->limit_per_page ? $report->limit_per_page : null,
            ],
        ]);

        return $this->render('view', [
            'report' => $report,
            'clientFilter' => $clientFilter,
            'gridColumn' => $selectedField,
            'dataProvider' => $dataProvider
        ]);
    }

    public function actionNew()
    {
        $customer = FieldAlias::find()
            ->where(['type' => 'CUSTOMER'])
            ->orderBy('id')
            ->all();

        $shipment = FieldAlias::find()
            ->where(['type' => 'SHIPMENT'])
            ->orderBy('id')
            ->all();

        $advancedFilter = FieldAlias::find()
            ->where(['use_as_filter' => 1])
            ->orderBy('id')
            ->all();

        $sortingOrder = FieldAlias::find()
            ->where(['use_as_order' => 1])
            ->orderBy('id')
            ->all();

        $clientFilter = FieldAlias::find()
            ->where(['use_as_client_filter' => 1])
            ->orderBy('id')
            ->all();

        $reportTemplate = new ReportWizardForm();
//        $className = \app\models\Customer::className();
//        $classInstance = new $className;
        return $this->render('create', [
            'customer' => $customer,
            'shipment' => $shipment,
            'advancedFilter' => $advancedFilter,
            'sortingOrder' => $sortingOrder,
            'clientFilter' => $clientFilter,
            'model' => $reportTemplate,
//            'relations' => $classInstance->getRelationData()
        ]);
    }

    public function actionSave()
    {
        $model = new ReportTemplate();
        $response = new JSONResponse();
        // validate any AJAX requests fired off by the form
        if (Yii::$app->request->isAjax) {
            $model->attributes = Yii::$app->request->post();
            if ($model->validate()) {
                $model->field_order = json_encode($model->field_order, JSON_NUMERIC_CHECK);
                $model->filter = json_encode($model->filter, JSON_NUMERIC_CHECK);
                $model->sorting_order = json_encode($model->sorting_order, JSON_NUMERIC_CHECK);
                $model->client_filter = json_encode($model->client_filter, JSON_NUMERIC_CHECK);
                $model->save(false);
                $response->message = "Successfully saved a new template";
            } else {
                $response->errorcode = 1000;
                $response->form_error = $model->errors;
            }
            Yii::$app->response->format = Response::FORMAT_JSON;
            return $response;
        }
    }

    public function actionRemove()
    {
        $response = new JSONResponse();
        // validate any AJAX requests fired off by the form
        if (Yii::$app->request->isAjax) {
            $report_id = Yii::$app->request->post('id');
            if ($report_id) {
                $model = $this->findModel($report_id);
                $response->message = "Report '" . $model->report_name . "' has been deleted";
                $model->delete();
                Yii::$app->session->setFlash('success', $response->message);
            } else {
                $response->errorcode = 1;
                $response->message = "Report ID cannot be null";
            }
            Yii::$app->response->format = Response::FORMAT_JSON;
            return $response;
        }
    }

    public function actionClone()
    {
        $response = new JSONResponse();
        // validate any AJAX requests fired off by the form
        if (Yii::$app->request->isAjax) {
            $report_id = Yii::$app->request->post('id');
            $report_name = Yii::$app->request->post('name');
            if ($report_id) {
                $model = $this->findModel($report_id);
                $response->message = "Report '" . $model->report_name . "' has been cloned";
                $newRecords = new ReportTemplate;
                $newRecords->attributes = $model->attributes;
                $newRecords->report_name = $report_name;
                $newRecords->isNewRecord = true;
                $newRecords->save(false);
                Yii::$app->session->setFlash('success', $response->message);
            } else {
                $response->errorcode = 1;
                $response->message = "Report ID cannot be null";
            }
            Yii::$app->response->format = Response::FORMAT_JSON;
            return $response;
        }
    }

    public function actionExport($id)
    {
        $report = ReportTemplate::findOne($id);

        $field_alias = array();
        $field_alias_res = FieldAlias::find()->asArray()->all();
        foreach ($field_alias_res as $rows):
            $field_alias['k' . $rows['id']] = $rows;
        endforeach;

        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->setTitle($report->report_name);

        $selectedField = $this->translateSelect($report->field_order, $field_alias);
        $selectedLabel = $this->translateLabel($report->field_order, $field_alias);
        $filteredField = $this->translateFilter($report->filter, $field_alias);
        $orderedField = $this->translateOrder($report->sorting_order, $field_alias);
        $clientFilter = $this->translateClientFilter($report->client_filter, $field_alias);

        $query = Shipment::find()
            ->innerJoinWith('customer')
            ->orderBy($orderedField);

        foreach ($filteredField as $ff_temp):
            $query->andWhere($ff_temp);
        endforeach;

        $letter = 65;
        foreach ($selectedLabel as $labels) {
            $objPHPExcel->getActiveSheet()->setCellValue(chr($letter) . '1', $labels);
            $objPHPExcel->getActiveSheet()->getStyle(chr($letter) . '1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $letter++;
        }

        $row = 2;
        $letter = 65;
        foreach ($query->asArray()->all() as $rows):
            foreach ($selectedField as $fields) {
                $objPHPExcel->getActiveSheet()->setCellValue(chr($letter) . $row, $this->readCell($rows, $fields));
                $objPHPExcel->getActiveSheet()->getStyle(chr($letter) . $row)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                $letter++;
            }
            $letter = 65;
            $row++;
        endforeach;

        header('Content-Type: application/vnd.ms-excel');
        $filename = $report->report_name . ".xls";
        header('Content-Disposition: attachment;filename=' . $filename);
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit();
    }

    public function readCell($cell, $field)
    {
        if (strpos($field, '.')) {
            $explodedField = explode('.', $field);
            return $cell[$explodedField[0]][$explodedField[1]];
        } else {
            return $cell[$field];
        }
    }

    public function actionUpload()
    {
        $response = new JSONResponse();
        // validate any AJAX requests fired off by the form
        if (Yii::$app->request->isAjax) {
            $model = new UploadForm();

            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
            if ($model->uploadAsTemp()) {
                $response->data = $model->newName;
                $response->message = "Successfully uploaded the file";
            } else {
                $response->errorcode = 1000;
                $response->form_error = $model->errors;
            }
            Yii::$app->response->format = Response::FORMAT_JSON;
            return $response;
        }
    }

    private function translateClientFilter($client_filter, $fields)
    {
        $client_filter_arr = json_decode($client_filter);
        $translated_filter = array();

        if (!is_array($client_filter_arr))
            return $translated_filter;

        foreach ($client_filter_arr as $rows) :
            $translated_filter[] = array(
                'id' => $rows->id,
                'label' => $fields['k' . $rows->id]['label_name'],
                'name' => $fields['k' . $rows->id]['field_name'],
                'op' => $rows->op
            );
        endforeach;

        return $translated_filter;
    }

    private function translateAppliedClientFilter($client_filter, $params, $fields)
    {
        $client_filter_arr = json_decode($client_filter);
        $client_filter_alias = array();
        $translated_filter = array();

        if (!is_array($client_filter_arr))
            return $translated_filter;

        foreach ($client_filter_arr as $rows) :
            $client_filter_alias['c' . $rows->id] = $rows->op;
        endforeach;

        foreach ($params as $v => $a):
            if (array_key_exists('k' . $v, $fields) && array_key_exists('c' . $v, $client_filter_alias)) {
                $translated_filter[] = [$client_filter_alias['c' . $v], $fields['k' . $v]['field_name'], $a];
            }
        endforeach;

        return $translated_filter;
    }

    private function translateSelect($selected, $fields)
    {
        $selected_arr = json_decode($selected);
        $translated_column = array();

        if (!is_array($selected_arr))
            return $translated_column;

        foreach ($selected_arr as $id):
            $translated_column[] = $fields['k' . $id]['field_name'];
        endforeach;

        return $translated_column;
    }

    private function translateLabel($selected, $fields)
    {
        $selected_arr = json_decode($selected);
        $translated_column = array();

        if (!is_array($selected_arr))
            return $translated_column;

        foreach ($selected_arr as $id):
            $translated_column[] = $fields['k' . $id]['label_name'];
        endforeach;

        return $translated_column;
    }

    private function translateFilter($filtered, $fields)
    {
        $filtered_arr = json_decode($filtered);
        $translated_filter = array();

        if (!is_array($filtered_arr))
            return $translated_filter;

        foreach ($filtered_arr as $filter_temp):
            $translated_filter[] = $this->translateWhere($fields['k' . $filter_temp->id]['field_name'], $filter_temp->op, $filter_temp->value);
        endforeach;

        return $translated_filter;
    }

    private function translateOrder($ordered, $fields)
    {
        $ordered_arr = json_decode($ordered);
        $translated_order = array();

        if (!is_array($ordered_arr))
            return $translated_order;

        foreach ($ordered_arr as $order_temp):
            $translated_order[$fields['k' . $order_temp->id]['field_name']] = ($order_temp->type == "desc" ? SORT_DESC : SORT_ASC);
        endforeach;

        return $translated_order;
    }

    private function translateWhere($field, $op, $value)
    {
        switch ($op) {
            case "eq":
                return ['=', $field, $value];
                break;
            case "nq":
                return ['!=', $field, $value];
                break;
            case "lt":
                return ['<', $field, $value];
                break;
            case "gt":
                return ['>', $field, $value];
                break;
            case "le":
                return ['<=', $field, $value];
                break;
            case "ge":
                return ['>=', $field, $value];
                break;
            case "sw":
                return ['LIKE', $field, $value . "%"];
                break;
            case "ns":
                return ['LIKE', $field, "%" . $value];
                break;
            case "in":
                return ['IN', $field, $value];
                break;
            case "ex":
                return ['NOT IN', $field, $value];
                break;
            default:
                return ['=', $field, $value];
        }
    }

    protected function findModel($id)
    {
        if (($model = ReportTemplate::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

}