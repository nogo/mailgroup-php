<?php
// TODO Add attachments to sender message
// TODO Skip large attachments

require_once 'app/config.php';

if (!file_exists(QUEUE_FILE) || filesize(QUEUE_FILE) <= 0) {
  die('Run `php app/update.php` to initialize database.');
}

$mailbox = new PhpImap\Mailbox(sprintf('{%s:993/imap/ssl}INBOX', CONFIGURATION['IMAP']['HOST']), CONFIGURATION['IMAP']['USER'], CONFIGURATION['IMAP']['PASSWORD'], MAIL_ATTACHMENTS);
$mailbox->setExpungeOnDisconnect(true);
$mailsIds = $mailbox->searchMailbox(CONFIGURATION['IMAP']['SEARCH']);

foreach ($mailsIds as $message_uid) {
  $recieved = $mailbox->getMail($message_uid);
  if (!isset(CONFIGURATION['LIST'][$recieved->fromAddress])) {
    $mailbox->moveMail($message_uid, CONFIGURATION['IMAP']['ERRORS']);
    continue;
  }

  $messageDate = new DateTime($recieved->date);

  $stmt = $queue->insert('messages', [
    'message_uid' => $message_uid,
    'message_date' => $messageDate->getTimestamp(),
    'message_from' => $recieved->fromAddress,
    'subject' => $recieved->subject,
    'plain'=> $recieved->textPlain,
    'html'=> $recieved->textHtml
  ]);

  if ($stmt->rowCount() > 0) {
    $message_id = $queue->id();

    foreach (CONFIGURATION['LIST'] as $sender => $name) {
      if ($recieved->fromAddress === $sender) continue;
      $queue->insert('queue', [
        'message_id' => $message_id,
        'send_to' => $sender
      ]);
    }
  }

  $mailbox->moveMail($message_uid, CONFIGURATION['IMAP']['RECIEVED']);
}

$mailbox->disconnect();
