<?php

//ini_set('display_errors', 1);
//ini_set('error_reporting', -1);

//require_once (__DIR__) . "/html-compressor/html-compressor.phar";
if (!class_exists('\WebSharks\HtmlCompressor')) {
    require_once (__DIR__) . "/html-compressor/html-compressor.phar";
}

/**
 * The base class for Compressor.
 */
class Compressor
{
    /* @var modX $modx */
    public $modx;

    /** @var mixed|null $namespace */
    public $namespace = 'compressor';
    /** @var string $version */
    public $version = '1.0.7-beta';

    /** @var array $config */
    public $config = array();

    /** @var array $options */
    public $options = array(
        'cache_dir_public'  => '{assets_path}components/compressor/.~cache/public',
        'cache_dir_private' => '{core_path}cache/default/compressor/.~cache/private',
    );

    /** @var array $initialized */
    public $initialized = array();

    /** @var Compressor $compressor */
    public $compressor;

    /**
     * @param modX  $modx
     * @param array $config
     */
    function __construct(modX &$modx, array $config = array())
    {
        $this->modx =& $modx;

        $corePath = $this->getOption('core_path', $config,
            $this->modx->getOption('core_path', null, MODX_CORE_PATH) . 'components/compressor/');

        $this->config = array_merge(array(
            'namespace' => $this->namespace,
            'corePath'  => $corePath,
            'modelPath' => $corePath . 'model/',
            'showLog'   => false,
        ), $config);

        $this->modx->addPackage('compressor', $this->getOption('modelPath'));
        $this->modx->lexicon->load('compressor:default');
    }

    /**
     * @param       $n
     * @param array $p
     */
    public function __call($n, array$p)
    {
        echo __METHOD__ . ' says: ' . $n;
    }

    /**
     * @param       $key
     * @param array $config
     * @param null  $default
     *
     * @return mixed|null
     */
    public function getOption($key, $config = array(), $default = null, $skipEmpty = false)
    {
        $option = $default;
        if (!empty($key) AND is_string($key)) {
            if ($config != null AND array_key_exists($key, $config)) {
                $option = $config[$key];
            } elseif (array_key_exists($key, $this->config)) {
                $option = $this->config[$key];
            } elseif (array_key_exists("{$this->namespace}_{$key}", $this->modx->config)) {
                $option = $this->modx->getOption("{$this->namespace}_{$key}");
            }
        }
        if ($skipEmpty AND empty($option)) {
            $option = $default;
        }

        return $option;
    }


    public function translatePath($path)
    {
        return str_replace(array(
            '{core_path}',
            '{base_path}',
            '{assets_path}',
        ), array(
            $this->modx->getOption('core_path', null, MODX_CORE_PATH),
            $this->modx->getOption('base_path', null, MODX_BASE_PATH),
            $this->modx->getOption('assets_path', null, MODX_ASSETS_PATH),
        ), $path);
    }

    public function initialize($ctx = 'web', array $scriptProperties = array())
    {
        if (isset($this->initialized[$ctx])) {
            return $this->initialized[$ctx];
        }

        $this->modx->error->reset();
        $this->config = array_merge($this->config, $scriptProperties, array('ctx' => $ctx));

        if ($ctx != 'mgr' AND (!defined('MODX_API_MODE') OR !MODX_API_MODE)) {

        }

        $initialize = true;
        $this->initialized[$ctx] = $initialize;

        return $initialize;
    }

    public function isResourceCompress(modResource $resource)
    {
        if ($resource->contentType != 'text/html') {
            return false;
        }
        if ($resource->deleted != 0) {
            return false;
        }

        return true;
    }

    public function removeDir($dir)
    {
        $dir = rtrim($dir, '/') . '/';
        if (is_dir($dir) AND $list = @scandir($dir)) {
            foreach ($list as $file) {
                if ($file[0] == '.') {
                    continue;
                } elseif (is_dir($dir . '/' . $file)) {
                    $this->removeDir($dir . '/' . $file);
                } else {
                    @unlink($dir . '/' . $file);
                }
            }
        }
        @rmdir($dir);

        return !file_exists($dir);
    }

    public function clearFileCache()
    {
        $options = array();
        foreach (array('cache_dir_public', 'cache_dir_private') as $k) {
            $options[$k] = $this->translatePath($this->options[$k]);
        }

        foreach ($options as $dir) {
            $this->removeDir($dir);
        }

        return true;
    }

    public function getCompressor($options = array())
    {
        if (!$this->compressor) {
            try {
                if (!class_exists('CompressorX')) {
                    require 'compressorx.class.php';
                }
                $this->compressor = new CompressorX($this, $options);
            } catch (CompressorException $e) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage());

                return false;
            }
        }

        return $this->compressor;
    }

    public function HtmlCompress($html = '', $options = array())
    {
        if (!$compressor = $this->getCompressor($options)) {
            return $html;
        }

        $tagFooterCss = "<!-- footer-css -->";
        $tagFooterScript = "<!-- footer-scripts -->";

        // process css
        $cssClient = '';
        $cssAdd = '';

        $isFooterCss = preg_match_all("#{$tagFooterCss}(.*){$tagFooterCss}#Usi", $html, $matchScripts);
        if ($isFooterCss) {
            foreach ($matchScripts[0] as $idx => $matchScript) {
                $html = str_replace(
                    $matchScript,
                    "",
                    $html
                );
                $cssAdd .= $matchScripts[1][$idx];
            }
        }
        if (!empty($cssClient)) {
            $cssClient .= "\n";
        }

        // process js
        $scriptsClient = $this->modx->getRegisteredClientScripts();
        $scriptsAdd = '';

        $isFooterScript = preg_match_all("#{$tagFooterScript}(.*){$tagFooterScript}#Usi", $html, $matchScripts);
        if ($isFooterScript) {
            foreach ($matchScripts[0] as $idx => $matchScript) {
                $html = str_replace(
                    $matchScript,
                    "",
                    $html
                );
                $scriptsAdd .= $matchScripts[1][$idx];
            }
        }

        if (!empty($scriptsClient)) {
            $scriptsClient .= "\n";
        }
        $html = str_replace(
            $scriptsClient . "</body>",
            $tagFooterCss . $cssAdd . $cssClient . $tagFooterCss . "\n" .
            $tagFooterScript . $scriptsAdd . $scriptsClient . $tagFooterScript . "\n</body>",
            $html
        );

        $html = $compressor->compress($html);

        return $html;
    }

    public function CssCompress($css = '', $options = array())
    {
        $cssCompressor = new \WebSharks\CssMinifier\Core($options);
        $css = $cssCompressor->compress($css);

        return $css;
    }

    public function JsCompress($js = '', $options = array())
    {
        $cssCompressor = new \WebSharks\JsMinifier\Core($options);
        $js = $cssCompressor->compress($js);

        return $js;
    }

}