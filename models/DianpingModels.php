<?php

require_once dirname(dirname(__FILE__)) . '/lib/httpkit.php';

class DianpingModels extends DataModel {

    public function request($url, $params) {
        ksort($params);
        $codes = DIANPING_APPKEY;
        $queryString = '';
        while ((list($key, $val) = each($params))) {
          $codes .= $key . $val;
          $queryString .= '&' . $key . '=' . urlencode($val);
        }
        $codes .= DIANPING_SECRET;
        $sign = strtoupper(sha1($codes));
        $url .= '?appkey=' . DIANPING_APPKEY . "&sign={$sign}{$queryString}";
        $result = httpkit::request(
            $url, null, null, false, false, 3, 3, 'json', true
        );
        if ($result
         && $result['http_code'] === 200
         && $result['json']
         && $result['json']['status'] === 'OK') {
            return $result['json'];
        }
        return null;
    }


    public function getSingleBusiness($business_id) {
        $key   = "dianping:{$business_id}";
        $place = getCache($key);
        if (!$place) {
            $params = [
                'business_id'     => $business_id,
                'out_offset_type' => 1,
                'platform'        => 2,
                'format'          => 'json',
            ];
            $rawResult = $this->request(
                'http://api.dianping.com/v1/business/get_single_business', $params
            );
            if ($rawResult && $rawResult['businesses']) {
                $name     = $business['name'] . (
                    $business['branch_name'] ? "({$business['branch_name']})" : ''
                );
                $business = $rawResult['businesses'][0];
                $regions  = implode(' ', $business['regions']);
                $address  = $business['city']
                          . ($regions ? " {$regions}" : '')
                          . " {$business['address']}";
                $place = new Place(
                    0, $name, $address, $business['longitude'],
                    $business['latitude'], 'dianping', $business['business_id']
                );
                setCache($key, $place);
                return $place;
            }
        }
        return null;
    }

}

