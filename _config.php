<?php

use Firesphere\CSPHeaders\View\CSPBackend;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\View\Requirements;

// Set the requirements update
$backend = Injector::inst()->get(CSPBackend::class);
Requirements::set_backend($backend);
