#!/bin/sh
DIR="$( cd "$( dirname "$0" )" && pwd )"
cd $DIR
cd ../
env=$1
echo "Running Class Central bi weekly new session cron for $env environment"
echo "Reindexing all the courses"
php app/console classcentral:elasticsearch:indexer --env=$env --no-debug
today=`date +%F`

#sleep for 60 seconds
echo "Indexing done. Going to sleep for a while"
sleep 300s

echo "Creating Jobs for MOOC Tracker New Session(Just Announced courses) notifications to be sent"
php app/console mooctracker:notification:newsessions  $today --env=$env --no-debug

echo "All jobs created. Going to sleep for a while"
#sleep for index to be updated
sleep 300s

echo "Running Jobs to send reminder emails for courses with new sessions"
php app/console classcentral:elasticsearch:runjobs email_notification_new_session_2weeks $today --env=$env --no-debug
