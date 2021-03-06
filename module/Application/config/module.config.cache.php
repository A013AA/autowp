<?php

namespace Application;

return [
    'cachemanager' => [
        'fast' => [
            'frontend' => [
                'name' => 'Core',
                'customFrontendNaming' => 0,
                'options' => [
                    'lifetime' => 180,
                    'automatic_serialization' => true
                ]
            ],
            'backend' => [
                'name' => 'Memcached',
                'options' => [
                    'servers' => [
                        'host' => 'localhost',
                        'port' => 11211
                    ]
                ]
            ]
        ],
        'long' => [
            'frontend' => [
                'name' => 'Core',
                'customFrontendNaming' => 0,
                'options' => [
                    'lifetime' => 600,
                    'automatic_serialization' => true
                ]
            ],
            'backend' => [
                'name' => 'Memcached',
                'options' => [
                    'servers' => [
                        'host' => 'localhost',
                        'port' => 11211
                    ]
                ]
            ]
        ],
        'locale' => [
            'frontend' => [
                'name' => 'Core',
                'customFrontendNaming' => 0,
                'options' => [
                    'lifetime' => 600,
                    'automatic_serialization' => true
                ]
            ],
            'backend' => [
                'name' => 'Memcached',
                'options' => [
                    'servers' => [
                        'host' => 'localhost',
                        'port' => 11211
                    ]
                ]
            ]
        ]
    ],
    'caches' => [
        'fastCache' => [
            'adapter' => [
                'name'     =>'memcached',
                'lifetime' => 180,
                'options'  => [
                    'servers'   => [
                        ['localhost', 11211]
                    ],
                    'namespace'  => 'FAST',
                    'liboptions' => [
                        'COMPRESSION'     => false,
                        'binary_protocol' => true,
                        'no_block'        => true,
                        'connect_timeout' => 100
                    ]
                ]
            ],
            /*'plugins' => [
             'exception_handler' => [
                 'throw_exceptions' => false
             ],
            ],*/
        ],
        'longCache' => [
            'adapter' => [
                'name'     =>'memcached',
                'lifetime' => 600,
                'options'  => [
                    'servers'   => [
                        ['localhost', 11211]
                    ],
                    'namespace'  => 'LONG',
                    'liboptions' => [
                        'COMPRESSION'     => false,
                        'binary_protocol' => true,
                        'no_block'        => true,
                        'connect_timeout' => 100
                    ]
                ]
            ],
            /*'plugins' => [
             'exception_handler' => [
                 'throw_exceptions' => false
             ],
            ],*/
        ],
        'localeCache' => [
            'adapter' => [
                'name'     =>'memcached',
                'lifetime' => 600,
                'options'  => [
                    'servers'   => [
                        ['localhost', 11211]
                    ],
                    'namespace'  => 'LOCALE',
                    'liboptions' => [
                        'COMPRESSION'     => false,
                        'binary_protocol' => true,
                        'no_block'        => true,
                        'connect_timeout' => 100
                    ]
                ]
            ],
            /*'plugins' => [
             'exception_handler' => [
                 'throw_exceptions' => false
             ],
            ],*/
        ],
    ],
];
