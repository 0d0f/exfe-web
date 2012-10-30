<?php

class TimeModels extends DataModel {

    public function parseTimeString($string, $timezone = '') {
        // init
        $untreated = $string;
        // get precise time
        $timePatterns = [
            '/^.*(\b[0-9]{1,4}([ap]\.?m\.?)?\b).*$/i',
            '/^.*(\b[0-9]{1,2}\:[0-9]{1,2}([ap]\.?m\.?)?\b).*$/i',
        ];
        $actTimes = [];
        do {
            $lenTaken = 0;
            $rawTime  = '';
            foreach ($timePatterns as $pattern) {
                if (preg_match($pattern, $untreated)) {
                    $tryTime  = preg_replace($pattern, '$1', $untreated);
                    $tryToken = mb_strlen($tryTime, 'utf8');
                    if ($tryToken >= $lenTaken) {
                        $rawTime  = $tryTime;
                        $lenTaken = $tryToken;
                    }
                }
            }
            if ($rawTime) {
                $actTimes[] = $rawTime;
                $untreated = str_replace($rawTime, '', $untreated);
            }
        } while ($rawTime);
        // get fuzzy time
        $fuzzyTimeDic = [
            'daybreak',
            'morning',
            'breakfast',
            'brunch',
            'lunch',
            'noon',
            'afternoon',
            'teabreak',
            'tea break',
            'tea-break',
            'offwork',
            'off work',
            'off-work',
            'dinner',
            'evening',
            'night',
            'midnight',
        ];
        $fuzzyTime = [];
        foreach ($fuzzyTimeDic as $fuzzyWord) {
            $pattern = "/^.*(\b{$fuzzyWord}\b).*$/i";
            if (preg_match($pattern, $untreated)) {
                $rawTime = preg_replace($pattern, '$1', $untreated);
                $fuzzyTime[] = $rawTime;
                $untreated = str_replace($rawTime, '', $untreated);
            }
        }
        //
        if (sizeof($actTimes) + sizeof($fuzzyTime) > 1) {
            // error
        }



        var_dump($actTimes);
        var_dump($fuzzyTime);

        echo $untreated;

        //echo preg_replace($timePatterns[1], '$1', $string);
        return;
        //$rawTime = strtotime($string);


    }


}
