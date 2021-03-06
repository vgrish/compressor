<?php

//ini_set('display_errors', 1);
//ini_set('error_reporting', -1);

if (!class_exists('\WebSharks\HtmlCompressor')) {
    require_once (__DIR__) . "/html-compressor/html-compressor.phar";
}

class CompressorX extends WebSharks\HtmlCompressor\Core
{
    /** @var Compressor $Compressor */
    protected $Compressor;
    /** @var modX $modx */
    protected $modx;

    public function __construct(Compressor $Compressor, array $options = [])
    {
        //parent::__construct($options);

        $this->Compressor = $Compressor;
        $this->modx = $Compressor->modx;

        $options = array_merge(array(
            'css_exclusions'                     => array(),
            'js_exclusions'                      => array('.php?'),
            'uri_exclusions'                     => array(),
            // 'cache_dir_public'                 => '{assets_path}components/compressor/.~cache/public',
            // 'cache_dir_private'                => '{core_path}cache/default/compressor/.~cache/private',
            'current_url_scheme'                 => $this->modx->getOption('url_scheme', null, MODX_URL_SCHEME),
            'current_url_host'                   => $this->modx->getOption('http_host', null, MODX_HTTP_HOST),
            'compress_combine_head_body_css'     => $this->getOption('compress_combine_head_body_css', null, true),
            'compress_combine_head_css_inline'   => $this->getOption('compress_combine_head_css_inline', null, true),
            // +
            'compress_combine_head_js'           => $this->getOption('compress_combine_head_js', null, true),
            'compress_combine_head_js_inline'    => $this->getOption('compress_combine_head_js_inline', null, true),
            // +
            'compress_combine_footer_css'        => $this->getOption('compress_combine_footer_css', null, true),
            // +
            'compress_combine_footer_css_inline' => $this->getOption('compress_combine_footer_css_inline', null, true),
            // +
            'compress_combine_footer_js'         => true,//$this->getOption('compress_combine_footer_js', null, true),
            'compress_combine_remote_css_js'     => true,//$this->getOption('compress_combine_remote_css_js', null, true),
            'compress_inline_js_code'            => true,//$this->getOption('compress_inline_js_code', null, true),
            'compress_css_code'                  => true,//$this->getOption('compress_css_code', null, true),
            'compress_js_code'                   => true,//$this->getOption('compress_js_code', null, true),
            'compress_html_code'                 => $this->getOption('compress_html_code', null, false),
            'timing'                             => $this->getOption('benchmark', null, true),
            'product_title'                      => 'Compressor',
            'benchmark'                          => false,
            'amp_exclusions_enable'              => true,
        ), $this->Compressor->options, $options);

        foreach (array('cache_dir_public', 'cache_dir_private') as $k) {
            $options[$k] = $this->Compressor->translatePath($options[$k]);
        }
        $this->options = $options; // Config.


        # Benchmark and Hook API instances.
        $this->benchmark = new WebSharks\HtmlCompressor\Benchmark();
        $this->hook_api = new WebSharks\HtmlCompressor\HookApi();

        # Product Title; i.e., White-Label HTML Compressor
        if (!empty($this->options['product_title']) && is_string($this->options['product_title'])) {
            $this->product_title = (string)$this->options['product_title'];
        }
        # Cache Expiration Time Configuration
        if (!empty($this->options['cache_expiration_time']) && is_string($this->options['cache_expiration_time'])) {
            $this->cache_expiration_time = (string)$this->options['cache_expiration_time'];
        }
        # Vendor-Specific CSS Prefixes
        if (isset($this->options['vendor_css_prefixes']) && is_array($this->options['vendor_css_prefixes'])) {
            $this->regex_vendor_css_prefixes = implode('|', $this->pregQuote($this->options['vendor_css_prefixes']));
        } else {
            $this->regex_vendor_css_prefixes = implode('|', $this->pregQuote($this->default_vendor_css_prefixes));
        }
        # CSS Exclusions (If Applicable)
        if (isset($this->options['regex_css_exclusions']) && is_string($this->options['regex_css_exclusions'])) {
            $this->regex_css_exclusions = $this->options['regex_css_exclusions'];
        } else if (isset($this->options['css_exclusions']) && is_array($this->options['css_exclusions'])) {
            if ($this->options['css_exclusions']) {
                $this->regex_css_exclusions = '/' . implode('|',
                        $this->pregQuote($this->options['css_exclusions'])) . '/ui';
            }
        } else if ($this->default_css_exclusions) {
            $this->regex_css_exclusions = '/' . implode('|', $this->pregQuote($this->default_css_exclusions)) . '/ui';
        }
        if ($this->built_in_regex_css_exclusion_patterns && empty($this->options['disable_built_in_css_exclusions'])) {
            $this->built_in_regex_css_exclusions = '/' . implode('|',
                    $this->built_in_regex_css_exclusion_patterns) . '/ui';
        }
        # JavaScript Exclusions (If Applicable)
        if (isset($this->options['regex_js_exclusions']) && is_string($this->options['regex_js_exclusions'])) {
            $this->regex_js_exclusions = $this->options['regex_js_exclusions'];
        } else if (isset($this->options['js_exclusions']) && is_array($this->options['js_exclusions'])) {
            if ($this->options['js_exclusions']) {
                $this->regex_js_exclusions = '/' . implode('|',
                        $this->pregQuote($this->options['js_exclusions'])) . '/ui';
            }
        } else if ($this->default_js_exclusions) {
            $this->regex_js_exclusions = '/' . implode('|', $this->pregQuote($this->default_js_exclusions)) . '/ui';
        }
        if ($this->built_in_regex_js_exclusion_patterns && empty($this->options['disable_built_in_js_exclusions'])) {
            $this->built_in_regex_js_exclusions = '/' . implode('|',
                    $this->built_in_regex_js_exclusion_patterns) . '/ui';
        }
        # URI Exclusions; i.e., Exclude from Everything (If Applicable)
        // remove
        # Automatic APM Exclusions; i.e., Auto-Exclude Features (If Applicable)
        if (!isset($this->options['amp_exclusions_enable'])) {
            $this->options['amp_exclusions_enable'] = true;
        }
    }

