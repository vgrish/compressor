<?php

/** @var $modx modX */
if (!$modx = $object->xpdo AND !$object->xpdo instanceof modX) {
    return true;
}

$lexicons = [
    'ru' => [
        'core' => [
            'resource' => [
                'resource_compress'      => 'Сжимать',
                'resource_compress_help' => 'Включение этой опции включает сжатие ресурса. При выводе контент ресурса будет обработан пакетом "Compressor".',
            ],
        ],
    ],
    'en' => [
        'core' => [
            'resource' => [
                'resource_compress'      => 'Сжимать',
                'resource_compress_help' => 'Включение этой опции включает сжатие ресурса. При выводе контент ресурса будет обработан пакетом "Compressor".',
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
