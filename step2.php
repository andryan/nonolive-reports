<?php
date_default_timezone_set('Asia/Jakarta');
$fileArr = file('processed.csv', FILE_SKIP_EMPTY_LINES);

for ($i = 0; $i < count($fileArr); $i++) {
  $csvArr = explode(',', $fileArr[$i]);
  $talentID = $csvArr[0];
  $time = date_parse($csvArr[2]);
  $day = $time['day'];
  $duration = trim($csvArr[4]);
  @$talent[$talentID][$day] = $talent[$talentID][$day] + $duration;
  @$talent[$talentID]['entry']++;
}
foreach ($talent as $talentID => $value) {
  // if > 3 hrs, set to 3hrs
  // calculate 5 consecutive days bonus
  // calculate extra days bonus
  $dates = array();
  $extradays[$talentID] = array();
  $duration = 0;
  $total = 0;
  $totalAcknowledged = 0;
  $excess = 0;
  foreach ($value as $day => $duration) {
#  if ($talent[$key] > 10800) {
#    $talent[$key] = 10800;
#  }
#    echo "$day " . is_numeric($day)."\n";
    if (is_numeric($day)) {
      // 5-day consecutive bonus check
      array_push($dates, $day);
      if ($duration >= 3600) { // check for sessions with duration >= 3600 seconds
        $extradays[$talentID][$day] = $duration;
        if ($duration > 10800) {
          $extradays[$talentID][$day] = 10800;
        }
      }
      // max 3 hours check
      if ($duration > 10800) {
        // record total duration acknowledged by Nonolive, if duration is more than 3 hours, record 3 hours
        $totalAcknowledged += 10800;
        $excess = $duration - 10800;
        @$talent[$talentID]['excess'] += $excess;
        $talent[$talentID][$day] = 10800;
        #echo "\$key = $key, \$value = $value, \$subkey = $subkey, \$subvalue = $subvalue\n";
      } else {
        // record total duration acknowledged by Nonolive when duration is <= 3 hours, record as is
        $totalAcknowledged += $duration;
      }
      // record actual total duration
      $total += $duration;
    } else {
        // deal with non numeric keys in array
        #echo "skipping\n";
    }
  }
  $talent[$talentID]['total'] = $total; // total duration in seconds
  $talent[$talentID]['totalAcknowledged'] = $totalAcknowledged; // total duration acknowledged by Nonolive in seconds
  $talent[$talentID]['noOfDays'] = count($dates); // total of airing days
  $talent[$talentID]['noOfDaysMoreThanOneHour'] = count($extradays[$talentID]); // number of days with duration >= 3600 seconds
  $talent[$talentID]['moreThanDealHours'] = 0;
  if ($totalAcknowledged >= 180000) { // replace 60*60*50=180000 with secondary source dealHours variable
    $talent[$talentID]['moreThanDealHours'] = 1;
    $talent[$talentID]['noOfAcknowledgedHours'] = 50; // replace with secondary data source for dealHours
  } else {
    $talent[$talentID]['noOfAcknowledgedHours'] = floor($talent[$talentID]['totalAcknowledged']/3600); // display number of hours acknowledged, rounded down
  }
  if ($talent[$talentID]['moreThanDealHours'] == 1) {
#    $talent[$talentID]['extraDays'] = $talent[$talentID]['noOfDaysMoreThanOneHour'] - 17; // replace 17 with variable of no of days required to fulfill dealHours
  }
  #$talent[$talentID]['dealHours'] = $talentdeal[$talentID]['dealHours']; // secondary data source
  #$talent[$talentID]['dealDays'] = round($talentdeal[$talentID]['dealHours'] / 3); //max 3 hours per day
  if ((count($extradays[$talentID]) < 1) || ($talent[$talentID]['totalAcknowledged'] <= 180000)) {
    unset($extradays[$talentID]);
  }
#  echo "$talentID ";
#  print_r($dates);

  // 5-day bonus granting, min 1 hour per day
  $consecutivecount = 0;
  for ($i = 0; $i < count($dates); $i++) {
    $curday = $dates[$i];
#    echo "talent $talentID - $i iter\n";
    if ($talent[$talentID][$curday] >= 3600) {
      if ($i == 0) {
        $lastday = $curday;
      } else {
        $lastday = $dates[($i-1)];
      }
      $consecutivecount++;
#       echo "talent $talentID - adding curday $curday (".$talent[$talentID][$curday].") to consecutivecount=$consecutivecount\n";
      if (($consecutivecount > 1) && (($curday - $lastday) != 1)) {
        // only apply logic after consecutivecount = 1, inconsecutive days, break the streak
#        echo "talent $talentID - curday=$curday (".$talent[$talentID][$curday].") lastday=$lastday (".$talent[$talentID][$lastday]."), broken streak, inconsecutive days, consecutivecount=$consecutivecount\n";
        $consecutivecount = 0;
        continue;
      }
    } else {
        // duration <3600, break the streak
#        echo "talent $talentID - curday=$curday (".$talent[$talentID][$curday]."), broken streak, insufficient duration, consecutivecount=$consecutivecount\n";
        $consecutivecount = 0;
        continue;
    }
    if ($consecutivecount == 5) {
#      echo "talent $talentID - 5 days reached, bonus!\n";
      @$talent[$talentID]['consecutive']++;
      $consecutivecount = 0;
    }
  }

  // OLD
  // calculate extra days bonus
  // formula: number of deal hours (mostly 50) divided by 3 (max 3 hours per day), round up result
  // logic: if totalAcknowledged > 180000 (if 50 hours or no of deal hours * 3600), calculate bonus, otherwise skip
  // OLD
  if (array_key_exists($talentID, $extradays)) { // reverse numeric sort on the duration, calculate number of days required to achieve dealHours, noOfDaysMoreThanOneHour - result
    array_multisort($extradays[$talentID], SORT_NUMERIC, SORT_DESC);
    // calculate how many days required to achieve 50 hours (or dealHours later if secondary data source is ready)
    // go through the array, calculate the days
    $durCount = 0;
    $counter = 0;
    for ($i = 0; $i < count($extradays[$talentID]); $i++) {
      $durCount += $extradays[$talentID][$i];
      $counter++;
      if ($durCount > 180000) { // stop, record the i
        break;
      }
    }
    $talent[$talentID]['noOfDaysToReachDealHours'] = $counter;
    $talent[$talentID]['extraDays'] = $talent[$talentID]['noOfDaysMoreThanOneHour'] - $talent[$talentID]['noOfDaysToReachDealHours'];
  }

  $talent[$talentID]['noOfAchievedHours'] = floor($talent[$talentID]['totalAcknowledged'] / 3600);

  if (@$talent[$talentID]['consecutive']) {
    $talent[$talentID]['consecutiveBonus'] = $talent[$talentID]['consecutive'] * 50000;
  }
  if (@$talent[$talentID]['extraDays']) {
    $talent[$talentID]['extraDaysBonus'] = $talent[$talentID]['extraDays'] * 50000;
  }
  if (@$talent[$talentID]['consecutiveBonus'] || @$talent[$talentID]['extraDaysBonus']) {
    @$talent[$talentID]['totalBonus'] = $talent[$talentID]['consecutiveBonus'] + $talent[$talentID]['extraDaysBonus'];
  }
}

