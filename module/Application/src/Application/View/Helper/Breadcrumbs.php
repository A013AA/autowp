<?php

namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;

use Exception;

use Zend_View_Abstract;

class Breadcrumbs extends AbstractHelper
{
    private $data = [];

    public function reset()
    {
        $this->data = [];
    }

    public function __invoke($url = null, $name = null, $placement = 'append')
    {
        if ($url || $name) {
            $node = array('url' => $url, 'name' => $name);
            switch ($placement) {
                case 'append':
                    $this->data[] = $node;
                    break;
                case 'prepend':
                    array_unshift($this->data, $node);
                    break;
            }
        }

        return $this;
    }

    public function __toString()
    {
        try {
            $items = array();
            foreach ($this->data as $node) {

                $name = $node['name'];
                $url = $node['url'];

                if ($url)
                    $items[] = '<li>'.$this->view->htmlA(['href' => $url], $name).'</li>';
                else
                    $items[] = '<li>'.$this->view->escapeHtml($name).'</li>';
            }

            array_pop($items);

            if (!$items) {
                return '';
            }

            $endTag = ' />';
            if (($this->view instanceof Zend_View_Abstract) && !$this->view->doctype()->isXhtml()) {
                $endTag= '>';
            }

            return  '<ul class="breadcrumb">'.
                        /*'<strong style="margin-right:8px">'.
                            $this->view->escape($this->view->translate('breadcrumbs/title')) .
                        ':</strong>'.*/
                        implode($items).
                    '</ul>';
        } catch (Exception $e) {
            print $e->getMessage();
        }
    }

}