<?php
class KlearMatrix_Model_DispatchResponseFactory
{
    public static function build()
    {
//         Zend_Json::$useBuiltinEncoderDecoder = true;
        $jsonResponse = new Klear_Model_DispatchResponse();
        $jsonResponse->setModule('klearMatrix');
        $jsonResponse->addJsFile("/js/plugins/jquery.ui.form.js");
        $jsonResponse->addJsFile("/js/plugins/jquery.klearmatrix.template.helper.js");
        $jsonResponse->addJsFile("/js/translation/jquery.klearmatrix.translation.js");
        $jsonResponse->addJsFile("/js/plugins/jquery.klearmatrix.module.js");
        $jsonResponse->addCssFile("/css/klearMatrix.css");

        return $jsonResponse;
    }
}