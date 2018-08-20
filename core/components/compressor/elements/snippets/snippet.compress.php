<?php

/** @var array $scriptProperties */
/** @var Compressor $Compressor */
if (!$Compressor = $modx->getService('compressor', 'Compressor', MODX_CORE_PATH . 'model/compressor/', $scriptProperties)) {
    return 'Could not load Compressor class!';
}

$includeWrapper = $modx->getOption('includeWrapper', $scriptProperties, 1);
$cssWrapper = $modx->getOption('cssWrapper', $scriptProperties, '<!-- footer-css -->{$output}<!-- footer-css -->');
$jsWrapper = $modx->getOption('jsWrapper', $scriptProperties, '<!-- footer-scripts -->{$output}<!-- footer-scripts -->');

$jsCompress = $cssCompress = '';

$cssTags = [];
if (!empty($cssFile)) {
    $cssFile = is_array($cssFile) ? $cssFile : json_decode($cssFile, true);
    foreach ($cssFile as $src) {
        $openTag = '<link rel="stylesheet" href="' . $src . '" type="text/css" />';
        $closingTag = '';
        $cssTags[] = [
            'all'                   => $openTag . $closingTag,
            'link_self_closing_tag' => $openTag,
            'link_href'             => $src,
        ];
    }
}
if (!empty($cssTags)) {
    $cssCompress = $Compressor->cssCompressToHtml($cssTags, 'foot');
    if ($includeWrapper AND !empty($cssWrapper)) {
        $cssCompress = str_replace('{$output}', $cssCompress, $cssWrapper);
    }
}

$jsTags = [];
if (!empty($jsFile)) {
    $jsFile = is_array($jsFile) ? $jsFile : json_decode($jsFile, true);
    foreach ($jsFile as $src) {
        $openTag = '<script src="' . $src . '">';
        $closingTag = '</script>';
        $jsTags[] = [
            'all'                => $openTag . $closingTag,
            'script_open_tag'    => $openTag,
            'script_src'         => $src,
            'script_closing_tag' => $closingTag,
        ];
    }
}
if (!empty($jsTags)) {
    $jsCompress = $Compressor->jsCompressToHtml($jsTags, 'foot');
    if ($includeWrapper AND !empty($jsWrapper)) {
        $jsCompress = str_replace('{$output}', $jsCompress, $jsWrapper);
    }
}


$html .= $jsCompress . "\n" . $cssCompress;

return $html;