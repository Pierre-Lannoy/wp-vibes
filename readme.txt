=== Vibes ===
Contributors: PierreLannoy, hosterra
Tags: rum, real user monitoring, ux, web performance, web vitals
Requires at least: 5.6
Requires PHP: 7.2
Tested up to: 6.3
Stable tag: 1.6.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Truthful user experience and browsing performances monitoring.

== Description ==

**Truthful user experience and browsing performances monitoring.**

**Vibes** is a robust user experience and browsing performances monitoring solution that analyzes perceived performances from users' viewpoint.

> 🎁 Give this plugin a drive test on a free dummy site: [One-Click Test!](https://tastewp.com/new/?pre-installed-plugin-slug=vibes)

It is fully autonomous - does not rely on external services and does not require any API keys, works on any type of hosting and in any type of environment - including staging, intranets or password protected sites.

By continuously monitoring user experience, **Vibes** can report:

* navigation performance KPIs per pages - like latency, redirections, browser caching hit rates, etc.;
* network timelines as if you were in the dev tools of your users' browsers;
* resources details - like initiators, protocols, mime types, average sizes, etc.;
* Web Vitals: LCP, FID, CLS, FCP and TTFB.

It can segment all this data per:

* user type (anonymous vs. authenticated);
* channel (frontend vs. backend);
* country (requires the free [IP Locator](https://wordpress.org/plugins/ip-locator/) plugin);
* device classes and types (requires the free [Device Detector](https://wordpress.org/plugins/device-detector/) plugin).

**Vibes** supports multisite report delegation (see FAQ).

**Vibes** supports WP-CLI commands to:

* display (past or current) performances signals in console - see `wp help vibes tail` for details;
* toggle on/off main settings - see `wp help vibes settings` for details.

For a full help on WP-CLI commands in Vibes, please [read this guide](https://perfops.one/vibes-wpcli).

> **Vibes** is part of [PerfOps One](https://perfops.one/), a suite of free and open source WordPress plugins dedicated to observability and operations performance.

**Vibes** is a free and open source plugin for WordPress. It integrates many other free and open source works (as-is or modified). Please, see 'about' tab in the plugin settings to see the details.

= Support =

This plugin is free and provided without warranty of any kind. Use it at your own risk, I'm not responsible for any improper use of this plugin, nor for any damage it might cause to your site. Always backup all your data before installing a new plugin.

Anyway, I'll be glad to help you if you encounter issues when using this plugin. Just use the support section of this plugin page.

= Donation =

If you like this plugin or find it useful and want to thank me for the work done, please consider making a donation to [La Quadrature Du Net](https://www.laquadrature.net/en) or the [Electronic Frontier Foundation](https://www.eff.org/) which are advocacy groups defending the rights and freedoms of citizens on the Internet. By supporting them, you help the daily actions they perform to defend our fundamental freedoms!

== Installation ==

= From your WordPress dashboard =

1. Visit 'Plugins > Add New'.
2. Search for 'Vibes'.
3. Click on the 'Install Now' button.
4. Activate Vibes.

= From WordPress.org =

1. Download Vibes.
2. Upload the `vibes` directory to your `/wp-content/plugins/` directory, using your favorite method (ftp, sftp, scp, etc...).
3. Activate Vibes from your Plugins page.

= Once Activated =

1. Visit 'PerfOps One > Control Center > Vibes' in the left-hand menu of your WP Admin to adjust settings.
2. Enjoy!

== Frequently Asked Questions ==

= What are the requirements for this plugin to work? =

You need at least **WordPress 5.6** and **PHP 7.2**.

= Can this plugin work on multisite? =

Yes. It is designed to work on multisite too. Network Admins can configure the plugin and have access to all analytics reports. Sites Admins have access to the analytics reports of their sites.

= Where can I get support? =

Support is provided via the official [WordPress page](https://wordpress.org/support/plugin/vibes/).

= Where can I report a bug? =
 
You can report bugs and suggest ideas via the [GitHub issue tracker](https://github.com/Pierre-Lannoy/wp-vibes/issues) of the plugin.

== Changelog ==

Please, see [full changelog](https://perfops.one/vibes-changelog).

== Upgrade Notice ==

== Screenshots ==

1. Web Vitals
2. Navigation Analytics
3. Resources Analytics
4. Live Monitoring