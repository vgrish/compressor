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
    /** @var Compressor $client */
    public $client;
    /** @var mixed|null $namespace */
    public $namespace = 'compressor';
    /** @var string $version */
    public $version = '1.0.13-beta';

    /** @var array $config */
    public $config = [];
    /** @var array $options */
    public $options = array(
        'cache_dir_public'  => '{assets_path}components/compressor/.~cache/public',
        'cache_dir_private' => '{core_path}cache/default/compressor/.~cache/private',
    );
    /** @var array $initialized */
    public $initialized = [];

    /**
     * @param       $n
     * @param array $p
     */
    public function __call($n, array $p)
    {
        echo __METHOD__ . ' says: ' . $n;
    }

    /**
     * @param modX $modx
     * @param array $config
     */
    function __construct(modX &$modx, array $config = [])
    {
        $this->modx =& $modx;

        $corePath = $this->getOption('core_path', $config, MODX_CORE_PATH . 'components/compressor/');
        $assetsUrl = $this->getOption('assets_url', $config, MODX_ASSETS_URL . 'components/compressor/');

        $this->config = array_merge(array(
            'namespace' => $this->namespace,
            'corePath'  => $corePath,
            'modelPath' => $corePath . 'model/',
            'cssUrl'    => $assetsUrl . 'css/',
            'jsUrl'     => $assetsUrl . 'js/',
            'showLog'   => false,
        ), $config);
    }

    /**
     * @param       $key
     * @param array $config
     * @param null $default
     *
     * @return mixed|null
     */
    public function getOption($key, $config = [], $default = null, $skipEmpty = false)
    {
        $option = $default;
        if (!empty($key) AND is_string($key)) {
            if ($config != null AND array_key_exists($key, $config)) {
                $option = $config[$key];
            } else if (array_key_exists($key, $this->config)) {
                $option = $this->config[$key];
            } else if ($key = $this->namespace . '_' . $key AND array_key_exists($key, $this->modx->config)) {
                $option = $this->modx->getOption($key);
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

    public function initialize($ctx = 'web', array $scriptProperties = [])
    {
        if (isset($this->initialized[$ctx])) {
            return $this->initialized[$ctx];
        }

        $this->config = array_merge($this->config, $scriptProperties, array('ctx' => $ctx));

        if ($ctx !== 'mgr' AND (!defined('MODX_API_MODE') OR !MODX_API_MODE)) {

        }

        $initialize = true;
        $this->initialized[$ctx] = $initialize;

        return $initialize;
    }

    public function removeDir($dir)
    {
        $dir = rtrim($dir, '/') . '/';
        if (is_dir($dir) AND $list = @scandir($dir, 1)) {
            foreach ($list as $file) {
                if ($file[0] === '.') {
                    continue;
                }
                if (is_dir($dir . '/' . $file)) {
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
        $options = [];
        foreach (array('cache_dir_public', 'cache_dir_private') as $k) {
            $options[$k] = $this->translatePath($this->options[$k]);
        }

        foreach ($options as $dir) {
            $this->removeDir($dir);
        }

        $this->modx->log(modX::LOG_LEVEL_INFO, $this->modx->lexicon('refresh_default') . ': Compressor');

    }

    public function getClient($options = [])
    {
        if (!$this->client) {
            try {
                if (!class_exists('CompressorX')) {
                    require 'compressorx.class.php';
                }
                $this->client = new CompressorX($this, $options);
            } catch (CompressorException $e) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage());

                return false;
            }
        }

        return $this->client;
    }

    public function resourceCompress(modResource $resource, $options = [])
    {
        if ($resource->contentType !== 'text/html' OR $resource->deleted) {
            return $resource->_output;
        }

        $this->modx->invokeEvent('compressorOnBeforeResourceCompress', array(
            'compressor' => &$this,
            'resource'   => &$resource,
        ));

        $compress = $resource->get('compress');
        if ($compress === null) {
            $compress = $this->getOption('compress_resource', null);
        }

        if ($compress) {
            $resource->_output = $this->outputCompress($resource->_output, $options);
        }

        $this->modx->invokeEvent('compressorOnResourceCompress', array(
            'compressor' => &$this,
            'resource'   => &$resource,
        ));

        return $resource->_output;
    }

    public function outputCompress($output = '', $options = [])
    {
        if (!$client = $this->getClient($options)) {
            return $output;
        }

        $tagFooterCss = "<!-- footer-css -->";
        $tagFooterScript = "<!-- footer-scripts -->";

        // process css
        $cssAdd = '';
        $isFooterCss = preg_match_all("#{$tagFooterCss}(.*){$tagFooterCss}#Usi", $output, $matchScripts);
        if ($isFooterCss) {
            foreach ($matchScripts[0] as $idx => $matchScript) {
                $output = str_replace(
                    $matchScript,
                    "",
                    $output
                );
                $cssAdd .= $matchScripts[1][$idx];
            }
        }

        // process js
        $scriptsAdd = '';
        $isFooterScript = preg_match_all("#{$tagFooterScript}(.*){$tagFooterScript}#Usi", $output, $matchScripts);
        if ($isFooterScript) {
            foreach ($matchScripts[0] as $idx => $matchScript) {
                $output = str_replace(
                    $matchScript,
                    "",
                    $output
                );
                $scriptsAdd .= $matchScripts[1][$idx];
            }
        }

        $cssClient = '';
        if (!empty($cssClient)) {
            $cssClient .= "\n";
        }

        $scriptsClient = $this->modx->getRegisteredClientScripts();
        if (!empty($scriptsClient)) {
            $scriptsClient .= "\n";
        }

        $output = str_replace(
            $scriptsClient . "</body>",
            $tagFooterCss . $cssAdd . $cssClient . $tagFooterCss . "\n" .
            $tagFooterScript . $scriptsAdd . $scriptsClient . $tagFooterScript . "\n</body>",
            $output
        );

        return $client->compress($output);
    }

    public function jsCompressToHtml(array $js_tag_frags, $for = 'foot')
    {
        $html = '';
        if (!$client = $this->getClient()) {
            return $html;
        }

        try {
            if ($js_parts = $client->compileJsTagFragsIntoParts($js_tag_frags, $for, false)) {
                $compressed_js_tags = [];
                foreach ($js_parts as $_js_part) {
                    if (isset($_js_part['exclude_frag'], $js_tag_frags[$_js_part['exclude_frag']]['all'])) {
                        $compressed_js_tags[] = $js_tag_frags[$_js_part['exclude_frag']]['all'];
                    } else {
                        $compressed_js_tags[] = $_js_part['tag'];
                    }
                }
                $html = implode("\n", $compressed_js_tags);
            }
        } catch (\Exception $e) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, print_r($e->getMessage(), 1));
        }

        return $html;
    }

    public function cssCompressToHtml(array $css_tag_frags, $for = 'foot')
    {
        $html = '';
        if (!$client = $this->getClient()) {
            return $html;
        }

        try {
            if ($css_parts = $client->compileCssTagFragsIntoParts($css_tag_frags, $for)) {
                $compressed_css_tags = [];
                foreach ($css_parts as $_css_part) {
                    if (isset($_css_part['exclude_frag'], $css_tag_frags[$_css_part['exclude_frag']]['all'])) {
                        $compressed_css_tags[] = $css_tag_frags[$_css_part['exclude_frag']]['all'];
                    } else {
                        $compressed_css_tags[] = $_css_part['tag'];
                    }
                }
                $html = implode("\n", $compressed_css_tags);
            }
        } catch (\Exception $e) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, print_r($e->getMessage(), 1));
        }

        return $html;
    }

    public function cssCompress($css = '', $options = [])
    {
        if ($cssCompressor = new \WebSharks\CssMinifier\Core('')) {
            return $cssCompressor->compress($css);
        }

        return $css;
    }

    public function jsCompress($js = '', $options = [])
    {
        if ($jsCompressor = new \WebSharks\JsMinifier\Core('')) {
            return $jsCompressor->compress($js);
        }

        return $js;
    }

    public function injectMap()
    {
        if ($this->modx->loadClass('modResource')) {
            $this->modx->map['modResource']['fields']['compress'] = 1;
            $this->modx->map['modResource']['fieldMeta']['compress'] = [
                'dbtype'     => 'tinyint',
                'precision'  => 1,
                'attributes' => 'unsigned',
                'phptype'    => 'bool',
                'null'       => true,
                'default'    => 1,
            ];
        }
    }

    public function injectJs()
    {
        /** @var modManagerController $controller */
        if ($controller = &$this->modx->controller) {
            $controller->addLastJavascript($this->config['jsUrl'] . 'mgr/resource/inject/inject.js');
        }
    }
}