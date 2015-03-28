<?php

class ErrorController extends Zend_Controller_Action {
    public function init() {
        if (stripos($this->getRequest()->getRequestUri(), 'ajax') == false) {
            $this->_helper->_layout->setLayout('dialog');
            $this->view->title = $this->view->translate('error');
        }
    }
    
    public function errorAction() {
        $errors = $this->_getParam('error_handler');

        switch ($errors->type) {
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
                // 404 error -- controller or action not found
                $this->getResponse()->setHttpResponseCode(404);
                $this->view->message = 'Page not found';
                $this->view->headMeta()->appendHttpEquiv('refresh', '5; URL=/');
                break;
            default:
                // application error 
                $this->getResponse()->setHttpResponseCode(500);
                $this->view->message = 'Application error';
                break;
        }

        $this->view->exception = $errors->exception;
        $this->view->request = $errors->request;
    }
}
