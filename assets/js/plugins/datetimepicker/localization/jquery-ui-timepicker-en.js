/* Spanish translation for the jQuery Timepicker Addon */
/* Written by Ianaré Sévi */
(function($) {

	
	(function loadTimezone() {
		if (!$.timepicker) {
			setTimeout(loadTimezone,20);
			return;
		}
		
    $.timepicker.regional['en-GB'] = { // Default regional settings
        currentText: 'Now',
        closeText: 'Done',
        ampm: false,
        amNames: ['AM', 'A'],
        pmNames: ['PM', 'P'],
        timeFormat: 'hh:mm tt',
        timeSuffix: '',
        timeOnlyTitle: 'Choose Time',
        timeText: 'Time',
        hourText: 'Hour',
        minuteText: 'Minute',
        secondText: 'Second',
        millisecText: 'Millisecond',
        timezoneText: 'Time Zone'
    };

	$.datepicker.regional["en-GB"] = {
		closeText : "Done",
		prevText : "Prev",
		nextText : "Next",
		currentText : "Today",
		monthNames : ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"],
		monthNamesShort : ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
		dayNames : ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"],
		dayNamesShort : ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
		dayNamesMin : ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"],
		weekHeader : "Wk",
		dateFormat : "dd/mm/yy",
		firstDay : 1,
		isRTL : !1,
		showMonthAfterYear : !1,
		yearSuffix : ""
	};

    })();

})(jQuery);
