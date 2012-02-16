/**
 * @Description: Maps module
 * @Author:      Handaoliang <han@exfe.com>
 * @createDate:  Feb 16, 2012
 * @CopyRights:  http://www.exfe.com
 */

var moduleNameSpace = 'odof.apps.maps';
var ns = odof.util.initNameSpace(moduleNameSpace);

(function(ns) {
    ns.userOldLocation = '';
    ns.userCurLat = '';
    ns.userCurLng = '';
    ns.cityLat = 0;
    ns.cityLng = 0;

    ns.getUserLatLng = function(){

        var getPositionSuccess = function(position){
            odof.apps.maps.userCurLat= position.coords.latitude;
            odof.apps.maps.userCurLng = position.coords.longitude;
        };

        var getPositionError = function(error){
            /*
            switch(error.code){
                case error.TIMEOUT :
                    console.log("连接超时，请重试");
                    break;
                case error.PERMISSION_DENIED :
                    console.log("您拒绝了使用位置共享服务，查询已取消");
                    break;
                case error.POSITION_UNAVAILABLE : 
                    console.log("暂时无法为您提供位置服务");
                    break;
            }
            */
            console.log(error.code);
        };
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(getPositionSuccess, getPositionError);
        }
    };

    ns.getLocation = function(){
        var placeDetail = jQuery('#gather_place').val();
        var placeArr = odof.util.parseLocation(placeDetail);
        var strPlace = odof.util.toDBC(placeArr[0]);
        //只有当输入大于两个字符的时候，才进行查询。
        if(strPlace != ns.userOldLocation && odof.util.trim(placeDetail).length > 2){
            var postData = {
                l:strPlace,
                userLat:odof.apps.maps.userCurLat,
                userLng:odof.apps.maps.userCurLng
            };
            jQuery.ajax({
                type: "POST",
                data: postData,
                url: site_url+"/Maps/GetLocation",
                dataType:"json",
                //async:false,
                success: function(JSONData){
                    var curLocationDetail = jQuery('#gather_place').val();
                    var curLocation = odof.util.parseLocation(curLocationDetail);
                    var strLocation = odof.util.toDBC(curLocation[0]);

                    if(!JSONData.error
                        && JSONData.response.length != 0
                        && JSONData.s_key == strLocation
                    ){
                        ns.drawLocationSelector(JSONData);
                    }
                }
            });
        }
    };

    ns.drawLocationSelector = function(locationData){
        var placeList = '';
        ns.cityLat = locationData.c_lat;
        ns.cityLng = locationData.c_lng;
        var locationListData = locationData.response;
        jQuery.each(locationListData, function(i,val){
            placeList += '<ul class="place_detail" id="'+val.place_id
                      + '"><li class="place_name" id="place_name_'+val.place_id+'">'
                      + val.place_name
                      + '</li>'
                      + '<input type="hidden" id="place_lat_'+val.place_id+'" value="'+val.place_lat+'">'
                      + '<input type="hidden" id="place_lng_'+val.place_id+'" value="'+val.place_lng+'">'
                      + '<li class="place_addr" id="place_addr_'+val.place_id+'">'
                      + val.place_address
                      + '</li></ul>';
        });

        jQuery("#gather_place_selector").unbind('clickoutside');
        jQuery('#gather_place_selector').bind('clickoutside', function(event) {
            jQuery("#gather_place_selector").hide();
        });
        jQuery("#gather_place_selector").show();
        jQuery("#gather_place_selector").html(placeList);

        var userSelectAddress = function(e){
            var curElementID = e.currentTarget.id;
            var userPlaceName = jQuery("#place_name_"+curElementID).html();
            var userPlaceAddr = jQuery("#place_addr_"+curElementID).html();
            var userPlaceLat = jQuery("#place_lat_"+curElementID).val();
            var userPlaceLng = jQuery("#place_lng_"+curElementID).val();

            //设置一个全局的变量值，以判断是否改变了内容。
            ns.userOldLocation = userPlaceName;
            jQuery("#gather_place").val(userPlaceName+"\r\n"+userPlaceAddr);

            //更新Preview的显示。
            odof.x.gather.updatePlace();
            jQuery("#gather_place_selector").hide();

            //画Google地图。
            var center =  new google.maps.LatLng(odof.apps.maps.cityLat,odof.apps.maps.cityLng);
            var myOptions = {
                zoom: 12,
                center: center,
                mapTypeId: google.maps.MapTypeId.ROADMAP
            };
            
            var map = new google.maps.Map(document.getElementById("calendar_map_container"), myOptions);
            var initialLocation = new google.maps.LatLng(userPlaceLat,userPlaceLng);
            map.setCenter(initialLocation);

            var position = new google.maps.LatLng(userPlaceLat,userPlaceLng); 
            var marker = new google.maps.Marker({
                position: position, 
                map: map,
                title:userPlaceName
            });
            //结束画Google Maps......

            crossData.place.lat = userPlaceLat;
            crossData.place.lng = userPlaceLng;
            crossData.place.external_id = e.currentTarget.id;
            crossData.place.provider = 'foursquare';
        };
        jQuery(".place_detail").unbind("click");
        jQuery(".place_detail").bind("click",function(e){
            userSelectAddress(e);
        });
    };

})(ns);


$(document).ready(function() {
    // place
    $('#gather_place').bind('keyup', function (event) {
        setTimeout(function(){
            odof.apps.maps.getLocation();
        },1000);
    });
    //get user location
    odof.apps.maps.getUserLatLng();
});
