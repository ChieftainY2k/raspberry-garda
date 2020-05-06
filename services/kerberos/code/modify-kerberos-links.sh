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

sed -i "s|Route::get('login|Route::get('kerberos/login|g" /var/www/web/routes/* /var/www/web/app/Providers/*
sed -i "s|Route::post('login/login|Route::post('kerberos/login/login|g" /var/www/web/routes/* /var/www/web/app/Providers/*
sed -i "s|Route::prefix('api|Route::prefix('kerberos/api|g" /var/www/web/routes/* /var/www/web/app/Providers/*
sed -i "s|Route::get('users|Route::get('kerberos/users|g" /var/www/web/routes/* /var/www/web/app/Providers/*

sed -i "s|Route::get('images|Route::get('kerberos/images|g" /var/www/web/routes/* /var/www/web/app/Providers/*
sed -i "s|Route::get('system|Route::get('kerberos/system|g" /var/www/web/routes/* /var/www/web/app/Providers/*

sed -i "s|Route::post('cloud|Route::post('kerberos/cloud|g" /var/www/web/routes/* /var/www/web/app/Providers/*

sed -i "s|Route::get('name|Route::get('kerberos/name|g" /var/www/web/routes/* /var/www/web/app/Providers/*
sed -i "s|Route::put('name|Route::put('kerberos/name|g" /var/www/web/routes/* /var/www/web/app/Providers/*

sed -i "s|Route::get('condition|Route::get('kerberos/condition|g" /var/www/web/routes/* /var/www/web/app/Providers/*
sed -i "s|Route::put('condition|Route::put('kerberos/condition|g" /var/www/web/routes/* /var/www/web/app/Providers/*


#Route::get('
#Route::get('/kerberos/
#
#Route::post('
#Route::post('/kerberos/
#
#
#{{URL::to('
#{{URL::to('/kerberos/
#
#RouteServiceProvider.php
#Route::prefix('api')
#Route::prefix('/kerberos/api')
#
#$.get( _baseUrl + "/api/v1/system/kerberos", function(data)
#$.get( _baseUrl + "/kerberos/api/v1/system/kerberos", function(data)