    public function getOption($key, $config = array(), $default = null, $skipEmpty = false)
    {
        return $this->Compressor->getOption($key, $config, $default, $skipEmpty);
    }

    public function getBaseUrl()
    {
        return $this->currentUrlScheme() . '://' . $this->currentUrlHost();
    }

    public function getBasePath()
    {
        return $this->modx->getOption('base_path', null, MODX_BASE_PATH);
    }

    public function getCssCode($html)
    {
        $code = '';
        $baseUrl = $this->getBaseUrl();
        $basePath = $this->getBasePath();
        preg_match_all("#<link[^>]*rel=[\"\']stylesheet[\"\'][^>]*href=[\"\']([^>\"\']*)[\"\'].*?>#i", $html, $matches);
        foreach ($matches[1] as $path) {
            $path = str_replace($baseUrl, '', $path);
            $path = $basePath . trim($path, '/');

            if ($css = file_get_contents($path)) {
                $css = preg_replace("'<html[^>]*?>.*?</html>'si", "", $css);
                $code .= $css;
            }
            else {
                $this->modx->log(modX::LOG_LEVEL_ERROR, '[' . __CLASS__ . ']' . sprintf('Unable to get file: `%1$s`.', $path));
            }
        }

        return trim($code);
    }

    public function getJsCode($html)
    {
        $code = '';
        $baseUrl = $this->getBaseUrl();
        $basePath = $this->getBasePath();
        preg_match_all("#<script[^>]*type=[\"\']text/javascript[\"\'][^>]*src=[\"\']([^>\"\']*)[\"\'].*?>#i", $html,
            $matches);
        foreach ($matches[1] as $path) {
            $path = str_replace($baseUrl, '', $path);
            $path = $basePath . trim($path, '/');

            if ($js = file_get_contents($path)) {
                $js = preg_replace("'<html[^>]*?>.*?</html>'si", "", $js);
                $code .= $js;
            }
            else {
                $this->modx->log(modX::LOG_LEVEL_ERROR, '[' . __CLASS__ . ']' . sprintf('Unable to get file: `%1$s`.', $path));
            }
        }

        return trim($code);
    }

