<?php
class KlearMatrix_Controller_Helper_CreateListWhere extends Zend_Controller_Action_Helper_Abstract
{
    public function createListWhere(
            KlearMatrix_Model_ColumnCollection $columns,
            $model,
            KlearMatrix_Model_MatrixResponse $responseData,
            KlearMatrix_Model_ResponseItem $responseItem,
            $logger = null)
    {
        $where = array();

        if ($responseItem->hasFilterClass()) {
            $where[] = $responseItem->getFilterClassCondition();
        }

        if ($responseItem->hasRawCondition()) {
            $where[] = $responseItem->getRawCondition();
        }

        if ($responseItem->isFilteredScreen()) {
            $where[] = $responseItem->getFilteredCondition($responseItem->getRouteDispatcher()->getParam('pk'));
        }

        if ($responseItem->hasForcedValues()) {
            $where = array_merge($where, $responseItem->getForcedValuesConditions());
        }

        $request = $this->getRequest();

        $whereProccessor = new KlearMatrix_Model_FilterProcessor;
        $whereProccessor
        ->setLogger($logger)
        ->setModel($model)
        ->setResponseData($responseData)
        ->setRequest($request)
        ->setColumnCollection($columns);

        if ($whereProccessor->isFilteredRequest()) {
            $where[] = $whereProccessor->getCondition();
        }


        if (count($where) == 0) {

            $where = null;

        } else {

            $values = $expressions = array();

            foreach ($where as $condition) {

                if (is_array($condition)) {
                    $expressions[] = $condition[0];
                    $values = array_merge($values, $condition[1]);
                } else {
                    $expressions[] = $condition;
                }
            }

            $where = array(implode(" and ", $expressions), $values);
        }

        return $where;
    }

    public function direct(
            KlearMatrix_Model_ColumnCollection $columns,
            $model,
            KlearMatrix_Model_MatrixResponse $responseData,
            KlearMatrix_Model_ResponseItem $responseItem,
            $logger = null)
    {
        return $this->createListWhere($columns, $model, $responseData, $responseItem);
    }
}