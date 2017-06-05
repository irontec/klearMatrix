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
            $where += $responseItem->getFilterClassCondition();
        }

        if ($responseItem->hasRawCondition()) {
            $where[] = $responseItem->getRawCondition();
        }

        if ($responseItem->isFilteredScreen()) {
            $pk = $responseItem->getRouteDispatcher()->getParam('pk');
            if ($pk === 'error') {
                $pk = 0;
            }

            $where[] = $responseItem->getFilteredCondition($pk);
        }

        if ($responseItem->hasForcedValues()) {
            $where = array_merge($where, $responseItem->getForcedValuesConditions());
        }

        $request = $this->getRequest();

        $whereProccessor = new KlearMatrix_Model_FilterProcessor;
        $whereProccessor
            ->setLogger($logger)
            ->setResponseData($responseData)
            ->setResponseItem($responseItem)
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

                if (empty($condition)) {
                    continue;
                }

                if (is_array($condition)) {
                    $expressions[] = $condition[0];
                    if (isset($condition[1])) {
                        $values += $condition[1];
                    }
                } else {
                    $expressions[] = $condition;
                }
            }

            $parsedCondition = Klear_Model_QueryHelper::replaceSelfReferences(
                implode(' AND ', $expressions),
                $responseItem->getEntityName()
            );

            $where = array(
                $parsedCondition,
                $values
            );
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
        return $this->createListWhere($columns, $model, $responseData, $responseItem, $logger);
    }
}