    public function compress($input)
    {
        if (!($input = trim((string)$input))) {
            return $input; // Nothing to do.
        }
        if (mb_stripos($input, '</html>') === false) {
            return $input; // Not an HTML doc.
        }

        if (($timing = !empty($this->options['timing']))) {
            $time = microtime(true);
        }

        $html = &$input; // Raw HTML.
        $is_valid_utf8 = $this->isValidUtf8($html);
        if ($is_valid_utf8) { // Must have valid UTF-8.
            if (!empty($this->options['amp_exclusions_enable']) && $this->isDocAmpd($html)) {
                $this->options['compress_combine_head_body_css'] = false;
                $this->options['compress_combine_head_js'] = false;
                $this->options['compress_combine_footer_js'] = false;
                $this->options['compress_combine_footer_css'] = false;
                $this->options['compress_combine_remote_css_js'] = false;
            }// This auto-enables AMP compatibility.
            $html = $this->tokenizeGlobalExclusions($html);
            //$html = $this->maybeCompressCombineHeadBodyCss($html);
            $html = $this->maybeCompressCombineHeadCss($html); // +
            $html = $this->maybeCompressCombineHeadJs($html);
            $html = $this->maybeCompressCombineFooterCss($html); // +
            $html = $this->maybeCompressCombineFooterJs($html);
            $html = $this->maybeCompressInlineJsCode($html);
            $html = $this->maybeCompressInlineJsonCode($html);
            $html = $this->restoreGlobalExclusions($html);
            $html = $this->maybeCompressHtmlCode($html);
        }

        if ($timing && !empty($time)) {
            $time = number_format(microtime(true) - $time, 5, '.', '');
            $html .= "\n\n" . '<!-- ' . sprintf(
                    '%1$s took %2$s seconds',
                    htmlspecialchars($this->product_title, ENT_NOQUOTES, 'UTF-8'),
                    htmlspecialchars($time, ENT_NOQUOTES, 'UTF-8')
                ) . ' -->';
        }

        return $html; // HTML markup.
    }

    protected function getFooterCssFrag($html)
    {
        if (!($html = (string)$html)) {
            return []; // Nothing to do.
        }
        if (preg_match('/(?P<all>(?P<open_tag>\<\!\-\-\s*footer[\s_\-]+css\s*\-\-\>)(?P<contents>.*?)(?P<closing_tag>(?P=open_tag)))/uis',
            $html, $head_frag)) {
            return $this->removeNumericKeysDeep($head_frag);
        }

        return [];
    }

    protected function maybeCompressCombineHeadCss($html)
    {
        $html = (string)$html; // Force string value.

        if (isset($this->options['compress_combine_head_body_css'])) {
            if (!$this->options['compress_combine_head_body_css']) {
                $disabled = true; // Disabled flag.
            }
        }

        $inline = true;
        if (isset($this->options['compress_combine_head_css_inline'])) {
            if (!$this->options['compress_combine_head_css_inline']) {
                $inline = false; // Disabled flag.
            }
        }

        if (!$html || !empty($disabled)) {
            goto finale; // Nothing to do.
        }

        if (($head_frag = $this->getHeadFrag($html)) /* No need to get the HTML frag here; we're operating on the `<head>` only. */) {
            if (($css_tag_frags = $this->getCssTagFrags($head_frag)) && ($css_parts = $this->compileCssTagFragsIntoParts($css_tag_frags, 'head'))) {
                $css_tag_frags_all_compiled = $this->compileKeyElementsDeep($css_tag_frags, 'all');
                $html = $this->replaceOnce($head_frag['all'], '%%htmlc-head%%', $html);
                $html = $this->replaceOnce($css_tag_frags_all_compiled, '', $html);
                $cleaned_head_contents = $this->replaceOnce($css_tag_frags_all_compiled, '', $head_frag['contents']);
                $cleaned_head_contents = $this->cleanupSelfClosingHtmlTagLines($cleaned_head_contents);

                $compressed_css_tags = []; // Initialize.

                foreach ($css_parts as $_css_part) {
                    if (isset($_css_part['exclude_frag'], $css_tag_frags[$_css_part['exclude_frag']]['all'])) {
                        $compressed_css_tags[] = $css_tag_frags[$_css_part['exclude_frag']]['all'];
                    } else {
                        $compressed_css_tags[] = $_css_part['tag'];
                    }
                } // unset($_css_part); // Housekeeping.

                // get css code
                if (!empty($inline)) {
                    $code = '';
                    foreach ($compressed_css_tags as $compressed_css_tag) {
                        $code .= $this->getCssCode($compressed_css_tag);
                    }
                    $compressed_css_tags = "";
                    if (!empty($code)) {
                        $compressed_css_tags = "<style type=\"text/css\">" . $code . "</style>";
                    }
                } else {
                    $compressed_css_tags = implode("\n", $compressed_css_tags);
                }

                $compressed_head_parts = [
                    $head_frag['open_tag'],
                    $cleaned_head_contents,
                    $compressed_css_tags,
                    $head_frag['closing_tag'],
                ];

                $html = $this->replaceOnce('%%htmlc-head%%', implode("\n", $compressed_head_parts), $html);
            }
        }
        finale: // Target point; finale/return value.

        if ($html) {
            $html = trim($html);
        } // Trim it up now!

        return $html; // With possible compression having been applied here.
    }

