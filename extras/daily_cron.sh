#!/bin/sh
DIR="$( cd "$( dirname "$0" )" && pwd )"
cd $DIR
cd ../
env=$1
echo "Running Class Central daily cron for $env environment"

# Computing feedback calculations for reviews
echo "Computing feedback calculations for reviews"
php app/console classcentral:reviews:precomputefeedback --env=$env

# Computing scores for reviews
echo "Computing scores for reviews"
php app/console classcentral:reviews:score --env=$env

# Computing profile score
echo "Calculating profile score"
# php app/console classcentral:user:profilescore --env=$env

# Run Coursera scraper
echo "Updating edX courses"
php app/console classcentral:scrape Coursera --simulate=N --type=add
php app/console classcentral:scrape Coursera --simulate=N --type=update

# Run edX scraper
echo "Updating edX courses"
php app/console classcentral:scrape edx --simulate=N --type=add
php app/console classcentral:scrape edx --simulate=N --type=update

echo "Update FutureLearn courses"
php app/console classcentral:scrape Futurelearn --simulate=N --type=add
php app/console classcentral:scrape Futurelearn --simulate=N --type=update

echo "Update Udacity courses"
php app/console classcentral:scrape Udacity --simulate=N --type=add
php app/console classcentral:scrape Udacity --simulate=N --type=update

echo "Update Canvas courses"
php app/console classcentral:scrape Canvas --simulate=N --type=add
php app/console classcentral:scrape Canvas --simulate=N --type=update

echo "Update Kadenze courses"
php app/console classcentral:scrape Kadenze --simulate=N --type=add
php app/console classcentral:scrape Kadenze --simulate=N --type=update

echo "Reindexing all the courses"
php app/console classcentral:elasticsearch:indexer --env=$env --no-debug
today=`date +%F`
yesterday=`date +%F --date="-1 day"`

# Update user activity stats in slack
php app/console classcentral:dailyuseractivity $yesterday --env=$env

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