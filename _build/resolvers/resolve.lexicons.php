<?php

/** @var $modx modX */
if (!$modx = $object->xpdo AND !$object->xpdo instanceof modX) {
    return true;
}

$lexicons = [
    'ru' => [
        'core' => [
            'resource' => [
                'resource_compressed'      => 'Cжатый',
                'resource_compressed_help' => 'Включение этой опции включает сжатие ресурса. При выводе страница ресурса будет обработана пакетом "Compressor".',
            ],
        ],
    ],
    'en' => [
        'core' => [
            'resource' => [
                'resource_compressed'      => 'Compressed',
                'resource_compressed_help' => 'Enabling this option turns on compression of the resource. In the output page of the resource will be handled by the package "Compressor".',
            ],
        ],
    ],
];

foreach ($lexicons as $language => $strings) {
    foreach ($strings as $namespace => $topics) {
        foreach ($topics as $topic => $values) {
            foreach ($values as $name => $value) {
                $key = [
                    'name'      => $name,
                    'namespace' => $namespace,
                    'language'  => $language,
                    'topic'     => $topic,
                ];
                /** @var $options */
                switch ($options[xPDOTransport::PACKAGE_ACTION]) {
                    case xPDOTransport::ACTION_INSTALL:
                    case xPDOTransport::ACTION_UPGRADE:
                        if (!$entry = $modx->getObject('modLexiconEntry', $key)) {
                            $entry = $modx->newObject('modLexiconEntry', $key);
                        }
                        $entry->set('value', $value);
                        $entry->save();
                        break;
                    case xPDOTransport::ACTION_UNINSTALL:
                        if ($entry = $modx->getObject('modLexiconEntry', $key)) {
                            $entry->remove();
                        }
                        break;
                }
            }
        }
    }
}

return true;
