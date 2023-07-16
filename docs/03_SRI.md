# SubResource Integrity

## Configuration
```yaml

Firesphere\CSPHeaders\View\CSPBackend:
  jsSRI: true
  cssSRI: false
---
Only:
  environment: 'dev'
---
Firesphere\CSPHeaders\View\CSPBackend:
  jsSRI: false
```

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

This will simply delete all hashes, they will be recalculated the first time they are required on a page.
