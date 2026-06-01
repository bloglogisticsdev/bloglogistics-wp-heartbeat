=== BlogLogistics WP Heartbeat ===
Contributors: bloglogistics
Tags: heartbeat, performance, admin-ajax, autosave, editor
Requires at least: 7.0
Tested up to: 7.0
Requires PHP: 8.3
Stable tag: 2.1.1
License: GPL-3.0-or-later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Adjusts or disables the WordPress Heartbeat API in the dashboard, post editor, and frontend.

== Description ==

BlogLogistics WP Heartbeat controls the WordPress Heartbeat API in three common areas: the Dashboard, the post editor, and the frontend.

The default setting is:

* Dashboard Heartbeat: Disabled
* Post Editor Heartbeat: 60 seconds
* Frontend Heartbeat: Disabled

A value of 0 disables Heartbeat for that area. Enabled intervals are enforced at a minimum of 15 seconds.

Disabling Heartbeat can reduce background admin-ajax.php requests, but it can also affect autosave, post locking, editor presence, and collaboration features. Keep the post editor enabled unless you have a clear reason to disable it.

== Features ==

* Adds settings under BlogLogistics > WP Heartbeat.
* Controls Heartbeat on the main Dashboard screen.
* Controls Heartbeat in the post editor.
* Controls Heartbeat on the frontend when a theme or plugin enqueues it.
* Allows each area to be disabled by setting its interval to 0.
* Enforces WordPress-friendly enabled intervals of at least 15 seconds.
* Adds a Settings link on the Plugins screen.
* Removes plugin settings when the plugin is deleted.
* Uses BlogLogistics manifest-based updates.

== Installation ==

1. Upload the plugin folder to /wp-content/plugins/.
2. Activate the plugin in WordPress.
3. Go to BlogLogistics > WP Heartbeat.
4. Choose the Heartbeat settings you want to use.
5. Save your changes.

== Frequently Asked Questions ==

= What is WordPress Heartbeat? =

Heartbeat is a WordPress API that allows the browser and server to communicate in the background. WordPress uses it for features such as autosave and post locking.

= Should I disable Heartbeat in the post editor? =

Usually no. The recommended post editor setting is 60 seconds. Disabling Heartbeat in the editor can affect autosave, post locking, editor presence, and collaboration features.

= Why would I disable Dashboard or Frontend Heartbeat? =

Some sites receive unnecessary background admin-ajax.php requests from Heartbeat. Disabling it in areas where it is not needed can reduce server load.

= What does a value of 0 mean? =

A value of 0 disables Heartbeat for that area.

= What is the minimum enabled interval? =

Enabled intervals are enforced at a minimum of 15 seconds because WordPress ignores extremely low values.

= What happens if I delete the plugin? =

The plugin removes its saved settings during uninstall.

== Changelog ==

= 2.1.1 =
* Fix settings page fatal error caused by an invalid readonly helper call after the bootstrap refactor.
* Make all Heartbeat controls render correctly on the settings page.

= 2.1.0 =
* Refactor the main plugin file into a bootstrap loader.
* Move the main plugin class into the includes directory.
* Add translation support and bundled language files.
* Add Domain Path metadata for local translations.

= 2.0.0 =
* Rebuild as a standard BlogLogistics plugin repository.
* Add BlogLogistics admin menu integration.
* Add BlogLogistics manifest-based updates.
* Move settings to BlogLogistics > WP Heartbeat.
* Add no-change save handling and recommended-default restore handling.
* Preserve safe defaults for WordPress 7: Dashboard disabled, post editor set to 60 seconds, frontend disabled.
* Add uninstall cleanup for plugin settings.
