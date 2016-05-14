#!/usr/bin/env bash

# Crontab usage:
# * * * * * lockf -s -t 0 /tmp/failAlert.lock /root/failAlert.sh
# lockf to ensure its only started once, and restarted at boot / some reason it dies

while sleep 1; do /usr/local/bin/php /root/Monitor.php > /root/failAlert.log; done
