<?php
class KlearMatrix_Model_Field_Select_Decorator_Autocomplete extends KlearMatrix_Model_Field_DecoratorAbstract
{
    const DYNAMIC_DATA_LOADING = true;

    protected function _init()
    {
        $this->_helper->ContextSwitch()
                      ->clearContexts();

        $this->_helper->viewRenderer->setNoRender(true);
    }

    public function run()
    {
        $mapperName = $this->_request->getParam("mappername");
        $mapper = new $mapperName;

        $searchTerm = $this->_request->getParam("term");
        $labelField = $this->_request->getParam("label");
        $pkField = $this->_request->getParam("id");

        if ( $this->_request->getParam("reverse") ) {

            $results = array($mapper->find($this->_request->getParam("value")));

        } else {

            $results = $mapper->fetchList(array($labelField . ' like :term', array(
                            ':term' => '%' . $searchTerm . '%'
                       )));
        }

        $options = array();
        $labelGetter = 'get' . ucfirst($labelField);

        foreach ($results as $tienda) {

            $options[] = array(
                'id' => $tienda->getPrimaryKey(),
                'label' => $tienda->$labelGetter(),
                'value' => $tienda->$labelGetter(),
            );
        }

        echo json_encode($options);
    }
}