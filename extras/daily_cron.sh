#!/bin/sh
DIR="$( cd "$( dirname "$0" )" && pwd )"
cd $DIR
cd ../
env=$1
echo "Running Class Central daily cron for $env environment"


today=`date +%F`
yesterday=`date +%F --date="-1 day"`

# Update user activity stats in slack
php5.6 app/console classcentral:dailyuseractivity $yesterday --env=$env

# Computing feedback calculations for reviews
echo "Computing feedback calculations for reviews"
php5.6 app/console classcentral:reviews:precomputefeedback --env=$env

# Computing scores for reviews
echo "Computing scores for reviews"
php5.6 app/console classcentral:reviews:score --env=$env

# Computing profile score
echo "Calculating profile score"
php5.6 app/console classcentral:user:profilescore --env=$env

# Generate follow counts
echo "Generate follow counts"
php5.6 app/console classcentral:follows:calculatecount --env=$env

# Run Coursera scraper
echo "Updating edX courses"
php5.6 app/console classcentral:scrape Coursera --simulate=N --type=add
php5.6 app/console classcentral:scrape Coursera --simulate=N --type=update

# Run edX scraper
echo "Updating edX courses"
php5.6 app/console classcentral:scrape edx --simulate=N --type=add
php5.6 app/console classcentral:scrape edx --simulate=N --type=update

echo "Update FutureLearn courses"
php5.6 app/console classcentral:scrape Futurelearn --simulate=N --type=add
php5.6 app/console classcentral:scrape Futurelearn --simulate=N --type=update

echo "Update Udacity courses"
php5.6 app/console classcentral:scrape Udacity --simulate=N --type=add
php5.6 app/console classcentral:scrape Udacity --simulate=N --type=update

echo "Update Canvas courses"
php5.6 app/console classcentral:scrape Canvas --simulate=N --type=add
php5.6 app/console classcentral:scrape Canvas --simulate=N --type=update

echo "Update Kadenze courses"
php5.6 app/console classcentral:scrape Kadenze --simulate=N --type=add
php5.6 app/console classcentral:scrape Kadenze --simulate=N --type=update

echo "Update Open2Study courses"
php5.6 app/console classcentral:scrape Open2study --simulate=N --type=add

echo "Reindexing all the courses"
php5.6 app/console classcentral:elasticsearch:indexer --courses=Yes --offset=1 --env=$env --no-debug
php5.6 app/console classcentral:elasticsearch:indexer --courses=Yes --offset=1000 --env=$env --no-debug
php5.6 app/console classcentral:elasticsearch:indexer --courses=Yes --offset=2000 --env=$env --no-debug
php5.6 app/console classcentral:elasticsearch:indexer --courses=Yes --offset=3000 --env=$env --no-debug
php5.6 app/console classcentral:elasticsearch:indexer --courses=Yes --offset=4000 --env=$env --no-debug
php5.6 app/console classcentral:elasticsearch:indexer --courses=Yes --offset=5000 --env=$env --no-debug
php5.6 app/console classcentral:elasticsearch:indexer --courses=Yes --offset=6000 --env=$env --no-debug
php5.6 app/console classcentral:elasticsearch:indexer --courses=Yes --offset=7000 --env=$env --no-debug
php5.6 app/console classcentral:elasticsearch:indexer --courses=Yes --offset=8000 --env=$env --no-debug
php5.6 app/console classcentral:elasticsearch:indexer --courses=Yes --offset=9000 --env=$env --no-debug
php5.6 app/console classcentral:elasticsearch:indexer --courses=Yes --offset=10000 --env=$env --no-debug
php5.6 app/console classcentral:elasticsearch:indexer --courses=Yes --offset=11000 --env=$env --no-debug
php5.6 app/console classcentral:elasticsearch:indexer --courses=Yes --offset=12000 --env=$env --no-debug
php5.6 app/console classcentral:elasticsearch:indexer --courses=Yes --offset=13000 --env=$env --no-debug
php5.6 app/console classcentral:elasticsearch:indexer --courses=Yes --offset=14000 --env=$env --no-debug
php5.6 app/console classcentral:elasticsearch:indexer --courses=Yes --offset=15000 --env=$env --no-debug
php5.6 app/console classcentral:elasticsearch:indexer --courses=Yes --offset=16000 --env=$env --no-debug
php5.6 app/console classcentral:elasticsearch:indexer --courses=Yes --offset=17000 --env=$env --no-debug
php5.6 app/console classcentral:elasticsearch:indexer --courses=No --env=$env --no-debug