    protected function maybeCompressCombineHeadBodyCss($html) /// REMOVE!!!
    {
        $html = (string)$html; // Force string value.

        if (isset($this->options['compress_combine_head_body_css'])) {
            if (!$this->options['compress_combine_head_body_css']) {
                $disabled = true; // Disabled flag.
            }
        }

        $inline = true;
        if (isset($this->options['compress_combine_head_css_inline'])) {
            if (!$this->options['compress_combine_head_css_inline']) {
                $inline = false; // Disabled flag.
            }
        }

        if (!$html || !empty($disabled)) {
            goto finale; // Nothing to do.
        }

        if (($html_frag = $this->getHtmlFrag($html)) && ($head_frag = $this->getHeadFrag($html))) {
            if (($css_tag_frags = $this->getCssTagFrags($html_frag)) && ($css_parts = $this->compileCssTagFragsIntoParts($css_tag_frags, 'head'))) {
                $css_tag_frags_all_compiled = $this->compileKeyElementsDeep($css_tag_frags, 'all');
                $html = $this->replaceOnce($head_frag['all'], '%%htmlc-head%%', $html);
                $html = $this->replaceOnce($css_tag_frags_all_compiled, '', $html);
                $cleaned_head_contents = $this->replaceOnce($css_tag_frags_all_compiled, '', $head_frag['contents']);
                $cleaned_head_contents = $this->cleanupSelfClosingHtmlTagLines($cleaned_head_contents);

                $compressed_css_tags = []; // Initialize.

                foreach ($css_parts as $_css_part) {
                    if (isset($_css_part['exclude_frag'], $css_tag_frags[$_css_part['exclude_frag']]['all'])) {
                        $compressed_css_tags[] = $css_tag_frags[$_css_part['exclude_frag']]['all'];
                    } else {
                        $compressed_css_tags[] = $_css_part['tag'];
                    }
                } // unset($_css_part); // Housekeeping.

                // get css code
                if (!empty($inline)) {
                    $code = '';
                    foreach ($compressed_css_tags as $compressed_css_tag) {
                        $code .= $this->getCssCode($compressed_css_tag);
                    }
                    $compressed_css_tags = "";
                    if (!empty($code)) {
                        $compressed_css_tags = "<style type=\"text/css\">" . $code . "</style>";
                    }
                } else {
                    $compressed_css_tags = implode("\n", $compressed_css_tags);
                }

                $compressed_head_parts = [
                    $head_frag['open_tag'],
                    $cleaned_head_contents,
                    $compressed_css_tags,
                    $head_frag['closing_tag'],
                ];

                $html = $this->replaceOnce('%%htmlc-head%%', implode("\n", $compressed_head_parts), $html);
            }
        }
        finale: // Target point; finale/return value.

        if ($html) {
            $html = trim($html);
        } // Trim it up now!

        return $html; // With possible compression having been applied here.
    }

