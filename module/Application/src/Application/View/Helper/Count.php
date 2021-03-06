<?php

namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;

class Count extends AbstractHelper
{
    public function __invoke($count, $new = null, array $options = [])
    {
        if ($count == 0) {
            $result = $this->view->translate('count 0');
        } else {
            $result = $count-$new;
            if ($new) {
                $result .= '+<span>'.$new.'</span>';
            }
        }

        return '<span class="count">('.$result.')</span>';
    }
}