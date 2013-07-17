#!/usr/bin/env bash
# usage rrd_fall_checker.sh path/to/file.rrd 10 20
# 10 - min critical level
# 20 - max critical level
# usage rrd_fall_checker.sh path/to/file.rrd 8 10 100 80
# 8 - min critical level
# 10 - min warning level
# 100 - max critical level
# 80 - max warning level
FILE=$1

BASEPATH="/usr/local/www/cacti/rra/"
RRDRES=900
RRD="$BASEPATH$FILE"
#now
TIME=$(date +%s)-300
NOW=`rrdtool fetch $RRD AVERAGE -r $RRDRES \
   -e $(($TIME/$RRDRES*$RRDRES)) -s e-1h`
if [ "$?" != "0" ];then echo Fetch RRD data failed [$RRD]!; exit 3;fi;

/usr/bin/env php "`dirname $0`/rrd_level_checker.php" "$NOW" $2 $3 $4 $5
