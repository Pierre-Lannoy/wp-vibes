# Vibes
[![version](https://badgen.net/github/release/Pierre-Lannoy/wp-vibes/)](https://wordpress.org/plugins/vibes/)
[![php](https://badgen.net/badge/php/7.2+/green)](https://wordpress.org/plugins/vibes/)
[![wordpress](https://badgen.net/badge/wordpress/5.6+/green)](https://wordpress.org/plugins/vibes/)
[![license](https://badgen.net/github/license/Pierre-Lannoy/wp-vibes/)](/license.txt)

__Vibes__ is a robust user experience and browsing performances monitoring solution that analyzes perceived performances from users' viewpoint.

See [WordPress directory page](https://wordpress.org/plugins/vibes/) or [official website](https://perfops.one/vibes).

It is fully autonomous - does not rely on external services and does not require any API keys, works on any type of hosting and in any type of environment - including staging, intranets or password protected sites.

By continuously monitoring user experience, __Vibes__ can report:

* navigation performance KPIs per pages - like latency, redirections, browser caching hit rates, etc.;
* network timelines as if you were in the dev tools of your users' browsers;
* resources details - like initiators, protocols, mime types, average sizes, etc.;
* Web Vitals: LCP, FID, CLS, FCP and TTFB.

It can segment all this data per country (requires the free [IP Locator](https://wordpress.org/plugins/ip-locator/) plugin) and per device classes and types (requires the free [Device Detector](https://wordpress.org/plugins/device-detector/) plugin).

__Vibes__ supports multisite report delegation (see FAQ).

> __Vibes__ is part of [PerfOps One](https://perfops.one/), a suite of free and open source WordPress plugins dedicated to observability and operations performance.

__Vibes__ is a free and open source plugin for WordPress. It integrates many other free and open source works (as-is or modified). Please, see 'about' tab in the plugin settings to see the details.

## WP-CLI

__Vibes__ implements a set of WP-CLI commands. For a full help on these commands, please read [this guide](WP-CLI.md).

## Hooks

__Vibes__ introduces some filters and actions to allow plugin customization. Please, read the [hooks reference](HOOKS.md) to learn more about them.

## Installation

1. From your WordPress dashboard, visit _Plugins | Add New_.
2. Search for 'Vibes'.
3. Click on the 'Install Now' button.

You can now activate __Vibes__ from your _Plugins_ page.

## Support

For any technical issue, or to suggest new idea or feature, please use [GitHub issues tracker](https://github.com/Pierre-Lannoy/wp-vibes/issues). Before submitting an issue, please read the [contribution guidelines](CONTRIBUTING.md).

Alternatively, if you have usage questions, you can open a discussion on the [WordPress support page](https://wordpress.org/support/plugin/vibes/). 

## Contributing

Before submitting an issue or a pull request, please read the [contribution guidelines](CONTRIBUTING.md).

> ⚠️ The `master` branch is the current development state of the plugin. If you want a stable, production-ready version, please pick the last official [release](https://github.com/Pierre-Lannoy/wp-vibes/releases).

## Smoke tests
[![WP compatibility](https://plugintests.com/plugins/vibes/wp-badge.svg)](https://plugintests.com/plugins/vibes/latest)
[![PHP compatibility](https://plugintests.com/plugins/vibes/php-badge.svg)](https://plugintests.com/plugins/vibes/latest)