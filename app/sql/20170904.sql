CREATE TABLE IF NOT EXISTS messages (
  id INTEGER PRIMARY KEY,
  list_name TEXT NOT NULL,
  message_uid TEXT NOT NULL,
  message_date INTEGER NOT NULL,
  message_from TEXT NOT NULL,
  subject TEXT,
  plain TEXT,
  html TEXT,
  CONSTRAINT uc_message UNIQUE (message_uid)
);

CREATE TABLE IF NOT EXISTS queue (
  id INTEGER PRIMARY KEY,
  message_id INTEGER NOT NULL,
  send_to TEXT NOT NULL,
  sent INTEGER DEFAULT 0,
  CONSTRAINT uc_message_to UNIQUE (message_id, send_to)
);