// retrieve data dari file untuk data salary
$salaryArr = file('feeds/anchor-current.csv');
for ($i = 0; $i < count($salaryArr); $i++) {
  $csvArr = explode(',', $salaryArr[$i]);
  @$talent[$csvArr[0]]['giftCount'] += $csvArr[6];
  @$talent[$csvArr[0]]['giftRevenue'] += $csvArr[7]*100;
  @$talent[$csvArr[0]]['giftCommission'] += $csvArr[7]*20;
  @$talent[$csvArr[0]]['newFans'] += $csvArr[5];
}

$dataArr = file('data.csv');
for ($i = 0; $i < count($dataArr); $i++) {
  $csvArr = explode(',', $dataArr[$i]);
  if (@$talent[$csvArr[0]]['total']) {
    @$talent[$csvArr[0]]['name'] = trim($csvArr[2]);
    @$talent[$csvArr[0]]['baseSalary'] = $csvArr[1];
  }
}

$linePrint = "talentID,username,name,baseSalary,totalPayout,hourSalary,totalBonus,giftCommission,giftRevenue,consecutive,extraDays,rate,noOfValidHours,newFans,noOfDays,remarks\n";

foreach ($talent as $talentID => $value) {
  $content = '';
  // calculate salary based on airing hours, extra days (if any), consecutive (if any), and gifts (if any)
  if (@$talent[$talentID]['baseSalary'] == 2000000) {
/*
    if ($talent[$talentID]['noOfAcknowledgedHours'] == 50) {
*/
      $rate = 40000;
/*
    } elseif ($talent[$talentID]['noOfAcknowledgedHours'] >= 30) {
      $rate = 35000;
    } elseif ($talent[$talentID]['noOfAcknowledgedHours'] >= 15) {
      $rate = 30000;
    } elseif ($talent[$talentID]['noOfAcknowledgedHours'] >= 1) {
      $rate = 25000;
    } else {
      $rate = 0;
    }
*/
  } elseif (@$talent[$talentID]['baseSalary']) {
    $rate = $talent[$talentID]['baseSalary'] / 50;
  } else {
    $rate = 0;
  }
  $talent[$talentID]['rate'] = $rate;
//if (!isset($talent[$talentID]['noOfAcknowledgedHours'])) echo "MOO $talentID\n";
  @$talent[$talentID]['hourSalary'] = $rate * $talent[$talentID]['noOfAcknowledgedHours'];
  @$talent[$talentID]['totalPayout'] = $talent[$talentID]['hourSalary'] + $talent[$talentID]['giftCommission'] + $talent[$talentID]['totalBonus'];
#  if (@$talent[$talentID]['baseSalary'] && ($talent[$talentID]['totalPayout'] > 0)) {
      @$linePrint .= $talentID . ',' . str_replace(',', '-', $talent[$talentID]['username']) . ',' . str_replace(',', '-', $talent[$talentID]['name']) . ',' . $talent[$talentID]['baseSalary'] . ',' . $talent[$talentID]['totalPayout'] . ',' . $talent[$talentID]['hourSalary'] . ',' . $talent[$talentID]['totalBonus'] . ',' . $talent[$talentID]['giftCommission'] . ',' . $talent[$talentID]['giftRevenue'] . ',' . $talent[$talentID]['consecutive'] . ',' . $talent[$talentID]['extraDays'] . ',' . $talent[$talentID]['rate'] . ',' . $talent[$talentID]['noOfAcknowledgedHours'] . ',' . $talent[$talentID]['newFans'] . ',' . $talent[$talentID]['noOfDays'] . ',' . $talent[$talentID]['remarks']  . "\n";
#  }
#  $content .= "<html><head><title>".$talent[$talentID]['name']." (".$talentID.")</title><style>table, th, td {border: 1px solid black;} table {border-collapse: collapse;}</style></head><body>";
#  $content .= "<div><h2>Nonolive Pay Slip</h2><table>";
#  $content .= "<tr><td width=\"200\">ID:</td><td style=\"text-align:right;\" width=\"200\">".$talentID."</td></tr>";
#  $content .= "<tr><td width=\"200\"><strong>Name:</strong></td><td style=\"text-align:right;\" width=\"200\">".$talent[$talentID]['name']."</td></tr>";
#  $content .= "<tr><td width=\"200\">Period:</td><td style=\"text-align:right;\" width=\"200\">June 2016</td></tr>";
#  $content .= "<tr><td width=\"200\">Target Base Salary:</td><td style=\"text-align:right;\" width=\"200\">".number_format($talent[$talentID]['baseSalary'], 0, ',', '.')."</td></tr>";
#  $content .= "<tr><td width=\"200\">Airing Hours:</td><td style=\"text-align:right;\" width=\"200\">".$talent[$talentID]['noOfAcknowledgedHours']."</td></tr>";
#  $content .= "<tr><td width=\"200\">Rate per hour:</td><td style=\"text-align:right;\" width=\"200\">".number_format($talent[$talentID]['rate'], 0, ',', '.')."</td></tr>";
#  $content .= "<tr><td width=\"200\">Hour Salary:</td><td style=\"text-align:right;\" width=\"200\">".number_format($talent[$talentID]['hourSalary'], 0, ',', '.')."</td></tr>";
#  $content .= "<tr><td width=\"200\">Consecutive BONUS:</td><td style=\"text-align:right;\" width=\"200\">".$talent[$talentID]['consecutive']." (".number_format(($talent[$talentID]['consecutive']*50000), 0, ',', '.').")</td></tr>";
#  $content .= "<tr><td width=\"200\">Extra Days BONUS:</td><td style=\"text-align:right;\" width=\"200\">".$talent[$talentID]['extraDays']." (".number_format(($talent[$talentID]['extraDays']*50000), 0, ',', '.').")</td></tr>";
#  $content .= "<tr><td width=\"200\">Gift Revenue:</td><td style=\"text-align:right;\" width=\"200\">".number_format($talent[$talentID]['giftRevenue'], 0, ',', '.')."</td></tr>";
#  $content .= "<tr><td width=\"200\">Gift Commission (20%):</td><td style=\"text-align:right;\" width=\"200\">".number_format($talent[$talentID]['giftCommission'], 0, ',', '.')."</td></tr>";
#  $content .= "<tr><td width=\"200\">Total BONUS:</td><td style=\"text-align:right;\" width=\"200\">".number_format($talent[$talentID]['totalBonus'], 0, ',', '.')."</td></tr>";
#  $content .= "<tr><td width=\"200\"><strong>Total Payout:</strong></td><td style=\"text-align:right;\" width=\"200\">".number_format($talent[$talentID]['totalPayout'], 0, ',', '.')."</td></tr>";
#  if ($talent[$talentID]['newFans'] >= 1000) {
#    $content .= "<tr><td width=\"200\">Total New Fans BONUS:</td><td style=\"text-align:right;\" width=\"200\">".number_format(($talent[$talentID]['newFans']*100), 0, ',', '.')."</td></tr>";
#    $content .= "<tr><td width=\"200\"><strong>Grand Total:</strong></td><td style=\"text-align:right;\" width=\"200\">".number_format(($talent[$talentID]['totalPayout']+($talent[$talentID]['newFans']*100)), 0, ',', '.')."</td></tr>";
#  }
#  $content .= "</table></div>";
#  $content .= "<div><br/><h3>Day Broadcasting Details</h3><table>";
#  $content .= "<tr><th>Date</th><th>Duration (in seconds)</th></tr>";
#  for ($i = 1; $i <= 31; $i++) {
#    if ($talent[$talentID][$i]) {
#      $content .= "<tr><td width=\"100\" style=\"text-align:center;\">$i June 2016</td><td width=\"50\" style=\"text-align:center;\">".$talent[$talentID][$i]."</td></tr>";
#    }
#  }
#  $content .= "</div></table>";
#  $content .= "</body></html>";
  @$content .= "<html><head><title>Live Hours Calculation for ".$talent[$talentID]['name']."</title></head><body><p>ID=\"".$talentID."\" Name=\"".$talent[$talentID]['name']."\" NoOfValidHours=\"".$talent[$talentID]['noOfAcknowledgedHours']."\" Consecutive=\"".$talent[$talentID]['consecutive']."\" ExtraDays=\"".$talent[$talentID]['extraDays']."\" GiftRevenue=\"".$talent[$talentID]['giftRevenue']."\" GiftCommission=\"".$talent[$talentID]['giftCommission']."\" NewFans=\"".$talent[$talentID]['newFans']."\"<br/>";
  @$content .= "Data used to generate this report ended at 3AM GMT+7, this report was generated at ".date('d-m-Y H:i:s').".</p></body></html>";
  $filehtml = fopen('hour/'.$talentID.'.html', 'w');
  fwrite($filehtml, $content);
  fclose($filehtml);
}
#
$fileHdl = fopen('output.csv', 'w');
fwrite($fileHdl, $linePrint);
fclose($fileHdl);

print_r($talent);
//print_r($extradays);
?>
