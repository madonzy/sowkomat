<?php

class HomeController extends Zend_Controller_Action {
    public function init() {
        // Do nothing.
    }

    public function indexAction() {
        $form = new Model_Form_Generate;
        $model = new Model_Csv_Books;
        
        $resources = $model->getResources();
        //var_dump($resources); die();
        $this->view->form = $form->setActionView($resources);
        
        $this->view->resources = array_keys($resources);
    }
    
    public function generateAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        
        $this->getResponse()->setHeader('Cache-Control', 'no-cache, must-revalidate');
        
        $model = new Model_Csv_Books;
        $resources = $model->getResources();
        
        $book = $this->getRequest()->getParam('book');
        $resource = $this->getRequest()->getParam('translation');
        //$unit = $this->getRequest()->getParam('unit');
        $units = array();
        foreach ($this->getRequest()->getParams() as $key => $value) {
            if (strstr($key, 'unit_') && ($key = (int) substr($key, 5))) {
                $units[] = $key;
            }
        }
        $difficulty = $this->getRequest()->getParam('difficulty', NULL);
        $count = $this->getRequest()->getParam('count', 1);
        
        switch (strtolower($this->getRequest()->getMethod())) {
            case 'post':
                //-------------------------------------------------------------
                // SAMPLES
                //-------------------------------------------------------------
                
                $output = $model->getDictionary($book, $resource, $units, $difficulty, $count);
                
                if($resource == 'mieszane')
                {
                    foreach ($output[0] as $key => $data) {
                        if($key % 2 != 0) $pl[$key] = $data[4];
                        else $en[$key] = $data[0];
                    }
                    $output = array(0 => array_merge($en,$pl));
                    //var_dump($output); die();
                }
                else 
                {
                    foreach ($output as $key => $data) {
                        $output[$key] = array_values_recursive($data, 'WORD');
                    }
                }
                
                $this->getResponse()
                    ->setHeader('Content-Type', 'application/json; charset=UTF-8', true)
                    ->appendBody(Zend_Json::encode($output));
                break;
        }
    }
    
    public function pdfAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        
        $this->getResponse()->setHeader('Cache-Control', 'no-cache, must-revalidate');
        
        $model = new Model_Csv_Books;
        
        $book = $this->getRequest()->getParam('book');
        $resource = $this->getRequest()->getParam('translation');
        //$unit = $this->getRequest()->getParam('unit');
        $units = array();
        foreach ($this->getRequest()->getParams() as $key => $value) {
            if (strstr($key, 'unit_') && ($key = (int) substr($key, 5))) {
                $units[] = $key;
            }
        }
        $difficulty = $this->getRequest()->getParam('difficulty', NULL);
        $count = $this->getRequest()->getParam('count', 1);
        
        switch (strtolower($this->getRequest()->getMethod())) {
            case 'post':
                //-------------------------------------------------------------
                // PDF
                //-------------------------------------------------------------
                
                $output = $model->getAllDictionary($book, $resource, $units, $difficulty);
                foreach ($output as $key => $data) {
                    //$output[$key] = array_values_recursive($data, 'WORD');
                }
                $output = new Model_Pdf_Test($output);
                
                $this->getResponse()
                    ->setHeader('Content-Type', 'application/pdf')
                    ->appendBody($output)
                    ->sendResponse();
                break;
            
            default:
                $this->getResponse()
                    ->setHeader('Content-Type', 'text/html')
                    ->appendBody('Something went wrong! ;(');
                break;
        }
    }
}

