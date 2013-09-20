<?php
class KlearMatrix_Model_Field_Multiselect_Decorator_Autocomplete extends KlearMatrix_Model_Field_DecoratorAbstract
{
    //Por ahora se gestiona desde template.helper.js [getValuesFromSelectColumn()] y list.js
    const APPLY_TO_LISTS = false;
    const APPLY_TO_LIST_FILTERING = true;

    const DYNAMIC_DATA_LOADING = true;

    protected function _init()
    {
        $this->_helper->viewRenderer->setNoRender(true);
    }

    public function run()
    {
        $mainRouter = $this->_request->getParam("mainRouter");
        $commandConfiguration = $mainRouter->getCurrentCommand()->getConfig()->getRaw()->autocomplete;

        $mapperName = $commandConfiguration->mapperName;
        $mapper = new $mapperName;
        $model = $mapper->loadModel(null);

        $searchTerm = $this->_request->getParam("term");
        $labelField = $commandConfiguration->label;
        $pkField = $model->getPrimaryKeyName();

        if ($this->_request->getParam("reverse")) {

            $results = array();
            $lastResults = null;
            $itemIds = array();
            $totalItems = 0;

            foreach ($this->_request->getParam("value") as $value) {

                $where = $pkField . ' in (' . $value . ')';

                $results[$value] = $lastResults = $mapper->fetchList($where);
                foreach ($lastResults as $record) {
                    $itemIds[] = $record->getprimarykey();
                }
            }

            $totalItems = count(array_unique($itemIds));

        } else {

            $limit = null;
            $order = null;

            if (isset($commandConfiguration->limit)) {
                $limit = intval($commandConfiguration->limit);
            }

            if (isset($commandConfiguration->order)) {
                $order = $commandConfiguration->order;
            }

            $preCondition = '';
            if (isset($commandConfiguration->condition)) {
                $preCondition = '(' . $commandConfiguration->condition . ') and ';
            }

            $multiLangColumns = array_keys($model->getMultiLangColumnsList());
            if (in_array($labelField, $multiLangColumns)) {

                $query = array();
                $params = array();
                foreach ($model->getAvailableLangs() as $language) {
                    $query[] = $labelField . '_' . $language . ' like ?';
                    $params[] = '%' . $searchTerm . '%';
                }

                $query = '('. implode(" OR ", $query) .')';
                $where =  array(
                    $preCondition . $query,
                    $params
                );

            } else {

                $where =  array(
                    $preCondition . $labelField . ' like ?',
                    array(
                        '%' . $searchTerm . '%'
                    )
                );
            }

            $records = $mapper->fetchList($where, $order, $limit);
            $results = array();

            foreach ($records as $record) {
                $results[$record->getPrimaryKey()] = $record;
            }

            $totalItems = $mapper->countByQuery($where);
        }

        $options = array();
        $labelGetter = 'get' . ucfirst($labelField);
        foreach ($results as $key => $tupla) {
            $options[$key] = array();
            if (!is_array($tupla)) {
                $tupla = array($tupla);
            }

            foreach ($tupla as $record) {
                $options[$key][] = array(
                    'id' => $record->getPrimaryKey(),
                    'label' => $record->$labelGetter(),
                    'value' => $record->$labelGetter(),
                );
            }
        }

        $this->_view->totalItems = $this->_view->translate("%d items encontrados", $totalItems);

        if (isset($limit) && !is_null($limit)) {
            $show = ($limit < $totalItems)? $limit : $totalItems;
            $this->_view->totalItems .= ' ' . $this->_view->translate("(mostrando %d)", $show);
        }

        $this->_view->results = $options;
    }
}