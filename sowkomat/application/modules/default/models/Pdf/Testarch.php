<?php

class Model_Pdf_Test extends Snow_Pdf_Grid {
    private $_font;
    
    private $_data;
    
    public function __construct(array $data) {
        parent::__construct(15.1);
        
        $this->_pdf = new Zend_Pdf;        
        $this->_font = Zend_Pdf_Font::fontWithPath(APPLICATION_PATH . '/../public/_font/calibrib.ttf');        
        $this->_data = $data;
        
        $this->draw();
    }
    
    private function draw() {
        $perPage = 25;
        
        $left = 75;
        $top = 50;
        
        foreach ($this->_data as $key => $word) {
            if (!(($key) % $perPage)) {
                $this->_pdf->pages[] = $page = $this->_pdf->newPage(Zend_Pdf_Page::SIZE_A4);
                $page->setFont($this->_font, 15);
                $height = 0;
            }
            
            $page->drawText($word, $left, $page->getHeight() - $top - ($height += 29), $this->_charset);
        }
    }
}
