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
    function setDate(year, month, day) {
        if (dateField) {
            if (month < 10) {month = "0" + month;}
            if (day < 10) {day = "0" + day;}

            var dateString = month+"-"+day+"-"+year;
            dateField.value = dateString;
            //hide();
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

        calendar = document.getElementById(calendarId);
        calendar.innerHTML = drawCalendar();
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
            timeList.push(currentHours + ":30" + dayNow);
        }

        //在此之后的时间。
        for(i=currentHours+1; i<=23; i++){
            if(i < 12){ dayNow = " AM"; }else{ dayNow = " PM"; }
            timeList.push(i + ":00" + dayNow);
            timeList.push(i + ":30" + dayNow);
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
                var dateString = new String(dateField.value);
                var dateArr = dateString.split(" ");
                if(dateArr.length > 0){
                    fieldString = dateArr[0] + " " + timeStr;
                }
            } catch(e) {}
        }
        dateField.value = fieldString;
    }

    /**
     * draw calendar...
     *
     * */
    function drawCalendar() {
        var dayOfMonth = 1;
        var validDay = 0;
        var startDayOfWeek = getDayOfWeek(currentYear, currentMonth, dayOfMonth);
        var daysInMonth = getDaysInMonth(currentYear, currentMonth);
        var css_class = null; //CSS class for each day

        var excalCon = "<div class='exCalDays'><table cellspacing='1' cellpadding='0' border='0'>";
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
                    if (dayOfMonth == selectedDay && currentYear == selectedYear && currentMonth == selectedMonth) {
                        css_class = 'current';
                    } else if (dayOfWeek == 0 || dayOfWeek == 6) {
                        css_class = 'weekend';
                    } else {
                        css_class = 'weekday';
                    }

                    excalCon += "<td><a class='"+css_class+"' href=\"javascript:setCalendarDate("+currentYear+","+currentMonth+","+dayOfMonth+")\">"+dayOfMonth+"</a></td>";
                    dayOfMonth++;
                } else {
                    excalCon += "<td class='empty'>&nbsp;</td>";
                }
            }
            excalCon += "</tr>";
        }
        excalCon += "</table></div>";

        var timeList = createTimeList();
        excalCon += "<div class='exCalTimes'><ul><li class='header'>All-day</li></ul><ul class='list'>";
        for(i=0; i<timeList.length; i++){
            excalCon += "<li style='cursor:pointer;' onclick='setCalendarTime(\""+ timeList[i] +"\");'>"+ timeList[i] +"</li>";
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
        if (dateField == field) { // If the calendar is visible and associated with, this field do not do anything.
            return;
        } else {
            dateField = field;
        }

        if(dateField) {
            try {
                var dateString = new String(dateField.value);
                var dateParts = dateString.split("-");

                selectedMonth = parseInt(dateParts[0],10);
                selectedDay = parseInt(dateParts[1],10);
                selectedYear = parseInt(dateParts[2],10);
            } catch(e) {}
        }

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
            calendar.innerHTML = drawCalendar(currentYear, currentMonth);
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
            setElementProperty('display', 'none', 'exCalendarIframe');
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

document.write("<iframe id='exCalendarIframe' src='javascript:false;' frameBorder='0' scrolling='no'></iframe>");
document.write("<div id='exCalendarContainer'></div>");
