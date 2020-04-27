CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name STRING NOT NULL,
    email STRING,
    auth_token STRING,
    created_at DATETIME,
    updated_at DATETIME
)
