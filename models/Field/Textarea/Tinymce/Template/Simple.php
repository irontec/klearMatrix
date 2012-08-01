<?php

class KlearMatrix_Model_Field_Textarea_Tinymce_Template_Simple extends KlearMatrix_Model_Field_Textarea_Tinymce_Template_Abstract
{

    protected $_jsController = "/js/plugins/tinymce/templates/jquery.tinymce.simple.js";
    protected $_jsControllerClass = "Simple";
    
    public function __contruct()
    {
    
    }
    
    protected function _loadBars()
    {
        $this->_bars[0] = array(
                'bold',
                'italic',
                'underline',
                'strikethrough',
                '|',
                'bullist',
                'numlist',
                'blockquote',
                '|',
                'justifyleft',
                'justifycenter',
                'justifyright',
                '|',
                'link',
                'unlink',
                'pagebreak',
                'fullscreen'
        );
    }

}

//EOF