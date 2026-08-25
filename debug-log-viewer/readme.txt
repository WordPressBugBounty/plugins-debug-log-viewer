=== Debug Log Viewer ===

Contributors: lysyiweb
Tags: log, debugging, error log, debug, developer tools
Requires at least: 5.8
Tested up to: 7.1
Stable tag: 2.5.2
Requires PHP: 7.2
License: GPLv2 or later

Effortlessly view, search, filter and manage your WordPress debug.log in the admin dashboard. Real-time monitoring and email alerts

== Description ==

= Debug Log Viewer: Your Essential WordPress Debugging Tool =

Tired of struggling to access and understand your WordPress debug.log file?

Debug Log Viewer simplifies WordPress debugging by providing a user-friendly interface to view, search, and manage your debug.log directly within your WordPress admin area.

It is designed for WordPress developers, site administrators, freelancers, and anyone who needs to quickly identify and resolve website issues.

Debug Log Viewer helps you find errors faster without using FTP, hosting panels, or manually opening log files.

= Gain Real-Time Insights into Your WordPress Site Health =

Monitor errors, warnings, and deprecated function notices in real-time. Debug Log Viewer helps you identify problems early and maintain a more stable WordPress environment.

= Key Features for Efficient WordPress Debugging =

-   Real-Time Log Viewer: Monitor your WordPress debug.log file in real-time, directly from your WordPress dashboard. No more hunting for files via FTP or cPanel!
-   Easy Debug Log Access: Access and view your full debug log within a clean and intuitive interface.
-   Smart Search: Find log entries quickly with intelligent search that matches space-separated words in any order.
-   Advanced Search & Filtering: Quickly find specific log entries with powerful search and filtering options. Filter by error type, keywords, and date range (Pro). Use regular expressions for complex search patterns (Pro).
-   Pagination: Navigate through large debug logs with ease using pagination, ensuring smooth performance even with extensive logs.
-   Email Alerts for Errors: Get email alerts when new errors are logged. Pro users can customize error severity levels and recipients.

= Flexible Settings Panel =

-   Control WP_DEBUG Constants: Enable or disable WP_DEBUG and WP_DEBUG_LOG constants directly from the plugin settings.
-   Customize Logging Options: Configure your debug log settings to match your needs.
-   Custom Log Path Support: Automatically detect and read custom debug.log locations.

= Benefits of Using Debug Log Viewer =

-   Save Time and Effort: Stop manually accessing and parsing your debug log file.
-   Faster Error Detection: Monitor errors as they happen and reduce troubleshooting time.
-   Simplified Troubleshooting: Quickly identify the source of errors and warnings.

= Debug Log Viewer Pro Features =

Upgrade to Pro and get advanced tools for professional WordPress debugging and site maintenance:

-   Regular Expression Search: Use powerful regex patterns to find complex log entries and patterns.
-   Advanced Email Alerts: Choose error levels and send notifications to multiple recipients.
-   CSV Export: Export debug log entries for analysis, reporting, or sharing.
-   Custom Date Range Filtering: Analyze logs from specific time periods.
-   Infinite Scroll Log Viewer: Browse large log files smoothly without pagination interruptions.
-   Advanced Log Cleanup Controls: Manage debug.log size with custom limits, schedules, cleanup methods, and retention settings.
-   Cleanup History: Review previous cleanup actions and track log maintenance.

= Why choose Debug Log Viewer Pro? =

Managing WordPress errors is not only about viewing logs.

Large debug files can consume disk space, become difficult to analyze, and slow down troubleshooting.

Pro gives you automation tools to monitor, export, filter, and maintain your debug logs efficiently.

= Who is Debug Log Viewer For? =

-   WordPress developers
-   Website administrators
-   Freelancers managing client sites
-   Agencies maintaining WordPress websites
-   Anyone who wants an easy way to monitor WordPress errors

== Installation ==
1.  Installation: Install Debug Log Viewer from the WordPress Plugin Directory or upload the plugin zip file through your WordPress admin.
2.  Activation: Activate the plugin from your Plugins page.
3.  Access:  Navigate to the "Debug Log Viewer" menu in your WordPress dashboard to start monitoring your debug log.

== Screenshots ==

1. Main screen of the plugin interface showing the debug log viewer dashboard with error entries and filtering options.
2. Settings page
3. Email Alerts setup page
4. Cleanup page

== Changelog ==

= 2.5.2 =
* Updated dependencies
* Added Smart Search: Intelligent search that matches space-separated words in any order
* Added Regular Expression Search for advanced pattern matching (Pro feature)


= 2.4 =
* UI and design improvements
* Bug fixes

= 2.2.3 =
* Full-width layout
* Changed log file info panels
* Improved review banner
* UI and design improvements
* Package updates

= 2.1 =
* Added Auto-Cleanup feature to automatically manage debug.log file size (Pro: configurable 1-5000MB, Free: fixed at 50MB)
* Three cleanup methods available: Truncate (keep recent logs), Clear (remove all), Archive & Rotate (backup before clearing)
* Added cleanup history tracking with expandable view for up to 50 entries (Pro only)
* Various UI improvements, package upgrade and code optimizations

= 2.0.7 =
* Added ability to choose timezone for log entries

= 2.0.5 =
* Adjustments for Pro plan modal
* Fixed alerts cron unshedule issue (thanks to Leo for report)

= 2.0.4 =
* Security fix

= 2.0.3 =
* Minor UI fixes
* Added Ask for review banner

= 2.0.0 =
* Added datetime format selection (absolute/relative time display)
* Implemented custom date range filtering (Pro feature)
* Enhanced CSV export functionality (Pro feature)
* Introduced log file information popover with warnings and status
* Aadvanced email alerts, multiple error levels, up to 3 recipients (Pro feature)
* Increased log file size limit from 5MB to 10MB
* Security improvements and UI fixes
* Performance optimizations and code refactoring


= 1.4.3 =
* Updated design
* Added time display for the next notification
* Updated Freemius SDK to the latest version

= 1.4.2 =
* Callstack is now displayed in an accordion instead of a modal — allows viewing multiple entries simultaneously
* Added one-click copy functionality for Stack trace
* Updated Freemius SDK to the latest version
* Fixed issue with colVis extension, improved stability

= 1.4.1 =
* Updated design of Alert Emails
* Added chat for faster communication

= 1.4 =
* Added Custom Search Builder for advanced filtering conditions.
* Added a check for the debug file to determine if it is publicly accessible via a direct URL.
* Added a warning modal when enabling the "Display errors" toggle.

= 1.3 =
* Removed outdated and deprecated packages, reducing plugin size
* Parsing custom log records
* Added the ability to filter records by time intervals
* Updated Freemius SDK
* Updated DataTables
* Updated translations
* Various UI improvements

= 1.2.1 =
* Freemius and WP-Config-Transformer update

= 1.2 =
* Removed SSE implementation for live updates, because of unstable behaviour in some cases
* Implemented automatic live updates based on incremental AJAX ping requests.
* Added translations for the front-end phrases
* Implemented full-container width mode with ability to hide sidebar
* Intergated Freemius to become closer to users: contact us, forum links are added

= 1.1 =
* Fixed SSE streaming. Implemented incremental updates
* Decreased log reading limit from 10Mb to 5Mb
* Added ability to collapse Alert block in sidebar to make workspace more clear
* Fixed regular expression to parse datetime with long timezones

= 1.0.3 =
* Removed Toast plugin, used Bootstrap toasts instead
* Small refactoring

= 1.0.2 =
* Added assets (logo)

= 1.0.1 =
* Fix UUID generation

= 1.0.0 =
* Initial release
