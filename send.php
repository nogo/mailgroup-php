<?php

require_once 'app/config.php';

if (!file_exists(QUEUE_FILE) || filesize(QUEUE_FILE) <= 0) {
  die('Run `php app/update.php` to initialize database.');
}

$queue_items = $queue->select('queue', '*', [ 'sent[=]' => false ]);
if (count($queue_items) <= 0) {
  die();
}

$list = [];

// create mailer by configuration
foreach (CONFIGURATION as $listName => $configuration) {
  $transport = new Swift_SmtpTransport($configuration['SMTP']['HOST'], 465, 'ssl');
  $transport->setUsername($configuration['SMTP']['USER'])->setPassword($configuration['SMTP']['PASSWORD']);

  $mailer = new Swift_Mailer($transport);
  $mailer->registerPlugin(new Swift_Plugins_AntiFloodPlugin(100, 30));

  $list[$listName] = [
      'mailer' => $mailer,
      'configuration' => $configuration
  ];
}

// process messages
foreach ($queue_items as $item) {
  $queue_message = $queue->get('messages', '*', ['id[=]' => $item['message_id']]);
  if (empty($queue_message)) continue;

  $current = $list[$queue_message['list_name']];
  $configuration = $current['configuration'];
  $mailer = $current['mailer'];

  $message = new Swift_Message();
  $message->setSubject($queue_message['subject']);
  $message
    ->setDate(new DateTime('@' . $queue_message['message_date']))
    ->setFrom([$configuration['MAIL'] => $configuration['NAME']])
    ->setTo($item['send_to'])
    ->setReturnPath($configuration['SMTP']['BOUNCE']);

  $plain = trim($queue_message['plain']);
  $html = trim($queue_message['html']);

  if (empty($plain) && empty($html)) continue;

  if (empty($plain)){
    $message->setBody($html, 'text/html');
  } else if (empty($html)) {
    $message->setBody($plain);
  } else {
    $message->setBody($plain)->addPart($queue_message['html'], 'text/html');
  }

  try {
    $failed_recipients = [];
    if ($mailer->send($message, $failed_recipients) > 0) {
      $queue->update('queue', [ 'sent' => true ], [ 'id[=]' => $item['id'] ]);
    } else {
      Analog::error(sprintf('Problem to sent message[%d] to %s', $item['message_id'], join('; ', $failed_recipients)));
    }
  } catch (\Exception $e) {
    Analog::error(sprintf('Problem to sent message[%d] to %s', $item['message_id'], $item['send_to']));
  }
}
