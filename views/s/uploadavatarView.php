<?php include "share/header.php"; ?>
<script type="text/javascript" src="/static/js/libs/jquery.imgareaselect.js"></script>
</head>
<body>
<?php include "share/nav.php"; ?>
<?php
$large_photo_exists=$this->getVar("large_photo_exists");
$thumb_width=$this->getVar("thumb_width");
$thumb_height=$this->getVar("thumb_height");

$thumb_image_location=$this->getVar("thumb_image_location");
$large_image_location=$this->getVar("large_image_location");

//Only display the javacript if an image has been uploaded
if(strlen($large_photo_exists)>0){
    $current_large_image_width = getWidth($large_image_location);
    $current_large_image_height = getHeight($large_image_location);?>
    <script type="text/javascript">
    function preview(img, selection) {
    var scaleX = <?php echo $thumb_width; ?> / selection.width;
    var scaleY = <?php echo $thumb_height; ?> / selection.height;
    $('#thumbnail + div > img').css({
        width: Math.round(scaleX * <?php echo $current_large_image_width;?>) + 'px',
        height: Math.round(scaleY * <?php echo $current_large_image_height;?>) + 'px',
        marginLeft: '-' + Math.round(scaleX * selection.x1) + 'px',
        marginTop: '-' + Math.round(scaleY * selection.y1) + 'px'
    });
    $('#x1').val(selection.x1);
    $('#y1').val(selection.y1);
    $('#x2').val(selection.x2);
    $('#y2').val(selection.y2);
    $('#w').val(selection.width);
    $('#h').val(selection.height);
}

$(document).ready(function () {
   $('#save_thumb').click(function() {
        var x1 = $('#x1').val();
        var y1 = $('#y1').val();
        var x2 = $('#x2').val();
        var y2 = $('#y2').val();
        var w = $('#w').val();
        var h = $('#h').val();
        if(x1=="" || y1=="" || x2=="" || y2=="" || w=="" || h==""){
            alert("You must make a selection first");
            return false;
        }else{
            return true;
        }
    });
});

$(window).load(function () {
        $('#thumbnail').imgAreaSelect({ aspectRatio: '1:1', onSelectChange: preview });
});

</script>
<?php }?>
<?php
//Display error message if there are any
if(strlen($error)>0){
    echo "<ul><li><strong>Error!</strong></li><li>".$error."</li></ul>";
}
if(strlen($large_photo_exists)>0 && strlen($thumb_photo_exists)>0){
    echo "<p><strong>NOTE:</strong> If the thumbnail image looks the same as the previous one, just hit refresh a couple of times.</p>";
    echo $large_photo_exists."&nbsp;".$thumb_photo_exists;
    echo "<p><a href=\"".$_SERVER["PHP_SELF"]."?a=delete\">Delete Images</a></p>";
}else{
    if(strlen($large_photo_exists)>0){?>
        <h2>Create Thumbnail</h2>
        <div align="center">
            <img src="/<?php echo $large_photo_exists;?>" style="float: left; margin-right: 10px;" id="thumbnail" alt="Create Thumbnail" />
            <div style="float:left; position:relative; overflow:hidden; width:<?php echo $thumb_width;?>px; height:<?php echo $thumb_height;?>px;">
                <img src="/<?php echo $large_photo_exists;?>" style="position: relative;" alt="Thumbnail Preview" />
            </div>
            <br style="clear:both;"/>
            <form name="thumbnail" action="" method="post">
                <input type="hidden" name="x1" value="" id="x1" />
                <input type="hidden" name="y1" value="" id="y1" />
                <input type="hidden" name="x2" value="" id="x2" />
                <input type="hidden" name="y2" value="" id="y2" />
                <input type="hidden" name="w" value="" id="w" />
                <input type="hidden" name="h" value="" id="h" />
                <input type="submit" name="upload_thumbnail" value="Save Thumbnail" id="save_thumb" />
            </form>
        </div>
        <hr />
    <?php 	} ?>
        <h2>Upload Photo</h2>
        <form name="photo" enctype="multipart/form-data" action="" method="post">
            Photo <input type="file" name="image" size="30" /> <input type="submit" name="upload" value="Upload" />
        </form>
<?php } ?>
</body>
</html>
