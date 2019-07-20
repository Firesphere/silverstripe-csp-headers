<?php


namespace Firesphere\CSPHeaders\Builders;

use Firesphere\CSPHeaders\View\CSPBackend;
use GuzzleHttp\Exception\GuzzleException;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\ValidationException;
use SilverStripe\View\HTML;

class CSSBuilder
{
    /**
     * @var CSPBackend
     */
    protected $owner;
    /**
     * @var SRIBuilder
     */
    protected $sriBuilder;

    public function __construct($backend)
    {
        $this->owner = $backend;
        $this->sriBuilder = Injector::inst()->get(SRIBuilder::class);
    }

    /**
     * @param $file
     * @param $params
     * @param string $requirements
     * @param string $path
     * @return string
     * @throws GuzzleException
     * @throws ValidationException
     */
    public function buildCSSTags($file, $params, string $requirements, string $path): string
    {
        $htmlAttributes = array_merge([
            'rel'  => 'stylesheet',
            'type' => 'text/css',
            'href' => $path,
        ], $params);

        if (CSPBackend::isCssSRI()) {
            $htmlAttributes = $this->sriBuilder->buildSRI($file, $htmlAttributes);
        }

        $requirements .= HTML::createTag('link', $htmlAttributes);
        $requirements .= "\n";

        // Literal custom CSS content
        foreach ($this->owner->getCustomCSS() as $css) {
            $options = ['type' => 'text/css'];
            // Use nonces for inlines if requested
            if (CSPBackend::isUsesNonce()) {
                $options['nonce'] = base64_encode(Controller::curr()->getNonce());
            }

            $requirements .= HTML::createTag('style', $options, "\n{$css}\n");
            $requirements .= "\n";
        }

        return $requirements;
    }
}