    protected function maybeCompressCombineFooterCss($html)
    {
        $html = (string)$html; // Force string value.

        if (isset($this->options['compress_combine_footer_css'])) {
            if (!$this->options['compress_combine_footer_css']) {
                $disabled = true; // Disabled flag.
            }
        }

        $inline = true;
        if (isset($this->options['compress_combine_footer_css_inline'])) {
            if (!$this->options['compress_combine_footer_css_inline']) {
                $inline = false; // Disabled flag.
            }
        }

        if (!$html || !empty($disabled)) {
            goto finale; // Nothing to do.
        }
        if (($footer_css_frag = $this->getFooterCssFrag($html)) /* e.g. <!-- footer-css --><!-- footer-css --> */) {
            if (($css_tag_frags = $this->getCssTagFrags($footer_css_frag)) && ($css_parts = $this->compileCssTagFragsIntoParts($css_tag_frags, 'foot'))) {
                $css_tag_frags_all_compiled = $this->compileKeyElementsDeep($css_tag_frags, 'all');
                $html = $this->replaceOnce($footer_css_frag['all'], '%%htmlc-footer-css%%', $html);
                $cleaned_footer_css = $this->replaceOnce($css_tag_frags_all_compiled, '',
                    $footer_css_frag['contents']);

                $compressed_css_tags = []; // Initialize.

                foreach ($css_parts as $_css_part) {
                    if (isset($_css_part['exclude_frag'], $css_tag_frags[$_css_part['exclude_frag']]['all'])) {
                        $compressed_css_tags[] = $css_tag_frags[$_css_part['exclude_frag']]['all'];
                    } else {
                        $compressed_css_tags[] = $_css_part['tag'];
                    }
                } // unset($_js_part); // Housekeeping.

                // get css code
                if (!empty($inline)) {
                    $code = '';
                    foreach ($compressed_css_tags as $compressed_css_tag) {
                        $code .= $this->getCssCode($compressed_css_tag);
                    }
                    $compressed_css_tags = "";
                    if (!empty($code)) {
                        $compressed_css_tags = "<style type=\"text/css\">" . $code . "</style>";
                    }
                } else {
                    $compressed_css_tags = implode("\n", $compressed_css_tags);
                }

                $compressed_footer_css_parts = [
                    $footer_css_frag['open_tag'],
                    $cleaned_footer_css,
                    $compressed_css_tags,
                    $footer_css_frag['closing_tag'],
                ];
                $html = $this->replaceOnce('%%htmlc-footer-css%%', implode("\n", $compressed_footer_css_parts),
                    $html);

            }
        }
        finale: // Target point; finale/return value.

        if ($html) {
            $html = trim($html);
        } // Trim it up now!

        return $html; // With possible compression having been applied here.
    }


    protected function maybeCompressCombineHeadJs($html)
    {
        $html = (string)$html; // Force string value.

        if (isset($this->options['compress_combine_head_js'])) {
            if (!$this->options['compress_combine_head_js']) {
                $disabled = true; // Disabled flag.
            }
        }

        $inline = true;
        if (isset($this->options['compress_combine_head_js_inline'])) {
            if (!$this->options['compress_combine_head_js_inline']) {
                $inline = false; // Disabled flag.
            }
        }

        if (!$html || !empty($disabled)) {
            goto finale; // Nothing to do.
        }
        if (($head_frag = $this->getHeadFrag($html)) /* No need to get the HTML frag here; we're operating on the `<head>` only. */) {
            if (($js_tag_frags = $this->getJsTagFrags($head_frag)) && ($js_parts = $this->compileJsTagFragsIntoParts($js_tag_frags, 'head'))) {
                $js_tag_frags_all_compiled = $this->compileKeyElementsDeep($js_tag_frags, 'all');
                $html = $this->replaceOnce($head_frag['all'], '%%htmlc-head%%', $html);
                $cleaned_head_contents = $this->replaceOnce($js_tag_frags_all_compiled, '', $head_frag['contents']);
                $cleaned_head_contents = $this->cleanupSelfClosingHtmlTagLines($cleaned_head_contents);

                $compressed_js_tags = []; // Initialize.

                foreach ($js_parts as $_js_part) {
                    if (isset($_js_part['exclude_frag'], $js_tag_frags[$_js_part['exclude_frag']]['all'])) {
                        $compressed_js_tags[] = $js_tag_frags[$_js_part['exclude_frag']]['all'];
                    } else {
                        $compressed_js_tags[] = $_js_part['tag'];
                    }
                } // unset($_js_part); // Housekeeping.

                // get css code
                if (!empty($inline)) {
                    $code = '';
                    foreach ($compressed_js_tags as $compressed_js_tag) {
                        $code .= $this->getJsCode($compressed_js_tag);
                    }

                    $compressed_js_tags = "";
                    if (!empty($code)) {
                        $compressed_js_tags = "<script type=\"text/javascript\">" . $code . "</script>";
                    }
                } else {
                    $compressed_js_tags = implode("\n", $compressed_js_tags);

                }

                $compressed_head_parts = [
                    $head_frag['open_tag'],
                    $cleaned_head_contents,
                    $compressed_js_tags,
                    $head_frag['closing_tag'],
                ];
                $html = $this->replaceOnce('%%htmlc-head%%', implode("\n", $compressed_head_parts), $html);
            }
        }
        finale: // Target point; finale/return value.

        if ($html) {
            $html = trim($html);
        } // Trim it up now!

        return $html; // With possible compression having been applied here.
    }

