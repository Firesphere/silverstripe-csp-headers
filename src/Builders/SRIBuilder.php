<?php


namespace Firesphere\CSPHeaders\Builders;

use Exception;
use Firesphere\CSPHeaders\Extensions\ControllerCSPExtension;
use Firesphere\CSPHeaders\Models\SRI;
use Firesphere\CSPHeaders\View\CSPBackend;
use GuzzleHttp\Client;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Security;

class SRIBuilder
{
    /**
     * @var Client
     */
    protected $client;

    public function __construct()
    {
        $this->setClient(new Client());
    }

    /**
     * @param $file
     * @param array $htmlAttributes
     * @return array
     * @throws ValidationException
     * @throws Exception
     */
    public function buildSRI($file, array $htmlAttributes): array
    {
        /** @var SRI|null $sri */
        $sri = SRI::get()->filter(['File' => $file])->first();
        // Create on first time it's run, or if it's been deleted because the file has changed, known to the admin
        if (!$sri || !$sri->isInDB()) {
            $sri = SRI::create(['File' => $file]);
            $sri->write();
        }
        if ($this->shouldUpdateSRI()) {
            $sri->forceChange();
            $sri->write();
        }

        $request = Controller::curr()->getRequest();
        $cookieSet = ControllerCSPExtension::checkCookie($request);

        // Don't write integrity in dev, it's breaking build scripts
        if ($sri->SRI && (Director::isLive() || $cookieSet)) {
            $htmlAttributes['integrity'] = sprintf('%s-%s', CSPBackend::SHA384, $sri->SRI);
            $htmlAttributes['crossorigin'] = Director::is_site_url($file) ? '' : 'anonymous';
        }

        return $htmlAttributes;
    }

    /**
     * @return bool
     */
    private function shouldUpdateSRI(): bool
    {
        // Is updateSRI requested?
        return (Controller::curr()->getRequest()->getVar('updatesri') &&
            // Does the user have the powers
            ((Security::getCurrentUser() && Security::getCurrentUser()->inGroup('administrators')) ||
                // OR the site is in dev mode
                Director::isDev()));
    }

    /**
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * @param Client $client
     */
    public function setClient(Client $client): void
    {
        $this->client = $client;
    }
}
