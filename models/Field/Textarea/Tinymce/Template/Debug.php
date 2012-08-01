<?php

class KlearMatrix_Model_Field_Textarea_Tinymce_Template_Debug extends KlearMatrix_Model_Field_Textarea_Tinymce_Template_Abstract
{

    protected $_jsController = "/js/plugins/tinymce/templates/jquery.tinymce.debug.js";
    protected $_jsControllerClass = "Debug";
    
    public function __contruct()
    {
    
    }
    
    protected function _loadBars()
    {
        $this->_bars[0] = array(
                'save',
                'newdocument',
                '|',
                'bold',
                'italic',
                'underline',
                'strikethrough',
                '|',
                'justifyleft',
                'justifycenter',
                'justifyright',
                'justifyfull',
                'styleselect',
                'formatselect',
                'fontselect',
                'fontsizeselect');
        $this->_bars[1] = array(
                'cut',
                'copy',
                'paste',
                'pastetext',
                'pasteword',
                '|',
                'search',
                'replace',
                '|',
                'bullist',
                'numlist',
                '|',
                'outdent',
                'indent',
                'blockquote',
                '|',
                'undo',
                'redo',
                '|',
                'link',
                'unlink',
                'anchor',
                'image',
                'cleanup',
                'help',
                'code',
                '|',
                'insertdate',
                'inserttime',
                'preview',
                '|',
                'forecolor',
                'backcolor');
        $this->_bars[2] = array(
                'tablecontrols',
                '|',
                'hr',
                'removeformat',
                'visualaid',
                '|',
                'sub',
                'sup',
                '|',
                'charmap',
                'emotions',
                'iespell',
                'media',
                'advhr',
                '|',
                'print',
                '|',
                'ltr',
                'rtl',
                '|',
                'fullscreen');
        $this->_bars[3] = array(
                'insertlayer',
                'moveforward',
                'movebackward',
                'absolute',
                '|',
                'styleprops',
                '|',
                'cite',
                'abbr',
                'acronym',
                'del',
                'ins',
                'attribs',
                '|',
                'visualchars',
                'nonbreaking',
                'template',
                'pagebreak');
    }

}

//EOF