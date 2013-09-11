<?php
class KlearMatrix_Controller_Helper_HookedDataForScreen extends Zend_Controller_Action_Helper_Abstract
{

    /**
     * @var KlearMatrix_Model_Screen $screen
     */
    protected $_screen;

    protected function _execHook($hookName, $args)
    {
        if ($this->_screen->getHook($hookName)) {

            $hook = $this->_screen->getHook($hookName);
            $helper = $this->getActionController()->getHelper($hook->helper);
            return call_user_func_array(array($helper,$hook->action), $args);
        }
        return false;
    }

    protected function _execJsArray(KlearMatrix_Model_ColumnCollection $columns)
    {
        $retValue = $this->_execHook('addJsArray', array($columns));
        if (false !== $retValue) {
            return $retValue;
        }
        return $columns->getColsJsArray();
    }

    protected function _execCssArray(KlearMatrix_Model_ColumnCollection $columns)
    {
        $retValue = $this->_execHook('addCssArray', array($columns));
        if (false !== $retValue) {
            return $retValue;
        }
        return $columns->getColsCssArray();
    }

    protected function _execSetData(KlearMatrix_Model_MatrixResponse $data)
    {
        $retValue = $this->_execHook('setData', array($data, $data->getParentData()));
        if (false !== $retValue) {
            return $retValue;
        }

        return $data->toArray();
    }

    protected function _execAttachView(Zend_View $view)
    {

        $retValue = $this->_execHook('attachView', array($view));
        if (false !== $retValue) {
            return $retValue;
        }
        return $view;
    }

    public function HookedDataForScreen(KlearMatrix_Model_Screen $screen, $hookName, $argument)
    {
        $this->_screen = $screen;

        if ($hookName == 'addJsArray') {
            return $this->_execJsArray($argument);
        }

        if ($hookName == 'addCssArray') {
            return $this->_execCssArray($argument);
        }

        if ($hookName == 'setData') {
            return $this->_execSetData($argument);
        }

        if ($hookName == 'attachView') {
            return $this->_execAttachView($argument);
        }

        throw new Klear_Exception_Default('not a valid asset type especified');
    }


    public function direct(KlearMatrix_Model_Screen $screen, $hookName, $argument)
    {
        return $this->HookedDataForScreen($screen, $hookName, $argument);
    }
}