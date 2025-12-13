=== Ultimate SEO Checklist ===
Contributors: [your-wordpress-dot-org-username]
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Tags: seo, checklist, core web vitals, eeat, optimization

A real-time, lightweight on-page SEO checklist for the WordPress editor, focusing on core ranking signals and E-E-A-T.

== Description ==

The Ultimate SEO Checklist provides instant, real-time feedback on your post's SEO performance directly within the Classic Editor (or Gutenberg Code Editor). Unlike heavy, resource-intensive SEO plugins, this lightweight tool focuses solely on critical, high-impact on-page factors like Core Web Vitals (via proxy checks) and E-E-A-T signals.

It calculates a weighted score out of 100 based on three categories: Core Optimization, Strategic/Advanced SEO, and Technical/UX checks.

**Key Features Include:**

* **Real-time Scoring:** Get an instant weighted score without needing to save or update the post.
* **Targeted Checks:** Focuses on essential checks: Keyword in H1, Title, URL, and density.
* **Featured Snippet Optimization:** Specific checks for the "Quick Answer Format" (Question Hx followed by a concise P tag).
* **CWV Proxy Checks:** Includes checks for Image Dimensions to prevent Cumulative Layout Shift (CLS).
* **E-E-A-T Signals:** Checks for Author and Post Date metadata presence to satisfy Google's quality guidelines.
* **Anchor Risk Warning:** Alerts you if you use the Focus Keyword as anchor text, a common SEO mistake.

== Installation ==

1.  Upload the `ultimate-seo-checklist` folder to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Navigate to any Post or Page editor.
4.  Enter your Focus Keyword in the sidebar box to begin the real-time audit.

== Frequently Asked Questions ==

= Does this plugin work with the Classic Editor? =

Yes, this plugin is designed to work seamlessly within the Classic Editor interface. It also works when the Gutenberg editor is switched to Code mode.

= Does this plugin store data in the database? =

No. The Ultimate SEO Checklist is completely stateless. It performs all checks and calculations instantly via AJAX and does not store any custom data in the WordPress database, ensuring maximum performance.

= How is the score weighted? =

The score is calculated based on three weighted categories:
* Core Optimization: 50%
* Strategic/Advanced SEO: 30%
* Technical/UX Checks: 20%

== Screenshots ==

1. [Screenshot of the plugin meta box in the post editor with a keyword entered and the checklist visible.]
2. [Screenshot showing a 'green' score and successful check for the 'Quick Answer Format'.]
3. [Screenshot highlighting the red 'Anchor Text Risk' warning.]

== Changelog ==

= 1.0.0 (December 2025) =
* Initial Release.
* Implemented real-time, weighted scoring engine.
* Added checks for Keyword in H1/Title/URL, Density, and Content Length.
* Added advanced checks for Internal/External links and Keyword Anchor Risk.
* Implemented CWV proxy (Image Dimension) and E-E-A-T checks.
* Full Internationalization and security hardening (Nonce implementation).

