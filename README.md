# User Notes for BuddyPress

[![WordPress Plugin Version](https://img.shields.io/badge/WordPress-5.8%2B-blue.svg)](https://wordpress.org/plugins/usernotes-for-buddypress/)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-8892bf.svg)](https://php.net/)
[![License: GPL-2.0-or-later](https://img.shields.io/badge/License-GPL--2.0%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

A secure, modern notes and personal journaling suite built specifically for **BuddyPress** and **BuddyBoss Platform**. Notes stay private to each member by default, with optional one-click public sharing, rich formatting, real-time AJAX interactions, and native WordPress Core GDPR compliance.

---

## 🌟 Features

- **Private by Default**: Personal entries and journal reflections remain strictly confidential to the author unless explicitly marked public.
- **One-Click Public Sharing**: Members can choose to publish selected notes directly to their community profile.
- **Modern Responsive UI**: Built with responsive CSS custom properties, featuring seamless toggling between Grid and List view layouts.
- **Distraction-Free Composer**: Accessible modal dialog with a rich formatting toolbar (bold, italic, underline, strikethrough, headings, lists, quotes, and links).
- **Instant Search & Filter**: Real-time debounced keyword search and status filter tabs (`All`, `Private`, `Public`).
- **Note Pinning**: Pin crucial notes to the top of your notebook.
- **Zero Query Leakage / Anti-IDOR**: Comprehensive authorization checks and query isolation guarantee unauthorized visitors or members can never access private notes.
- **WordPress Core GDPR Compliance**: Full integration with WordPress Core Personal Data Exporter (`wp_privacy_personal_data_exporters`) and Personal Data Eraser (`wp_privacy_personal_data_erasers`).
- **BuddyPress Component Architecture**: Built atop `BP_Component`, fully supporting BuddyPress Nouveau, Legacy, and BuddyBoss Platform themes.
- **Modern Admin Settings**: Dedicated configuration panel under `Settings > User Notes` using the WordPress Settings API.

---

## 📂 Architecture Overview

```
usernotes-for-buddypress/
├── usernotes-for-buddypress.php          # Main plugin bootstrap & lifecycle hooks
├── readme.txt                             # WordPress.org standard repository readme
├── README.md                              # Developer documentation
├── uninstall.php                          # Clean uninstaller (GDPR compliant)
├── assets/
│   ├── css/
│   │   ├── admin.css                      # Admin dashboard styling
│   │   └── frontend.css                   # Frontend notes & journal styling
│   └── js/
│       ├── admin.js                       # Admin interactions & confirmations
│       └── frontend.js                    # Frontend reactive controller & AJAX CRUD
├── includes/
│   ├── class-autoloader.php               # PSR-4 compliant class autoloader
│   ├── class-plugin.php                   # Core plugin orchestrator (Singleton)
│   ├── class-post-type.php                # Custom Post Type 'bp_note' schema & meta
│   ├── class-component.php                # BuddyPress BP_Component profile integration
│   ├── class-security.php                 # Authorization, capabilities & KSES sanitization
│   ├── class-query.php                    # Privacy-isolated query builder
│   ├── class-ajax-handler.php             # Secure AJAX endpoints
│   ├── class-privacy.php                  # WP Core GDPR Data Exporter & Eraser
│   └── admin/
│       └── class-admin-settings.php       # WordPress Settings API controller
├── templates/
│   ├── notes-container.php                # Profile screen main wrapper & toolbar
│   ├── single-note.php                    # Single note full-view template
│   ├── note-modal.php                     # Note composer modal dialog
│   ├── empty-state.php                    # Modern SVG empty state
│   └── admin-settings-page.php            # Admin settings dashboard view
└── languages/
    └── usernotes-for-buddypress.pot       # Translation template
```

---

## 🚀 Installation

1. Clone or download this repository into your WordPress installation:
   ```bash
   wp-content/plugins/usernotes-for-buddypress
   ```
2. Activate the plugin via the WordPress Admin:
   - Navigate to **Plugins > Installed Plugins**
   - Click **Activate** under **User Notes for BuddyPress**
3. Configure settings under **Settings > User Notes**.
4. Visit your BuddyPress member profile to start writing notes!

---

## 🔒 Security & Standards

- **Strict Access Protection**: Every PHP file contains `defined( 'ABSPATH' ) || exit;`.
- **Nonces & Capabilities**: All AJAX mutation requests require nonce verification and user capability checks.
- **Sanitization & Escaping**: All inputs are sanitized using `sanitize_text_field` and `wp_kses`. All outputs are properly escaped with `esc_html`, `esc_attr`, `esc_url`, or `wp_kses_post`.
- **Database Safety**: Uses high-level `WP_Query` parameters with metadata indexing; direct SQL is avoided.

---

## 📄 License

Distributed under the **GPLv2 or later** license. See [GPL-2.0](https://www.gnu.org/licenses/gpl-2.0.html) for full details.