<?php
interface KlearMatrix_Model_Interfaces_ParentOptionCustomizer
{
    public function __construct(Zend_Config $configuration);

    public function setOption (KlearMatrix_Model_AbstractOption $option);

    /**
     * @return KlearMatrix_Model_ParentOptionCustomizer_Response
     */
    public function customize($parentModel);
}