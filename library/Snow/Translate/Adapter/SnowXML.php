<?php

class Snow_Translate_Adapter_SnowXML extends Zend_Translate_Adapter {
    // Internal variables
    private $_file        = false;
    private $_cleared     = array();
    private $_lang        = null;
    private $_content     = null;
    private $_tag         = null;
    private $_data        = array();

    protected function _loadTranslationData($filename, $locale, array $options = array()) {
        $this->_data = array();
        $this->_lang = $locale;
        if (!is_readable($filename)) {
            require_once 'Zend/Translate/Exception.php';
            throw new Zend_Translate_Exception('Translation file \'' . $filename . '\' is not readable.');
        }

        $encoding    = $this->_findEncoding($filename);
        $this->_file = xml_parser_create($encoding);
        xml_set_object($this->_file, $this);
        xml_parser_set_option($this->_file, XML_OPTION_CASE_FOLDING, 0);
        xml_set_element_handler($this->_file, '_startElement', '_endElement');
        xml_set_character_data_handler($this->_file, '_contentElement');

        if (!xml_parse($this->_file, file_get_contents($filename))) {
            $ex = sprintf('XML error: %s at line %d',
                          xml_error_string(xml_get_error_code($this->_file)),
                          xml_get_current_line_number($this->_file));
            xml_parser_free($this->_file);
            require_once 'Zend/Translate/Exception.php';
            throw new Zend_Translate_Exception($ex);
        }

        return $this->_data;
    }

    private function _startElement($file, $name, $attrib) {
        switch (strtolower($name)) {
            case 'translate':
                $this->_tag     = $attrib['id'];
                $this->_content = null;
                break;
            default:
                break;
        }
    }

    private function _endElement($file, $name) {
        switch (strtolower($name)) {
            case 'translate':
                if (!empty($this->_tag) and !empty($this->_content) or
                    (isset($this->_data[$this->_lang][$this->_tag]) === false)) {
                    $this->_data[$this->_lang][$this->_tag] = $this->_content;
                }
                $this->_tag     = null;
                $this->_content = null;
                break;

            default:
                break;
        }
    }

    private function _contentElement($file, $data) {
        if (($this->_tag !== null)) {
            $this->_content .= $data;
        }
    }

    private function _findEncoding($filename) {
        $file = file_get_contents($filename, null, null, 0, 100);
        
        if (strpos($file, 'encoding') !== false) {
            $encoding = substr($file, strpos($file, 'encoding') + 9);
            $encoding = substr($encoding, 1, strpos($encoding, $encoding[0], 1) - 1);
            return $encoding;
        }
        
        return 'UTF-8';
    }

    /**
     * Returns the adapter name
     *
     * @return string
     */
    public function toString() {
        return 'SnowXML';
    }
}
