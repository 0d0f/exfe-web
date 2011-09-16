//exCalendar config.
var exCalPath = "/static/js/excal";
var exCalLangPath = exCalPath + "/lang";
var exCalLang = "en";

//Set exCalendar container.
var calendarId = 'exCalendarContainer';
var calendarContainerClassName = 'exCalendarContainer';

/**
 * Include a javascript file
 *
 **/
function exInclude(fileName){
    var headElementObject = document.getElementsByTagName('head')[0];
    var scriptObj = document.createElement('script');
    scriptObj.src = fileName;
    scriptObj.type = 'text/javascript';
    headElementObject.appendChild(scriptObj)
}

// Include language file..
var languageFile = exCalLangPath + "/" + exCalLang + ".js";
exInclude(languageFile);

//exCalendar main class.
function exCal() {
    var currentYear = 0;
    var currentMonth = 0;
    var currentDay = 0;

    var selectedYear = 0;
    var selectedMonth = 0;
    var selectedDay = 0;

    var weekArr = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
    var monthArr = ["January","February","March","April","May","June","July","August","September","October","November","December"];
    var dateField = null;

    /**
     * Exfe Calendar initialize function
     **/
    this.init = initialize;
    function initialize(textField, calendarContainer) {
        //set language...
        weekArr = exLang.weekArr;
        monthArr = exLang.monthArr;

        //set container id
        if (typeof calendarContainer == "undefined"){
            calendarId = 'exCalendarContainer';
        }else{
            calendarId = calendarContainer;
        }

        //设置统一的ClassName以方便样式控制。
        calendarContainerObj = document.getElementById(calendarId);
        calendarContainerObj.className = calendarContainerClassName;

        this.show(textField);
    }


    /**
     * get days in a month.
     *
     * */
    function getDaysInMonth(year, month) {
        return [31,((!(year % 4 ) && ( (year % 100 ) || !( year % 400 ) ))?29:28),31,30,31,30,31,31,30,31,30,31][month-1];
    }

    /**
     * get day of week.
     *
     * */
    function getDayOfWeek(year, month, day) {
        var date = new Date(year,month-1,day)
            return date.getDay();
    }

    /**
     * set element property..
     *
     * */
    function setElementProperty(eleProperty, eleValue, elementId){
        var myElement = elementId;
        var elementObj = null;

        if(typeof(myElement) == "object"){
            elementObj = myElement;
        } else {
            elementObj = document.getElementById(myElement);
        }
        if((elementObj != null) && (elementObj.style != null)){
            elementObj = elementObj.style;
            elementObj[ eleProperty ] = eleValue;
        }
    }

    /**
     * do set element property.
     *
     * */
    function setProperty(eleProperty, eleValue) {
        setElementProperty(eleProperty, eleValue, calendarId);
    }

    /**
     * clear text field data.
     *
     * */
    this.clearDate = clearDate;
    function clearDate() {
        dateField.value = '';
        hide();
    }

    /**
     * set text field data.
     *
     * */
    this.setDate = setDate;
    function setDate(curYear, curMonth, curDay) {
        if (dateField) {
            monthVar = curMonth >= 10 ? curMonth : "0"+curMonth;
            dayVar = curDay >= 10 ? curDay : "0"+curDay;
            var dateString = monthVar + "-" + dayVar + "-" + curYear;
            dateField.value = dateString;
            //hide();
            //Refresh date table display
            refreshDateTables(curYear, curMonth, curDay);
        }
        return;
    }

    /**
     * change month actions
     *
     * */
    this.changeMonth = changeMonth;
    function changeMonth(change) {
        currentMonth += change;
        currentDay = 0;
        if(currentMonth > 12) {
            currentMonth = 1;
            currentYear++;
        } else if(currentMonth < 1) {
            currentMonth = 12;
            currentYear--;
        }

        exCalendarObj = document.getElementById(calendarId);
        exCalendarObj.innerHTML = drawCalendar();
    }

    /**
     * change year actions
     *
     * */
    this.changeYear = changeYear;
    function changeYear(change) {
        currentYear += change;
        currentDay = 0;
        calendar = document.getElementById(calendarId);
        calendar.innerHTML = drawCalendar();
    }

    /**
     * get current year
     *
     * */
    function getCurrentYear() {
        var year = new Date().getYear();
        if(year < 1900) year += 1900;
        return year;
    }

    /**
     * get current month
     *
     * */
    function getCurrentMonth() {
        return new Date().getMonth() + 1;
    }

    /**
     * get current day
     *
     * */
    function getCurrentDay() {
        return new Date().getDate();
    }

    /**
     * Get Current Hours for display time.
     *
     * */
    function getCurrentHours() {
        return new Date().getHours();
    }

    /**
     * Get Current minutes for display time.
     *
     * */
    function getCurrentMinutes() {
        return new Date().getMinutes();
    }

    /**
     * create the time list
     *
     * */
    function createTimeList(){
        var currentHours = getCurrentHours();
        var currentMinutes = getCurrentMinutes();

        var dayNow = " PM";
        if(currentHours < 12){ dayNow = " AM"; }

        var timeList = [];

        //当前时间。
        if(currentMinutes < 30){
            var showHours = currentHours > 12 ? parseInt(currentHours-12) : currentHours;
            showHours = showHours >= 10 ? showHours : "0"+showHours;
            timeList.push(showHours + ":30" + dayNow);
        }

        //在此之后的时间。往后显示24个小时的时间。
        for(j=0,i=currentHours+1; j<24; j++, i++){
            h = i>=24 ? parseInt(i-24) : i;
            dayNow = h>=12 ? " PM" : " AM";
            var showHours = h > 12 ? h-12 : h;
            showHours = showHours >= 10 ? showHours : "0"+showHours;

            timeList.push(showHours + ":00" + dayNow);
            timeList.push(showHours + ":30" + dayNow);
        }
        return timeList;
    }

    /**
     * set time when user click the time list.
     *
     * */
    this.setTimeData = setTimeData;
    function setTimeData(timeStr){
        var currentYear = getCurrentYear();
        var currentMonth = getCurrentMonth();
        if(currentMonth < 10){ currentMonth = "0" + currentMonth; }
        var currentDay = getCurrentDay();
        if(currentDay < 10){ currentDay = "0" + currentDay; }

        var fieldString = currentMonth + "-" + currentDay + "-" + currentYear + " " + timeStr;
        if(dateField.value) {
            try {
                var objRegExpDateTime = /^(\d{2})\-(\d{2})\-(\d{4})( (\d{2}):(\d{2}) ([AM|PM]{2}))?$/;
                var dateString = new String(dateField.value);
                var dateTimeRegMatchArr = dateString.match(objRegExpDateTime);
                if(dateTimeRegMatchArr != null){
                    var dateArr = dateString.split(" ");
                    if(dateArr.length > 0){
                        fieldString = dateArr[0] + " " + timeStr;
                    }
                }
            } catch(e) { /*alert(e);*/ }
        }
        dateField.value = fieldString;
        refreshTimeList(timeStr);
    }

    /**
     * Refresh time list display style
     *
     * */
    this.refreshTimeList = refreshTimeList;
    function refreshTimeList(currentTime){
        var timeArr = document.getElementsByName("exCalTimeList");
        for(i=0; i<timeArr.length; i++){
            timeArr[i].className = "";
        }
        var currentTimeListObj = document.getElementById(currentTime);
        currentTimeListObj.className = "current";
    }

    /**
     * Refresh date tables display
     *
     * */
    this.refreshDateTables = refreshDateTables;
    function refreshDateTables(currentYear,currentMonth,currentDay){
        var dateString = currentYear + "-" + currentMonth + "-" + currentDay;
        var dateArr = document.getElementsByName("exCalDateLink");
        for(i=0; i<dateArr.length; i++){
            if(dateArr[i].className == "current"){
                dateArr[i].className = "";
            }else{
                classNameArr = dateArr[i].className.split(" ");
                if(classNameArr.length >= 2){
                    for(j=0; j<classNameArr.length; j++){
                        if(classNameArr[j] == "current"){
                            classNameArr.splice(j,1);
                        }
                    }
                }
                curClassName = classNameArr.join(" ");
                dateArr[i].className = curClassName;
            }
        }
        var currentDateLinkObj = document.getElementById(dateString);
        if(currentDateLinkObj.className == ""){
            currentDateLinkObj.className = "current";
        }else{
            currentDateLinkObj.className = currentDateLinkObj.className + " current";
        }
    }

    /**
     * draw calendar...
     *
     * */
    function drawCalendar() {
        //当天
        var thisYear = getCurrentYear();
        var thisMonth = getCurrentMonth();
        var thisDay = getCurrentDay();
        var todayTimestamp = Date.parse(thisYear + '/' + thisMonth + '/' + thisDay + ' 00:00:00');

        //计算一月内的时间。
        var dayOfMonth = 1;
        var validDay = 0;
        var startDayOfWeek = getDayOfWeek(currentYear, currentMonth, dayOfMonth);
        var daysInMonth = getDaysInMonth(currentYear, currentMonth);
        var css_class = null; //CSS class for each day

        var excalCon = "<div class='exCalDays'><table cellspacing='0' cellpadding='0' border='1' bordercolor='D3D4D4' id='exCalDateTable'>";
        excalCon += "<tr class='header'>";
        excalCon += "  <td class='previous'><a href='javascript:changeCalendarMonth(-1);'>&lt;</a></td>";
        excalCon += "  <td colspan='5' class='title'>" + monthArr[currentMonth-1] + "&nbsp;&nbsp;" + currentYear + "</td>";
        excalCon += "  <td class='next'><a href='javascript:changeCalendarMonth(1);'>&gt;</a></td>";
        excalCon += "</tr>";
        excalCon += "<tr><th>"+ weekArr[0] +"</th><th>"+ weekArr[1] +"</th><th>"+ weekArr[2] +"</th><th>"+ weekArr[3] +"</th><th>"+ weekArr[4] +"</th><th>"+ weekArr[5] +"</th><th>"+ weekArr[6] +"</th></tr>";

        for(var week=0; week < 6; week++) {
            excalCon += "<tr>";
            for(var dayOfWeek=0; dayOfWeek < 7; dayOfWeek++) {
                if(week == 0 && startDayOfWeek == dayOfWeek) {
                    validDay = 1;
                } else if (validDay == 1 && dayOfMonth > daysInMonth) {
                    validDay = 0;
                }

                if(validDay) {
                    var curTimestamp = Date.parse(currentYear + '/' + currentMonth + '/' + dayOfMonth + ' 00:00:00');
                    //console.log(curTimestamp);
                    if (dayOfWeek == 0 || dayOfWeek == 6) {
                        cssClass = 'weekend';
                        if(curTimestamp < todayTimestamp){
                            cssClass = 'weekend invalid';
                        }else{
                            if(dayOfMonth == thisDay && currentMonth == thisMonth && currentYear == thisYear
                                    && dayOfMonth == selectedDay && currentYear == selectedYear && currentMonth == selectedMonth ){
                                cssClass = 'weekend current today';
                            }else if (dayOfMonth == selectedDay && currentYear == selectedYear && currentMonth == selectedMonth) {
                                cssClass = 'weekend current';
                            }else if(dayOfMonth == thisDay && currentMonth == thisMonth && currentYear == thisYear){
                                cssClass = 'weekend today';
                            }
                        }
                    } else {
                        cssClass = 'weekday';
                        if(curTimestamp < todayTimestamp){
                            cssClass = 'weekday invalid';
                        }else{
                            if(dayOfMonth == thisDay && currentMonth == thisMonth && currentYear == thisYear
                                    && dayOfMonth == selectedDay && currentYear == selectedYear && currentMonth == selectedMonth ){
                                cssClass = 'weekday current today';
                            }else if (dayOfMonth == selectedDay && currentYear == selectedYear && currentMonth == selectedMonth) {
                                cssClass = 'weekday current';
                            }else if(dayOfMonth == thisDay && currentMonth == thisMonth && currentYear == thisYear){
                                cssClass = 'weekday today';
                            }
                        }
                    }

                    excalCon += "<td><a class='"+cssClass+"' id='"+currentYear+"-"+currentMonth+"-"+dayOfMonth+"' name='exCalDateLink' onclick=\"javascript:setCalendarDate("+currentYear+","+currentMonth+","+dayOfMonth+")\" href='javascript:;'>"+dayOfMonth+"</a></td>";
                    dayOfMonth++;
                } else {
                    excalCon += "<td class='empty'>&nbsp;</td>";
                }
            }
            excalCon += "</tr>";
        }

        excalCon += "</table></div>";

        var timeList = createTimeList();
        excalCon += "<div class='exCalTimes'><ul><li class='header'>" + exLang.timeAllDay + "</li></ul><ul class='list'>";
        for(i=0; i<timeList.length; i++){
            excalCon += "<li onclick='setCalendarTime(\""+ timeList[i] +"\");' name='exCalTimeList' id='" + timeList[i] + "'>"+ timeList[i] +"</li>";
        }
        excalCon += "</ul></div>";

        return excalCon;
    }

    /**
     * show calendar..
     *
     * */
    this.show = show;
    function show(field) {
        can_hide = 0;
        // If the calendar is visible and associated with, this field do not do anything.
        if (dateField == field) {
            return;
        } else {
            dateField = field;
        }

        //如果当前填充字段不为空，则取过来。
        if(dateField) {
            try {
                var dateString = new String(dateField.value);
                var dateParts = dateString.split("-");

                selectedMonth = parseInt(dateParts[0],10);
                selectedDay = parseInt(dateParts[1],10);
                selectedYear = parseInt(dateParts[2],10);
            } catch(e) {}
        }

        //如果当前填充字段为空，则取当天日期。
        if (!(selectedYear && selectedMonth && selectedDay)) {
            selectedMonth = getCurrentMonth();
            selectedDay = getCurrentDay();
            selectedYear = getCurrentYear();
        }

        currentMonth = selectedMonth;
        currentDay = selectedDay;
        currentYear = selectedYear;

        if(document.getElementById){
            calendar = document.getElementById(calendarId);
            calendar.innerHTML = drawCalendar();
            setProperty('display', 'block');
        }
    }

    /**
     * hiden calendar..
     *
     * */
    this.hide = hide;
    function hide() {
        if(dateField) {
            setProperty('display', 'none');
            dateField = null;
        }
    }

    /**
     * set visible..
     *
     * */
    this.visible = visible;
    function visible() {
        return dateField
    }

    this.can_hide = can_hide;
    var can_hide = 0;

}

//new calendar object.
var exCalObj = new exCal();

//main function
var exfeCalendar = function(textField, calendarContainer){
    exCalObj.init(textField, calendarContainer);
};

//clear calendar..
function clearCalendar() {
    exCalObj.clearDate();
}

//hidden calendar
function hideCalendar() {
    if (exCalObj.visible()) {
        exCalObj.hide();
    }
}

//set calendar date
function setCalendarDate(year, month, day) {
    exCalObj.setDate(year, month, day);
}

//set calendar time
function setCalendarTime(timeStr) {
    exCalObj.setTimeData(timeStr);
}

//while user change year
function changeCalendarYear(change) {
    exCalObj.changeYear(change);
}

//while user change month.
function changeCalendarMonth(change) {
    exCalObj.changeMonth(change);
}

document.write("<div id='exCalendarContainer'></div>");
