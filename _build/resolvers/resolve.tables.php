<?php

/** @var $modx modX */
if (!$modx = $object->xpdo AND !$object->xpdo instanceof modX) {
    return true;
}

/** @var $options */
switch ($options[xPDOTransport::PACKAGE_ACTION]) {
    case xPDOTransport::ACTION_INSTALL:
    case xPDOTransport::ACTION_UPGRADE:

        /** @var Compressor $Compressor */
        $corePath = $modx->getOption('compressor_core_path', null, $modx->getOption('core_path') . 'components/compressor/');
        /** @noinspection PhpIncludeInspection */
        require_once $corePath . 'model/compressor/compressor.class.php';
        if (!$Compressor = new Compressor($modx)) {
            return false;
        }

        $Compressor->injectMap();
        $manager = $modx->getManager();
        $level = $modx->getLogLevel();
        $modx->setLogLevel(xPDO::LOG_LEVEL_FATAL);
        $manager->addField('modResource', 'compress');
        $modx->setLogLevel($level);

        break;

    case xPDOTransport::ACTION_UNINSTALL:
        break;
}

return true;
