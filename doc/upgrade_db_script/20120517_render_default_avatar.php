<?php
error_reporting(E_ALL ^ E_NOTICE);

require_once dirname(__FILE__) . '/common.php';
require_once dirname(__FILE__) . '/DataModel.php';
// require_once dirname(__FILE__) . '/models/v2/IdentityModels.php';


class IdentityModels extends DataModel {

    public function makeDefaultAvatar($external_id, $name = '') {
        // image config
        $specification = array(
            'width'  => 80,
            'height' => 80,
        );
        $colors = array(
            array(138,  59, 197),
            array(189,  53,  55),
            array(219,  98,  11),
            array( 66, 163,  36),
            array( 41,  95, 204),
        );
        $ftSize = 36;
        // init path
        $curDir = dirname(__FILE__);
        $resDir = "{$curDir}/default_avatar_portrait/";
        $ftFile = "{$resDir}/HelveticaNeueDeskUI.ttc";
        // get image
        $bgIdx  = rand(1, 3);
        $image  = ImageCreateFromPNG("{$resDir}/bg_{$bgIdx}.png");
        // get color
        $clIdx  = rand(0, count($colors) - 1);
        $fColor = imagecolorallocate($image, $colors[$clIdx][0], $colors[$clIdx][1], $colors[$clIdx][2]);
        // get name
        $name   = substr($name ?: $external_id, 0, 3);
        // calcular font size
        do {
            $posArr = imagettftext(imagecreatetruecolor(80, 80), $ftSize, 0, 3, 70, $fColor, $ftFile, $name);
            $fWidth = $posArr[2] - $posArr[0];
            $ftSize--;
        } while ($fWidth > (80 - 2));
        imagettftext($image, $ftSize, 0, (80 - $fWidth) / 2, 70, $fColor, $ftFile, $name);
        // show image
        // header('Pragma: no-cache');
        // header('Cache-Control: no-cache');
        // header('Content-Transfer-Encoding: binary');
        // header('Content-type: image/png');
        // imagepng($image);
        // save image
        $hashed_path_info = hashFileSavePath('eimgs', "default_avatar_{$external_id}");
        $filename = "{$hashed_path_info['fname']}.png";
        if ($hashed_path_info['error'] || !imagepng($image, "{$hashed_path_info['fpath']}/{$filename}")) {
            return null;
        }
        // release memory
        imagedestroy($image);
        // return
        return IMG_URL . "{$hashed_path_info['webpath']}/{$filename}";
    }

}


class UpgradeIdentityAvatar extends DataModel {

    public function run() {
        // init models
        $modIdentity = new IdentityModels;
        // get all identities
        $ids = $this->getAll(
            "SELECT `id`, `name`, `external_identity`, `external_username`, `avatar_file_name` FROM `identities`"
        );
        // loop
        foreach ($ids as $id) {
            $name = $id['name'] ?: $id['external_username'];
            if ($id['avatar_file_name'] && !preg_match('/80_80_default\.png/', strtolower($id['avatar_file_name']))) {
                echo ":) Identity {$id['id']} : {$id['external_identity']} : {$name} is already having an avatar.\r\n";
                continue;
            }
            $avatar = $modIdentity->makeDefaultAvatar($id['external_identity'], $name);
            if ($avatar) {
                $this->query("UPDATE `identities` SET `avatar_file_name` = '{$avatar}' WHERE `id` = {$id['id']}");
                echo ":) Successful build avatar for {$id['id']} : {$id['external_identity']} : {$name}.\r\n";
            } else {
                echo ":( Error build default avatar for {$id['id']} : {$id['external_identity']} : {$name}.\r\n";
            }
        }
        //
        echo "Render finished....\r\n";
    }

}

$upgradeObj = new UpgradeIdentityAvatar();
$upgradeObj->run();
