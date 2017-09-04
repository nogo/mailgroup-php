<?php
require_once 'config.php';

if (!file_exists(dirname(MAIL_ATTACHMENTS))) {
  mkdir(dirname(MAIL_ATTACHMENTS), 0644, true);
}
if (!file_exists(dirname(LOG_FILE))) {
  mkdir(dirname(LOG_FILE), 0644, true);
}

$files = glob(ROOT_DIR . '/app/sql/*.{sql}', GLOB_BRACE);
foreach($files as $file) {
  $queries = explode(';', file_get_contents($file));
  foreach ($queries as $query) {
    $queue->exec($query);
  }
}
