# Permission Policy headers

[Permission policies define what a site and the included iframes are allowed to access](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Permissions-Policy).

If this header is _not_ configured and enabled, the browser defaults of `allow: *` is used by the browser

## Configuration

```yaml
Firesphere\CSPHeaders\View\CSPBackend:
  permissions_config:
    enabled: true
    accelerator:
      allow: [none]
    ambient-light-sensor:
      allow: [none]
    autoplay:
      self: true
      allow: []
    battery:
      allow: [none]
    camera:
      allow: [none]
    display-capture:
      self: true
      allow: ['*']
    encrypted-media:
      self: true
      allow: []
    fullscreen:
      self: true
      allow: []
    geolocation:
      allow: [none]
    interest-cohort:
      allow: [none]
    microphone:
      allow: [none]
```

