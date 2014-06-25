<?php
class KlearMatrix_Model_Field_Ip extends KlearMatrix_Model_Field_Abstract
{

    protected function _init()
    {
        //Source: http://jsfiddle.net/AJEzQ/
        $this->_propertyMaster['pattern'] = '/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$|^(([a-zA-Z]|[a-zA-Z][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z]|[A-Za-z][A-Za-z0-9\-]*[A-Za-z0-9])$|^\s*((([0-9A-Fa-f]{1,4}:){7}([0-9A-Fa-f]{1,4}|:))|(([0-9A-Fa-f]{1,4}:){6}(:[0-9A-Fa-f]{1,4}|((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){5}(((:[0-9A-Fa-f]{1,4}){1,2})|:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){4}(((:[0-9A-Fa-f]{1,4}){1,3})|((:[0-9A-Fa-f]{1,4})?:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){3}(((:[0-9A-Fa-f]{1,4}){1,4})|((:[0-9A-Fa-f]{1,4}){0,2}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){2}(((:[0-9A-Fa-f]{1,4}){1,5})|((:[0-9A-Fa-f]{1,4}){0,3}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){1}(((:[0-9A-Fa-f]{1,4}){1,6})|((:[0-9A-Fa-f]{1,4}){0,4}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(:(((:[0-9A-Fa-f]{1,4}){1,7})|((:[0-9A-Fa-f]{1,4}){0,5}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:)))(%.+)?\s*$/';
        $this->_properties['nullIfEmpty']= false;
    }



    public function getCustomSearchCondition($values, $searchOps)
    {


        $searchField = $this->_column->getDbFieldName();
        $_fieldValues = $vals = array();
        $cont = 0;



        foreach ($values as $idx => $_val) {
            $filteredVal = $this->filterValue($_val);

            $op = '=';

            if ($this->_column->namedParamsAreSupported()) {
                $template = ':' . $searchField . $cont;
                $cont++;
                $vals[] = $searchField .' '.$op.' '. $template;
                $_fieldValues[$template] = $filteredVal;

            } else {

                $vals[] = $searchField . ' ' . $op .' ?';
                $_fieldValues[] = $this->filterValue($_val);
            }



        }

        // Campos datetime / date / time se "conjugan" con and >> Antes de las 12 y despues de las 10
        return array(
                '(' . implode(' and ', $vals). ')',
                $_fieldValues
        );
    }


    /*
     * Filtra (y adecua) el valor del campo antes del setter
    *
    */
    public function filterValue($value)
    {
        set_error_handler(array($this, '_ParseAddrErrorHandler'));


        if (!empty($value)) {
            $addr = inet_pton($value);
            restore_error_handler();

            return $addr;
        }
        return NULL;
    }


    public function prepareValue($value)
    {
        set_error_handler(array($this, '_ParseAddrErrorHandler'));
        if (!empty($value)) {
            $addr = inet_ntop($value);
            restore_error_handler();

            return $addr;
        }

        return '';
    }

    protected function _ParseAddrErrorHandler($errno, $errstr, $errfile, $errline)
    {
        throw new Exception(_("Invalid IP Address"));

    }
}
