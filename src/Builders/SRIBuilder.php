<?php


namespace Firesphere\CSPHeaders\Builders;

use Exception;
use Firesphere\CSPHeaders\Extensions\ControllerCSPExtension;
use Firesphere\CSPHeaders\Models\SRI;
use Firesphere\CSPHeaders\View\CSPBackend;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Security;

class SRIBuilder
{
    use Configurable;

    /**
     * An array of javascript or css files to skip applying SRI to.
     * Files only need to start with the configured value, e.g. if this array contains
     * 'https://example.com' then all scripts from that site will be skipped.
     * @var array
     */
    private static $skip_domains = [];

    /**
     * @param $file
     * @param array $htmlAttributes
     * @return array
     * @throws ValidationException
     * @throws Exception
     */
    public function buildSRI($file, array $htmlAttributes): array
    {
        $skipFiles = $this->config()->get('skip_domains') ?? [];
        foreach ($skipFiles as $filename) {
            if (strpos($file, $filename) === 0) {
                return $htmlAttributes;
            }
        }
        // If an update is needed, set the SRI to null
        $sri = SRI::findOrCreate($file);
        if ($this->shouldUpdateSRI()) {
            $sri->SRI = null;
            $sri->forceChange();
            $sri->write();
        }

        if (!self::$sri) {
            self::$sri = ArrayList::create(SRI::get()->toArray());
        }
        $sri = self::$sri->find('File', $file);
        if (!$sri) {
            $sri = SRI::findOrCreate($file);
            self::$sri->push($sri);
        }

        // To skip applying SRI for an environment use yml to disable jsSRI or cssSRI within chosen envs
        if ($sri->SRI) {
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
}
