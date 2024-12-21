<?php return array(
    'root' => array(
        'name' => 'arraypress/google-places-plugin',
        'pretty_version' => '1.0.0+no-version-set',
        'version' => '1.0.0.0',
        'reference' => null,
        'type' => 'wordpress-plugin',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'dev' => true,
    ),
    'versions' => array(
        'arraypress/google-places' => array(
            'pretty_version' => '1.0.0',
            'version' => '1.0.0.0',
            'reference' => '0746b3f19893445a6b134cb09df962b3a87b3689',
            'type' => 'library',
            'install_path' => __DIR__ . '/../arraypress/google-places',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'arraypress/google-places-plugin' => array(
            'pretty_version' => '1.0.0+no-version-set',
            'version' => '1.0.0.0',
            'reference' => null,
            'type' => 'wordpress-plugin',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
    ),
);
