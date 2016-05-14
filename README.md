# phpNetmonitor
simple PHP network monitor with pushover ONLINE and OFFLINE push notifications

i use this script to monitor my switches/servers at a LAN-Party from a BSD (pfSense) router.
so this script will probably only work on FREEBSD!

Usage
==============

lockf to ensure it is only running once, crontab to restart it in case of reboot/crash

```
* * * * * lockf -s -t 0 /tmp/failAlert.lock /root/failAlert.sh
```
