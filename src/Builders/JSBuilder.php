<?php


namespace Firesphere\CSPHeaders\Builders;

use Firesphere\CSPHeaders\Interfaces\BuilderInterface;
use Firesphere\CSPHeaders\View\CSPBackend;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\ValidationException;
use SilverStripe\View\HTML;

class JSBuilder implements BuilderInterface
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
     * JSBuilder constructor.
     * @param CSPBackend $backend
     */
    public function __construct($backend)
    {
        $this->owner = $backend;
        $this->setSriBuilder(Injector::inst()->get(SRIBuilder::class));
    }

    /**
     * @param $attributes
     * @param $file
     * @param $requirements
     * @param $path
     * @return string
     * @throws ValidationException
     */
    public function buildTags($file, $attributes, string $requirements, string $path): string
    {
        // Build html attributes
        $htmlAttributes = array_merge([
            'type' => $attributes['type'] ?? 'application/javascript',
            'src'  => $path,
        ], $attributes);

        // Build SRI if it's enabled
        if (CSPBackend::isJsSRI()) {
            $htmlAttributes = $this->getSriBuilder()->buildSRI($file, $htmlAttributes);
        }
        // Use nonces for inlines if requested
        if (CSPBackend::isUsesNonce()) {
            $htmlAttributes['nonce'] = base64_encode(Controller::curr()->getNonce());
        }

        $requirements .= HTML::createTag('script', $htmlAttributes);
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
        $javascript = CSPBackend::getHeadJS();
        foreach ($javascript as $tag => $script) {
            $item = $javascript[$tag];
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
        // Add all inline JavaScript *after* including external files they might rely on
        foreach ($this->getOwner()->getCustomScripts() as $script) {
            $options = ['type' => 'application/javascript'];
            if (CSPBackend::isUsesNonce()) {
                $options['nonce'] = Controller::curr()->getNonce();
            }
            $requirements .= HTML::createTag(
                'script',
                $options,
                "//<![CDATA[\n{$script}\n//]]>"
            );
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
