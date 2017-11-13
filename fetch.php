<?php
// TODO Add attachments to sender message
// TODO Skip large attachments

require_once 'app/config.php';

if (!file_exists(QUEUE_FILE) || filesize(QUEUE_FILE) <= 0) {
  die('Run `php app/update.php` to initialize database.');
}

foreach (CONFIGURATION as $listName => $configuration) {
  $mailbox = new PhpImap\Mailbox(sprintf('{%s:993/imap/ssl}INBOX', $configuration['IMAP']['HOST']), $configuration['IMAP']['USER'], $configuration['IMAP']['PASSWORD'], MAIL_ATTACHMENTS);
  $mailbox->setExpungeOnDisconnect(true);
  $mailsIds = $mailbox->searchMailbox($configuration['IMAP']['SEARCH']);
  $listPublic = isset($configuration['PUBLIC']) ? $configuration['PUBLIC'] : false;

  foreach ($mailsIds as $message_uid) {
    $recieved = $mailbox->getMail($message_uid, false);

    if (!$listPublic && !isset($configuration['LIST'][$recieved->fromAddress])) {
      $mailbox->markMailAsRead($message_uid);
      $mailbox->moveMail($message_uid, $configuration['IMAP']['ERRORS']);
      Analog::info(sprintf('Mail[%] not in mailing list', $recieved->fromAddress ));
      continue;
    }

    if ($queue->has('messages', [ 'message_uid' => $recieved->messageId ])) {
      $mailbox->markMailAsRead($message_uid);
      $mailbox->moveMail($message_uid, $configuration['IMAP']['RECEIVED']);
      Analog::info(sprintf('Mail[%] already exists', $recieved->messageId ));
      continue;
    }

    $messageDate = new DateTime($recieved->date);

    $stmt = $queue->insert('messages', [
      'list_name' => $listName,
      'message_uid' => $recieved->messageId,
      'message_date' => $messageDate->getTimestamp(),
      'message_from' => $recieved->fromAddress,
      'subject' => $recieved->subject,
      'plain'=> $recieved->textPlain,
      'html'=> $recieved->textHtml
    ]);

    if ($stmt->rowCount() > 0) {
      $message_id = $queue->id();

      foreach ($configuration['LIST'] as $sender => $name) {
        if ($recieved->fromAddress === $sender) continue;
        $queue->insert('queue', [
          'message_id' => $message_id,
          'send_to' => $sender
        ]);
      }
    }

    $mailbox->markMailAsRead($message_uid);
    $mailbox->moveMail($message_uid, $configuration['IMAP']['RECEIVED']);
  }

  $mailbox->disconnect();
}
