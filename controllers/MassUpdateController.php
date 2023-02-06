<?php

class KlearMatrix_MassUpdateController extends Zend_Controller_Action
{

    /**
     * Route Dispatcher desde klear/index/dispatch
     * @var KlearMatrix_Model_RouteDispatcher
     */
    protected $_mainRouter;



    /**
     * Lista of selected results
     * @var unknown
     */
    protected $_results;


    /**
     * @var KlearMatrix_Model_Column
     */
    protected $_column;
    /**
     * Screen|Dialog
     * @var KlearMatrix_Model_ResponseItem
     */
    protected $_item;

    public function init()
    {
        /* Initialize action controller here */
        $this->_helper->layout->disableLayout();

        $this->_helper->ContextSwitch()
            ->addActionContext('index', 'json')
            ->addActionContext('update', 'json')
            ->initContext('json');

        $this->_mainRouter = $this->getRequest()->getUserParam("mainRouter");
        $this->_item = $this->_mainRouter->getCurrentItem();
    }

    protected function _isFieldMassUpdateable($fieldConfig)
    {
        if (
            !$fieldConfig->isMassUpdateable()
        ) {
            throw new Exception("Mass Update is only compatible with KlearMatrix_Model_Field_Select and KlearMatrix_Model_Field_Checkbox");
        }
    }


    /**
     * TODO: Devolver solo la estructura de column (toArray),
     * crear un jquery.massupdate.js, y que se dibuje utilizando select.phtml
     * (Y que sea compatible con más de un tipo de campo :D)
     *
     * @param KlearMatrix_Model_Column $column
     * @throws Exception
     */
    protected function _getEditableContent()
    {
        $fieldConfig = $this->_column->getFieldConfig();
        $this->_isFieldMassUpdateable($fieldConfig);
        $adapterConfig = $fieldConfig->getConfig();
        $data = '';
        $data .= '<select name="updateable" ';
        if ($fieldConfig instanceof KlearMatrix_Model_Field_Multiselect) {
            $data .= ' multiple class="multiselect" ';
        }
        $data .= ' >';
        foreach ($adapterConfig['values'] as $val) {
            $data .= '<option value="'.$val['key'].'">'.$val['item']."</option>";
        }
        $data .= '</select>';
        return $data;
    }

    protected function _getValueToUpdate()
    {
        $value = $this->getRequest()->getParam("updateable");

        $fieldConfig = $this->_column->getFieldConfig();

        $this->_isFieldMassUpdateable($fieldConfig);

        $adapterConfig = $fieldConfig->getConfig();

        if ($fieldConfig instanceof KlearMatrix_Model_Field_Multiselect) {
            $aValidVals = array();
            if (!is_array($value)) {
                return array();
            }
            foreach ($adapterConfig['values'] as $val) {
                if (in_array($val['key'], $value)) {
                    $aValidVals[] = $val['key'];
                }
            }
            return $aValidVals;
        } else {
            foreach ($adapterConfig['values'] as $val) {
                if ($val['key'] == $value) {
                    return $value;
                }
            }
        }
        throw new \Exception("valid value not found!");
    }

