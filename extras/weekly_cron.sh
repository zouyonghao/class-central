#!/bin/sh
DIR="$( cd "$( dirname "$0" )" && pwd )"
cd $DIR
cd ../
env=$1
echo "Running Class Central weekly cron for $env environment"

# Generate follow counts
echo "Generate follow counts"
php app/console classcentral:follows:calculatecount --env=$env