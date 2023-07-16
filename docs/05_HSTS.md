# Strict Transport Security Header

HTTP Strict Transport Security (HSTS) is a policy mechanism that helps to protect websites against man-in-the-middle attacks such as protocol downgrade attacks and cookie hijacking.

## Configuration

```yaml
Firesphere\CSPHeaders\View\CSPBackend:
  HSTS:
    enabled: false
    max-age: 31536000
    include_subdomains: true
```

