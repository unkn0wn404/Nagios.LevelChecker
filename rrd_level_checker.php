#!/usr/bin/env php
<?php
if (sizeof($argv) < 3)
	die("You must provide two parameters with rrd values and min level");

define("RES_OK",    0);
define("RES_WARN",  1);
define("RES_CRIT",  2);
define("RES_UNKNOWN",3);

$nowData = $argv[1];
if (sizeof($argv) <= 4)
{
	$minCritLevel = (isset($argv[2])?(float)$argv[2]:null);
	$minWarnLevel = $minCritLevel;
	$maxCritLevel = (isset($argv[3])?(float)$argv[3]:null);
	$maxWarnLevel = $maxCritLevel;
}
else
{
	$minCritLevel = (isset($argv[2])?(float)$argv[2]:null);
	$minWarnLevel = (isset($argv[3])?(float)$argv[3]:null);
	$maxCritLevel = (isset($argv[4])?(float)$argv[4]:null);
	$maxWarnLevel = (isset($argv[5])?(float)$argv[5]:null);
}


echo "Check with min=$minWarnLevel..$minCritLevel, max=$maxWarnLevel..$maxCritLevel; ";
/**
 * Values are like
 *  RRD file name

 1358497800: 1.3100900000e+03
 1358498100: 1.3100900000e+03
 *
 * Output is like
 * Array
 (
     [1358498700] => 1310.09
     [1358499000] => 1310.09
     ...
     [1358502000] => 1426.2029
     [1358502300] => 1426.52
 )
 */
function parseFromString($data, $timeFormat = 'U')
{
	$return = array();
	if (preg_match_all('#([0-9]+):\s+([0-9\.e+-]+)#i',$data, $match))
	{
		for ($i = 0;$i < sizeof($match[1]);$i++)
		{
			$return[date($timeFormat, $match[1][$i])] = (float)$match[2][$i];
		}
	}
	return $return;
}

$now = parseFromString($nowData, 'H:i');
$yesterday = parseFromString($yesterdayData,'H:i');


$badPeriods = array();
$continuosError = false;
$result = RES_UNKNOWN;
foreach ($now as $time=>$value)
{
	$level = number_format($value,3,'.','');
	if ($level < $minCritLevel)
	{
		$badPeriods[$time] = $level;
		$continuosError = true;
		$result = RES_CRIT;
	}
	elseif ($level < $minWarnLevel)
	{
		$badPeriods[$time] = $level;
		$continuosError = true;
		$result = RES_WARN;
	}
	elseif ($maxCritLevel && $level > $maxCritLevel)
	{
		$badPeriods[$time] = $level;
		$continuosError = true;
		$result = RES_CRIT;
	}
	elseif ($maxWarnLevel && $level > $maxWarnLevel)
	{
		$badPeriods[$time] = $level;
		$continuosError = true;
		$result = RES_WARN;
	}
	else
	{
		$continuosError = false;
		$result = RES_OK;
	}
}

//show results
if (sizeof($badPeriods))
{
	if ($continuosError)
	{
		//error persist at now
		echo "Bad level! (last is $level) ";
		foreach ($badPeriods as $time => $level)
			echo "[$time: $level];";

		exit($result);
	}
	else
	{
		//bad ratio was in past
		echo "Bad level was in past. Now is OK. (last is $level) ";
		foreach ($badPeriods as $time => $level)
			echo "[$time: $level];";

		exit($result);
	}
}
else
{
	echo "Level is ok. Last for $time is $level";
	exit(RES_OK);
}
