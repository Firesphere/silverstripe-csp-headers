<?php

namespace Firesphere\CSPHeaders\Helpers;

use Firesphere\CSPHeaders\View\CSPBackend;
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
     * @return string|void
     */
    public static function toYml($response, $return = false)
    {
        $cspHeader = $response->getHeader('content-security-policy') ?? $response->getHeader('content-security-policy-report-only');

        $asArray = explode(';', $cspHeader);
        $arrayHeader = ['enabled' => true];

        foreach ($asArray as $headerPart) {
            if (empty($headerPart)) continue;
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
            foreach ($parts as $partkey => &$part) {
                $part = trim(str_replace(['"', "'"], '', $part));
                $asUrl = str_replace('*', 'www', $part); // little hack for `https://*.google.com` etc.
                if (!filter_var($asUrl, FILTER_VALIDATE_URL) && $part !== 'none') {
                    if (in_array($part, $allowedKeys)) {
                        $part = rtrim($part, ':');
                        $arrayHeader[$key][$part] = true;
                    }
                    unset($parts[$partkey]);
                }
            }
            rsort($parts);
            $arrayHeader[$key]['allow'] = $parts;
        }
        $arrayHeader['default-src']['self'] = true; // Always allow self
        $data = [
            CSPBackend::class => [
                'csp_config' => $arrayHeader
            ]
        ];
        $yaml = Yaml::dump($data, 5, 2);
        if ($return) {
            return $yaml;
        }
        print_r("<pre>");
        print_r($yaml);
        print_r("</pre>");
        exit;
    }
}
