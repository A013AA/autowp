<?php

namespace Application\Form\Element;

use Zend\Form\Element\Text;
use Zend\InputFilter\InputProviderInterface;

class Year extends Text implements InputProviderInterface
{
    protected $attributes = [
        'type'      => 'text',
        'maxlength' => 4,
        'size'      => 4
    ];

    /**
     * @var null|string
     */
    protected $label = 'Год';

    /**
     * Provide default input rules for this element
     *
     * Attaches a phone number validator.
     *
     * @return array
     */
    public function getInputSpecification()
    {
        return [
            'name' => $this->getName(),
            'required' => true,
            'filters' => [
                ['name' => 'StringTrim']
            ],
            'validators' => [
                ['name' => 'Digits'],
                [
                    'name'    => 'Between',
                    'options' => [
                        'min'       => 1700,
                        'max'       => date('Y')+3,
                        'inclusive' => true
                    ]
                ]
            ]
        ];
    }
}