<?php

namespace Application\Form\Element;

use Zend\Form\Element\Select;

use Brands;

class Brand extends Select
{
    /**
     * @var null|string
     */
    protected $label = 'Производитель';

    /**
     * @param  null|int|string  $name    Optional name for the element
     * @param  array            $options Optional options for the element
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($name = null, $options = [])
    {
        parent::__construct($name, $options);

        $table = new Brands();

        $db = $table->getAdapter();

        $values =$db->fetchPairs(
            $db->select()
                ->from($table->info('name'), ['id', 'caption'])
                ->order(['position', 'caption'])
        );

        $this->setValueOptions(['' => '--'] + $values);
    }
}