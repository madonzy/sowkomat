<?php

class Model_Pdf_Test extends Snow_Pdf_Grid {
    private $_font;
    
    private $_data;
    
    public function __construct(array $data) {
        parent::__construct(15.1);
        
        $this->_pdf = new Zend_Pdf();        
        $this->_font = Zend_Pdf_Font::fontWithPath(APPLICATION_PATH . '/../public/_font/calibrib.ttf');        
        $this->_data = $data;
        
        $this->draw();
    }
    
    private function draw() {
        $width = 75;
        $height = 75;
        
        foreach ($this->_data as $test) {
            $this->_pdf->pages[] = $page = $this->_pdf->newPage(Zend_Pdf_Page::SIZE_A4);
            $page->setFont($this->_font, 15);
            
            $i = 1;
            foreach ($test as $key => $value) {
                if($i % 30 == 0 )
                {
                    $this->_pdf->pages[] = $page = $this->_pdf->newPage(Zend_Pdf_Page::SIZE_A4);
                    $page->setFont($this->_font, 15);
                    $i = 1;
                }
                $page->drawText($value, $width, $page->getHeight() - $height - ($i++ * 25), $this->_charset);
            }
        }
    }
}
