# CSP Headers for SilverStripe

Adds CSP headers to your request, based on configuration in a yml file.

[Setting up a report-uri account is free and easy](https://report-uri.com)

# Requirements

SilverStripe Framework 4.x
PHP 5.6+

# Installation

`composer require firesphere/cspheaders`

# WARNING

When using this module and have CSS hashes or nonces enabled, any inline styles declared on HTML Elements themselves will not work anymore.

To enable or disable inline javascripts or css, set the appropriate flag (`allow-inline`) in your yml config.

Same goes for javascripts. Javascripts specifically should live either in a separate file, or be added using `Requirementns::customScripts()`

Default for css is therefore `false`, javascript however defaults to `true` for security reasongs.

# Configuration

```yaml

Firesphere\CSPHeaders\View\CSPBackend:
  csp_config:
    report-only: false
    report-uri: "https://mydomain.report-uri.com"
    base-uri:
      allow: []
      self: true
    default-src: []
    frame-src:
      allow: []
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
    media-src: []
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

When you enable the Reporting API you will receive deprecation, intervention and crash reports from the browser. To enable this you need to set a HTTP response header with the following name and value.

## csp_config

Configure the allowed domains. If domains change, they need to be added programmatically.

### Using a nonce

If you don't want to use the script tag, use the nonce. In the template, you can use `$Nonce` to get the current request nonce, e.g. if you are using <script> tags in your template

## wizard

It's useful to only use the wizard in dev mode, to discover the URI's and sha's you need to add.
This prevents needless reports and helps you set up the wizard.

You do need to set the report-to uri to your wizard uri, otherwise the system will encounter a failure.

## forms

If you want to submit forms to a different domain, you can add the allowed domains under the forms section

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
