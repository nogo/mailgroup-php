<?php

require_once 'app/config.php';

if (!file_exists(QUEUE_FILE) || filesize(QUEUE_FILE) <= 0) {
  die('Run `php app/update.php` to initialize database.');
}

$queue_items = $queue->select('queue', '*', [ 'sent[=]' => false ]);
if (count($queue_items) <= 0) {
  die();
}

foreach (CONFIGURATION as $configuration) {
  $transport = new Swift_SmtpTransport($configuration['SMTP']['HOST'], 465, 'ssl');
  $transport->setUsername($configuration['SMTP']['USER'])->setPassword($configuration['SMTP']['PASSWORD']);
  $mailer = new Swift_Mailer($transport);
  $mailer->registerPlugin(new Swift_Plugins_AntiFloodPlugin(100, 30));

  foreach ($queue_items as $item) {
    $queue_message = $queue->get('messages', '*', ['id[=]' => $item['message_id']]);

    $message = new Swift_Message();
    $message->setSubject($queue_message['subject']);
    $message->setFrom([$configuration['SETUP']['MAIL'] => $configuration['SETUP']['NAME']])
      ->setBody($queue_message['plain'])
      ->addPart($queue_message['html'], 'text/html')
      ->setDate(new DateTime('@' . $queue_message['message_date']))
      ->setTo($item['send_to'])
      ->setReturnPath($configuration['SMTP']['BOUNCE']);

    try {
      $failed_recipients = [];
      if ($mailer->send($message, $failed_recipients) > 0) {
        $queue->update('queue', [ 'sent' => true ], [ 'id[=]' => $item['id'] ]);
        Analog::info(sprintf('Sent message[%d] to %s', $item['message_id'], $item['send_to']));
      } else {
        Analog::error(sprintf('Problem to sent message[%d] to %s', $item['message_id'], join('; ', $failed_recipients)));
      }
    } catch (\Exception $e) {
      Analog::error(sprintf('Problem to sent message[%d] to %s', $item['message_id'], $item['send_to']));
    }
  }
}
