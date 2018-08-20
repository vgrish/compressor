<?php

/** @var modX $modx */
/** @var array $scriptProperties */
/** @var Compressor $Compressor */

$fqn = $modx->getOption('compressor_class', null, 'compressor.compressor', true);
$path = $modx->getOption('compressor_core_path', null, $modx->getOption('core_path', null, MODX_CORE_PATH) . 'components/compressor/');
if (!$Compressor = $modx->getService($fqn, '', $path . 'model/', array('core_path' => $path))) {
    return false;
}

switch ($modx->event->name) {
    case 'OnWebPagePrerender':
        $modx->resource->_output = $Compressor->resourceCompress($modx->resource);
        break;
    case 'OnSiteRefresh':
        $Compressor->clearFileCache();
        break;
    case 'OnMODXInit':
        $Compressor->injectMap();
        break;
    case 'OnDocFormPrerender':
        $Compressor->injectJs();
        break;
}