    public function indexAction()
    {
        $column = null;
        $mapperName = $this->_item->getMapperName();
        $mapper = new $mapperName;

        $pk = $this->_mainRouter->getParam("pk");


        if (is_array($pk)) {
            $this->_helper->log('Mass Update for mapper (not executed):' . $mapperName . ' > various PK('.implode(",", $pk).')');
        } else {
            $this->_helper->log('Mass Update for mapper (not executed):' . $mapperName . ' > PK('.$pk.')');
            $pk = array($pk);
        }

        $cols = $this->_item->getVisibleColumns();
        $field = $this->_item->getConfigAttribute('field');

        $baseModel = $this->_item->getObjectInstance();
        $defaultGetter = $cols->getDefaultCol()->getGetterName();

        $this->_column = $cols->getColFromDbName($field);

        $fieldConfig = $this->_column->getFieldConfig();

        $where = $baseModel->getPrimaryKeyName() . " in ('".implode("','", $pk)."')";
        $this->_results = $mapper->fetchList($where);

        if (sizeof($this->_results) != sizeof($pk)) {
            throw new Klear_Exception_Default($this->view->translate('Record not found. Could not Mass Update.'));
        }

        $editableContent = $this->_getEditableContent();

        if ($this->getRequest()->getPost("activate") == true) {
            return $this->_doTheUpdate();
        }

        $message = '<p>';
        if ($this->_item->getDescription()) {
            $message .= $this->_item->getDescription();
        } else {
            $message .= sprintf(
                $this->view->translate('Do you want to update "%s"?'),
                $column->getPublicName()
            );
        }
        $message .= '</p>';

        $counter = 0;
        $showLimit = 4;
        foreach ($this->_results as $item) {
            if (++$counter>$showLimit) {
                break;
            }
            $message .= '<p class="updateable-item">'  .
                $item->{$defaultGetter}().' <em>(#'.$item->getPrimaryKey().')</em></p>';

        }
        $totalEls = is_countable($this->_results) ? count($this->_results) : 0;
        if ($totalEls>$showLimit) {
            $countEls = $totalEls-$showLimit;
            $message .= ' <p class="updateable-item"> ' .
                    sprintf($this->view->translate('and %s more'), $countEls) .
                    '.</p>';
        }
        $message .= '<p class="updateable-control">' . $editableContent . '</p>';


        $title = $this->_item->getTitle();
        if (empty($title)) {
            $title = sprintf(
                $this->view->translate('Update %s'),
                $column->getPublicName()
            );
        }



        $data = array(
            'message' => $message,
            'title' => $this->_item->getTitle(),
            'buttons' => array(
                $this->view->translate('Cancel') => array(
                        'recall' => false,
                ),
                $this->view->translate('Update') => array(
                    'recall' => true,
                    'params' => array('activate'=>true)
                )

            )
        );

        $jsonResponse = new Klear_Model_DispatchResponse();
        $jsonResponse->setModule('klearMatrix');
        $jsonResponse->setPlugin('klearMatrixGenericDialog');

        $jsonResponse->addJsFile("/../klearMatrix/js/plugins/jquery.klearmatrix.genericdialog.js");

        if ($fieldConfig instanceof KlearMatrix_Model_Field_Multiselect) {
            $jsonResponse->addJsFile("/../klearMatrix/js/plugins/jquery.multiselect.filter.js");
            $jsonResponse->addJsFile("/../klearMatrix/js/plugins/jquery.multiselect.js");
            $jsonResponse->addCssFile("/../klearMatrix/css/jquery.multiselect.css");
            $jsonResponse->addCssFile("/../klearMatrix/css/jquery.multiselect.filter.css");
        }

        $jsonResponse->setData($data);
        $jsonResponse->attachView($this->view);
    }

    protected function _doTheUpdate()
    {

        $setter = $this->_column->getSetterName();
        $value = $this->_getValueToUpdate();

        $fieldConfig = $this->_column->getFieldConfig();



        if ($fieldConfig instanceof KlearMatrix_Model_Field_Multiselect) {

            $adapter = $fieldConfig->getAdapter();

            $relationMapperName = $adapter->getRelationMapper();
            $relationProperty = $adapter->getRelationProperty();

            $relationMapper = new $relationMapperName;
            $relationModel = $relationMapper->loadModel(null);

            $total = 0;
            $pks = array();

            foreach ($this->_results as $entity) {
                $pks[] = $entity->getPrimaryKey();

                $entityRels = $entity->{'get' . $this->_column->getDbFieldName()}();

                $aIds = array();

                $newEntityRels = array();

                foreach ($entityRels as $entityRel) {
                    $entityRelPK = $entityRel->{'get' . $relationProperty}()->getPrimaryKey();
                    $aIds[] = $entityRelPK;

                    if (in_array($entityRelPK, $value)) {
                        $newEntityRels[] = $entityRel;
                    }

                }

                foreach ($value as $val) {
                    if (in_array($val, $aIds)) {
                        continue;
                    }
                    $relModel = new $relationModel;
                    //TODO cambiar este setter que no mola nada
                    $relModel->{'set' . $relationProperty . 'Id'}($val);
                    $newEntityRels[] = $relModel;
                }


                $entity->{'set' . $this->_column->getDbFieldName()}($newEntityRels, true);

                $entity->saveRecursive();

                $total++;
            }

        } else {


            $total = 0;
            $pks = array();
            foreach ($this->_results as $entity) {
                $pks[] = $entity->getPrimaryKey();
                $entity->{$setter}($value)->save();

                $total++;
            }

            $this->_helper->log($total . ' models succesfully update > PK('. implode(',', $pks). ') > ' . $this->_column->getPublicName() . ' >> ' . $value);
        }

        if ($this->_item->getMessage()) {
            $message = $this->_item->getMessage();
            $message = str_replace('%total%', $total, $message);

        } else {
            $message = sprintf(
                $this->view->translate('(%s) %s successfully updated'),
                $total,
                $this->view->translate('Records')
            );
        }

        $data = array(
                'message' => $message,
                'title' => $this->_item->getTitle(),
                'buttons' =>  array(
                    $this->view->translate('Close') => array(
                        'recall' => false,
                        'reloadParent' => true
                    )
                )
        );

        $jsonResponse = new Klear_Model_DispatchResponse();
        $jsonResponse->setModule('klearMatrix');
        $jsonResponse->setPlugin('klearMatrixGenericDialog');
        $jsonResponse->addJsFile("/../klearMatrix/js/plugins/jquery.klearmatrix.genericdialog.js");
        $jsonResponse->setData($data);
        $jsonResponse->attachView($this->view);
    }
}
