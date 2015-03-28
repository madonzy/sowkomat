<?php

class Model_Form_Generate extends Model_Form {
    private $_data;
    
    public function init() {
        $this->setMethod(Zend_Form::METHOD_POST);
        $this->setName('generate');
        $this->setAction($this->getView()->url(array(
            'module' => 'default',
            'controller' => 'home',
            'action' => 'generate'
        ), NULL, TRUE));
    
        $this->_controls['book'] = $this->createElement('Select', 'book', array(
                    'label' => 'workbook',
                    'required' => true,
                    'style' => 'width: 225px'
                ))
                ->addFilter('StripTags')
                ->addValidator('Digits');
        
        $this->_controls['translation'] = $this->createElement('Select', 'translation', array(
                    'label' => 'translation',
                    'required' => true,
                    'style' => 'width: 225px'
                ))
                ->addFilter('StripTags')
                ->addValidator('Digits');
        
        $this->_controls['unit'] = $this->createElement('Select', 'unit', array(
                    'label' => 'unit',
                    'required' => true,
                    'style' => 'width: 225px'
                ))
                ->addFilter('StripTags')
                ->addValidator('Digits');
        
        $this->_controls['difficulty'] = $this->createElement('Select', 'difficulty', array(
                    'label' => 'difficulty',
                    'required' => true,
                    'style' => 'width: 225px'
                ))
                ->addFilter('StripTags')
                ->addValidator('Digits')
                ->addValidator('NotEmpty');
        
//        $this->_controls['count'] = $this->createElement('NumberSpinner', 'count', array(
//                    'label' => 'count',
//                    'style' => 'width: 100px; margin-top: 2px;'
//                ))
//                ->setDijitParams(array(
//                    'constraints' => array(
//                        'min' => 1
//                    )
//                ))
//                ->addFilter('StripTags')
//                ->addValidator('Digits')
//                ->setValue(1);
        
//        $this->_controls['output'] = $this->createElement('CheckBox', 'output', array(
//                    'label' => 'output',
//                    'checkedValue' => 1,
//                    'uncheckedValue' => 0,
//                    'style' => 'margin-right: -1px;'
//                ))
//                ->addFilter('StripTags')
//                ->addValidator('Digits');
        
        
        $this->_controls['submit'] = $this->createElement('SubmitButton', 'submit', array('label' => 'generateButton'))
                ->setAttrib('class', 'action');
        $this->_controls['pdfSubmit'] = $this->createElement('SubmitButton', 'pdfSubmit', array('label' => 'pdfButton'))
                ->setAttrib('class', 'action');
        
        $this->addElements($this->_controls);
    }
    
    public function setActionView(array $data) {
        $this->_data = $data;
        
        $this->setElementMultiOptions('book', array_combine(array_keys($this->_data), array_keys($this->_data)));
        $this->setElementMultiOptions('difficulty', array(
            '0'=>'mixed', 1 => 'beginner', 2 => 'intermediate', 3 => 'advanced'
        ),true);
        
        $this->setDecorators(array(array('ViewScript', array('viewScript' => 'home/forms/' . $this->getName() . '.phtml'))));
        
        return $this;
    }
    
    public function getResources() {
        return json_encode($this->_data);
    }
}