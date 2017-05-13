<?php

/** @var array $scriptProperties */
/** @var Compressor $Compressor */
if (!$Compressor = $modx->getService('compressor', 'Compressor', $modx->getOption('compressor_core_path', null,
        $modx->getOption('core_path') . 'components/compressor/') . 'model/compressor/',
    $scriptProperties)
) {
    return 'Could not load Compressor class!';
}
//$Compressor->initialize($modx->context->key, $scriptProperties);


$html = <<<HTML
<html>
    <head>
        <title>Test</title>
        <script type="text/javascript">
            var test = {
                hello: 'hello',
                world: 'world'
            };
        </script>
        <script type="application/ld+json">
            {
              "@context": "http://schema.org",
              "@type": "Person",
              "name": "John Doe",
              "jobTitle": "Graduate research assistant",
              "affiliation": "University of Dreams",
              "additionalName": "Johnny",
              "url": "http://www.example.com",
              "address": {
                "@type": "PostalAddress",
                "streetAddress": "1234 Peach Drive",
                "addressLocality": "Wonderland",
                "addressRegion": "Georgia"
              }
            }
        </script>
        <style type="text/css">
            #test > .test {
                font-size: 100%;
                color: #FFFFFF;
            }
        </style>
    </head>
    <body>
        Testing one, two, three.
    </body>
</html>
HTML;

$options = array();

$html = $Compressor->getHtmlCompress($html, $options);

var_dump($html);