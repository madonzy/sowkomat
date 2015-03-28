<?php

class Model_Pdf_Test extends Snow_Pdf_Grid {
    private $_font;
    private $_fontBold;
    
    private $_data;
    
    public function __construct(array $data) {
        parent::__construct(15.1);
        
        $this->_pdf = new Zend_Pdf();        
        $this->_font = Zend_Pdf_Font::fontWithPath(APPLICATION_PATH . '/../public/_font/calibri.ttf');
        $this->_fontBold = Zend_Pdf_Font::fontWithPath(APPLICATION_PATH . '/../public/_font/calibrib.ttf');
        $this->_data = $data;
        
        $this->draw();
    }
    
    private function draw() {
        $width = 75;
        $height = 75;
        
        $this->groupByUnit();
        //var_dump($this->_data);die();
        foreach ($this->_data as $key => $unit) {
            $this->_pdf->pages[] = $page = $this->_pdf->newPage(Zend_Pdf_Page::SIZE_A4);
            $page->setFont($this->_fontBold, 18);
            $page->drawText('Rozdział: '.$key, 1 * $width, $page->getHeight() - $height + 25, $this->_charset);
            $page->setFont($this->_font, 15);
            
            $i = 1;
            $colNr = 1;
            foreach ($unit as $key => $value) {
                if($i % 30 == 0 )
                {
                    if($colNr == 1) $colNr = 5;         
                    else 
                    {
                        $colNr = 1;
                        $this->_pdf->pages[] = $page = $this->_pdf->newPage(Zend_Pdf_Page::SIZE_A4);
                        $page->setFont($this->_font, 15);
                    }
                    $i = 1;
                } 
                $page->drawText($value, $colNr * $width, $page->getHeight() - $height - ($i++ * 25), $this->_charset);
            }
        }
    }
    
    private function groupByUnit()
    {
        $array = array();
        foreach($this->_data as $level) {$array = array_merge ($array, $level); }
        $data = array();
        foreach($array as $word)
        {
            $data[$word[1]][] = $word[0]; // przypisanie słowa do rozdziału
        }
        $this->_data = $data;
    }
}
