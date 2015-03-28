<?php

class Plugin_ModuleInit extends Zend_Controller_Plugin_Abstract {
    private $_acl = null;
    
    public function __construct(Zend_Acl $acl) {
        $this->_acl = $acl;
    }
    
    public function preDispatch(Zend_Controller_Request_Abstract $request) {
        if (stripos($request->getRequestUri(), 'ajax') == false) {
            $module = $request->getModuleName();
            $layout = Zend_Layout::getMvcInstance();
            $view = $layout->getView();
            $auth = Zend_Auth::getInstance();
            
            //-----------------------------------------------------------------
            // INIT LAYOUTS
            //-----------------------------------------------------------------

            if (file_exists($layout->getLayoutPath() . DIRECTORY_SEPARATOR . $module . '.phtml')) {
                $layout->setLayout($module);
            } else {
                $layout->setLayout('default');
            }
            
            //-----------------------------------------------------------------
            // INIT USER DETAILS
            //-----------------------------------------------------------------
            
            $layout->user = $auth->hasIdentity() ? (array) $auth->getStorage()->read() : false;
            
            //-----------------------------------------------------------------
            // INIT NAVIGATION
            //-----------------------------------------------------------------
            
//            $view->navigation(new Zend_Navigation(new Zend_Config_Xml(APPLICATION_PATH . '/configs/navigation.xml', 'navigation')))
//                    ->setAcl($this->_acl)
//                    ->setRole(Zend_Registry::get('role'));
//
//            $layout->navMain = $view->navigation()->findByResource((empty($module) ? 'default' : $module) . ':home');
//            $layout->navSide = $view->navigation()->findBy('module', $module);
//                        
//            $layout->version = Zend_Registry::get('cfg')->getVersion();

            //-----------------------------------------------------------------
            // INIT VIEW HELPERS
            //-----------------------------------------------------------------
            
            $view->setHelperPath(APPLICATION_PATH . '/helpers', '');
            
            $view->addHelperPath('Snow/View/Helper/', 'Snow_View_Helper');
            $view->addHelperPath('Zend/Dojo/View/Helper/', 'Zend_Dojo_View_Helper');

            $view->doctype('HTML4_STRICT');
            $view->headMeta()
                    ->appendHttpEquiv('Content-type', 'text/html;charset=utf-8')
                    ->appendName('viewport', 'initial-scale=1.0, user-scalable=no')
                    ->appendName('description', $view->translate('siteDescription'))
                    ->appendName('keywords', $view->translate('siteKeywords'));
            $view->headTitle()
                    ->setSeparator(' :: ')
                    ->headTitle($view->translate('webTitle'));
            $view->headLink()
                    ->appendStylesheet($view->baseUrl() . '/_css/styles.css')
                    ->headLink(array('rel' => 'shortcut icon', 'href' => $view->baseUrl() . '/_img/favicon.png', 'type' => 'image/icon'))
                    ->appendStylesheet('http://ajax.googleapis.com/ajax/libs/dojo/1.8.3/dojo/resources/dojo.css', 'screen');
                    //->appendStylesheet($view->baseUrl() . '/_js/dojo/resources/dojo.css', 'screen');
            $view->dojo() 
                    ->setDjConfig(array('parseOnLoad' => true, 'idDebug' => true, 'locale' => Zend_Registry::get('Zend_Locale')->toString()))
                    ->addStylesheetModule('dijit.themes.claro')
                    ->setLocalPath('http://ajax.googleapis.com/ajax/libs/dojo/1.8.3/dojo/dojo.js');
                    //->setLocalPath($view->baseUrl() . '/_js/dojo/dojo.js');
            $view->headScript()
                    ->appendFile($view->baseUrl() . '/_js/script.js');
        }
    }
}