    protected function maybeCompressCombineFooterJs($html)
    {
        $html = (string)$html; // Force string value.

        if (isset($this->options['compress_combine_footer_js'])) {
            if (!$this->options['compress_combine_footer_js']) {
                $disabled = true; // Disabled flag.
            }
        }
        if (!$html || !empty($disabled)) {
            goto finale; // Nothing to do.
        }
        if (($footer_scripts_frag = $this->getFooterScriptsFrag($html)) /* e.g. <!-- footer-scripts --><!-- footer-scripts --> */) {
            if (($js_tag_frags = $this->getJsTagFrags($footer_scripts_frag)) && ($js_parts = $this->compileJsTagFragsIntoParts($js_tag_frags, 'foot', true))) {
                $js_tag_frags_all_compiled = $this->compileKeyElementsDeep($js_tag_frags, 'all');
                $html = $this->replaceOnce($footer_scripts_frag['all'], '%%htmlc-footer-scripts%%', $html);
                $cleaned_footer_scripts = $this->replaceOnce($js_tag_frags_all_compiled, '', $footer_scripts_frag['contents']);

                $compressed_js_tags = []; // Initialize.

                foreach ($js_parts as $_js_part) {
                    if (isset($_js_part['exclude_frag'], $js_tag_frags[$_js_part['exclude_frag']]['all'])) {
                        $compressed_js_tags[] = $js_tag_frags[$_js_part['exclude_frag']]['all'];
                    } else {
                        $compressed_js_tags[] = $_js_part['tag'];
                    }
                } // unset($_js_part); // Housekeeping.

                $compressed_js_tags = implode("\n", $compressed_js_tags);
                $compressed_footer_script_parts = [$footer_scripts_frag['open_tag'], $cleaned_footer_scripts, $compressed_js_tags, $footer_scripts_frag['closing_tag']];
                $html = $this->replaceOnce('%%htmlc-footer-scripts%%', implode("\n", $compressed_footer_script_parts), $html);

            }
        }
        finale: // Target point; finale/return value.

        if ($html) {
            $html = trim($html);
        } // Trim it up now!

        return $html; // With possible compression having been applied here.
    }

