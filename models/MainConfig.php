<?php

/**
 * Clase que lee la configuración de fichero para este módulo y resuelve la ruta
* @author jabi
*
*/
class KlearMatrix_Model_MainConfig
{
    public const module = 'klearMatrix';

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
     * La configuración debe recibir la ruta de ficheros de configuración,
     * para cargar configuraciones auxiliares de cada módulo
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
            $msgString = "Invalid item specified [" . $itemName . "]";
            Throw new Zend_Exception($msgString);
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
                    $_data; //Avoid PMD UnusedLocalVariable warning
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

    protected function _getOptionConfig($type, $identifier)
    {

        if (!$this->_config->exists($type . "->" . $identifier)) {
            Throw new Zend_Exception("Configuration for selected option [".$identifier.", type: ".$type."] not found");
        }
        return $this->_config->getRaw()->{$type}->{$identifier};
    }

    public function getScreenConfig($screen)
    {
        return $this->_getOptionConfig('screens', $screen);
    }

    public function getDialogConfig($dialog)
    {
        return $this->_getOptionConfig('dialogs', $dialog);
    }

    public function getCommandConfig($command)
    {
        return $this->_getOptionConfig('commands', $command);
    }

    public function getLinkConfig($link)
    {
        return $this->_getOptionConfig('links', $link);
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
