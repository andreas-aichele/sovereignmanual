#!/bin/bash

ssh -p22 ubuntu@sovereignmanual.com mysqldump --single-transaction --lock-tables=false --skip-lock-tables sovereignmanual > live.sql
ddev import-db --file=live.sql

# copy images from storage/app/public to local storage/app/public
rsync -avz -p -e "ssh -p22" ubuntu@sovereignmanual.com:/var/www/html/sovereignmanual.com/storage/app/public/ storage/app/public/