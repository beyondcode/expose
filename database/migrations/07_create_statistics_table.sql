CREATE TABLE IF NOT EXISTS statistics (
     id INTEGER PRIMARY KEY AUTOINCREMENT,
     timestamp DATE,
     shared_sites INTEGER,
     shared_ports INTEGER,
     unique_shared_sites INTEGER,
     unique_shared_ports INTEGER,
     incoming_requests INTEGER
)
