# Changelog
All notable changes to **Vibes** are documented in this *changelog*.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and **Vibes** adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.2.0] - Not Yet Released

### Added
- Compatibility with WordPress 6.8.

### Changed
-

### Fixed
- Plugin update process may be confused when it founds error in release file.

## [2.1.0] - 2024-11-22

### Added
- Compatibility with WordPress 6.6 & 6.7.

### Changed
- Ability to self-update from Github.
- The plugin user agent is now more consistent and "standard".

### Fixed
- There's a WordPress core "feature" which causes some PII to leak (to wp.org) during plugin and theme updates. This is no more the case for this plugin.
- In some cases, a WordPress notice can be triggered concerning the loading sequence of translations.

### Removed
- Test site launching from wordpress.org plugin page.
- All Databeam hooks and libraries, as the Databeam project is abandoned.
- Dependency on wp.org for updates.

## [2.0.0] - 2024-05-28

### Added
- [BC] To enable installation on more heterogeneous platforms, the plugin now adapts its internal logging mode to already loaded libraries.

### Changed
- Updated DecaLog SDK from version 4.1.0 to version 5.0.0.

### Fixed
- PHP error with some plugins like Woocommerce Paypal Payments.

## [1.9.0] - 2024-05-07

### Changed
- The plugin now adapts its requirements to the PSR-3 loaded version.

## [1.8.2] - 2024-05-04

### Fixed
- PHP error when DecaLog is not installed.

## [1.8.1] - 2024-05-04

### Changed
- Updated DecaLog SDK from version 3.0.0 to version 4.1.0.
- Minimal required WordPress version is now 6.2.

## [1.8.0] - 2024-03-02

### Added
- Compatibility with WordPress 6.5.

### Changed
- Minimal required WordPress version is now 6.1.
- Minimal required PHP version is now 8.1.

## [1.7.0] - 2023-10-25

### Added
- Compatibility with WordPress 6.4.

## [1.6.0] - 2023-07-12

### Added
- Compatibility with WordPress 6.3.

### Changed
- The color for `shmop` test in Site Health is now gray to not worry to much about it (was previously orange).

## [1.5.1] - 2023-03-02

### Fixed
- [SEC004] CSRF vulnerability / [CVE-2023-27444](https://www.cve.org/CVERecord?id=CVE-2023-27444) (thanks to [Mika](https://patchstack.com/database/researcher/5ade6efe-f495-4836-906d-3de30c24edad) from [Patchstack](https://patchstack.com)).

## [1.5.0] - 2023-02-24

The developments of PerfOps One suite, of which this plugin is a part, is now sponsored by [Hosterra](https://hosterra.eu).

Hosterra is a web hosting company I founded in late 2022 whose purpose is to propose web services operating in a European data center that is water and energy efficient and ensures a first step towards GDPR compliance.

This sponsoring is a way to keep PerfOps One plugins suite free, open source and independent.

### Added
- Compatibility with WordPress 6.2.

### Fixed
- In some edge-cases, detecting IP may produce PHP deprecation warnings (thanks to [YR Chen](https://github.com/stevapple)).

## [1.4.1] - 2022-12-13

### Changed
- Improved loading by removing unneeded jQuery references in public rendering (thanks to [Kishorchand](https://github.com/Kishorchandth)).

## [1.4.0] - 2022-10-06

### Added
- Compatibility with WordPress 6.1.
- [WPCLI] The results of `wp vibes` commands are now logged in [DecaLog](https://wordpress.org/plugins/decalog/).

### Changed
- Improved ephemeral cache in analytics.
- [WPCLI] The results of `wp vibes` commands are now prefixed by the product name.

### Fixed
- Live console with PHP 8 may be broken (thanks to [stuffeh](https://github.com/stuffeh)).
- [SEC003] Moment.js library updated to 2.29.4 / [Regular Expression Denial of Service (ReDoS)](https://github.com/moment/moment/issues/6012).

## [1.3.0] - 2022-04-20

### Added
- Compatibility with WordPress 6.0.

### Changed
- Updated DecaLog SDK from version 2.0.2 to version 3.0.0.

### Fixed
- [SEC001] Moment.js library updated to 2.29.2 / [CVE-2022-24785](https://github.com/advisories/GHSA-8hfj-j24r-96c4).

## [1.2.2] - 2022-02-21

### Changed
- Rename some JavaScripts to evade uBlock Origin.
- Site Health page now presents a much more realistic test about object caching.

## [1.2.1] - 2022-01-17

### Fixed
- The Site Health page may launch deprecated tests.

## [1.2.0] - 2022-01-17

### Added
- Compatibility with PHP 8.1.

### Changed
- Refactored cache mechanisms to fully support Redis and Memcached.
- The window (in minutes) used for metrics and widget is now clearly stated.

### Fixed
- Object caching method may be wrongly detected in Site Health status (thanks to [freshuk](https://profiles.wordpress.org/freshuk/)).
- There may be name collisions with internal APCu cache.

### Removed
- Metrics are now hidden from command line as they were irrelevant.

## [1.1.1] - 2021-12-21

### Changed
- Widget is now updated even if metrics publication is disabled.
- Updated PerfOps One library from 2.2.1 to 2.2.2.
- Improved bubbles display when width is less than 500px (thanks to [Pat Ol](https://profiles.wordpress.org/pasglop/)).
- The tables headers have now a better contrast (thanks to [Paul Bonaldi](https://profiles.wordpress.org/bonaldi/)).

### Fixed
- A PHP warning may be triggered if there's no data to display in dashboard widget.
- An innocuous Mysql error may be triggered at plugin activation.
- The Control Center layout may be ugly in some languages (thanks to [Paul Bonaldi](https://profiles.wordpress.org/bonaldi/), [Laurent Naudier](https://github.com/fr-laurentn) and [Grégory Thépault](https://profiles.wordpress.org/locomint85/)).

## [1.1.0] - 2021-12-19

### Added
- New dashboard widget to display Web Vitals.
- New selector in performances and Web Vitals analytics to differentiate frontend and backend pages.

### Changed
- Improved timescale computation and date display for all charts.
- Data, graph points and KPIs are now hidden when data quality does not reach an optimal level.
- Improved plugin activation sequence.
- Default sampling rates are higher than previously.

### Fixed
- Vibes logo is wrongly displayed in about box.
- An innocuous Mysql error may be triggered at plugin installation.

## [1.0.3] - 2021-12-16

### Changed
- Improved Web Vitals layouts.
- Improved display of long endpoint names in resources analytics.
- Improved title bar layout in all analytics reports.
- Updated DecaLog SDK from version 2.0.0 to version 2.0.2.

### Fixed
- In resources analytics, top boxes and pie boxes may not take into account subdomain or endpoint filters.
- In resources analytics, sources and mime types breakdowns do not take into account subdomain and endpoint filters.

## [1.0.2] - 2021-12-09

### Fixed
- The console menu may display an empty screen (thanks to [Renaud Pacouil](https://www.laboiteare.fr)).
- The Web Vitals variation charts may be unordered.

## [1.0.1] - 2021-12-08

### Changed
- Adjust files headers.

## [1.0.0] - 2021-12-07

Initial release