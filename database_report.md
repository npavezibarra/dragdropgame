# Database Tables Report

Based on the `includes/class-ddtg-installer.php` file, the DragLearn plugin creates the following three tables:

### 1. `wp_ddg_games`

This table stores the games.

| Column        | Type                | Description                                |
|---------------|---------------------|--------------------------------------------|
| `game_id`     | `BIGINT UNSIGNED`   | **Primary Key** - Unique identifier for the game. |
| `name`        | `VARCHAR(255)`      | The name of the game.                      |
| `description` | `TEXT`              | A description of the game.                 |
| `created_at`  | `TIMESTAMP`         | The date and time the game was created.    |

### 2. `wp_ddg_events`

This table stores events related to games.

| Column       | Type              | Description                                |
|--------------|-------------------|--------------------------------------------|
| `event_id`   | `BIGINT UNSIGNED` | **Primary Key** - Unique identifier for the event. |
| `game_id`    | `BIGINT UNSIGNED` | The ID of the game this event belongs to.  |
| `event_type` | `VARCHAR(50)`     | The type of event.                         |
| `event_data` | `TEXT`            | Data associated with the event.            |
| `created_at` | `TIMESTAMP`       | The date and time the event was created.   |

### 3. `wp_ddg_attempts`

This table stores user attempts at games.

| Column        | Type              | Description                                    |
|---------------|-------------------|------------------------------------------------|
| `attempt_id`  | `BIGINT UNSIGNED` | **Primary Key** - Unique identifier for the attempt. |
| `game_id`     | `BIGINT UNSIGNED` | The ID of the game that was attempted.         |
| `user_id`     | `BIGINT UNSIGNED` | The ID of the user who made the attempt.       |
| `start_time`  | `TIMESTAMP`       | The start time of the attempt.                 |
| `finish_time` | `TIMESTAMP NULL`  | The finish time of the attempt.                |
| `score`       | `INT`             | The user's score for the attempt.              |
| `total`       | `INT`             | The total possible score for the attempt.      |
