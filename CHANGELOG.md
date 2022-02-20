# Changelog
All notable changes to **Vibes** are documented in this *changelog*.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and **Vibes** adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.2] - Not Yet Released

### Added

### Changed
- Rename some JavaScripts to evade uBlock Origin.
- Site Health page now presents a much more realistic test about object caching.

### Fixed

### Removed

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