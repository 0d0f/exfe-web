<div id="userLoginBox" class="identification_dialog idialog_inpage"></div>
<script type="text/javascript">
    jQuery(document).ready(function(){
        var html = odof.user.identification.showdialog("reg");
        jQuery("#userLoginBox").html(html);
        odof.user.identification.bindDialogEvent("reg");
    });
    var showIdentificationDialog = false;
</script>
