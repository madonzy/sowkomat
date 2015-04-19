<?php

class Model_Csv_Books extends Model_Csv {
    private $_path;
    private $_resources;
    private $_mixedItemsCount = 0;
    
    const ITEMS_MIXED_COUNT = 20;
    const ITEMS_COUNT = 10;
    const DIFFICULTY_RANGE = 3;
    const DIFFICULTY_FACTOR = 0.7;
    
    public function __construct() {
        parent::__construct();
        
        $this->_path = APPLICATION_PATH . '/../resources';
        
        $this->loadResources();
        $bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
	$aConfig = $bootstrap->getOptions();
	$this->_mixedItemsCount = $aConfig['sowkomat']['settings']['translation']['mixed']['wordsCount'];
    }
    
    private function loadResources() {
        try {
            foreach (scandir($this->_path) as $dir) {
                if (!in_array($dir, array('.', '..')) && is_dir($this->_path . '/' . $dir)) {
                    foreach (scandir($this->_path . '/' . $dir) as $file) {
                        if (substr(strrchr($file, '.'), 1) == 'csv') {
                            $handle = fopen($this->_path . '/' . $dir . '/' . $file, 'r');
                            $file = substr($file, 0, -4);
                            
                            if (($data = fgetcsv($handle, NULL, ';')) === FALSE || ($key = array_search('UNIT', $data)) === FALSE) {
                                throw new Exception;
                            }

                            while (($data = fgetcsv($handle, NULL, ';')) !== FALSE) {
                                $this->_resources[$dir][$file][] = $data[$key];
                            }
                            
                            $this->_resources[$dir][$file] = array_unique($this->_resources[$dir][$file]);
                            sort($this->_resources[$dir][$file]);

                            fclose($handle);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            if (isset($handle) && is_resource($handle)) {
                @fclose($handle);
            }
            
            die('Something went wrong during file loading process! ;(');
        }
    }
    
    public function getResources() {
        return $this->_resources;
    }
    
    public function getDictionary($book, $resource, array $units, $difficulty = NULL, $count = 1) {
        $data = array('count' => 0);
        $mixed = false;
        if($resource =='mieszane') 
        {   
            $resource = 'z angielskiego na polski';
            $mixed = true;
        }
        if (!empty($units) && !empty($this->_resources[$book][$resource]) && !array_diff($units, $this->_resources[$book][$resource])) {
            try {
                $handle = fopen($this->_path . '/' . $book . '/' . $resource . '.csv', 'r');
                
                if (($header = fgetcsv($handle, NULL, ';')) === FALSE
                        || ($keyUnit = array_search('UNIT', $header)) === FALSE
                        || ($keyDif = array_search('DIF', $header)) === FALSE) {
                    throw new Exception;
                }
                
                while (($row = fgetcsv($handle, NULL, ';')) !== FALSE) {
                    if (in_array($row[$keyUnit], $units)) { // jeśli rozdział pobranego słówka jest w żądanych to pobieramy
                        $data['items'][$row[$keyDif]][] = self::convert($row); //data items, trudność
                        $data['count']++;
                    }
                }
                
                ksort($data['items']);
                
                for ($i = 0; $i < $count; $i++) {
                    $data['output'][] = $this->randomize($data, $difficulty, $mixed);
                }
                
                fclose($handle);
            } catch (Exception $e) {
                @fclose($handle);
                
                die('Something went wrong during file analysing process! ;(');
            }
        }
        
        return empty($data['output']) ? FALSE : $data['output'];
    }

    public function getIrregularDictionary($book) {
        try {
            $handle = fopen($this->_path . '/' . $book . '/' . $book . '.csv', 'r');

            if (($header = fgetcsv($handle, NULL, ';')) === FALSE
                || ($keyUnit = array_search('UNIT', $header)) === FALSE
                || ($keyDif = array_search('DIF', $header)) === FALSE) {
                throw new Exception;
            }

            while (($row = fgetcsv($handle, NULL, ';')) !== FALSE && $row[0]) {
                $data['items'][] = self::convert($row);
            }

            ksort($data['items']);

            fclose($handle);
        } catch (Exception $e) {
            @fclose($handle);

            die('Something went wrong during file analysing process! ;(');
        }

        return $data;
    }
    
    public function getAllDictionary($book, $resource, array $units, $difficulty = NULL, $count = 1) {
        $data = array('count' => 0);
        
        if (!empty($units) && !empty($this->_resources[$book][$resource]) && !array_diff($units, $this->_resources[$book][$resource])) {
            try {
                $handle = fopen($this->_path . '/' . $book . '/' . $resource . '.csv', 'r');
                putenv("LANG=pl_PL.UTF-8");
                if (($header = fgetcsv($handle, NULL, ';')) === FALSE
                        || ($keyUnit = array_search('UNIT', $header)) === FALSE
                        || ($keyDif = array_search('DIF', $header)) === FALSE) {
                    throw new Exception;
                }
                
                while (($row = fgetcsv($handle, NULL, ';')) !== FALSE) {
                    if (in_array($row[$keyUnit], $units)) {
                        $data['items'][$row[$keyDif]][] = self::convert($row);
                        $data['count']++;
                    }
                }
                
                ksort($data['items']);
                for ($i = 0; $i < $count; $i++) {
                    $data['output'][] = $this->randomize($data, $difficulty);
                }
                
                fclose($handle);
            } catch (Exception $e) {
                @fclose($handle);
                
                die('Something went wrong during file analysing process! ;(');
            }
        }
        
        return empty($data['items']) ? FALSE : $data['items'];
    }
    
    private function randomize(array $data, $difficulty = NULL, $mixed = false) {
        $pool = array();
        
        if (empty($data) || $data['count'] < self::ITEMS_COUNT) {
            throw new Exception;
        } else {
            if($mixed == true) { //algorytm dla mieszanych
                //die('hello');
                if(empty($difficulty)) $difficulty = array(1,2,3);
                else $difficulty = array($difficulty);
                $diffWordCount = (int)($this->_mixedItemsCount / count($difficulty));
                $diffWordRest = $this->_mixedItemsCount % count($difficulty);
                //die($diffWordCount.'  --  '.$diffWordRest);
                foreach ($difficulty as $level) {
                    $total = count($data['items'][$level]) - 1;
                    $pool['used'][$level] = array();
                    for ($value = 0; $value < $diffWordCount; $value++) {
                        do {
                            $random = mt_rand(0, $total);
                        } while (in_array($random, $pool['used'][$level]));
                        
                        $pool['items'][] = $data['items'][$level][$random];
                        $pool['used'][$level][] =  $random;
                    }
                }
                for($i = 0; $i<$diffWordRest; ++$i)
                {
                    $level = mt_rand(1, 3); 
                    $total = count($data['items'][$level]) - 1;
                    do {
                        $random = mt_rand(0, $total);
                    } while (in_array($random, $pool['used'][$level]));

                    $pool['items'][] = $data['items'][$level][$random];
                    $pool['used'][$level][] =  $random;
                }
            } else {
                if ($data['rest'] = (self::ITEMS_COUNT - ($data['factor'] = floor(self::ITEMS_COUNT * self::DIFFICULTY_FACTOR))) % (self::DIFFICULTY_RANGE - 1)) {
                    $data['factor'] += $data['rest'];
                }

                $data['rest'] = (self::ITEMS_COUNT - $data['factor']) / (self::DIFFICULTY_RANGE - 1);

                for ($level = 1; $level <= self::DIFFICULTY_RANGE; $level++) {
                    $pool['used'][$level] = array();

                    $total = count($data['items'][$level]) - 1;
                    if(empty($difficulty))
                    {
                        if($level == 1) $range = floor(self::ITEMS_COUNT / 3) + (self::ITEMS_COUNT % 3);
                        else $range = floor(self::ITEMS_COUNT / 3);
                    } else {
                        $range = $data[$level == $difficulty ? 'factor' : 'rest'];
                    }
                    //$range = empty($difficulty) ? $data[$level > 1 ? 'rest' : 'factor']
                        //: $data[$level == $difficulty ? 'factor' : 'rest'];

                    for ($value = 0; $value < $range; $value++) {
                        do {
                            $random = mt_rand(0, $total);
                        } while (in_array($random, $pool['used'][$level]));

                        $pool['items'][] = $data['items'][$level][$random];
                        $pool['used'][$level][] =  $random;
                    }
                }
            }
            
        }
        
        return $pool['items'];
    }
    
    public static function convert($data, $encode = FALSE) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::convert($value, $encode);
            }
        } else {
            if (empty($encode)) {
                $data = iconv('Windows-1250', 'UTF-8', $data);
            } else {
                $data = iconv('UTF-8', 'Windows-1250', $data);
            }
            
            $data = trim($data);
        }
        
        return $data;
    }
}
