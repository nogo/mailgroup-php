# Mailgroup

Mailgroup is a multi-account mailing list written in PHP using cron.

## Features

* Mult-accounting
* IMAP Account connection to read emails
* SwiftMailer to send emails
* Access only for members of the mail group
* Attachments are not forwared
* History save email in imap folder

## FUNCTIONALLITY

1. A mail sender sends an email to the IMAP account.
1. If the sender mail is on the mailing list, the mail gets into a queue and will be sent to each other member in the list.

## SETUP

Copy configuration file in data and edit it.
```
cp data\configuration.yml.dist data\configuration.yml

# Configuration

keyname_for_mailing_please_modify:              --> important to identify the account in the queue
  NAME: Mailing list name
  MAIL: mailing@domain.tld
  IMAP:
    HOST: imap.domain.tld
    USER: mailing@domain.tld
    PASSWORD: secure
    SEARCH: UNSEEN
    RECEIVED: INBOX.received
    ERROR: INBOX.errors
  SMTP:
    HOST: smtp.domain.tld
    USER: mailing@domain.tld
    PASSWORD: secure
    BOUNCE: bounce@domain.tld
  LIST:
    email@domain.tld: Name
    - ...
```

Create queue database.
```
php app/update.php
```

Add two entries to the crontab or call the `fetch.php` and `send.php` from another php script.
```
# Mailgroup
*/5 * * * * www-data php -f /path/to/mailgroup/fetch.php >> /path/to/mailgroup/data/log/fetch.cron.log 2>&1
*/5 * * * * www-data php -f /path/to/mailgroup/send.php >> /path/to/mailgroup/data/log/send.cron.log 2>&1
```