    public function compileJsTagFragsIntoParts(array $js_tag_frags, $for, $defer = false)
    {
        $for = (string)$for; // Force string.
        $js_parts = []; // Initialize.
        $js_parts_checksum = ''; // Initialize.

        if (!$js_tag_frags) {
            goto finale; // Nothing to do.
        }
        $js_parts_checksum = $this->getTagFragsChecksum($js_tag_frags);
        $public_cache_dir = $this->cacheDir($this::DIR_PUBLIC_TYPE, $js_parts_checksum);
        $private_cache_dir = $this->cacheDir($this::DIR_PRIVATE_TYPE, $js_parts_checksum);
        $public_cache_dir_url = $this->cacheDirUrl($this::DIR_PUBLIC_TYPE, $js_parts_checksum);

        $cache_parts_file = $js_parts_checksum . '-compressor-parts.js-cache';
        $cache_parts_file_path = $private_cache_dir . '/' . $cache_parts_file;
        $cache_parts_file_path_tmp = $cache_parts_file_path . '.' . uniqid('', true) . '.tmp';
        // Cache file creation is atomic; i.e. tmp file w/ rename.

        $cache_part_file = '%%code-checksum%%-compressor-part.js';
        $cache_part_file_path = $public_cache_dir . '/' . $cache_part_file;
        $cache_part_file_url = $public_cache_dir_url . '/' . $cache_part_file;

        if (is_file($cache_parts_file_path) && filemtime($cache_parts_file_path) > strtotime('-' . $this->cache_expiration_time)) {
            if (is_array($cached_parts = unserialize(file_get_contents($cache_parts_file_path)))) {
                $js_parts = $cached_parts; // Use cached parts.
                goto finale; // Using the cache; we're all done here.
            }
        }
        $_js_part = 0; // Initialize part counter.

        foreach ($js_tag_frags as $_js_tag_frag_pos => $_js_tag_frag) {
            if ($_js_tag_frag['exclude']) {
                if ($_js_tag_frag['script_src'] || $_js_tag_frag['script_js'] || $_js_tag_frag['script_json']) {
                    if ($js_parts) {
                        ++$_js_part; // Starts new part.
                    }
                    $js_parts[$_js_part]['tag'] = '';
                    $js_parts[$_js_part]['exclude_frag'] = $_js_tag_frag_pos;
                    ++$_js_part; // Always indicates a new part in the next iteration.
                }
            } else if ($_js_tag_frag['script_src']) {
                if (($_js_tag_frag['script_src'] = $this->resolveRelativeUrl($_js_tag_frag['script_src']))) {
                    if (($_js_code = $this->stripUtf8Bom($this->mustGetUrl($_js_tag_frag['script_src'])))) {
                        $_js_code = rtrim($_js_code, ';') . ';';

                        if ($_js_code) {
                            if (!empty($js_parts[$_js_part]['code'])) {
                                $js_parts[$_js_part]['code'] .= "\n\n" . $_js_code;
                            } else {
                                $js_parts[$_js_part]['code'] = $_js_code;
                            }
                        }
                    }
                }
            } else if ($_js_tag_frag['script_js']) {
                $_js_code = $_js_tag_frag['script_js'];
                $_js_code = $this->stripUtf8Bom($_js_code);
                $_js_code = rtrim($_js_code, ';') . ';';

                if ($_js_code) {
                    if (!empty($js_parts[$_js_part]['code'])) {
                        $js_parts[$_js_part]['code'] .= "\n\n" . $_js_code;
                    } else {
                        $js_parts[$_js_part]['code'] = $_js_code;
                    }
                }
            } else if ($_js_tag_frag['script_json']) {
                if ($js_parts) {
                    ++$_js_part; // Starts new part.
                }
                $js_parts[$_js_part]['tag'] = $_js_tag_frag['all'];
                ++$_js_part; // Always indicates a new part in the next iteration.
            }
        } // unset($_js_part, $_js_tag_frag_pos, $_js_tag_frag, $_js_code);

        foreach (array_keys($js_parts = array_values($js_parts)) as $_js_part) {
            if (!isset($js_parts[$_js_part]['exclude_frag']) && !empty($js_parts[$_js_part]['code'])) {
                $_js_code = $js_parts[$_js_part]['code'];
                $_js_code_cs = md5($_js_code); // Before compression.
                $_js_code = $this->maybeCompressJsCode($_js_code);

                $_js_code_path = str_replace('%%code-checksum%%', $_js_code_cs, $cache_part_file_path);
                $_js_code_url = str_replace('%%code-checksum%%', $_js_code_cs, $cache_part_file_url);
                $_js_code_url = $this->hook_api->applyFilters('part_url', $_js_code_url, $for);
                $_js_code_path_tmp = $_js_code_path . '.' . uniqid('', true) . '.tmp';
                // Cache file creation is atomic; e.g. tmp file w/ rename.

                if (!(file_put_contents($_js_code_path_tmp, $_js_code) && rename($_js_code_path_tmp, $_js_code_path))) {
                    throw new \Exception(sprintf('Unable to cache JS code file: `%1$s`.', $_js_code_path));
                }

                if (empty($defer)) {
                    $js_parts[$_js_part]['tag'] = '<script type="text/javascript" src="' . htmlspecialchars($_js_code_url, ENT_QUOTES, 'UTF-8') . '"></script>';
                } else {
                    $js_parts[$_js_part]['tag'] = '<script type="text/javascript" src="' . htmlspecialchars($_js_code_url, ENT_QUOTES, 'UTF-8') . '" defer></script>';
                }

                unset($js_parts[$_js_part]['code']); // Ditch this; no need to cache this code too.
            }
        } // unset($_js_part, $_js_code, $_js_code_cs, $_js_code_path, $_js_code_path_tmp, $_js_code_url);

        if (!(file_put_contents($cache_parts_file_path_tmp, serialize($js_parts)) && rename($cache_parts_file_path_tmp, $cache_parts_file_path))) {
            throw new \Exception(sprintf('Unable to cache JS parts into: `%1$s`.', $cache_parts_file_path));
        }
        finale: // Target point; finale/return value.

        return $js_parts;
    }

