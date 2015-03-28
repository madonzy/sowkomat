<?php

class Snow_View_Helper_FlashMessenger extends Zend_View_Helper_Abstract {
    private $_flashMessenger = null;

    public function flashMessenger($key = 'warning', $template = '<div class="note %s">%s</div>') {
        $flashMessenger = $this->_getFlashMessenger();

        $messages = $flashMessenger->getMessages();

        if ($flashMessenger->hasCurrentMessages()) {
            $messages = array_merge($messages, $flashMessenger->getCurrentMessages());
            $flashMessenger->clearCurrentMessages();
        }

        $output = '';

        foreach ($messages as $message) {
            if (is_array($message)) {
                list($key, $message) = each($message);
            }
            $output .= sprintf($template, $key, $message);
        }

        return $output;
    }

    public function _getFlashMessenger() {
        if (null === $this->_flashMessenger) {
            $this->_flashMessenger = Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger');
        }
        
        return $this->_flashMessenger;
    }
}

?>