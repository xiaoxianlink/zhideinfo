var now = new Date();
var cusorTime = now;
var first = true;

function convertCN(num){
	if(num == 1){
		return "一";
	}
	if(num == 2){
		return "二";
	}
	if(num == 3){
		return "三";
	}
	if(num == 4){
		return "四";
	}
	if(num == 5){
		return "五";
	}
	if(num == 6){
		return "六";
	}
	if(num == 0){
		return "天";
	}
	return num;
}

function convertDate(date){
	var js_date = new Date(date);
	return js_date;
}

function isSameMinute(date1, date2){
	if(date1.getYear() == date2.getYear()
		&& date1.getMonth() == date2.getMonth()
		&& date1.getDate() == date2.getDate()
		&& date1.getHours() == date2.getHours()
		&& date1.getMinutes() == date2.getMinutes()){
		return true;
	}
	return false;
}

function isSameHour(date1, date2){
	if(date1.getYear() == date2.getYear()
		&& date1.getMonth() == date2.getMonth()
		&& date1.getDate() == date2.getDate()
		&& date1.getHours() == date2.getHours()){
		return true;
	}
	return false;
}

function isSameDay(date1, date2){
	if(date1.getYear() == date2.getYear()
		&& date1.getMonth() == date2.getMonth()
		&& date1.getDate() == date2.getDate()){
		return true;
	}
	return false;
}

function isSameWeek(date1, date2){
	var elapsed = date1.getTime() - date2.getTime();
	var d1day = date1.getDay(); 
	if(d1day == 0){
		diday = 7;
	}
	var d2day = date2.getDay();
	if(d2day == 0){
		d2day = 7;
	}
	if( elapsed > 0 && elapsed < (3600 * 24 * 7 * 1000) 
		&& d1day > d2day){
		return true;
	}
	return false;
}

function isIn60Minute(date1, date2){
	var elapsed = date1.getTime() - date2.getTime();
	if(elapsed < (3600 * 1 * 1000)){
		return true;
	}
	return false;
}

function isIn24Hour(date1, date2){
	var elapsed = date1.getTime() - date2.getTime();
	if(elapsed < (3600 * 24 * 1000)){
		return true;
	}
	return false;
}

function showHeadTime(date1, date2){
	if(isSameMinute(cusorTime, date2) && first == false){
	}
	else{
		if(isSameDay(date1, date2)){
			document.write(date2.toLocaleTimeString([], {hour12: false, hour: '2-digit', minute:'2-digit'}));
		}
		else if(isSameWeek(date1, date2)){
			document.write("星期" + convertCN(date2.getDay()));
			document.write("&nbsp;");
			document.write(date2.toLocaleTimeString([], {hour12: false, hour: '2-digit', minute:'2-digit'}));
		}
		else{
			//document.write("test");
			document.write(date2.toLocaleDateString());
			document.write("&nbsp;");
			document.write(date2.toLocaleTimeString([], {hour12: false, hour: '2-digit', minute:'2-digit'}));
		}
		cusorTime = date2;
	}
	first = false;
}

function showReadTime(date1, date2){
	if(isIn60Minute(date1, date2)){
		var gap = Math.round((date1.getTime() - date2.getTime()) / (1000 * 60));
		document.write("已在" + gap + "分钟前查看");
	}
	else if(isIn24Hour(date1, date2)){
		var elapsed = date1.getTime() - date2.getTime();
		var gap = Math.round(elapsed / (3600 * 1000));
		document.write("已在" + gap + "小时前查看");
	}
	else{
		var elapsed = date1.getTime() - date2.getTime();
		var gap = Math.round(elapsed / (3600 * 1000 * 24));
		document.write("已在" + gap + "天前查看");
	}
}