    public function compileCssTagFragsIntoParts(array $css_tag_frags, $for)
    {
        return parent::compileCssTagFragsIntoParts($css_tag_frags, $for);
    }

    protected function compressHtml($html)
    {
        if (!($html = (string)$html)) {
            return $html; // Nothing to do.
        }

        $static = &static::$static[__FUNCTION__];

        if (!isset($static['preservations'])) {
            $static['preservations'] = [
                'noindex_tags'            => '\<!--noindex-->(.*)<!--\/noindex-->',
                'nocompress_tags'         => '\<!--nocompress-->(.*)<!--\/nocompress-->',
                'special_tags'            => '\<(pre|code|script|style|textarea)(?:\s+[^>]*?)?\>.*?\<\/\\2>',
                'ie_conditional_comments' => '\<\![^[>]*?\[if\W[^\]]*?\][^>]*?\>.*?\<\![^[>]*?\[endif\][^>]*?\>',
                'special_attributes'      => '\s(?:style|on[a-z]+)\s*\=\s*(["\']).*?\\3',
            ];
            $static['preservations'] = '/(?P<preservation>' . implode('|', $static['preservations']) . ')/uis';

            if (preg_match_all($static['preservations'], $html, $preservation_matches, PREG_SET_ORDER)) {
                foreach ($preservation_matches as $_preservation_match_key => $_preservation_match) {
                    $preservations[] = $_preservation_match['preservation'];
                    $preservation_placeholders[] = '%%minify-html-' . $_preservation_match_key . '%%';
                }
                if (isset($preservations, $preservation_placeholders)) {
                    $html = $this->replaceOnce($preservations, $preservation_placeholders, $html);
                }
            }
        }

        if (!isset($static['compressions'], $static['compress_with'])) {

            $static['compressions']['remove_html_comments'] = '/\<\!\-{2}.*?\-{2}\>/uis';
            $static['compress_with']['remove_html_comments'] = '';

            $static['compressions']['remove_extra_whitespace'] = '/\s+/u';
            $static['compress_with']['remove_extra_whitespace'] = ' ';

            $static['compressions']['remove_extra_whitespace_in_self_closing_tags'] = '/\s+\/\>/u';
            $static['compress_with']['remove_extra_whitespace_in_self_closing_tags'] = '/>';
        }
        $html = preg_replace($static['compressions'], $static['compress_with'], $html);

        if (isset($preservations, $preservation_placeholders)) {
            $html = $this->replaceOnce($preservation_placeholders, $preservations, $html);
        }

        return $html ? trim($html) : $html;
    }

    protected function mustGetUrl($url)
    {
        $url = (string)$url; // Force string value.
        $response = $this->remote($url, '', 3, 10, [], '', true, true);

        if ($response['code'] >= 400 OR empty($response['code'])) {
            $this->modx->log(modX::LOG_LEVEL_ERROR,
                '[' . __CLASS__ . ']' . sprintf('HTTP response code: `%1$s`. Unable to get URL: `%2$s`.',
                    $response['code'], $url));

            return '';
        }

        return $response['body'];
    }

    /***********************/
    protected function currentUrlUri()
    {
        return '';
    }

}

class CompressorException extends Exception
{
}
