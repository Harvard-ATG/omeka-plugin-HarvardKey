<?php

class HarvardKey_DebugController extends Omeka_Controller_AbstractActionController
{
    public function indexAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();
        $response = $this->getResponse();
        debug("request = " . var_export($request,1));
        debug("cookies = " . var_export($_COOKIE,1));
        debug("current_user = ".var_export($this->getCurrentUser(), 1));
        $response->setBody("debug action");
    }
}