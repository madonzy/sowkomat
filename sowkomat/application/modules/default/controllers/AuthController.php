<?php

class AuthController extends Zend_Controller_Action {
    public function init() {
        $this->_helper->_layout->setLayout('dialog');
    }
    
    public function loginAction() {
        //$form = new Form_LoginForm();
        
        if ($this->getRequest()->isPost()) {
            if ($form->isValid($this->_request->getPost())) {
                $authAdapter = $this->getAuthAdapter();

                $username = $form->getValue('username');
                $password = $form->getValue('password');

                $authAdapter->setIdentity($username)
                        ->setCredential($password);

                $auth = Zend_Auth::getInstance();
                $result = $auth->authenticate($authAdapter);

                if ($result->isValid()) {
                    $identity = $authAdapter->getResultRowObject();

                    $authStorage = $auth->getStorage();
                    $authStorage->write($identity);
                    
                    $this->_helper->FlashMessenger(array('action' => $this->view->translate('loggedIn')));
                    $this->_redirect(str_replace($this->view->baseUrl(), '', $this->getRequest()->getRequestUri()));
                } else {
                    $this->_helper->FlashMessenger(array('warn' => $this->view->translate('invalidCredentials')));
                }
            }
        }

        //$this->view->form = $form;
    }
    
    public function accessAction() {
        $session = new Zend_Session_Namespace(Zend_Registry::get('cfg')->getNamespace());
        $this->view->headMeta()->appendHttpEquiv('refresh', '5; URL=' . (empty($session->returnUri) ? '/' : $session->returnUri));
        $this->view->title = $this->view->translate('permissions');
    }
    
    public function ajaxAction() {
        Zend_Layout::resetMvcInstance();
        $this->view->output = $this->view->translate('accessDenied');
    }

    public function logoutAction() {
        Zend_Layout::resetMvcInstance();
        Zend_Auth::getInstance()->clearIdentity();
        
        Zend_Session::namespaceUnset('Zend_Auth');
        Zend_Session::namespaceUnset(Zend_Registry::get('cfg')->getNamespace());
        
        $this->_helper->FlashMessenger(array('action' => $this->view->translate('loggedOut')));
        $this->_redirect('/');
    }

    private function getAuthAdapter() {
        $authAdapter = new Zend_Auth_Adapter_DbTable(Zend_Db_Table::getDefaultAdapter());
        
        $authAdapter->setTableName(Model_DbTable::PREFIX . '_users')
                ->setIdentityColumn('username')
                ->setCredentialColumn('password')
                ->setCredentialTreatment('SHA1(MD5(?))');

        return $authAdapter;
    }
}
