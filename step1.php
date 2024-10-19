<?php
// TODO:
// - intermonth/interyear sessions

date_default_timezone_set('Asia/Jakarta');
$fileArr = file('feeds/live-current.csv', FILE_SKIP_EMPTY_LINES);

for ($i = 0; $i < count($fileArr); $i++) {
  $lineArr[$i] = preg_replace('/ \+0700,/', ',', $fileArr[$i]);
  $csvArr = explode(',', $lineArr[$i]); 
  $talentID = $csvArr[0];
  if ($i == 0) {
    // display header line
//    echo $lineArr[$i];
    continue;
  }
  #echo $i."csvArr0 = ".$csvArr[0]." csvArr1 = ".$csvArr[1]." csvArr2 = ".$csvArr[2]." csvArr3 = ".$csvArr[3]." csvArr4 = ".$csvArr[4]."\n";
  $timeStart = date_parse($csvArr[2]);
  $timeStop = date_parse($csvArr[3]);
  $timeStartDay = $timeStart['day'];
  $timeStartMonth = $timeStart['month'];
  $timeStartYear = $timeStart['year'];
  $timeStopDay = $timeStop['day'];
  $timeStopMonth = $timeStop['month'];
  $timeStopYear = $timeStop['year'];
  if (($csvArr[1] != '') && ($csvArr[4] >= 60) && ($csvArr[4] <= 18000)) {
    // check for interday sessions
    if ($timeStart['day'] != $timeStop['day']) {
       // interday data
#      if (($timeStart['month'] == $timeStop['month']) && ($timeStart['year'] == $timeStop['year'])) {
        // break the session and create 2 new lines
        $newStopTime = $timeStart['year'] . '-';
        if ($timeStart['month'] < 10) {
          $newStopTime .= '0' . $timeStart['month'] . '-';
        } else {
          $newStopTime .= $timeStart['month'] . '-';
        }
        if ($timeStart['day'] < 10) {
          $newStopTime .= '0' . $timeStart['day'] . ' 23:59:59';
        } else {
          $newStopTime .= $timeStart['day'] . ' 23:59:59';
        }
        $newStartTime = $timeStop['year'] . '-';
        if ($timeStop['month'] < 10) {
          $newStartTime .= '0' . $timeStop['month'] . '-';
        } else {
          $newStartTime .= $timeStop['month'] . '-';
        }
        if ($timeStop['day'] < 10) {
          $newStartTime .= '0' . $timeStop['day'] . ' 00:00:00';
        } else {
          $newStartTime .= $timeStop['day'] . ' 00:00:00';
        }
        $newDuration1 = strtotime($newStopTime) - strtotime($csvArr[2]);
        $newDuration2 = strtotime($csvArr[3]) - strtotime($newStartTime);
        echo $csvArr[0] . ',' . $csvArr[1] . ',' . $csvArr[2] . ',' . $newStopTime . ',' . $newDuration1 . ",EOD\n";
        if ($timeStart['month'] == $timeStop['month']) {
          // if entry is of next month's, skip it
          // if entry is within month interday, print new entry
          echo $csvArr[0] . ',' . $csvArr[1] . ',' . $newStartTime . ',' . $csvArr[3] . ',' . $newDuration2 . ",SOD\n";
        }
#      } else {
#        // intermonth/interyear entry detected, handle by breaking the session
#        if ($timeStart['year'] <> $timeStop['year']) {
#          // handle month difference and year difference
#        }
#        if ($timeStart['month'] <> $timeStop['month']) {
#          
#        }
#        #echo "corrupted line #$i\n";
#        #echo $csvArr[0] . ',' . $csvArr[1] . ',' . $csvArr[2] . ',' . $csvArr[3] . ',' . trim($csvArr[4]) . ",error\n";
#        #exit;
#      }
    } else {
      // intraday data
      echo trim($lineArr[$i]) . "\n";
/*      // process the talents and input into array
      for ($i = 0; $i < count($lineArr); $i++) {
        $talentArr[$talentID][$day] += trim($csvArr[4]);
*/
    }
  }
}

function accountTalentData($talentID, $startTime, $duration) {

}
?>
