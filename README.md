# Juru-Empire-Database
Juru Empire Database
Changelog for Juru Empire Database Plugin
[Version 1.1.0] – 2025-07-27
Release Date: July 27, 2025
Time: 10:40 AM EDT

Added
Added support for a black background on archive and single post pages for custom post types (juru_system, juru_planet, juru_fauna) with white text for improved readability in dark themes.
Changed
Updated juru-styles.css to set a black background (#000000) for the navigation bar, dropdown menu, and offcanvas drawer, with white text (#ffffff) for all menu items and form elements.
Modified juru-navigation_shortcode to separate the offcanvas drawer HTML from the main navigation content, ensuring “Add New” and its associated buttons (submit, close) are not displayed inline with post information.
Adjusted juru-styles.css to apply smooth transitions (transition: color 0.2s ease, transition: background-color 0.2s ease) to hover effects on navigation items to prevent flashing.
Standardized all menu item link colors to white (#ffffff) across the navigation bar, including “Systems,” “Planets,” “Points of Interest,” “Fauna,” “Players,” and “Add New,” with a subtle #cccccc hover color for feedback.
Updated post content styles to target .post-type-archive-juru_system, .post-type-archive-juru_planet, .post-type-archive-juru_fauna, .single-juru_system, .single-juru_planet, and .single-juru_fauna with a black background and white text.
Fixed
Resolved an issue where “Systems” and “Planets” were missing from the menu by verifying the juru_navigation_shortcode structure and ensuring correct get_post_type_archive_link usage.
Addressed hover flashing on menu items by adding CSS transitions to smooth out state changes.
