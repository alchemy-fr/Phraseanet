#!/bin/bash

echo `date +“%Y-%m-%d %H:%M:%S”` ” - Newrelic and Blackfire extension deactivation.”
rm -f /usr/local/etc/php/conf.d/zz-blackfire.ini
rm -f /usr/local/etc/php/conf.d/newrelic.ini 