#sleep for 60 seconds
echo "Indexing done. Going to sleep for a while"
sleep 300s

echo "Creating Jobs for MOOC Tracker Reminders to be sent for courses starting 2 weeks later"
php5.6 app/console mooctracker:reminders:coursestart email_reminder_course_start_2weeks $today 6 --env=$env --no-debug

echo "Creating Jobs for MOOC Tracker Reminders to be sent for courses starting tomorrow"
php5.6 app/console mooctracker:reminders:coursestart email_reminder_course_start_1day $today 6 --env=$env --no-debug

# echo "Creating Jobs for Sending review solicitation emails"
# php5.6 app/console mooctracker:completedcourses:askforreviews $today --env=$env --no-debug

echo "Creating Jobs for Sending follow up emails"
php5.6 app/console mooctracker:user:followup $today --env=$env --no-debug

echo "All jobs created. Going to sleep for a while"
#sleep for index to be updated
sleep 300s
echo "Running MOOC Tracker jobs to send reminder emails for courses starting 2 weeks later"
php5.6 app/console classcentral:elasticsearch:runjobs email_reminder_course_start_2weeks $today 0 --env=$env --no-debug
nohup php5.6 app/console classcentral:elasticsearch:runjobs email_reminder_course_start_2weeks $today 1 --env=$env --no-debug &
nohup php5.6 app/console classcentral:elasticsearch:runjobs email_reminder_course_start_2weeks $today 2 --env=$env --no-debug &
nohup php5.6 app/console classcentral:elasticsearch:runjobs email_reminder_course_start_2weeks $today 3 --env=$env --no-debug &
nohup php5.6 app/console classcentral:elasticsearch:runjobs email_reminder_course_start_2weeks $today 4 --env=$env --no-debug &
nohup php5.6 app/console classcentral:elasticsearch:runjobs email_reminder_course_start_2weeks $today 5 --env=$env --no-debug &

echo "Running MOOC Tracker jobs to send reminder emails for courses starting tomorrow"
nohup php5.6 app/console classcentral:elasticsearch:runjobs email_reminder_course_start_1day $today 0 --env=$env --no-debug
nohup php5.6 app/console classcentral:elasticsearch:runjobs email_reminder_course_start_1day $today 1 --env=$env --no-debug &
nohup php5.6 app/console classcentral:elasticsearch:runjobs email_reminder_course_start_1day $today 2 --env=$env --no-debug &
nohup php5.6 app/console classcentral:elasticsearch:runjobs email_reminder_course_start_1day $today 3 --env=$env --no-debug &
nohup php5.6 app/console classcentral:elasticsearch:runjobs email_reminder_course_start_1day $today 4 --env=$env --no-debug &
nohup php5.6 app/console classcentral:elasticsearch:runjobs email_reminder_course_start_1day $today 5 --env=$env --no-debug &

#echo "Running job to send review solicitation emails"
#php5.6 app/console classcentral:elasticsearch:runjobs mt_ask_for_reviews_for_completed_courses $today --env=$env --no-debug

echo "Running job to send follow up emails"
php5.6 app/console classcentral:elasticsearch:runjobs mt_new_user_follow_up $today --env=$env --no-debug

echo "Generating Sitemap"
php5.6 app/console classcentral:sitemap:generate --env=$env --no-debug