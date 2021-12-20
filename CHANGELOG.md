# Changelog
All notable changes to **Vibes** are documented in this *changelog*.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and **Vibes** adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.1] - Not Yet Released

### Changed
- Widget is now updated even if metrics publication is disabled.
- Updated PerfOps One library from 2.2.1 to 2.2.2.
- Improved bubbles display when width is less than 500px (thanks to [Pat Ol](https://profiles.wordpress.org/pasglop/)).
- The tables headers have now a better contrast (thanks to [Paul Bonaldi](https://profiles.wordpress.org/bonaldi/)).

### Fixed
- A PHP warning may be triggered if there's no data to display in dashboard widget.
- An innocuous Mysql error may be triggered at plugin activation.
- The Control Center layout may be ugly in some languages (thanks to [Paul Bonaldi](https://profiles.wordpress.org/bonaldi/) and [Laurent Naudier](https://github.com/fr-laurentn)).

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