<?php

namespace Firesphere\CSPHeaders\Helpers;

use SilverStripe\Control\HTTPResponse;
use Symfony\Component\Yaml\Yaml;

/**
 * class \Firesphere\CSPHeaders\Helpers\CSPConvertor
 *
 * Helper class to convert a manually written CSP header to YML
 */
class CSPConvertor
{

    /**
     * @var string[] default values allowed
     */
    private static $non_url_defaults = [
        'self',
    ];

    /**
     * @var array[] Values for different header parts to be allowed
     */
    private static $non_url_args = [
        'script-src'      => [
            'unsafe-inline',
            'unsafe-eval',
            'unsafe-hashes',
            'strict-dynamic'
        ],
        'script-src-elem' => [
            'unsafe-inline',
            'unsafe-eval',
            'unsafe-hashes',
            'strict-dynamic'
        ],
        'script-src-attr' => [
            'unsafe-inline',
            'unsafe-eval',
            'unsafe-hashes',
            'strict-dynamic'
        ],
        'style-src'       => [
            'unsafe-inline',
        ],
        'style-src-elem'  => [
            'unsafe-inline',
        ],
        'style-src-attr'  => [
            'unsafe-inline',
        ],
        'img-src'         => [
            'data:',
            'blob:',
        ]
    ];

    /**
     * @param HTTPResponse $response
     * @return void
     */
    public static function toYml($response)
    {
        $cspHeader = $response->getHeader('content-security-policy') ?? $response->getHeader('content-security-policy-report-only');

        $asArray = explode(';', $cspHeader);
        $arrayHeader = [];
        foreach ($asArray as $headerPart) {
            $parts = explode(' ', trim($headerPart));
            $key = array_shift($parts);
            $arrayHeader[$key] = [];
            if ($key === 'report-to' || $key == 'report-uri') {
                $arrayHeader[$key] = array_shift($parts);
                continue;
            }
            if ($key === 'upgrade-insecure-requests') {
                $arrayHeader[$key] = true;
                continue;
            }
            $allowedKeys = array_merge(static::$non_url_defaults, static::$non_url_args[$key] ?? []);
            foreach ($parts as $partkey => $part) {
                $part = trim(trim($part, '"'), "'");
                $part = str_replace('*', 'www', $part); // little hack for `https://*.google.com` etc.
                if (!filter_var($part, FILTER_VALIDATE_URL) && $part !== 'none') {
                    if (in_array($part, $allowedKeys)) {
                        $arrayHeader[$key][$part] = true;
                    }
                    unset($parts[$partkey]);
                }
            }
            rsort($parts);
            $arrayHeader[$key]['allow'] = $parts;
        }
        $yaml = Yaml::dump($arrayHeader);
        print_r("<pre>");
        print_r($yaml);
        print_r("</pre>");
        exit;
    }
}
