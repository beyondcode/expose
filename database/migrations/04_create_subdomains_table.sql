CREATE TABLE IF NOT EXISTS subdomains (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    subdomain STRING NOT NULL,
    created_at DATETIME,
    updated_at DATETIME
)
