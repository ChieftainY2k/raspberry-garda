#!/bin/bash

#helper function
log_message()
{
    LOGPREFIX="[$(date '+%Y-%m-%d %H:%M:%S')][$(basename $0)]"
    MESSAGE=$1
    echo "$LOGPREFIX $MESSAGE"
}

#check for errors
check_errors()
{
    local EXITCODE=$1
    if [[ ${EXITCODE} -ne 0 ]]; then
        log_message "ERROR: Exit code ${EXITCODE} , check the ouput for details, press ENTER to continue or Ctrl-C to abort."
        exit 1
    fi
}

log_message "updating kerberos code..."

sed -i "s|Route::prefix('api|Route::prefix('kerberos/api|g" /var/www/web/app/Providers/RouteServiceProvider.php
sed -i "s|'/capture'|'/kerberos/capture'|g" /var/www/web/config/app.php
sed -i "s|Route::get('|Route::get('kerberos/|g" /var/www/web/routes/web.php
sed -i "s|Route::get('kerberos//'|Route::get('kerberos/dashboard'|g" /var/www/web/routes/web.php
sed -i "s|Route::post('|Route::post('kerberos/|g" /var/www/web/routes/web.php
sed -i "s|Route::put('|Route::put('kerberos/|g" /var/www/web/routes/web.php

find /var/www/web/resources/views -type f -exec sed -i -e "s|URL::to('/|URL::to('/kerberos/|g" {} \;
find /var/www/web/resources/views -type f -exec sed -i -e 's|_baseUrl = "http://|_baseUrl = "//|g' {} \;
find /var/www/web/resources/views -type f -exec sed -i -e 's|url: "|url: "/kerberos|g' {} \;
find /var/www/web/public/js -type f -exec sed -i -e 's|href="/api|href="/kerberos/api|g' {} \;

find /var/www/web/app/Http/Controllers -type f -exec sed -i -e "s|URL::to('/|URL::to('/kerberos/|g" {} \;
find /var/www/web/app/Http/Controllers -type f -exec sed -i -e "s|Redirect::to('|Redirect::to('kerberos/|g" {} \;
find /var/www/web/app/Http/Middleware -type f -exec sed -i -e "s|redirect('|redirect('kerberos|g" {} \;
find /var/www/web/app/Http/Middleware -type f -exec sed -i -e "s|redirect('kerberos/'|redirect('kerberos/dashboard'|g" {} \;

log_message "creating symbolic links..."
ln -s /var/www/web/public /var/www/web/public/kerberos
check_errors $?

