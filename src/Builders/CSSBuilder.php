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

    /**
     * CSSBuilder constructor.
     * @param CSPBackend $backend
     */
    public function __construct($backend)
    {
        $this->owner = $backend;
        $this->setSriBuilder(Injector::inst()->get(SRIBuilder::class));
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
            $htmlAttributes = $this->getSriBuilder()->buildSRI($file, $htmlAttributes);
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


    /**
     * @param string $requirements
     * @return string
     */
    public function getCSSHeadTags(string $requirements): string
    {
        $options = ['type' => 'text/css'];
        foreach (CSPBackend::getHeadCSS() as $css) {
            if (CSPBackend::isUsesNonce()) {
                $options['nonce'] = Controller::curr()->getNonce();
            }
            $requirements .= HTML::createTag(
                'style',
                $options,
                "\n{$css}\n"
            );
            $requirements .= "\n";
        }

        return $requirements;
    }

    /**
     * @return SRIBuilder
     */
    public function getSriBuilder(): SRIBuilder
    {
        return $this->sriBuilder;
    }

    /**
     * @param SRIBuilder $sriBuilder
     */
    public function setSriBuilder(SRIBuilder $sriBuilder): void
    {
        $this->sriBuilder = $sriBuilder;
    }

    /**
     * @return CSPBackend
     */
    public function getOwner(): CSPBackend
    {
        return $this->owner;
    }

    /**
     * @param CSPBackend $owner
     */
    public function setOwner(CSPBackend $owner): void
    {
        $this->owner = $owner;
    }
}
