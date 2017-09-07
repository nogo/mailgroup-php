<?php
// TODO Add attachments to sender message
// TODO Skip large attachments

require __DIR__ . '/../vendor/autoload.php';

define('ROOT_DIR', realpath(__DIR__ . '/../'));
define('CONFIGURATION_FILE', ROOT_DIR . '/data/configuration.yml');
define('QUEUE_FILE', ROOT_DIR . '/data/queue.sq3');
define('LOG_FILE', ROOT_DIR . '/data/log/mailgroup.log');
define('MAIL_ATTACHMENTS', ROOT_DIR . '/data/attachments');
define('INIT_DB', !file_exists(QUEUE_FILE));

use Analog\Analog;
use Analog\Handler\File as AnalogFileHandler;
use Medoo\Medoo;
use Symfony\Component\Yaml\Yaml;

Analog::handler(AnalogFileHandler::init(LOG_FILE));

if (!file_exists(CONFIGURATION_FILE)) {
  die('Copy the `cp configuration.yml.dist configuration.yml` in data folder.');
}
define('CONFIGURATION', Yaml::parse(file_get_contents(CONFIGURATION_FILE)));

$queue = new Medoo([
	'database_type' => 'sqlite',
	'database_file' => QUEUE_FILE
]);
