<?php
/**
 * Clase que devuelve la ruta al forward de _dispatch en base a la configuración a los parámetros de request
 * @author jabi
 *
 */
class KlearMatrix_Model_RouteDispatcher
{
    public const module = 'klearMatrix';

    /**
     * @var KlearMatrix_Model_Screen
     */
    protected $_screen;

    /**
     * @var KlearMatrix_Model_Dialog
     */
    protected $_dialog;

    /**
     * @var KlearMatrix_Model_Command
     */
    protected $_command;

    /**
     * @var string
     */
    protected $_screenName;
    protected $_dialogName;
    protected $_commandName;

    protected $_selectedConfig;

    /**
     * @var string
     * Que tipo de request = dialog | *screen
     */
    protected $_typeName;

    /**
     * @var unknown_type
     * Acción por defecto a ejecutar
     * Si existe el campo _actionName, cargado desde getParam, éste prevalecerá.
     */
    protected $_action = 'index';

    protected $_controller;

    protected $_mapper;

    protected $_params = array();

    /**
     * @var KlearMatrix_Model_MainConfig
     */
    protected $_config;

    public function getModuleName()
    {
        if (isset($this->_selectedConfig->module)) {
            return $this->_selectedConfig->module;
        }

        return self::module;
    }

    public function setConfig(KlearMatrix_Model_MainConfig $config)
    {
        $this->_config = $config;
        $this->_typeName  = $this->_config->getDefaultType();
    }

    public function getConfig()
    {
        return $this->_config;
    }

    public function setParams(array $params)
    {
        foreach ($params as $param => $value) {

            switch($param) {
                case 'screen':
                case 'dialog':
                case 'command':
                case 'type':
                    $attrName = "_" . $param . "Name";
                    $this->{$attrName} = $value;
                    break;
                default:
                    $this->_params[$param] = $value;
                    break;
            }
        }
    }

    public function getParam($param, $required = true)
    {
        if (isset($this->_params[$param])) {
            return $this->_params[$param];
        }

        if (false === $required) {
            return false;
        }
        throw new Zend_Exception('Parámetro [' . $param . '] no encontrado.', 9999);
    }

    public function getParams()
    {
        return $this->_params;
    }

    public function getActionName()
    {
        return $this->_action;
    }

    public function getControllerName()
    {
        return $this->_controller;
    }

    public function getCurrentScreen()
    {

        if (null === $this->_screen) {

            $screen = new KlearMatrix_Model_Screen();
            $this->_screen = $this->_initResponseItem($screen, $this->_screenName);
        }
        return $this->_screen;
    }

    public function getCurrentDialog()
    {
        if (null === $this->_dialog) {

            $dialog = new KlearMatrix_Model_Dialog();
            $this->_dialog = $this->_initResponseItem($dialog, $this->_dialogName);
        }
        return $this->_dialog;
    }

    public function loadScreen($screenName)
    {
        $config = $this->_config->getScreenConfig($screenName);
        return $this->_initResponseItem(new KlearMatrix_Model_Screen(), $screenName, $config);
    }

    public function loadDialog($dialogName)
    {
        $config = $this->_config->getDialogConfig($dialogName);
        return $this->_initResponseItem(new KlearMatrix_Model_Dialog(), $dialogName, $config);
    }

    public function getCurrentCommand()
    {
        if (null === $this->_command) {

            $command = new KlearMatrix_Model_Command();
            $this->_command = $this->_initResponseItem($command, $this->_commandName);
        }
        return $this->_command;
    }

    protected function _initResponseItem(KlearMatrix_Model_ResponseItem $responseItem, $name, $config = null)
    {
        if (is_null($config)) {

            $config = $this->_selectedConfig;
        }

        $responseItem->setRouteDispatcher($this)
                     ->setName($name)
                     ->setConfig($config);

        return $responseItem;
    }

    /**
     * @return KlearMatrix_Model_ResponseItem
     */
    public function getCurrentItem()
    {
        switch($this->_typeName) {
            case "dialog":
                return $this->getCurrentDialog();
            case "command":
                return $this->getCurrentCommand();
            default:
                return $this->getCurrentScreen();
        }
    }

    public function getCurrentType()
    {
        return  $this->_typeName;
    }

    protected function _resolveCurrentItem()
    {

        switch($this->_typeName) {
            case "command":
                return $this->_resolveCurrentItemCommand();
            case "dialog":
                return $this->_resolveCurrentItemDialog();
            default:
                return $this->_resolveCurrentItemScreen();
        }

    }

    protected function _resolveCurrentItemCommand()
    {
        if ($this->_commandName == null) {
            $this->_commandName = $this->_config->getDefaultCommand();
        }
        return $this;
    }

    protected function _resolveCurrentItemScreen()
    {
        if ($this->_screenName == null) {
            $this->_screenName = $this->_config->getDefaultScreen();
        }
        return $this;
    }

    protected function _resolveCurrentItemDialog()
    {

        if ($this->_dialogName == null) {
            $this->_dialogName = $this->_config->getDefaultDialog();
        }
        return $this;
    }

    public function _resolveCurrentConfig()
    {
        // Aquí resolvemos a que métodos de MainConfig llamar:
        // getScreenConfig | getDialogConfig
        // a partir del atributo de entidad que corresponda según el type
        // _screenName | _dialogName | _commandName

        $configGetter = "get" . ucfirst($this->_typeName) . "Config";
        $attrName = "_" . $this->_typeName . "Name";

        if ($this->_selectedConfig == null) {
            $this->_selectedConfig = $this->_config->{$configGetter}($this->{$attrName});
        }
        return $this;
    }

    public function _resolveCurrentProperty($name, $required)
    {
        if (!isset($this->_selectedConfig->{$name})) {
            if ($required) {
                throw new Zend_Exception($name . " controller not in selected config");
            } else {

                return $this;
            }
        }

        $propName = '_' . $name;

        $this->{$propName} = $this->_selectedConfig->{$name};
        return $this;
    }

    protected function _resolveAction()
    {
        if (isset($this->_params['execute'])) {
            $this->_action = $this->_params['execute'];

            return $this;
        }

        // Si no hemos recibido por parámetro action
        // Cogeremos la del fichero de configuración
        // o 'index' por defecto
        $this->_resolveCurrentProperty('action', false);
        return $this;
    }

    public function getCurrentItemName()
    {
        switch($this->_typeName) {
            case "dialog":
                return $this->_dialogName;
            case "command":
                return $this->_commandName;
            default:
                return $this->_screenName;
        }
    }

    public function resolveDispatch()
    {
        $this
            ->_resolveCurrentItem()
            ->_resolveCurrentConfig()
            ->_resolveCurrentProperty("controller", true)
            ->_resolveAction();

    }
}
