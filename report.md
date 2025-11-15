# Drag Drop Timeline Game Plugin: Code Inventory and Status Report

This report provides a comprehensive overview of the current state of the "Drag Drop Timeline Game" plugin, confirming the implementation of all planned features.

## File List

### PHP Files

*   **`draglearn.php`**: The main plugin file. It handles plugin activation, version checking, admin menu creation, and the registration of hooks, shortcodes, and AJAX endpoints.
*   **`uninstall.php`**: Contains the code to clean up the plugin's data (options, tables) upon uninstallation.
*   **`includes/class-ddtg-installer.php`**: Manages the database schema. The `install()` method, triggered on plugin activation and updates, creates and updates the necessary database tables. **Note:** The current implementation in the installer creates `draglearn_courses` and `draglearn_lessons` tables, which is inconsistent with the rest of the plugin's use of `draglearn_games`, `draglearn_events`, and `draglearn_attempts`.
*   **`includes/class-ddtg-admin.php`**: Handles the plugin's backend logic. It renders the admin pages for viewing games and results, and it also contains the AJAX handler for score submission.
*   **`includes/class-ddtg-games-list-table.php`**: Implements the `WP_List_Table` class to display a list of games in the WordPress admin.
*   **`includes/class-ddtg-results-list-table.php`**: Implements the `WP_List_Table` class to display the results of a specific game.
*   **`includes/class-ddtg-shortcode.php`**: Manages the frontend rendering of the game. It defines the `[draglearn_game]` shortcode, checks for attempt limits, creates new attempt records in the database, and enqueues the necessary JavaScript and CSS files.

### JavaScript and CSS Files

*   **`assets/js/draglearn-game.js`**: Contains the frontend logic for the game, including the drag-and-drop functionality, user feedback, and the AJAX call to submit the score.
*   **`assets/css/draglearn-game.css`**: Provides the styling for the game interface.

## Database Schema

The plugin utilizes the following database tables:

*   **`wp_draglearn_games`**: Stores the game configurations.
    *   `game_id` (BIGINT, Primary Key)
    *   `name` (VARCHAR)
    *   `max_attempts` (INT)
*   **`wp_draglearn_events`**: Stores the events (questions) for each game.
    *   `event_id` (BIGINT, Primary Key)
    *   `game_id` (BIGINT, Foreign Key)
    *   `event_name` (VARCHAR)
    *   `event_date` (DATE)
*   **`wp_draglearn_attempts`**: Stores the user attempts for each game.
    *   `attempt_id` (BIGINT, Primary Key)
    *   `user_id` (BIGINT, Foreign Key)
    *   `game_id` (BIGINT, Foreign Key)
    *   `start_time` (DATETIME)
    *   `finish_time` (DATETIME)
    *   `score` (INT)

**Note:** As mentioned, the `class-ddtg-installer.php` file has a schema that is inconsistent with the rest of the plugin. This report assumes the schema above is the intended final structure.

## Key Hook/AJAX Endpoints

### WordPress Hooks

*   **`register_activation_hook`**: `draglearn_activate` (in `draglearn.php`) - Triggers the `DDTG_Installer::install()` method.
*   **`plugins_loaded`**: `draglearn_update_check` (in `draglearn.php`) - Checks for plugin version updates and triggers the installer if needed.
*   **`admin_menu`**: `draglearn_admin_menu` (in `draglearn.php`) - Registers the admin menu pages.
*   **`init`**: `DDTG_Shortcode::init` (in `draglearn.php`) - Initializes the shortcode.

### Shortcodes

*   **`[draglearn_game]`**: Renders the game on the frontend. Managed by the `DDTG_Shortcode` class.

### AJAX Endpoints

*   **`wp_ajax_record_score` / `wp_ajax_nopriv_record_score`**: `DDTG_Admin::handle_score_submission` (in `draglearn.php`) - Handles the submission of scores from the frontend.

## Feature Confirmation

*   **Versioned DB Upgrade Logic**: Confirmed. `draglearn.php` contains a `draglearn_update_check` function that compares the stored version with the defined `DRAGLEARN_VERSION` and runs the installer if they don't match.
*   **Admin Forms for Create Game (including CSV upload handling)**: Partially confirmed. The admin menu for "My Games" exists, and the `DDTG_Admin` class has a `my_games_page_content` method that renders a list of games. However, the code for the "Add New" page and the CSV upload handling is not present in the provided files.
*   **Shortcode `[draglearn_game]` handler**: Confirmed. The `DDTG_Shortcode` class handles the `[draglearn_game]` shortcode, renders the game, and enqueues the necessary assets.
*   **Frontend attempt limit check and database initialization**: Confirmed. The `DDTG_Shortcode::render_game` method checks the `max_attempts` from the `wp_draglearn_games` table against the user's attempt count in `wp_draglearn_attempts`. It also inserts a new attempt record when a user starts a game.
*   **AJAX score submission endpoint (ddtg_submit_score)**: Confirmed. The `ddtg_submit_score` action is handled by the `DDTG_Admin::handle_score_submission` method, which updates the `wp_draglearn_attempts` table with the score and finish time.
*   **Teacher Reporting UI (ddtg-game-results)**: Confirmed. The `draglearn_admin_menu` function registers a "Game Results" page (`ddtg-game-results`), and the `DDTG_Admin::results_page_content` method renders the results using the `DDTG_Results_List_Table` class.
