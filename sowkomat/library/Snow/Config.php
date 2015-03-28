<?php

class Snow_Config {
    private $file;
    private $tab = array();
    private $defaults = array(
        'languages' => array('en', 'pl')
    );

    public function __call($name, $arguments) {
        $method = substr($name, 0, 3);
        $variable = lcfirst(substr($name, 3));
        
        if ($method == 'get') {
            return (isset($this->tab[$variable]) ? $this->tab[$variable] : null);
        } else if ($method == 'set') {
            $this->tab[$variable] = $arguments[0];
            $this->save();
        }
    }

    public function getAll() {
        return $this->tab;
    }
    
    public function setAll(array $options) {
        $this->tab = $options;
        $this->save();
    }

    private function retrieveValue(DOMNode $node) {
        if ($node->getAttribute('array') == 'true') {
            $retVal = array();
            $children = $node->childNodes;
            
            foreach ($children as $child) {
                if ($child->nodeName == 'item') {
                    $key = $child->getAttribute('key');
                    $retVal[$key] = $this->retrieveValue($child);
                }
            }
        } else {
            $retVal = trim($node->firstChild->nodeValue);
        }
        
        return $retVal;
    }

    private function read() {
        $conf = new DOMDocument();
        @$conf->load($this->file);
        $vars = @$conf->getElementsByTagName('var');

        foreach ($vars as $var) {
            $this->tab[$var->getAttribute('name')] = $this->retrieveValue($var);
        }

        foreach ($this->defaults as $key => $def) {
            if (!isset($this->tab[$key]))
                    $this->tab[$key] = $def;
        }
    }
    
    private function appendValues(DOMDocument $dom, DOMNode $node, $elem) {
        if (is_array($elem)) {
            $node->setAttribute('array', 'true');
            
            foreach ($elem as $key => $item) {
                $itemNode = $dom->createElement('item');
                $itemNode->setAttribute('key', $key);
                $itemNode = $this->appendValues($dom, $itemNode, $item);
                $node->appendChild($itemNode);
            }
        }
        else {
            $node->appendChild($dom->createTextNode($elem));
        }
        
        return $node;
    }
    
    public function save() {
        $dom = new DOMDocument('1.0', 'utf-8');
        $root = $dom->appendChild($dom->createElement('siteconfig'));
        
        foreach ($this->tab as $key => $elem) {
            $node = $dom->createElement('var');
            $node->setAttribute('name', $key);
            $node = $this->appendValues($dom, $node, $elem);
            $root->appendChild($node);
        }
        
        $dom->formatOutput = true;
        $dom->save($this->file);
    }
    
    public function reinit() {
        $this->tab = array();
        $this->read();
    }

    public function __construct($f) {
        $this->file = $f;
        $this->read();
    }
}

?>
