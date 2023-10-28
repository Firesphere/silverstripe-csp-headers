# Security headers for Silverstripe

# License

[LICENSE.md](GPL v3.0 or later)
### Code status
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Firesphere/silverstripe-csp-headers/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Firesphere/silverstripe-csp-headers/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/Firesphere/silverstripe-csp-headers/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/Firesphere/silverstripe-csp-headers/?branch=master)
[![CircleCI](https://circleci.com/gh/Firesphere/silverstripe-csp-headers.svg?style=svg)](https://circleci.com/gh/Firesphere/silverstripe-csp-headers)
[![Maintainability](https://api.codeclimate.com/v1/badges/8a4483b471112003ccaf/maintainability)](https://codeclimate.com/github/Firesphere/silverstripe-csp-headers/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/8a4483b471112003ccaf/test_coverage)](https://codeclimate.com/github/Firesphere/silverstripe-csp-headers/test_coverage)
[![codecov](https://codecov.io/gh/Firesphere/silverstripe-csp-headers/branch/master/graph/badge.svg)](https://codecov.io/gh/Firesphere/silverstripe-csp-headers)

Adds CSP headers to your request, based on configuration in a yml file.

[Setting up a report-uri account is free and easy](https://report-uri.com)

## Requirements

SilverStripe Framework 4.x+
PHP 8.0+

## Installation

`composer require firesphere/cspheaders`

## Configuration and usage

[See the documentation](docs/readme.md)

# WARNING

When using this module and have CSS hashes or nonces enabled, any inline styles declared on HTML Elements themselves will not work anymore.

To enable or disable inline javascripts or css, set the appropriate flag (`allow-inline`) in your yml config.

Same goes for javascripts. Javascripts specifically should live either in a separate file, or be added using `Requirementns::customScripts()`

Default for css is therefore `false`, javascript however defaults to `true` for security reasons.

# CDN Providers

When using Incapsula or Imperva (and potentially other CDN providers),
your CSS and JavaScripts may be altered by the CDN, and therefore never compute correctly.

The only solution is to disable the SRI's for css and javascript on these providers.

## .htaccess

Any header set in the `.htaccess`, Apache `site.conf` or `nginx.conf` files will override the headers
set by this module.


# Did you read this entire readme? You rock!

Pictured below is a cow, just for you.
```

               /( ,,,,, )\
              _\,;;;;;;;,/_
           .-"; ;;;;;;;;; ;"-.
           '.__/`_ / \ _`\__.'
              | (')| |(') |
              | .--' '--. |
              |/ o     o \|
              |           |
             / \ _..=.._ / \
            /:. '._____.'   \
           ;::'    / \      .;
           |     _|_ _|_   ::|
         .-|     '==o=='    '|-.
        /  |  . /       \    |  \
        |  | ::|         |   | .|
        |  (  ')         (.  )::|
        |: |   |;  U U  ;|:: | `|
        |' |   | \ U U / |'  |  |
        ##V|   |_/`"""`\_|   |V##
           ##V##         ##V##
```
