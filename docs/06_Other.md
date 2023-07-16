# Other security headers

A few other security related headers are supported.
- Referer-policy
- X-Frame-Options
- X-Content-Type_Options

## Configuration

```yaml
Firesphere\CSPHeaders\View\CSPBackend:
  referrer: same-origin
  frame-options: SAMEORIGIN
  content-type-options: nosniff
```

Note that the module (currently) does _NOT_ validate if the values in the YML are _valid_ values for the header!
