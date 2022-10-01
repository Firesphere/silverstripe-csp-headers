<?php


namespace Firesphere\CSPHeaders\Builders;

use Firesphere\CSPHeaders\Extensions\ControllerCSPExtension;
use Firesphere\CSPHeaders\Interfaces\BuilderInterface;
use Firesphere\CSPHeaders\View\CSPBackend;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\ValidationException;
use SilverStripe\View\HTML;

class JSBuilder extends BaseBuilder implements BuilderInterface
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
    public function __construct(CSPBackend $backend)
    {
        $this->owner = $backend;
        $this->setSriBuilder(Injector::inst()->get(SRIBuilder::class));
    }

    /**
     * @param $file
     * @param $attributes
     * @param array $requirements
     * @param string $path
     * @return array
     * @throws ValidationException
     */
    public function buildTags($file, $attributes, array $requirements, string $path): array
    {
        // Build html attributes
        $htmlAttributes = array_merge([
            'type' => $attributes['type'] ?? 'application/javascript',
            'src'  => $path,
        ], $attributes);

        // Build SRI if it's enabled
        $request = Controller::has_curr() ? Controller::curr()->getRequest() : null;
        $cookieSet = $request ? ControllerCSPExtension::checkCookie($request) : false;
        if (CSPBackend::isJsSRI() || $cookieSet) {
            $htmlAttributes = $this->getSriBuilder()->buildSRI($file, $htmlAttributes);
        }
        // Use nonces for inlines if requested
        BaseBuilder::getNonce($htmlAttributes);
        $requirements[] = HTML::createTag('script', $htmlAttributes);

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
     * @param array $requirements
     * @return void
     */
    public function getHeadTags(array &$requirements): void
    {
        $javascript = CSPBackend::getHeadJS();
        $this->getBaseHeadTags($requirements, $javascript, 'script');
    }

    /**
     * @param array $requirements
     * @return array
     */
    public function getCustomTags($requirements = []): array
    {
        $scripts = $this->getOwner()->getCustomScripts();
        $this->getBaseCustomTags($requirements, $scripts, 'script');

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
