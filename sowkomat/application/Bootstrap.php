<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap {
    private $_acl = null;

    protected function _initAutoload() {
        $modelLoader = new Zend_Application_Module_Autoloader(array(
            'namespace' => '',
            'basePath' => APPLICATION_PATH . '/modules/default'));
        
        return $modelLoader;
    }
    
    protected function _initConfig() {
        $cfg = new Snow_Config(APPLICATION_PATH . '/configs/config.xml');
        Zend_Registry::set('cfg', $cfg);
        
        $alt = $cfg->getAutoLogoutTime() * 60;
        $session = new Zend_Session_Namespace('Zend_Auth');
        $session->setExpirationSeconds($alt);
        $session = new Zend_Session_Namespace(Zend_Registry::get('cfg')->getNamespace());
        $session->setExpirationSeconds($alt);
        
        if (($smtp = $this->getOption('smtp')) && !empty($smtp) && ($email = $this->getOption('email')) && !empty($email)) {
            Zend_Mail::setDefaultTransport(new Zend_Mail_Transport_Smtp($smtp, $email));
        }
    }
    
    protected function _initLocale() {
        $locale = new Zend_Locale;
        $locale->setLocale('pl');
        Zend_Registry::set('Zend_Locale', $locale);
        
        $translate = new Zend_Translate('Snow_Translate_Adapter_SnowXML', APPLICATION_PATH . '/configs/lang/' . 'pl.xml', 'pl');
        Zend_Registry::set('Zend_Translate', $translate);
        
        $session = new Zend_Session_Namespace(Zend_Registry::get('cfg')->getNamespace());
        $session->lang = 'pl';
    }
    
    protected function _initCache() {
        $cache = Zend_Cache::factory('Core', 'File', array(
            'lifetime' => 1,
            'automatic_serialization' => true,
            'caching' => true
        ), array(
            'cache_dir' => APPLICATION_PATH . '/../cache/'
        ));

        Zend_Registry::set('cache', $cache);
        Zend_Locale::setCache($cache);
    }

    protected function _initDatabase() {
        $this->bootstrapDb();
    }
    
    protected function _initPlugins() {
        include_once APPLICATION_PATH . '/../library/Snow/Functions.php';
        
        if (Zend_Auth::getInstance()->hasIdentity()) {
            Zend_Registry::set('role', Zend_Auth::getInstance()->getStorage()->read()->role);
        } else {
            Zend_Registry::set('role', 'guest');
        }

        $auth = Zend_Auth::getInstance();
        $this->_acl = new Model_Acl($auth);

        $fc = Zend_Controller_Front::getInstance();
        $fc->setDefaultModule('default')->setDefaultControllerName('home')->setDefaultAction('index');

        $fc->registerPlugin(new Plugin_AccessCheck($this->_acl, $auth));
        $fc->registerPlugin(new Plugin_ModuleInit($this->_acl));

        $this->bootstrap('layout');
    }
}

