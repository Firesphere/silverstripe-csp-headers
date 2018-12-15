# CSP Headers for SilverStripe

Adds CSP headers to your request, based on configuration in a yml file.

Requires `martijnc/php-csp` on dev-master for support of upgrading insecure requests.

[Setting up a report-uri account is free and easy](https://report-uri.com)

# Requirements

SilverStripe Framework 4.x
PHP 5.6+

# Installation

`composer require firesphere/cspheaders`

# Configuration

```yaml

---
Name: AppCSPHeaders
After:
  - '#CSPHeaders'
---
Firesphere\CSPHeaders\View\CSPBackend:
  report_to:
    report_to_uri: 'https://myreporturi.report-uri.com/a/d/g'
    report: true
    NEL: true
  csp_config:
    report_uri: 'https://myreporturi.report-uri.com/r/d/csp/enforce'
    report_only_uri: 'https://myreporturi.report-uri.com/r/d/csp/reportOnly'
    report_only: true
    default:
      domains:
        - 'analytics.mydomain.com'
    img:
      domains:
        - 'secure.gravatar.com'
        - 'a.slack-edge.com'
        - 'avatars.slack-edge.com'
        - 'emoji.slack-edge.com'
        - 'analytics.mydomain.com'
        - 'data:'
        - 'i.ytimg.com'
    media:
      domains:
        - '*.vimeocdn.com'
        - 'player.vimeo.com'
        - 'www.youtube.com'
        - 'www.youtube-nocookie.com'
    frame:
      domains:
        - '*.vimeocdn.com'
        - 'player.vimeo.com'
        - 'www.youtube.com'
        - 'www.youtube-nocookie.com'
    style:
      domains:
        - 'self'
      allow_inline: true
    script:
      domains:
        - 'code.jquery.com'
        - 'analytics.mydomain.com'
    font:
      domains:
        - 'netdna.bootstrapcdn.com'
        - 'fonts.gstatic.com'
    form:
      domains:
        - 'self'
---
Only:
  environment: dev
---
Firesphere\CSPHeaders\View\CSPBackend:
  csp_config:
    wizard: true
    wizard_uri: 'https://myreporturi.report-uri.com/r/d/csp/wizard'

```

## report_to

When you enable the Reporting API you will receive deprecation, intervention and crash reports from the browser. To enable this you need to set a HTTP response header with the following name and value.

## csp_config

Configure the allowed domains. If domains change, they need to be added programmatically.

## inline scripts

Enabling inline scripts can be done by using the `CSPRequirements` instead of the normal `Requirements`. It will give a new method to add inline javascripts via `CSPRequirements::insertJSTags($js, $identifier, $options);`
The javascript _**must not**_ contain the `<script>` tags!

## wizzard

It's useful to only use the wizard in dev mode, to discover the URI's and sha's you need to add.
This prevents needless reports and helps you set up the wizard.

You do need to set your own wizard_uri though, otherwise the system will encounter a failure.

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
