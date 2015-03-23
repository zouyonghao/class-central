#!/bin/bash

DIR="$( cd "$( dirname "$0" )" && pwd )"
cd $DIR
cd ../
env=$1

echo "Running Cron for Summaring reviews"
php app/console classcentral:reviews:summarize all 1 --env=$env --no-debug