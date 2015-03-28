<?php

class Model_Acl extends Zend_Acl {
    
    public function __construct(Zend_Auth $auth) {
        
        //---------------------------------------------------------------------
        // RESOURCES
        //---------------------------------------------------------------------
        
        $this->add(new Zend_Acl_Resource('default'))
                ->add(new Zend_Acl_Resource('default:home'), 'default')
                ->add(new Zend_Acl_Resource('default:error'), 'default');
        
        $this->add(new Zend_Acl_Resource('admin'))
                ->add(new Zend_Acl_Resource('admin:home'), 'admin')
                ->add(new Zend_Acl_Resource('admin:auth'), 'admin');
        
        //---------------------------------------------------------------------
        // ROLES
        //---------------------------------------------------------------------
        
        $this->addRole(new Zend_Acl_Role('guest'));
        $this->allow('guest', 'default:home')
                ->allow('guest', 'default:error', 'error');
        
        $this->addRole(new Zend_Acl_Role('admin'), 'guest');
        $this->deny('admin', 'admin:auth', 'login')
                ->allow('admin', 'admin:auth', 'logout')
                ->allow('admin', 'admin:home', 'index');
    }
}
