<?php

class TimeModels extends DataModel {

    public function parseTimeString($string, $timezone = '') {
        // check timezone
        if (!preg_match('/^[+-][0-9]{2}:[0-9]{2}(\ [a-z]{1,5})?$/i', $timezone)) {
            return null;
        }
        // init
        $date_word    = '';
        $date         = '';
        $time_word    = '';
        $time         = '';
        $outputformat = 0;
        $intDayPlus   = 0;
        if (preg_match('/^\".*\"$|^\'.*\'$/', $string)) {
            return new CrossTime(
                $date_word, $date, $time_word, $time, $timezone, $string, 1
            );
        }
        $untreated    = trim($string, '"\'');
        $dtUntreated  = trim($string, '"\'');
        // get raw date
        $rawDate  = strtotime($untreated);
        if ($rawDate !== false) {
            $year = date('Y', $rawDate);
            if (mb_substr_count($untreated, $year, 'utf8') === 1) {
                $untreated = str_replace($year, '', $untreated);
            }
        }
        // get precise time
        $timePatterns = [
            '/^.*[^\/\\\-](\b[0-9]{1,4}\ ?[ap]\.?m\.?\b).*$/i',
            '/^.*[^\/\\\-](\b[0-9]{3,4}\ ?([ap]\.?m\.?)?\b).*$/i',
            '/^.*[^\/\\\-](\b[0-9]{1,2}\:[0-9]{1,2}\ ?([ap]\.?m\.?)?\b).*$/i',
        ];
        $actTimes     = [];
        do {
            $lenTaken = 0;
            $rawTime  = '';
            foreach ($timePatterns as $pattern) {
                if (preg_match($pattern, $untreated)) {
                    $tryTime  = preg_replace($pattern, '$1', $untreated);
                    $tryTaken = mb_strlen($tryTime, 'utf8');
                    if ($tryTaken >= $lenTaken) {
                        $rawTime   = $tryTime;
                        $lenTaken  = $tryTaken;
                    }
                }
            }
            if ($rawTime) {
                // get am/pm
                if (preg_match('/a\.?m\.?/i', $rawTime)) {
                    $apm = 'am';
                } else if (preg_match('/p\.?m\.?/i', $rawTime)) {
                    $apm = 'pm';
                } else {
                    $apm = '';
                }
                // get digitals
                if (preg_match('/\:/', $rawTime)) {
                    $arrATime = explode(':', $rawTime);
                    $rawHour  = $arrATime[0];
                    $rawMin   = $arrATime[1];
                } else {
                    $dgts = preg_replace('/^[^0-9]*([0-9]*)[^0-9]*$/', '$1', $rawTime);
                    switch (($lenDgts = mb_strlen($dgts, 'utf8'))) {
                        case 1:
                        case 2:
                            $rawHour = $dgts;
                            $rawMin  = 0;
                            break;
                        case 3:
                        case 4:
                            $rawHour = mb_substr($dgts, 0, $lenDgts - 2, 'utf8');
                            $rawMin  = mb_substr($dgts, $lenDgts - 2, 2, 'utf8');
                    }
                }
                $rawHour = (int) $rawHour;
                $rawMin  = (int) $rawMin;
                // merge
                switch ($apm) {
                    case 'pm':
                        if ($rawHour <  12) {
                            $rawHour += 12;
                        }
                        break;
                    case 'am':
                    default:
                        if ($rawHour === 12) {
                            $rawHour  =  0;
                        }
                }
                $rawHour    += (int) ($rawMin  / 60);
                $rawMin      = $rawMin  % 60;
                $intDayPlus += (int) ($rawHour / 24);
                $rawHour     = $rawHour % 24;
                $actTimes[]  = [
                    'raw'  => $rawTime,
                    'hour' => $rawHour,
                    'min'  => $rawMin,
                ];
                $untreated   = str_replace($rawTime, '', $untreated);
                $dtUntreated = str_replace($rawTime, '', $dtUntreated);
            }
        } while ($rawTime);
        if ($actTimes) {
            $time = sprintf('%02d', $actTimes[0]['hour']) . ':'
                  . sprintf('%02d', $actTimes[0]['min']);
        }
        // get date
        $rawDate  = strtotime($dtUntreated);
        if ($rawDate !== false) {
            $rawDate += $intDayPlus * 60 * 60 * 24;
            $date = date('Y-m-d', $rawDate);
        }
        // get fuzzy time
        $fuzzyTimeDic = [
            ['Daybreak'],
            ['Morning'],
            ['Breakfast'],
            ['Brunch'],
            ['Lunch'],
            ['Noon'],
            ['Afternoon'],
            ['Tea-break', 'tea break', 'teabreak'],
            ['Off-work',  'off work',  'offwork'],
            ['Dinner'],
            ['Evening'],
            ['Night'],
            ['Midnight'],
        ];
        $fuzzyTime = [];
        foreach ($fuzzyTimeDic as $fuzzyWord) {
            foreach ($fuzzyWord as $fuzzyWordItem) {
                $pattern = "/^.*(\b{$fuzzyWordItem}\b).*$/i";
                if (preg_match($pattern, $untreated)) {
                    $fuzzyTime[] = $fuzzyWord[0];
                    $rawTime = preg_replace($pattern, '$1', $untreated);
                    $untreated = str_replace($rawTime, '', $untreated);
                }
            }
        }
        if ($fuzzyTime) {
            $time_word = $fuzzyTime[0];
        }
        // make CrossTime
        if ((sizeof($actTimes) && sizeof($fuzzyTime)) || sizeof($fuzzyTime) > 1) {
            $outputformat = 1;
        }
        return new CrossTime($date_word, $date, $time_word, $time, $timezone, $string, $outputformat);
    }

}
