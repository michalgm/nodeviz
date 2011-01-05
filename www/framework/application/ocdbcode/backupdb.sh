#!/bin/sh
mysqldump -u oilchange -poilchange oilchange > /home/media/backup2/`date +'%s'`.sql
