<?php

function get_files($dir) {
    $files = array();
    for (; $dir->valid(); $dir->next()) {
        if ($dir->isDir() && !$dir->isDot()) {
            if ($dir->haschildren()) {
                $files = array_merge($files, get_files($dir->getChildren()));
            };
        }else if($dir->isFile()){
            $files[] = $dir->getPathName();
        }
    }
    return $files;
}


$path = 'eimgs';
$dir  = new RecursiveDirectoryIterator($path);
$arrFiles = get_files($dir);


// 240_240_original_xxxxxxx.png -> original_xxxxxxx.png
echo "Step 1:\r\n";
foreach ($arrFiles as $filename) {
	if (preg_match('/.*\/240_240_original_.*/', $filename)) {
		$new_filename = preg_replace('/240_240_/', '', $filename);
		$mvResult = @rename($filename, $new_filename);
		if ($mvResult) {
			echo "😃 Successful rename file {$filename} as {$new_filename} .\r\n";
		} else {
			echo "😓 Error while renaming file {$filename} .\r\n";
		}
	}
}


// 300_300_xxxxxxx.png          -> 240_240_xxxxxxx.png
echo "Step 2:\r\n";
foreach ($arrFiles as $filename) {
	if (preg_match('/.*\/300_300_.*/', $filename)) {
		$new_filename = preg_replace('/300_300_/', '240_240_', $filename);
		$mvResult = @rename($filename, $new_filename);
		if ($mvResult) {
			echo "😃 Successful rename file {$filename} as {$new_filename} .\r\n";
		} else {
			echo "😓 Error while renaming file {$filename} .\r\n";
		}
	}
}


// xxxxxxx.png                  -> original_xxxxxxx.png
echo "Step 3:\r\n";
foreach ($arrFiles as $filename) {
	if (preg_match('/\/.{32}\..*/', $filename)) {
		$new_filename = preg_replace('/\/(.{32})(\..*)/', '/original_$1$2', $filename);
		$mvResult = @rename($filename, $new_filename);
		if ($mvResult) {
		 	echo "😃 Successful rename file {$filename} as {$new_filename} .\r\n";
		} else {
		 	echo "😓 Error while renaming file {$filename} .\r\n";
		}
	}
}

echo "Done!\r\n";
