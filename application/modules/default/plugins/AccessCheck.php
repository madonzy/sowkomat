<?php

class Plugin_AccessCheck extends Zend_Controller_Plugin_Abstract {
    private $_acl = null;
    private $_auth = null;
    
    public function __construct(Zend_Acl $acl, Zend_Auth $auth) {
        $this->_acl = $acl;
        $this->_auth = $auth;
    }
    
    public function preDispatch(Zend_Controller_Request_Abstract $request) {
    	$module = $request->getModuleName();
        $controller = $request->getControllerName();
        $action = $request->getActionName();
        
        $session = new Zend_Session_Namespace(Zend_Registry::get('cfg')->getNamespace());
        $requestUri = $request->getRequestUri();

        if (!$this->_acl->isAllowed(Zend_Registry::get('role'), $module . ':' . $controller, $action)) {
            if (stripos($requestUri, 'ajax') !== false) {
                $request->setModuleName('default')
                        ->setControllerName('auth')
                        ->setActionName('ajax');
            } elseif (!$this->_auth->hasIdentity()) {
                $request->setModuleName('default')
                        ->setControllerName('auth')
                        ->setActionName('login');
            } elseif ($module == 'default' && $controller == 'auth' && $action == 'login') {
                $session->returnUri = '/';
            } else {
                $request->setModuleName('default')
                        ->setControllerName('auth')
                        ->setActionName('access');
            }
        } elseif (stripos($requestUri, 'ajax') === false) {
            $session->returnUri = $requestUri;
        }
    }
    
    public function isAllowed($module, $controller, $action) {
        return $this->_acl->isAllowed(Zend_Registry::get('role'), $module . ':' . $controller, $action);
    }
}
