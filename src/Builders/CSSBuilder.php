<?php


namespace Firesphere\CSPHeaders\Builders;

use Firesphere\CSPHeaders\Interfaces\BuilderInterface;
use Firesphere\CSPHeaders\View\CSPBackend;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\ValidationException;
use SilverStripe\View\HTML;

class CSSBuilder implements BuilderInterface
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
     * @param $attributes
     * @param string $requirements
     * @param string $path
     * @return string
     * @throws ValidationException
     */
    public function buildTags($file, $attributes, string $requirements, string $path): string
    {
        $htmlAttributes = array_merge([
            'rel'  => 'stylesheet',
            'type' => 'text/css',
            'href' => $path,
        ], $attributes);

        if (CSPBackend::isCssSRI()) {
            $htmlAttributes = $this->getSriBuilder()->buildSRI($file, $htmlAttributes);
        }

        $requirements .= HTML::createTag('link', $htmlAttributes);
        $requirements .= "\n";

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
     * @param string $requirements
     * @return void
     */
    public function getHeadTags(string &$requirements): void
    {
        $js = CSPBackend::getHeadCSS();
        foreach ($js as $tag => $script) {
            $item = $js[$tag];
            $content = array_keys($item)[0];
            $options = $item[$content] ?? [];
            if (CSPBackend::isUsesNonce()) {
                $options['nonce'] = Controller::curr()->getNonce();
            }
            $requirements .= HTML::createTag(
                'script',
                $options,
                "//<![CDATA[\n{$content}\n//]]>"
            );
            $requirements .= "\n";
        }
    }


    /**
     * @param string $requirements
     * @return string
     */
    public function getCustomTags(string $requirements): string
    {
        // Literal custom CSS content
        foreach ($this->getOwner()->getCustomCSS() as $css) {
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
