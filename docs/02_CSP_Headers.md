# Content Security Policy headers

## Configuration

Configuration is done primarily in YML. An example below:

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
  useNonce: true
---
Only:
  environment: 'dev'
---
Firesphere\CSPHeaders\View\CSPBackend:
  csp_config:
    report-only: true
```

### Generating the YML

If you currently have a hard-coded or manually written security policy header, in a controller, you can take
this header and convert it to YML.

A helper class is included to take your existing headers and convert them to a workable starting point as YML.

Usage: `CSPConvertor::toYml(Controller::curr()->getResponse());`

## csp_config

Configure the allowed domains. If domains change, they need to be added programmatically.
You can disable CSP output entirely for target environments via yaml e.g. to only output the headers
in production you could use

```yaml
---
Except:
  environment: 'live'
---
Firesphere\CSPHeaders\View\CSPBackend:
  csp_config:
    enabled: false
```


## legacy_headers

Setting the `legacy_headers` flag to true, will add the IE legacy headers like `X-CONTENT-SECURITY-POLICY`. Default value is false

## report_to

The report_to directive is new, and not yet fully supported by all browsers, nor by the underlying Paragon library.

Use at your own discretion. For now, `report_uri` is supported across all browsers.

## Configuring domains

Each directive of the CSP can be controlled via an array in YML, or via the CMS.

## wizard

It's useful to only use the wizard in dev mode, to discover the URI's and sha's you need to add.
This prevents needless reports and helps you set up the wizard.

You do need to set the report-to uri to your wizard uri, otherwise the system will encounter a failure.

To enable the "wizard" mode, set `report_only` to `true`

## forms

If you want to submit forms to a different domain, you can add the allowed domains under the forms section

## inline scripts or custom scripts

If you use the default methods provided by the `Requirements` class,
the required SHA's and SRI's are automatically calculated for you.

## Nonces

Nonces are part of the Content Security Header, and used to generate a unique nonce for each request.

This nonce is used to allow javascripts to be included on the page, without requiring to calculate the hash of the
included javascript.

In the template, you can use `$Nonce` to get the current request nonce,
e.g. if you are using `<script>` tags in your template instead of using the
Requirements API. Note that using `<script>` tags will not generate or output SRI.
