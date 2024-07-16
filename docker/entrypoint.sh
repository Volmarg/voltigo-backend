#!/bin/bash

# This chain of commands will
# - restart apache (without it the site won't work on HOST) + run `tail` continuously (else container will die)
# - restart clamav daemon else it won't start itself

echo -e "[DEBUG] Restarting apache and clamav \n";

service apache2 restart \
&& service clamav-daemon restart ;

echo -e "[DEBUG] Starting supervisor \n";

# Prepare n run supervisor
    mkdir -p /var/www/html/var/log/supervisor \
&& service supervisor start \
&& supervisorctl reread \
&& supervisorctl update \
&& supervisorctl start all;

echo -e "[DEBUG] Linking upload dir \n";

# Link some upload directories for public access
ln -sf /var/www/upload public/data;

echo -e "[DEBUG] Create invoice dir \n";

# Create dirs if don't exist
   mkdir -p /var/www/upload/invoice \
&& chown www-data. /var/www/upload -R;

echo -e "[DEBUG] Starting cron \n";

# start cron
service cron start;

echo -e "[DEBUG] Calling install-or-update \n";
cd /var/www/html && ./install-or-update.sh;