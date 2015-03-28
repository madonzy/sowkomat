<?php

class Snow_View_Helper_ButtonAcl extends Zend_Dojo_View_Helper_Button {    
    public function buttonAcl($label, $module, $controller, $action, $urlParams = null, $attribs = null, $dojoParams = null, $dijit = null) {
        if (Zend_Controller_Front::getInstance()->getPlugin('Plugin_AccessCheck')->isAllowed($module, $controller, $action)) {
            $urlArray = array_merge(empty($urlParams) ? array() : $urlParams, array('module' => $module, 'controller' => $controller, 'action' => $action));

            if (empty($attribs)) {
                $attribs = array();
            }
            
            $attribs['name'] = $module . '-' . $controller . '-' . $action;
            if (!array_key_exists('onclick', $attribs)) {
                $attribs['onclick'] = 'location = \'' . $this->view->url($urlArray, null, true) . '\'';
            }

            if (!array_key_exists('id', $attribs)) {
                $attribs['id'] = $attribs['name'];
            }
            
            $attribs = $this->_prepareDijit($attribs, empty($dojoParams) ? array() : $dojoParams, 'element', $dijit);
            
            return $this->view->formButton($attribs['name'], $this->view->translate($label), $attribs);
        }
    }
}