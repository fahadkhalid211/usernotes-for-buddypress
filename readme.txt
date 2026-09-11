=== User Notes for BuddyPress ===
Contributors: fahadkhalid211
Tags: buddypress, notes, journal, personal notes, buddyboss
Requires at least: 5.8
Tested up to: 7.1
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A modern, private-by-default personal notes and journaling suite for BuddyPress and BuddyBoss community members.

== Description ==

**User Notes for BuddyPress** enables your community members to keep a private notebook, journal, or personal diary directly inside their BuddyPress profile.

Designed and engineered with strict security, high performance, and modern aesthetics in mind, this plugin guarantees that all notes stay **100% private to the author by default**. Members can choose to make individual notes public to share thoughts with the community, or keep them completely private.

### 🌟 Key Features

* **Private by Default**: Personal entries remain strictly confidential to the author unless explicitly toggled to public.
* **One-Click Public Sharing**: Authors can seamlessly share individual notes on their public BuddyPress profile tab.
* **Modern & Clean UI**: Polished, card-based interface with instant switching between Grid and List layouts.
* **Distraction-Free Rich Formatting**: Intuitive formatting toolbar supporting bold, italic, underline, strikethrough, headings, lists, blockquotes, and links.
* **Real-time Search & Instant Filtering**: Debounced search and quick filter tabs for "All", "Private", and "Public" entries.
* **Pin Important Notes**: Pin vital entries to the top of your notebook for immediate access.
* **Zero Bloat & Blazing Fast**: Lightweight architecture utilizing native WordPress post types and secure AJAX endpoints.
* **GDPR & Privacy Compliant**: Full integration with WordPress Core Personal Data Exporter and Personal Data Eraser tools.
* **BuddyPress & BuddyBoss Compatible**: Built on the official `BP_Component` framework, seamlessly integrating with BuddyPress Nouveau, BP Legacy, and BuddyBoss Platform.

== Installation ==

1. Upload the `usernotes-for-buddypress` directory to the `/wp-content/plugins/` directory, or install it directly via the WordPress Plugins directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Ensure BuddyPress (or BuddyBoss Platform) is installed and active.
4. Navigate to **Settings > User Notes** to configure your profile tab label, endpoint slug, and privacy options.
5. Community members can now visit their profile page and start writing notes under their **Notes** tab!

== Frequently Asked Questions ==

= Are notes really private? =
Yes. All notes are saved with private visibility by default. Query isolation ensures that only the author (and site moderators if moderation is permitted) can query or view private notes. Even direct API or AJAX requests cannot retrieve another user's private notes.

= Can non-logged-in visitors see notes? =
Visitors can only see notes that the author has explicitly published as **Public**. Private notes are completely hidden and stripped from all queries.

= Can I rename the "Notes" profile tab to "Journal" or "Diary"? =
Yes! Navigate to **Settings > User Notes** in your WordPress admin and adjust the **Profile Tab Label** to whatever fits your community best (e.g. Journal, Diary, Notebook).

= Does this plugin support WordPress GDPR data export and deletion? =
Yes. The plugin integrates directly with WordPress Core Privacy Tools (`Tools > Export Personal Data` and `Tools > Erase Personal Data`). When a member requests their personal data, their notes are included in the export file or permanently removed upon account erasure requests.

== Screenshots ==

1. Member Profile Notes screen with modern card layout, statistics, filter tabs, and search bar.
2. Distraction-free Note Composer modal with rich formatting toolbar and privacy selector.
3. Single note view with clean typography, reading time, and quick actions.
4. Admin Settings dashboard with system overview metrics and privacy toggles.

== Changelog ==

= 1.0.0 =
* Initial official release.
* Added native `BP_Component` integration for BuddyPress and BuddyBoss.
* Added private-by-default note creation and optional public sharing.
* Added modern card and list view layouts.
* Added rich formatting toolbar and keyboard shortcuts.
* Added full WordPress Core GDPR exporter and eraser support.
* Added comprehensive admin settings dashboard under Settings > User Notes.

== Upgrade Notice ==

= 1.0.0 =
Initial stable release.
