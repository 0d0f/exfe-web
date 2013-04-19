<?php
error_reporting(E_ALL ^ E_NOTICE);

require_once dirname(__FILE__) . '/common.php';
require_once dirname(__FILE__) . '/DataModel.php';

class UpgradeGravatars extends DataModel {

    public function getGravatarUrlByExternalUsername($external_username, $format = '', $fallback = '') {
        return $external_username
             ? ('http://www.gravatar.com/avatar/' . md5(strtolower($external_username))
              . ($format   ? ".{$format}"    : '')
              . ($fallback ? "?d={$fallback}" : ''))
             : '';
    }


    public function getGravatarByExternalUsername($external_username) {
        $url = $this->getGravatarUrlByExternalUsername($external_username, '', '404');
        if ($url) {
            $objCurl  = curl_init();
            curl_setopt($objCurl, CURLOPT_URL, $url);
            curl_setopt($objCurl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($objCurl, CURLOPT_HEADER, true);
            curl_setopt($objCurl, CURLOPT_NOBODY, true);
            curl_setopt($objCurl, CURLOPT_CONNECTTIMEOUT, 3);
            $httpHead = curl_exec($objCurl);
            $httpCode = curl_getinfo($objCurl, CURLINFO_HTTP_CODE);
            curl_close($objCurl);
            if ($httpCode === 200) {
                return $this->getGravatarUrlByExternalUsername($external_username);
            }
        }
        return '';
    }


    public function run() {
        $identities = $this->getAll(
            "SELECT * FROM `identities` WHERE `provider` = 'email' ORDER BY `id`"
        );
        // loop
        foreach ($identities as $item) {
            echo "Fetch Gravatar for {$item['id']} / {$item['external_username']}: ";
            $avatar_filename = $this->getGravatarByExternalUsername($item['external_username']);
            $this->query(
                "UPDATE `identities`
                 SET    `avatar_file_name` = '{$avatar_filename}',
                        `updated_at`       =  NOW()
                 WHERE  `id`               =  {$item['id']}"
            );
            echo $avatar_filename ? "[Succeed] [URL:{$avatar_filename}]\r\n" : "[Failed]\r\n";
        }
        //
        echo "\r\nDone. 😃\r\n";
    }

}

$upgradeObj = new UpgradeGravatars();
$upgradeObj->run();
