# CSP Headers for SilverStripe

### Code status
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Firesphere/silverstripe-csp-headers/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Firesphere/silverstripe-csp-headers/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/Firesphere/silverstripe-csp-headers/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/Firesphere/silverstripe-csp-headers/?branch=master)
[![CircleCI](https://circleci.com/gh/Firesphere/silverstripe-csp-headers.svg?style=svg)](https://circleci.com/gh/Firesphere/silverstripe-csp-headers)
[![Maintainability](https://api.codeclimate.com/v1/badges/8a4483b471112003ccaf/maintainability)](https://codeclimate.com/github/Firesphere/silverstripe-csp-headers/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/8a4483b471112003ccaf/test_coverage)](https://codeclimate.com/github/Firesphere/silverstripe-csp-headers/test_coverage)
[![codecov](https://codecov.io/gh/Firesphere/silverstripe-csp-headers/branch/master/graph/badge.svg)](https://codecov.io/gh/Firesphere/silverstripe-csp-headers)

Adds CSP headers to your request, based on configuration in a yml file.

[Setting up a report-uri account is free and easy](https://report-uri.com)

# Requirements

SilverStripe Framework 4.x+
PHP 8.0+

# Installation

`composer require firesphere/cspheaders`

# WARNING

When using this module and have CSS hashes or nonces enabled, any inline styles declared on HTML Elements themselves will not work anymore.

To enable or disable inline javascripts or css, set the appropriate flag (`allow-inline`) in your yml config.

Same goes for javascripts. Javascripts specifically should live either in a separate file, or be added using `Requirementns::customScripts()`

Default for css is therefore `false`, javascript however defaults to `true` for security reasons.

# Configuration

```yaml

Firesphere\CSPHeaders\View\CSPBackend:
  csp_config:
    enabled: true
    report-only: false
    report-uri: "https://your.report-uri.com/here/with/path"
    base-uri:
      allow: []
      self: true
    default-src: []
    frame-src:
      allow:
        - youtube.com
      self: false
    connect-src:
      allow: []
      self: true
    font-src:
      allow: []
      self: true
    form-action:
      self: true
    frame-ancestors: []
    img-src:
      allow: []
      blob: true
      self: true
      data: true
    media-src:
      allow:
        - youtube.com
        - vimeo.com
    object-src: []
    plugin-types: []
    script-src:
      allow: []
      self: true
      unsafe-inline: false
      unsafe-eval: false
    style-src:
      self: true
      unsafe-inline: true
    upgrade-insecure-requests: true
  jsSRI: true
  cssSRI: false
  useNonce: false
---
Only:
  environment: 'dev'
---
Firesphere\CSPHeaders\View\CSPBackend:
  csp_config:
    report-only: true

```

## legacy_headers

Setting the legacy headers flag to true, will add the IE legacy headers like `X-XSS-PROTECTION`. Default value is false

## report_to

Not yet implemented as not all browsers support it yet

### Generating the YML

A helper class is included to take your existing headers and convert them to a workable starting point as YML.

Usage: `CSPConvertor::toYml(Controller::curr()->getResponse());`

## csp_config

Configure the allowed domains. If domains change, they need to be added programmatically. You can disable CSP output entirely for target environments via yaml e.g. to only output the headers in production you could use

```yaml
---
Except:
  environment: 'live'
---
Firesphere\CSPHeaders\View\CSPBackend:
  csp_config:
    enabled: false
```

You can also use this to skip generating SRI for JS or CSS within target environments.

### Using a nonce

In the template, you can use `$Nonce` to get the current request nonce, e.g. if you are using <script> tags in your template instead of using the Requirements API. Note that using <script> tags will not generate or output SRI.

## wizard

It's useful to only use the wizard in dev mode, to discover the URI's and sha's you need to add.
This prevents needless reports and helps you set up the wizard.

You do need to set the report-to uri to your wizard uri, otherwise the system will encounter a failure.

## forms

If you want to submit forms to a different domain, you can add the allowed domains under the forms section

## inline scripts or custom scripts

If you use the default methods provided by the `Requirements` class, the needed SHA's and SRI's are automatically calculated for you.

## Skipping SRI for some files

You can specify files or domains to skip outputting (JS or CSS) SRI for by using the `skip_domains` array. If the file URI starts with or matches a value in this array then it will be skipped. In the example below, any files fetched from https://maps.googleapis.com/ would be skipped.

```yaml
Firesphere\CSPHeaders\Builders\SRIBuilder:
  skip_domains:
    - 'https://maps.googleapis.com/'
```

# Refreshing calculated values

To force-refresh the SRI calculations, add the URL Parameter `?updatesri=true`. You need to be admin to use this.

To force the headers to be set, for testing purposes, add the URL parameter `?build-headers=true`.
To disable this again, change the `true` to `false`

To have these automatically clear out on a dev/build (useful for updating integrity hashes on production when new assets are deployed) you can enable this via the yaml below - though note this only works on Silverstripe framework 4.7+
There is also an SRI Refresh dev task which can be manually run by visiting `/dev/tasks/SRIRefreshTask` in the browser or via sake on cli.

```yaml
Firesphere\CSPHeaders\Models\SRI:
  clear_sri_on_build: true
```

This will simply delete all of the hashes and they will be recalculated the first time they are required on a page.

# .htaccess

Any header set in the `.htaccess`, Apache `site.conf` or `nginx.conf` files will override the headers
set by this module.

# Actual license

This module is published under BSD 3-clause license, although these are not in the actual classes, the license does apply:

http://www.opensource.org/licenses/BSD-3-Clause

Copyright (c) 2012-NOW(), Simon "Sphere" Erkelens

All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

    Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
    Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.


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
