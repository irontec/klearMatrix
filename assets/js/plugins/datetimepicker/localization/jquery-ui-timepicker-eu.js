/* Basque translation for the jQuery Timepicker Addon */
/* Written by Arkaitz Etxeberria */
(function ($) {
	
	(function loadTimezone() {
		if (!$.timepicker) {
			setTimeout(loadTimezone,20);
			return;
		}
	    
		$.timepicker.regional['eu'] = {
			timeOnlyTitle: 'Ordu bat Hautatu',
			timeText: 'Ordu',
			hourText: 'Ordu',
			minuteText: 'Minutu',
			secondText: 'Segundo',
			millisecText: 'Milisegundo',
			timezoneText: 'Ordu-eremua',
			currentText: 'Orain',
			closeText: 'Itxi',
			timeFormat: 'hh:mm:ss',
			amNames: ['a.m.', 'AM', 'A'],
			pmNames: ['p.m.', 'PM', 'P'],
			ampm: false
		};
		
		$.timepicker.setDefaults($.timepicker.regional['eu']);

	})();
	
})(jQuery);
