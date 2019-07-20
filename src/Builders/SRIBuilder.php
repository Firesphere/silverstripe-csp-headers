<?php


namespace Firesphere\CSPHeaders\Builders;

use Exception;
use Firesphere\CSPHeaders\Extensions\ControllerCSPExtension;
use Firesphere\CSPHeaders\Models\SRI;
use Firesphere\CSPHeaders\View\CSPBackend;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
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
        $this->client = new Client();
    }

    /**
     * @param $file
     * @param array $htmlAttributes
     * @return array
     * @throws GuzzleException
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
        }
        if (!$sri->SRI || $this->canUpdateSRI()) {
            // Since this is the CSP Backend, an SRI for external files is automatically created
            $location = $file;

            if (!Director::is_site_url($file)) {
                $result = $this->getClient()->request('GET', $location);
                $body = $result->getBody()->getContents();
            } else {
                $body = file_get_contents(Director::baseFolder() . '/' . $location);
            }
            $hash = hash(CSPBackend::SHA384, $body, true);

            $sri->update([
                'SRI' => base64_encode($hash)
            ]);
        }

        $sri->write();

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
    private function canUpdateSRI(): bool
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
