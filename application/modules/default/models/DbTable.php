<?php

class Model_DbTable extends Zend_Db_Table_Abstract {
    protected $_dictionary = array();
    
    const PREFIX = 'crammer';
    
    public function prepareDojoSort($sort, $default, array $columnMap) {
        if (!empty($sort)) {
            if (strpos($sort, '-') === false) {
                $default = $columnMap[$sort] . ' ASC';
            } else {
                $default = $columnMap[substr($sort, 1)] . ' DESC';
            }
        }
        
        return $default;
    }
    
    public function prepareDojoFilter(array $data, array $columnMap) {
        $output = null;
        
        if (!empty($data)) {
            if (in_array($data['op'], array('all', 'any', 'not'))) {
                $output = array();
                foreach ($data['data'] as $entry) {
                    $output[] = $this->prepareDojoFilter($entry, $columnMap);
                }
            } elseif (is_array($data['data'])) {
                $output = array(
                    'column' => $columnMap[$data['data'][0]['data']],
                    'type' => $data['data'][0]['op'],
                    'value' => empty($data['data'][1]) ? null : $data['data'][1]['data']
                );

                switch ($output['type']) {
                    case 'date':
                        $output['value'] = date('Y-m-d', substr($output['value'], 0, 10));
                        break;
                    case 'time':
                        $output['value'] = date('H:i:s', substr($output['value'], 0, 6));
                        break;
                    default:
                        $output['value'] = $output['value'];
                        break;
                }
            }

            if (!empty($output)) {
                switch ($data['op']) {
                    case 'all':
                        $output = '(' . implode(') AND (', $output) . ')';
                        break;
                    case 'any':
                        $output = '(' . implode(') OR (', $output) . ')';
                        break;
                    case 'not':
                        $output = 'NOT (' . end($output) . ')';
                        break;
                    case 'equal':
                        $output = $this->getAdapter()->quoteInto($output['column'] . ' = ?', $output['value']);
                        break;
                    case 'less':
                        $output = $this->getAdapter()->quoteInto($output['column'] . ' < ?', $output['value']);
                        break;
                    case 'lessEqual':
                        $output = $this->getAdapter()->quoteInto($output['column'] . ' <= ?', $output['value']);
                        break;
                    case 'larger':
                        $output = $this->getAdapter()->quoteInto($output['column'] . ' > ?', $output['value']);
                        break;
                    case 'largerEqual':
                        $output = $this->getAdapter()->quoteInto($output['column'] . ' >= ?', $output['value']);
                        break;
                    case 'contains':
                        $output = $this->getAdapter()->quoteInto($output['column'] . ' LIKE ?', '%' . $output['value'] . '%');
                        break;
                    case 'startsWith':
                        $output = $this->getAdapter()->quoteInto($output['column'] . ' LIKE ?', $output['value'] . '%');
                        break;
                    case 'endsWith':
                        $output = $this->getAdapter()->quoteInto($output['column'] . ' LIKE ?', '%' . $output['value']);
                        break;
                    case 'isEmpty':
                        $output = $output['column'] . ' IS NULL';
                        break;
                    default:
                        $output = '1 = 1';
                        break;
                }
            }
        }
        
        return $output;
    }
    
    public function getTranslateDictionary($json = false) {
        $view = Zend_Layout::getMvcInstance()->getView();
        $output = array();
        
        foreach ($this->_dictionary as $word) {
            $output[$word] = $view->translate($word);
        }
        
        return empty($json) ? $output : json_encode($output);
    }
}
