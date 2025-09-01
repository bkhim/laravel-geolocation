# Changelog

## [v2.1.0] - 2025-09-01

### Added
- Complete namespace refactor from `Adrianorosa\GeoLocation` to `Bkhim\Geolocation`
- Enhanced error handling and validation
- Improved caching system with configurable TTL
- Comprehensive test suite with HTTP mocking
- Better documentation and configuration examples

### Changed
- **BREAKING**: Namespace changed to `Bkhim\Geolocation`
- Updated to support Laravel 5.7+ to 12.x
- Improved IP validation with filter_var checks
- Enhanced exception messages with better debugging information

### Fixed
- Cache key collisions with improved namespacing
- HTTP timeout handling for API providers
- Rate limit detection and proper error reporting
- Configuration loading and environment variable support

### Migration Guide
Update your namespace imports:
```php
// Before
use Adrianorosa\GeoLocation\GeoLocation;

// After  
use Bkhim\Geolocation\GeoLocation;
```

### v1.2.0

- update version constraint for require-dev testbench package
- typo fix
- Merge pull request #6 from BrekiTomasson/dev-l10-compatibility

### v1.1.0

- 2022-04-30 - update Travis config to support PHP `8.0` and `8.1`.
- 2022-04-27 - added support for Laravel `^9.0`.

### v1.0.0

- 2021-01-25 - fix composer requirements
- 2021-01-25 - add return type declaration
- 2021-01-25 - update README
- 2021-01-25 - removed unused class
- 2021-01-25 - upgrade testbench to 6.9 and guzzle to 7.0
- 2021-01-25 - upgrade tests

### v0.3.0

- 2019-11-15  - update requirement version constrain for laravel 7.0 and 8.0

### v0.2.0

- 2019-11-15  - update requirement version constrain for laravel 6.*

### v0.1.3

- 2019-08-29 - Removed DeferrableProvider interface, now the package is not deferred by default
- 2019-08-29 - Add new Facade `GeoLocation::countries` method to get the translation list of countries codes

### v0.1.2

 - 2019-08-28 - Add TravisCI
 - 2019-08-28 - Add new method GeoLocationDetails::getCountryCode()
 
 ### v0.1.1

 - 2019-08-22 - Add support for laravel 6.0
 
### v0.1.0

 - 2019-08-13 - Initial Release
