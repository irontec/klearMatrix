/* Spanish translation for the jQuery Timepicker Addon */
/* Written by Ianaré Sévi */
(function($) {
	 
	
	(function loadTimezone() {
		if (!$.timepicker) {
			setTimeout(loadTimezone,20);
			return;
		}
	    
		$.timepicker.regional['es'] = {
			timeOnlyTitle: 'Elegir una hora',
			timeText: 'Hora',
			hourText: 'Horas',
			minuteText: 'Minutos',
			secondText: 'Segs.',
			millisecText: 'Milisegundos',
			timezoneText: 'Huso horario',
			currentText: 'Ahora',
			closeText: 'Cerrar',
			timeFormat: 'hh:mm:ss',
			amNames: ['a.m.', 'AM', 'A'],
			pmNames: ['p.m.', 'PM', 'P'],
			ampm: false
		};
		
		$.timepicker.setDefaults($.timepicker.regional['es']);
	})();
	
})(jQuery);
