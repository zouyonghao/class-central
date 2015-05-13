#!/bin/sh
DIR="$( cd "$( dirname "$0" )" && pwd )"
cd $DIR
cd ../
env=$1
echo "Running Class Central daily cron for $env environment"
echo "Reindexing all the courses"
php app/console classcentral:elasticsearch:indexer --env=$env --no-debug
today=`date +%F`

#sleep for 60 seconds
echo "Indexing done. Going to sleep for a while"
sleep 300s

echo "Creating Jobs for MOOC Tracker Reminders to be sent for courses starting 2 weeks later"
php app/console mooctracker:reminders:coursestart email_reminder_course_start_2weeks $today --env=$env --no-debug

echo "Creating Jobs for MOOC Tracker Reminders to be sent for courses starting tomorrow"
php app/console mooctracker:reminders:coursestart email_reminder_course_start_1day $today --env=$env --no-debug

echo "Creating Jobs for Sending review solicitation emails"
php app/console mooctracker:completedcourses:askforreviews $today --env=$env --no-debug

echo "Creating Jobs for Sending follow up emails"
php app/console mooctracker:user:followup $today --env=$env --no-debug

echo "All jobs created. Going to sleep for a while"
#sleep for index to be updated
sleep 300s
echo "Running MOOC Tracker jobs to send reminder emails for courses starting 2 weeks later"
php app/console classcentral:elasticsearch:runjobs email_reminder_course_start_2weeks $today --env=$env --no-debug

echo "Running MOOC Tracker jobs to send reminder emails for courses starting tomorrow"
php app/console classcentral:elasticsearch:runjobs email_reminder_course_start_1day $today --env=$env --no-debug

echo "Running job to send review solicitation emails"
php app/console classcentral:elasticsearch:runjobs mt_ask_for_reviews_for_completed_courses $today --env=$env --no-debug

echo "Running job to send follow up emails"
php app/console classcentral:elasticsearch:runjobs mt_new_user_follow_up $today --env=$env --no-debug