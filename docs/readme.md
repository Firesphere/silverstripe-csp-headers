# Silverstripe security headers and policies

## Features

- [Content Security Policy header generation](02_CSP_Headers.md)
- [SRI calculation](03_SRI.md)
- [Permission Policies](04_PermissionPolicies.md)
- [HSTS](05_HSTS.md)
- [Various other headers](06_Other.md)

## Known caveats

This module currently relies on a Controller Extension to generate the headers.

This method is chosen over using middleware, so that the option of per-page headers can be used.

It's important to note that setting the inline javascript or CSS to true in the YML, will make it _always_ true,
however when it is set to `false`, but the checkbox is ticked in the CMS for a specific page, it will be true
on only that page.

## Caching

The headers are generated on the fly and currently not cached.
