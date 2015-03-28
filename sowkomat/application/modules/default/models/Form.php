<?php

class Model_Form extends Zend_Dojo_Form {
    protected $_controls = array();
    
    public function getElement($name) {
        return $this->_controls[$name];
    }
    
    public function setElementMultiOptions($element, array $options, $strict = FALSE) {
        if (empty($strict)) {
            array_unshift($options, array(
                'key' => 0,
                'value' => ''
            ));
        }
        
        $this->getElement($element)->setMultiOptions($options);
    }
    
    public function renderErrors($element) {
        $errors = $element->getMessages();
        $html = null;
        
        if (!empty($errors)) {
            $html = '<ul class="errors">';
            foreach ($errors as $error) {
                $html .= '<li>' . $error . '</li>';
            }
            $html .= '</ul>';
        }
        
        return $html;
    }
    
    public function getElementParams($name) {
        $params = $this->_controls[$name]->getDijitParams();
        if (stristr($this->_controls[$name]->getType(), 'CheckBox') === false) {
            $params['required'] = $this->_controls[$name]->isRequired();
        }
        
        return $params;
    } 
    
    public function getElementAttribs($name) {
        $attribs = $this->_elements[$name]->getAttribs();
        $attribs['id'] = $this->_controls[$name]->getId();
        unset($attribs['dijitParams']);
        unset($attribs['helper']);
        
        return $attribs;
    }
    
    public function getElementOptions($name) {
        $attribs = $this->_elements[$name]->getAttribs();
        
        return $attribs['options'];
    }
}
