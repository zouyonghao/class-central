#!/bin/bash
DIR="$( cd "$( dirname "$0" )" && pwd )"
cd $DIR
cd ../

env=$1
type=$2
echo "Running Class Central MOOC Tracker Search Cron of type $2 for $env environment"
echo "Reindexing all the courses"
php app/console classcentral:elasticsearch:indexer --env=$env --no-debug
today=`date +%F`

#sleep for 60 seconds
echo "Indexing done. Going to sleep for a while"
sleep 300s

echo "Create jobs"

echo "All jobs created. Going to sleep for a while"
#sleep for index to be updated
sleep 300s

echo "Run search jobs"
php app/console classcentral:elasticsearch:runjobs $2 $today --env=$env --no-debug