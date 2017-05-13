<?php

/** @var array $scriptProperties */
/** @var Compressor $Compressor */

$fqn = $modx->getOption('compressor_class', null, 'compressor.compressor', true);
$path = $modx->getOption('compressor_core_path', null,
    $modx->getOption('core_path', null, MODX_CORE_PATH) . 'components/compressor/');
if (!$Compressor = $modx->getService($fqn, '', $path . 'model/',
    array('core_path' => $path))
) {
    return false;
}

switch ($modx->event->name) {

    case 'OnWebPagePrerender':
        if (!$Compressor->isResourceCompress($modx->resource)) {
            return;
        }

        $html = $modx->resource->_output;
        $html = $Compressor->HtmlCompress($html);
        $modx->resource->_output = $html;

        break;
    case 'OnSiteRefresh':
        if ($Compressor->clearFileCache()) {
            $modx->log(modX::LOG_LEVEL_INFO, $modx->lexicon('refresh_default') . ': Compressor');
        }
        break;
}