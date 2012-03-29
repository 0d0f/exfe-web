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
    ns.locationInputBoxID = '';
    ns.curActions= '';
    ns.googleMapsContainerID = '';

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

    ns.getLocation = function(locationInputBoxID, googleMapsContainerID, curActions){
        if(typeof locationInputBoxID != "undefined"){
            ns.locationInputBoxID = locationInputBoxID;
        }
        if(typeof googleMapsContainerID != "undefined"){
            ns.googleMapsContainerID = googleMapsContainerID;
        }
        if(typeof curActions != "undefined"){
            ns.curActions = curActions;
        }

        var placeDetail = jQuery('#'+ns.locationInputBoxID).val();
        var placeArr = odof.util.parseLocation(placeDetail);
        var strPlace = odof.util.toDBC(placeArr[0]);
        //只有当输入大于两个字符的时候，才进行查询。
        if(strPlace != ns.userOldLocation && odof.util.trim(placeDetail).length > 2){
            var postData = {
                l:strPlace,
                userLat:odof.apps.maps.userCurLat,
                userLng:odof.apps.maps.userCurLng
            };
            if (typeof window.mapRequest !== 'undefined') {
                window.mapRequest.abort();
            }
            window.mapRequest = jQuery.ajax({
                type: "POST",
                data: postData,
                url: site_url+"/Maps/GetLocation",
                dataType:"json",
                //async:false,
                success: function(JSONData){
                    var curLocationDetail = jQuery('#'+ns.locationInputBoxID).val();
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

        // 快捷键
        var listLength = locationListData.length, li = 0,
            $uls = $('#gather_place_selector').find('ul.place_detail');
        $uls.eq(li).addClass('ulhover');
        $('#gather_place_selector')
            .attr('tabindex', -1).focus()
            .unbind('keydown')
            .bind('keydown', function (e) {
                $uls.eq(li).removeClass('ulhover');
                var keyCode = e.keyCode;
                switch (keyCode) {
                    case 37:
                    case 38:
                        li = li ? --li : listLength - 1;
                        break;
                    case 39:
                    case 40:
                        li++;
                        if (li === listLength) li = 0;
                        break;
                    case 13:
                        $uls.eq(li).trigger('click');
                        $(this).attr('tabindex', "");
                }
                $uls.eq(li).addClass('ulhover');
                e.preventDefault();
            });

        var userSelectAddress = function(e){
            var curElementID = e.currentTarget.id;
            var userPlaceName = jQuery("#place_name_"+curElementID).html();
            var userPlaceAddr = jQuery("#place_addr_"+curElementID).html();
            var userPlaceLat = jQuery("#place_lat_"+curElementID).val();
            var userPlaceLng = jQuery("#place_lng_"+curElementID).val();

            //设置一个全局的变量值，以判断是否改变了内容。
            ns.userOldLocation = userPlaceName;
            jQuery("#"+ns.locationInputBoxID).val(userPlaceName+"\r\n"+userPlaceAddr);

            //更新 Cross 地理位置
            crossData.place.lat         = userPlaceLat;
            crossData.place.lng         = userPlaceLng;
            crossData.place.external_id = e.currentTarget.id;
            crossData.place.provider    = 'foursquare';

            //画Google地图。
            ns.drawGoogleMaps(userPlaceLat, userPlaceLng, userPlaceName);

            //更新Preview的显示。
            switch (ns.curActions) {
                case 'create_cross':
                    odof.x.gather.updatePlace(true);
                    break;
                case 'edit_cross':
                    var arrPlace = odof.util.parseLocation($('#place_content').val());
                    crossData.place.line1 = arrPlace[0];
                    crossData.place.line2 = arrPlace[1];
                    odof.x.render.showPlace();
            }
            jQuery("#gather_place_selector").hide();

        };
        jQuery(".place_detail").unbind("click");
        jQuery(".place_detail").bind("click",function(e){
            userSelectAddress(e);
        });
    };

    ns.drawGoogleMaps = function(userPlaceLat, userPlaceLng, userPlaceName, width, height){
        var cityLat = userPlaceLat;
        var cityLng = userPlaceLng;

        if(odof.apps.maps.cityLat != 0){
            cityLat = odof.apps.maps.cityLat;
        }
        if(odof.apps.maps.cityLng != 0){
            cityLng = odof.apps.maps.cityLng;
        }

        if(typeof width == "undefined"){ width = 285; }
        if(typeof height == "undefined"){ height = 175; }

        var center =  new google.maps.LatLng(cityLat,cityLng);
        var myOptions = {
            zoom: 12,
            center: center,
            mapTypeId: google.maps.MapTypeId.ROADMAP
        };

        jQuery("#"+ns.googleMapsContainerID).css({"width":width+"px", "height":height+"px"});
        jQuery("#"+ns.googleMapsContainerID).show();
        var map = new google.maps.Map(document.getElementById(ns.googleMapsContainerID), myOptions);
        var initialLocation = new google.maps.LatLng(userPlaceLat,userPlaceLng);
        map.setCenter(initialLocation);

        var position = new google.maps.LatLng(userPlaceLat,userPlaceLng);
        var marker = new google.maps.Marker({
            position: position,
            map: map,
            title:userPlaceName
        });
    };

})(ns);

$(document).ready(function() {
    //get user location
    //odof.apps.maps.getUserLatLng();
});
