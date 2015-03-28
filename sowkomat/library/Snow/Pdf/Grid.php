<?php

abstract class Snow_Pdf_Grid {
    protected $_pdf;
    protected $_transition;
    protected $_charset;
    
    public function __construct($transition, $charset = 'UTF-8') {
        $this->_transition = $transition;
        $this->_charset = $charset;
    }
    
    protected function drawText($page, $string, $x, $y, $correction = null) {
        $indentation = 0;
        foreach ($this->explode($string, $correction) as $indentation => $char) {
            $page->drawText($char, $x + $indentation, $page->getHeight() - $y, $this->_charset);
        }
        
        return $indentation;
    }
    
    protected function explode($string, $correction = null) {
        $output = array();
        $length = 0;

        for ($i = 0; $i < iconv_strlen($string, $this->_charset); $i++) {
            $output[$length] = iconv_substr($string, $i, 1, $this->_charset);
            $length += $this->_transition + (empty($correction) ? null : (is_array($correction) ? (empty($correction[$i]) ? null : $correction[$i]) : $correction));
        }

        return $output;
    }
    
    public function __toString() {
        return $this->_pdf->render();
    }
    
    public function save($filename, $update = false) {
        return $this->_pdf->save($filename, $update);
    }
    
    public function setTransition($transition) {
        $this->_transition = $transition;
    }
}