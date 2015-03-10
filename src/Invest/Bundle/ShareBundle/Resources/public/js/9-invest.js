/**
 * Invest
 * Author: Imre Incze
 * Created on: 05/09/2014 
 */

$(document).ready(function(){
	$('body').delegate('input[class=dateInput]', 'focus', function() {
		$(this).datepicker({dateFormat: 'dd/mm/yy'});
	});
	$('body').delegate('a', 'click', function() {
		if ($(this).attr('question')) {
			return (confirm($(this).attr('question')));
		}
		return true;
	});
	
	$('body').delegate('[name=showhide]', 'click', function () {
		var s=$(this).html();
		var col=$(this).attr('column');
		if (s == 'Hide') {
			s='Show';
			$('[class='+col+']').each(function(index) {
				$(this).hide();
			});
		} else {
			s='Hide';
			$('[class='+col+']').each(function(index) {
				$(this).show();
			});
		}
		$(this).html(s);
	});
	
	
});
