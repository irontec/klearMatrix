<?php
class KlearMatrix_Model_Field_Select_Decorator_Autocomplete extends KlearMatrix_Model_Field_DecoratorAbstract
{
    const APPLY_TO_LISTS = false; //Por ahora se gestiona desde template.helper.js [getValuesFromSelectColumn()] y list.js
    const APPLY_TO_LIST_FILTERING = true;

    const DYNAMIC_DATA_LOADING = true;

    protected function _init() {
        $this->_helper->viewRenderer->setNoRender(true);
    }

    public function run() {
        $mainRouter = $this->_request->getParam("mainRouter");
        $commandConfiguration = $mainRouter->getCurrentCommand()->getConfig()->getRaw()->autocomplete;

        $mapperName = $commandConfiguration->mapperName;
        $mapper = new $mapperName;
        $model = $mapper->loadModel(null);

        $searchTerm = $this->_request->getParam("term");
        $labelField = $commandConfiguration->label;
        $pkField = $model->getPrimaryKeyName();

        if ( $this->_request->getParam("reverse") ) {

            $results = $mapper->findByField($pkField, $this->_request->getParam("value"));
            $totalItems = sizeof($results);

        } else {
            $limit = NULL;
            $order = NULL;

            if (isset($commandConfiguration->limit)) {
                $limit = intval($commandConfiguration->limit);
            }

            if (isset($commandConfiguration->order)) {
                $order = $commandConfiguration->order;
            }

            $condition = '';

            if (isset($commandConfiguration->condition)) {
                $condition = '(' . $commandConfiguration->condition .') and ';
            }

            $where =  array(
                    $condition . $labelField . ' like ?',
                    array(
                        '%' . $searchTerm . '%'
                    )
                );

            $results = $mapper->fetchList($where, $order, $limit);
            $totalItems = $mapper->countByQuery($where);

        }

        $options = array();
        $labelGetter = 'get' . ucfirst($labelField);

        foreach ($results as $tienda) {

            $options[] = array(
                'id' => $tienda->getPrimaryKey(),
                'label' => $tienda->$labelGetter(),
                'value' => $tienda->$labelGetter(),
            );
        }

        $this->_view->totalItems = $this->_view->translate("%d items encontrados",$totalItems);

        if (isset($limit) && !is_null($limit)) {
            $show = ($limit < $totalItems)? $limit : $totalItems;
            $this->_view->totalItems .= ' ' . $this->_view->translate("(mostrando %d)",$show);
        }

        $this->_view->results = $options;
    }
}