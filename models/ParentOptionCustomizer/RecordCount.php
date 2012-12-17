<?php
class KlearMatrix_Model_ParentOptionCustomizer_RecordCount extends KlearMatrix_Model_ParentOptionCustomizer_AbstractCount
{
    /**
     * @param string $sqlCondition
     */
    public function _init(Zend_Config $configuration)
    {
        $this->_cssClass = "recordCount";
    }

    /**
     * @return KlearMatrix_Model_ParentOptionCustomizer_Response
     */
    public function _parseWhereCondition($where)
    {
        return $where;
    }
}