<?php

$settings = array();

$tmp = array(

    'timing'             => array(
        'xtype' => 'combo-boolean',
        'value' => true,
        'area'  => 'compressor_main',
    ),
    'compress_resource' => array(
        'xtype' => 'combo-boolean',
        'value' => true,
        'area'  => 'compressor_main',
    ),
    'compress_html_code' => array(
        'xtype' => 'combo-boolean',
        'value' => true,
        'area'  => 'compressor_main',
    ),
    'compress_combine_head_css_inline' => array(
        'xtype' => 'combo-boolean',
        'value' => true,
        'area'  => 'compressor_main',
    ),
    'compress_combine_footer_css_inline' => array(
        'xtype' => 'combo-boolean',
        'value' => true,
        'area'  => 'compressor_main',
    ),
    'compress_combine_head_js_inline' => array(
        'xtype' => 'combo-boolean',
        'value' => true,
        'area'  => 'compressor_main',
    ),

    //временные

    /*'assets_path' => array(
        'value' => '{base_path}compressor/assets/components/compressor/',
        'xtype' => 'textfield',
        'area'  => 'compressor_temp',
    ),
    'assets_url'  => array(
        'value' => '/compressor/assets/components/compressor/',
        'xtype' => 'textfield',
        'area'  => 'compressor_temp',
    ),
    'core_path'   => array(
        'value' => '{base_path}compressor/core/components/compressor/',
        'xtype' => 'textfield',
        'area'  => 'compressor_temp',
    ),*/

    //временные
);

foreach ($tmp as $k => $v) {
    /* @var modSystemSetting $setting */
    $setting = $modx->newObject('modSystemSetting');
    $setting->fromArray(array_merge(
        array(
            'key'       => 'compressor_' . $k,
            'namespace' => PKG_NAME_LOWER,
        ), $v
    ), '', true, true);

    $settings[] = $setting;
}

unset($tmp);
return $settings;
