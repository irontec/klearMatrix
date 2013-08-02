<?php
class KlearMatrix_Model_Field_Html5_Email extends KlearMatrix_Model_Field_Html5_Abstract
{

    public function init()
    {
        $this->_settings = array(
                "type" => "text",
                "class" => "h5-email"
                );
    }

    public function filterValue($value)
    {

        if ($this->_config->getProperty("checkEmail")) {

            $check = (bool)$this->_config->getProperty("checkEmail");

            if ($check && $value != "") {

                list(, $host) = explode('@', $value);

                $mxhosts = array();
                if (false !== getmxrr($host, $mxhosts) || gethostbyname($host) !== $host) {
                    return $value;
                } else {

                    $translator = Zend_Registry::get(Klear_Plugin_Translator::DEFAULT_REGISTRY_KEY);
                    $invalidMail = $this->_config->getProperty("invalidMailError");

                    if ($invalidMail) {

                        throw new Klear_Exception_Default($invalidMail);

                    } else {

                        throw new Klear_Exception_Default($translator->translate("Email no válido."));

                    }
                }
            }
        }

        return $value;

    }

}