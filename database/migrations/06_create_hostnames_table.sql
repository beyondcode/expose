CREATE TABLE IF NOT EXISTS hostnames (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    hostname STRING NOT NULL,
    created_at DATETIME,
    updated_at DATETIME
)
