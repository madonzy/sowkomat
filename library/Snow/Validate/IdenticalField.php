<?php

class Snow_Validate_IdenticalField extends Zend_Validate_Abstract {
    const NOT_MATCH = 'notMatch';
    const MISSING_FIELD_NAME = 'missingFieldName';
    const INVALID_FIELD_NAME = 'invalidFieldName';

    protected $_messageTemplates = array(
        self::MISSING_FIELD_NAME  =>
            'DEVELOPMENT ERROR: Field name to match against was not provided.',
        self::INVALID_FIELD_NAME  =>
            'DEVELOPMENT ERROR: The field "%fieldName%" was not provided to match against.',
        self::NOT_MATCH =>
            'Does not match %fieldTitle%.'
    );
    protected $_messageVariables = array(
        'fieldName' => '_fieldName',
        'fieldTitle' => '_fieldTitle'
    );
    protected $_fieldName;
    protected $_fieldTitle;

    public function __construct($fieldName, $fieldTitle = null) {
        $this->setFieldName($fieldName);
        $this->setFieldTitle($fieldTitle);
    }

    public function getFieldName() {
        return $this->_fieldName;
    }

    public function setFieldName($fieldName) {
        $this->_fieldName = $fieldName;
        
        return $this;
    }

    public function getFieldTitle() {
        return $this->_fieldTitle;
    }

    public function setFieldTitle($fieldTitle = null) {
        $this->_fieldTitle = $fieldTitle ? $fieldTitle : $this->_fieldName;
        
        return $this;
    }

    public function isValid($value, $context = null) {
        $this->_setValue($value);
        $field = $this->getFieldName();

        if (empty($field)) {
            $this->_error(self::MISSING_FIELD_NAME);
            return false;
        } elseif (!isset($context[$field])) {
            $this->_error(self::INVALID_FIELD_NAME);
            return false;
        } elseif (is_array($context)) {
            if ($value == $context[$field]) {
                return true;
            }
        } elseif (is_string($context) && ($value == $context)) {
            return true;
        }
        
        $this->_error(self::NOT_MATCH);
        return false;
    }
}