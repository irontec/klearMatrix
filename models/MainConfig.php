<?php

/**
 * Clase que lee la configuración de fichero para este módulo y resuelve la ruta
* @author jabi
*
*/
class KlearMatrix_Model_MainConfig
{

    const module = 'klearMatrix';

    protected $_config;
    protected $_configPath;

    protected $_types = array("screen", "dialog", "command");

    static public function getModuleName()
    {
        return self::module;
    }


    public function setConfig(Zend_Config $config)
    {
        $this->_config = new Klear_Model_ConfigParser;
        $this->_config->setConfig($config);
        return $this;
    }

    /**
     * La configuración debe recibir la ruta de ficheros de configuración, para cargar configuraciones auxiliares de cada módulo
     * @param string $path
     */
    public function setConfigPath($path)
    {
        $this->_configPath = $path;
        return $this;
    }

    public function getConfigPath()
    {
        return $this->_configPath;
    }

    public function getDefaultType()
    {
        $defaultType = $this->_types[0]; // screen;

        foreach ($this->_types as $itemName) {

            if ($this->_config->exists("main->" . "default" . ucfirst($itemName))) {
                return $itemName;
            }

        }
        return $defaultType;
    }

    protected function _getDefaultItem($itemName)
    {

        $itemName = trim($itemName);

        if (!in_array($itemName, $this->_types)) {
            Throw new Zend_Exception("Error accediendo a la configuración. No se ha especificado un tipo de enrutado válido.");
        }

        $attrName = "_default" . ucfirst($itemName);
        $configItemName = "default" . ucfirst($itemName);
        $itemConfigWrapper = $itemName . "s";

        if ($this->_config->exists("main->" . $configItemName)) {

            $this->{$attrName} = $this->_config->getRaw()->main->$configItemName;

        } else {

            // Si no hay una defaultScreen, devolvemos la primera definida en el fichero de configuración.
            if ($this->_config->exists($itemConfigWrapper)) {
                foreach ($this->_config->getRaw()->{$itemConfigWrapper} as $_name => $_data) {
                    $this->{$attrName} = $_name;
                    break;
                }

            } else {
                Throw new Zend_Exception("Default item [".$itemName."] not found");
            }
        }

        return $this->{$attrName};
    }

    public function getDefaultScreen()
    {
        return $this->_getDefaultItem("screen");
    }

    public function getDefaultDialog()
    {
        return $this->_getDefaultItem("dialog");
    }

    public function getDefaultCommand()
    {
        return $this->_getDefaultItem("command");
    }


    public function getScreenConfig($screen)
    {


        if (!$this->_config->exists("screens->" . $screen)) {
            Throw new Zend_Exception("Configuration for selected screen [".$screen."] not found");
        }

        return $this->_config->getRaw()->screens->{$screen};
    }


    public function getDialogConfig($dialog)
    {

        if (!$this->_config->exists("dialogs->" . $dialog)) {
            Throw new Zend_Exception("Configuration for selected dialog not found");
        }

        return $this->_config->getRaw()->dialogs->{$dialog};
    }

    public function getCommandConfig($command)
    {

        if (!$this->_config->exists("commands->" . $command)) {
            Throw new Zend_Exception("Configuration for selected command not found");
        }

        return $this->_config->getRaw()->commands->{$command};
    }


    protected function _parseSelectedConfig()
    {

        $this->_controller = $this->_selectedConfig->controller;

        $propertiesToMap = array("action","mapper");

        foreach ($propertiesToMap as $prop) {
            if (isset($this->_selectedConfig->{$prop})) {
                $propName = '_' . $prop;
                $this->{$propName} = $this->_selectedConfig->{$prop};

            }
        }
    }


    /**
     * @return KlearMatrix_Model_RouteDispatcher
     */
    public function buildRouterConfig()
    {
        $router = new KlearMatrix_Model_RouteDispatcher();
        $router->setConfig($this);
        return $router;
    }




}