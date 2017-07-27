<?php

class HarvardKey_DebugController extends Omeka_Controller_AbstractActionController
{
    public function indexAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();
        $value = $request->getCookie("harvardkeyjwt");
        $response = $this->getResponse();
        if(is_null($value)) {
            $response->setBody("No cookie!");
        } else {
            $response->setBody($value);
        }
    }
}