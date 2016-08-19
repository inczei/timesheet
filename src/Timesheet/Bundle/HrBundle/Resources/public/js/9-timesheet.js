/**
 * Timesheet HR
 * Author: Imre Incze
 * Created on: 08/04/2015 
 */

jQuery.fn.center = function () {
    this.css("position","absolute");
    this.css("top", Math.max(0, (($(window).height() - $(this).outerHeight()) / 2) + 
                                                $(window).scrollTop()) + "px");
    this.css("left", Math.max(0, (($(window).width() - $(this).outerWidth()) / 2) + 
                                                $(window).scrollLeft()) + "px");
    return this;
}

//jQuery plugin to prevent double submission of forms
jQuery.fn.preventDoubleSubmission = function() {
  $(this).on('submit', function(e){
    var $form = $(this);

    if ($form.data('submitted') === true) {
    	// Previously submitted - don't submit again, but only when using ajax
    	if ($(this).attr('action').indexOf('ajax') != -1) {
    		e.preventDefault();
    	}
    } else {
      // Mark it so that the next submit can be ignored
      $form.data('submitted', true);
    }
  });

  // Keep chainability
  return this;
}

function showDateTime(element) {
	var dt=new Date();
	var datetime=('0'+dt.getDate()).slice(-2)+'/'
		+('0'+(dt.getMonth()+1)).slice(-2)+'/'
		+dt.getFullYear()+' '
		+('0'+dt.getHours()).slice(-2)+':'
		+('0'+dt.getMinutes()).slice(-2)+':'
		+('0'+dt.getSeconds()).slice(-2);
	$(element).html(datetime);
}

function flashing(element) {
	$(element).fadeOut(500);
	$(element).fadeIn(500);
}

$(document).ready(function(){

	var forms = [
	'[ name="holidayrequest"]'
	];

    $('body').delegate('select[class=timing_type_days]', 'change', function(){
        var value = $(this).val();
        $.ajax({
             type: 'POST',
             url: $(this).attr('data-action'),
             data: {
            	dayId: value 
             },
             success: function(data) {

                 // Remove current options
                 $('select[class=timing_type_shift]').html('');

                 $.each(data, function(k, v) {
                     $('select[class=timing_type_shift]').append('<option value="' + v['id'] + '">' + v['title'] + '</option>');
                 });
             }
         });
         return false;
    });

    $('body').delegate('tr[class=message_new],tr[class=message_old]', 'click', function(){
        var div = $('#message-url').attr('data-div');
        $('#'+div).html('');
        $.ajax({
             type: 'POST',
             url: $('#message-url').attr('data-action'),
             data: {
            	id: $(this).attr('data-id'),
            	folder: $('#message-url').attr('data-folder'),
             },
             success: function(data) {
            	 if (data.error) {
            		 alert(data.error);
            	 } else {
            		 $('#'+div).html(data.content);
            	 }
             }
         });
         return false;
    });

    $('body').delegate('tr[class=admin_config]', 'click', function(){
        var name = $(this).attr('data-name');
        var title = $(this).children('td:first').html();
        var value = $(this).children('td:last').html();
        if (name=='hct') {
        	var options=[];
            $('input[name=hct]').each(function(index) {
                options.push('<option value="' + $(this).attr('id') + '">' + $(this).val() + '</option>');
            });
        	var input_field='<select id="config_value">'+options.join('')+'</select>';
        } else {
        	var input_field='<input type="text" id="config_value" size="10" value="'+value+'">';
        }
        var form='<form action=""><input type="hidden" id="config_name" value="'+name+'"><label>'+title+' : </label>'+input_field+'<input type="submit" id="config_submit" value="Save"></form>';
		$('<div></div>').appendTo('body')
    		.html('<div>'+form+'</div>')
    		.dialog({
    		modal: true,
    		title: 'Change Config Parameter',
    		zIndex: 2000,
    		autoOpen: true,
    		width: 'auto',
    		resizable: false,
    		close: function (event, ui) {
    			$(this).remove();
    		}
    	});
        return false;
    });

    $('body').delegate('span[class=scrollTop]', 'click', function(){
    	$('html, body').animate({scrollTop:0}, 'slow');
    	return false;
    });
    
    
    $('body').delegate('span[class=scrollTo]', 'click', function(){
    	var pos=$('#'+$(this).attr('data-id')).offset().top;
    	$('html, body').animate({scrollTop: pos}, 'slow');
    	return false;
    });

    
    $('body').delegate('td[name=updatereader],td[name=syncreaderusers],td[name=resetreader],td[name=readeradminpwd],td[name=downloadattn],td[name=showallattn]', 'click', function(){
    	addWaitPopup();
    	$.ajax({
			url: $(this).attr('data-url'),
			dataType: 'json',
			timeout: 240000,
			beforeSend: function() {
				$('body').css('cursor', 'progress');
			}
		})
		.error(function(jqXHR, status, errorThrown) {
			alert('ajax error');
			removeMessage();
			removeWaitPopup();
			$('body').css('cursor', 'auto');
		})
		.done(function(data) {
			removeMessage();
			removeWaitPopup();
			$('body').css('cursor', 'auto');
			$('<div></div>').appendTo('body')
	    	.html('<div>'+data.content+'</div>')
	    	.dialog({
	    		modal: true,
	    		title: data.title,
	    		zIndex: 2000,
	    		autoOpen: true,
	    		width: 'auto',
	    		resizable: false,
	    		close: function (event, ui) {
	    			$(this).remove();
	    		}
	    	});
		});
    });



    /*
    $('body').delegate('#config_submit', 'click', function(e) {
    	e.preventDefault();
        $.ajax({
            type: 'POST',
            url: $('#config_url').attr('data-save'),
            data: {
            	name: $('#config_name').val(),
            	value: $('#config_value').val()
            },
            success: function(data) {
            	if (typeof data.error != 'undefined' && data.error.length) {
            		alert(data.error);
            	} else {
            		window.location=$('#config_url').val();
            	}
            }
        });
    });
*/    
    $('body').delegate('select[class=swapUser1],select[class=swapUser2],select[class=swapShift1],select[class=swapShift2]', 'change', function(){
        var dSelected = $(this).val();
        var dClass=$(this).attr('class');
        $.ajax({
             type: 'POST',
             url: $(this).attr('data-action'),
             data: {
            	dSelected: dSelected,
            	dClass: dClass
             },
             success: function(data) {
            	 var shft1=0;

                 if (dClass=='swapUser1') {
                	 target='swapShift1';
                 } else if (dClass=='swapUser2') {
                	 target='swapShift2';
                 } else if (dClass=='swapShift1') {
                	 target='swapUser2';
                 }

                 if (data.length) {
                     // Remove current options
                	 $('select[class='+target+']').html('');
                	 $('select[class='+target+']').append('<option value="">Please select</option>');
                 }
                 $.each(data, function(k, v) {
                     $('select[class='+target+']').append('<option value="' + v['id'] + '">' + v['title'] + '</option>');
                     shft1++;
                 });
                 if (dClass=='swapUser1') {
                	 if (shft1 == 0) {
                		 $('select[class=swapShift1]').html('');
                		 $('select[class=swapShift1]').append('<option value="">Please select</option>');
                	 }
                	 $('select[class=swapUser2]').html('');
                	 $('select[class=swapUser2]').append('<option value="">Please select</option>');
                	 $('select[class=swapShift2]').html('');
                	 $('select[class=swapShift2]').append('<option value="">Please select</option>');
                 } else if (dClass=='swapShift1') {
                	 if (shft1 == 0) {
                		 $('select[class=swapUser2]').html('');
                	 	$('select[class=swapUser2]').append('<option value="">Please select</option>');
                	 }
                	 $('select[class=swapShift2]').html('');
                	 $('select[class=swapShift2]').append('<option value="">Please select</option>');
                 } else if (shft1 == 0 && dClass=='swapUser2') {
                	 $('select[class=swapShift2]').html('');
                	 $('select[class=swapShift2]').append('<option value="">Please select</option>');
                 }
             }
         });
         return false;
    });

	$('body').delegate('form', 'submit', function( e ){
		if ($(this).attr('action').indexOf('ajax') != -1) {
			e.preventDefault();
			$('#popupDiv').fadeOut(250);
			postForm( $(this), function( response ){
				if (typeof response.redirect != 'undefined') {
					window.location=response.redirect;
				} else if (typeof response.refresh != 'undefined' && response.refresh.length>0) {
					if (typeof response.message != 'undefined' && response.message.length>0) {
						showMessage(response.message);
					}
					ajaxRefresh(response.refresh, '', '', '');
				} else if (typeof response.cause != 'undefined' && response.cause.length>0) {
					$('#popupDiv').fadeIn(250);
					alert(response.cause);
				}
			});
		} else {
			return true;
		}
	});
	           
	           
	if ($('div[class=clock]').size()) {
		setInterval(function() {
			showDateTime('div[class=clock]');
		}, 1000);
		showDateTime('div[class=clock]');
	};
	$('body').delegate('input[class=dateInput]', 'focus', function() {
		$(this).datepicker({dateFormat: 'dd/mm/yy', firstDay: 1});
	});
	$('body').delegate('input[class=timeInput]', 'focus', function() {
		$(this).timepicker({minutes: { interval: 15 }});
	});
	$('body').delegate('a', 'click', function() {
		if ($(this).attr('question')) {
			return (confirm($(this).attr('question')));
		}
		return true;
	});

	$('body').delegate('select[id=register_role]', 'change', function() {
		var ga=$('select[id=register_groupAdmin]');
		var la=$('select[id=register_locationAdmin]');
		switch ($(this).val()) {
			case 'ROLE_MANAGER' :
//				ga.show();
//				la.show();
				ga.css('read_only', false);
				ga.removeAttr('disabled');
				la.css('read_only', false);
				la.removeAttr('disabled');
				break;
			case 'ROLE_ADMIN' :
				ga.val('1');
				ga.css('read_only', true);
				ga.attr('disabled', 'disabled');
				la.val('1');
				la.css('read_only', true);
				la.attr('disabled', 'disabled');
//				ga.hide();
//				la.hide();
				break;
			default :
				ga.val('0');
				ga.css('read_only', true);
				ga.attr('disabled', 'disabled');
				la.val('0');
				la.css('read_only', true);
				la.attr('disabled', 'disabled');
//				ga.hide();
//				la.hide();
				break;
		};
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
	
	$('body').delegate('[class=infoButton]', 'click', function () {
		$.ajax({
			url: $(this).attr('data-url'),
			dataType: 'json',
			timeout: 10000
		})
		.error(function(jqXHR, status, errorThrown) {
			alert('ajax error');
		})
		.done(function(data) {
			$('<div></div>').appendTo('body')
	    	.html('<div>'+data.content+'</div>')
	    	.dialog({
	    		modal: true,
	    		title: 'User Informations',
	    		zIndex: 2000,
	    		autoOpen: true,
	    		width: 'auto',
	    		resizable: false,
	    		close: function (event, ui) {
	    			$(this).remove();
	    		}
	    	});
		});
	});

	
	$('body').delegate('[class=timesheetCheck]', 'click', function () {
		var date=$(this).attr('data-date');
		var userid=$(this).attr('data-userid');
		var ajaxurl=$('#tsc_ajaxurl').attr('data-ajaxurl');

		$.ajax({
			url: $(this).attr('data-url'),
			dataType: 'json',
			method: 'POST',
			timeout: 10000,
			data: {
				action: 'form',
				date: date,
				userid: userid
			}
		})
		.error(function(jqXHR, status, errorThrown) {
			alert('ajax error');
		})
		.done(function(data) {
			$('<div></div>').appendTo('body')
	    	.html('<div>'+data.content+'</div>')
	    	.dialog({
	    		modal: true,
	    		title: 'Timesheet Check',
	    		zIndex: 2000,
	    		autoOpen: true,
	    		width: 'auto',
	    		resizable: false,
	    		buttons: {
	    			'Save': function () {
	    				ajaxTimesheetCheck(ajaxurl, date, userid, $('#timesheetcheck_comment').val());
	    				$(this).dialog("close");
	    			},
	    			'Cancel': function () {
	    				$(this).dialog("close");
	    			}
	    		},
	    		close: function (event, ui) {
	    			$(this).remove();
	    		}
	    	});
		});
	});

	$('body').delegate('input[name=userSearch],select[name=domainSearch],select[name=groupSearch],select[name=qualificationSearch]', 'keyup change', function () {
		var url=$(this).attr('data-url');
		var name=$('input[name=userSearch]').val();
		var group=$('select[name=groupSearch]').val();
		var domain=$('select[name=domainSearch]').val();
		var qualification=$('select[name=qualificationSearch]').val();
		var base=$(this).attr('base-url');
		var listType=$(this).attr('data-type');
		var delay=0;
		if (($(this).attr('name') == 'userSearch') || ($(this).attr('name') == 'domainSearch')) {
			if ($('#lastSearch').val() != name || $('#lastDomain').val() != domain) {
				var delay=500;
				$('#lastSearch').val(name);
				$('#lastDomain').val(domain);
			}
		} else {
			var delay=100;
		}
		if (delay > 0) {
			window.clearTimeout($(this).data('timeout'));
			$(this).data('timeout', setTimeout(function() {
				$.ajax({
					url: url,
					method: 'POST',
					dataType: 'json',
					data: {
						name: name,
						group: group,
						domain: domain,
						qualification: qualification,
						base: base,
						listType: listType
					},
					beforeSend: function() {
						$('body').css('cursor', 'pointer');
					}
				})
				.error(function(jqXHR, status, errorThrown) {
//					alert('ajax error...');
				})
				.done(function(data) {
					if (data.content != $('#usersList').html()) {
						$('#usersList').html(data.content);
					}
				$('body').css('cursor', 'auto');
				});
			}, delay));
		}
	});
	
	$('body').delegate('span[class^="addRequest"]', 'click', function () {
		ajaxAddRequest($(this).attr('data-url'), $(this).attr('base-url'), $(this).attr('data-action'), $(this).attr('data-date'), $(this).attr('data-id'));
	});


	$('body').delegate('span[class^="problemShow"]', 'click', function () {
		var id=$(this).attr('id');
		$('#problem_'+id+'_full').show();
		$('#problem_'+id+'_short').hide();
	});


	$('body').delegate('[class=closeButton]', 'click', function () {
		$(this).parent().fadeOut(250);
	});
	
	$('body').delegate('button[type=submit]', 'click', function () {
		$('form').preventDoubleSubmission();
	});

	$('body').delegate('input[class=buttonApprove],input[class=buttonDeny]', 'click', function () {
		var userAction=(($(this).attr('class')=='buttonApprove')?('approve'):('deny'));
		var id=$(this).attr('id');
		var userComment=$('#comment_'+id).val();
		var ajaxUrl=$('#holidayapproval').attr('data-url');
		var returnUrl=$('#holidayapproval').attr('data-base');
		var refresh=$('#holidayapproval').attr('data-refresh');
		
		if ($(this).attr('data-question').length) {
			$('<div></div>').appendTo('body')
		    	.html('<div><h3>'+($(this).attr('data-question'))+'</h3></div>')
		    	.dialog({
		    		modal: true,
		    		title: 'Approve / Deny request',
		    		zIndex: 2000,
		    		autoOpen: true,
		    		width: 'auto',
		    		resizable: false,
		    		buttons: {
		    			'Approve': function () {
		    				ajaxApproveDeny(ajaxUrl, 'approve', id, userComment, returnUrl, refresh);
		    				$(this).dialog("close");
		    			},
		    			'Deny': function () {
		    				ajaxApproveDeny(ajaxUrl, 'deny', id, userComment, returnUrl, 'holidayDiv');
		    				$(this).dialog("close");
		    			},
		    			'Cancel': function () {
		    				$(this).dialog("close");
		    			}
		    		},
		    		close: function (event, ui) {
		    			$(this).remove();
		    		}
		    	});
		} else {
			ajaxApproveDeny(ajaxUrl, userAction, id, userComment, returnUrl, refresh);
		}
	});

	
	$('body').delegate('input[class=buttonApproveSwap]', 'click', function () {
		var userAction='approve';
		var id=$(this).attr('id');
		var ajaxUrl=$('#swapapprovedeny').attr('data-url');
		var returnUrl=$('#swapapprovedeny').attr('data-base');
		var question=$(this).attr('data-question');

		$('<div></div>').appendTo('body')
	    	.html('<div><h3>'+question+'</h3></div>')
	    	.dialog({
	    		modal: true,
	    		title: 'Approve / Deny swap request',
	    		zIndex: 2000,
	    		autoOpen: true,
	    		width: 'auto',
	    		resizable: false,
	    		buttons: {
	    			'Approve': function () {
	    				ajaxSwapApproveDeny(ajaxUrl, 'approve', id, returnUrl);
	    				$(this).dialog("close");
	    			},
	    			'Deny': function () {
	    				ajaxSwapApproveDeny(ajaxUrl, 'deny', id, returnUrl);
	    				$(this).dialog("close");
	    			},
	    			'Cancel': function () {
	    				$(this).dialog("close");
	    			}
	    		},
	    		close: function (event, ui) {
	    			$(this).remove();
	    		}
	    	});
	});

	$('body').delegate('td[name=calendarCell]', 'click', function () {
		$.ajax({
			url: $(this).attr('data-url'),
			method: 'POST',
			dataType: 'json',
			data: {
				base: $(this).attr('base-url'),
				action: $(this).attr('data-action'),
				refresh: $(this).attr('data-refresh'),
				date: $(this).attr('data-date')
			},
		})
		.error(function(jqXHR, status, errorThrown) {
			alert('ajax error');
		})
		.done(function(data) {
			if (data.content.length > 0) {
				$('<div id="dialog" title="'+data.title+'"></div>').appendTo('body')
				.html('<div id="container">'+data.content+'</div>')
				.dialog({
					modal: true,
					zIndex: 2000,
					autoOpen: true,
					width: 'auto',
					height: 'auto',
					minHeight: 'auto',
					position: ['center', 20],
					resizable: false,
					close: function (event, ui) {
						$(this).remove();
					}
				});
			} else {
				$('#popupDiv').fadeOut(250);
				$('#container').html('');
			}
		});
	});

	
	$('body').delegate('div[name=selectUser]', 'click', function () {
		var id=$(this).attr('id');

		$('div[name=selectUser]').each(function() {
			$(this).removeClass('scheduleUserSelected').addClass('scheduleUser');
		});
		$(this).removeClass().addClass('scheduleUserSelected');

		$('#selectedUser').val(id);
	});

	$('body').delegate('span[class=allocationRemove]', 'click', function () {
		var t1=$(this).attr('data-id');

		var t=t1.split('_');
		var targetId=t[0]+'_'+t[1]+'_'+t[2];

	    ajaxSchedule('remove', targetId, t[0], t[1], t[2], t[3]);
	});


	$('body').delegate('td[class=addPunch],td[class=editPunch]', 'click', function () {
		var type=$(this).attr('data-type');
		var typeId=$(this).attr('data-typeid');
		var date=$(this).attr('data-date');
		var dateDisplay=$(this).attr('data-datedisplay');
		var origdatetime=$(this).attr('data-origdatetime');
		var origtime=$(this).attr('data-origtime');
		var userId=$(this).attr('data-userid');
		var username=$(this).attr('data-username');
		var ajaxUrl=$('#addPunch').attr('data-url');

		$('<div></div>').appendTo('body')
		   	.html('<div>'+
   				'<h3>Would you like to add/change '+type+' time for '+username+' on '+dateDisplay+'</h3>'+
   				'<table>'+
   				'<tr><td>Time:</td><td><input type="text" class="timeInput" size="5" id="addPunchTime" value="'+origtime+'"></td></tr>'+
   				'<tr><td>Comment:</td><td><input type="text" id="addPunchComment" value=""></td></tr>'+
   				'</div>')
		   	.dialog({
		   		modal: true,
		   		title: 'Add/Change Punch',
		   		zIndex: 2000,
		   		autoOpen: true,
		   		width: 'auto',
		   		resizable: false,
		   		buttons: {
		   			'Yes': function () {
		   				var apTime=$('#addPunchTime').val();
		   				var apComment=$('#addPunchComment').val();
		   				$(this).dialog("close");
		   				ajaxAddPunch(ajaxUrl, typeId, userId, date, apTime, apComment, origdatetime);
		   				ajaxRefresh('timesheetDiv', '', '', '');		   				
		   			},
		   			'No': function () {
		   				$(this).dialog("close");
		   			}
		   		},
		   		close: function (event, ui) {
		   			$(this).remove();
		   		}
		   	});
	});

	$('body').delegate('div[class=photoThumbnail]', 'click', function () {
		$.ajax({
			url: $('#photoThumbnail').attr('data-url'),
			method: 'POST',
			dataType: 'json',
			data: {
				photoid: $(this).attr('data-photoid'),
				func: $(this).attr('data-func'),
				userid: $(this).attr('data-userid')
			},
		})
		.error(function(jqXHR, status, errorThrown) {
			alert('ajax error');
		})
		.done(function(data) {
			if (data.content.length > 0) {
				$('<div id="dialog" title="'+data.title+'"></div>').appendTo('body')
				.html('<div id="container">'+data.content+'</div>')
				.dialog({
					modal: true,
					zIndex: 2000,
					autoOpen: true,
					width: 'auto',
					height: 'auto',
					minHeight: 'auto',
					position: ['center', 20],
					resizable: false,
					close: function (event, ui) {
						$(this).remove();
					}
				});
			} else {
				$('#popupDiv').fadeOut(250);
				$('#container').html('');
			}
		});
	});

	$('body').delegate('div[class=residentHistory]', 'click', function () {
		$.ajax({
			url: $(this).attr('data-url'),
			method: 'POST',
			dataType: 'json',
			data: {
				id: $(this).attr('data-id')
			},
		})
		.error(function(jqXHR, status, errorThrown) {
			alert('ajax error');
		})
		.done(function(data) {
			if (data.content.length > 0) {
				$('<div id="dialog" title="'+data.title+'"></div>').appendTo('body')
				.html('<div id="container">'+data.content+'</div>')
				.dialog({
					modal: true,
					zIndex: 2000,
					autoOpen: true,
					width: 'auto',
					height: 'auto',
					minHeight: 'auto',
					position: ['center', 20],
					resizable: false,
					close: function (event, ui) {
						$(this).remove();
					}
				});
			} else {
				$('#popupDiv').fadeOut(250);
				$('#container').html('');
			}
		});
	});

	$('body').delegate('a[class=showRequest]', 'click', function () {
		$.ajax({
			url: $(this).attr('data-url'),
			method: 'POST',
			dataType: 'json',
			data: {
				base: $(this).attr('base-url'),
				refresh: $(this).attr('data-refresh'),
				userid: $(this).attr('data-userid')
			},
		})
		.error(function(jqXHR, status, errorThrown) {
			alert('ajax error');
		})
		.done(function(data) {
			if (data.content.length > 0) {
				$('<div id="dialog" title="'+data.title+'"></div>').appendTo('body')
				.html('<div id="container">'+data.content+'</div>')
				.dialog({
					modal: true,
					zIndex: 2000,
					autoOpen: true,
					width: 'auto',
					height: 'auto',
					minHeight: 'auto',
					position: ['center', 20],
					resizable: false,
					close: function (event, ui) {
						$(this).remove();
					}
				});
			} else {
				$('#popupDiv').fadeOut(250);
				$('#container').html('');
			}
		});
		
	});
	
	$('body').delegate('input[id=usersearch],select[id=timesheetUserSelect]', 'change', function () {
		ajaxRefresh('timesheetDiv', '', $('#usersearch').val(), $('#timesheetUserSelect').val());
	});

	$('body').delegate('button[class="refreshButton"],input[id=usersearchButton]', 'click', function () {
		ajaxRefresh('timesheetDiv', '', $('#usersearch').val(), $('#timesheetUserSelect').val());
	});

	$('body').delegate('span[class=switchMonth]', 'click', function () {
		ajaxRefresh($(this).attr('data-div'), $(this).attr('name'), '', '');
	});

	$('body').delegate('span.dayInfoProblem,span.noProblem', 'click', function () {
		var dataUrl=$(this).attr('data-url');
		var dataDate=$(this).attr('data-date');
		var dataLocation=$(this).attr('data-location');

		$.ajax({
			url: dataUrl,
			method: 'POST',
			dataType: 'json',
			data: {
				date: dataDate,
				location: dataLocation
			},
		})
		.error(function(jqXHR, status, errorThrown) {
			alert('ajax error');
		})
		.done(function(data) {

			$('<div id="dialog" title="'+data.title+'"></div>').appendTo('body')
			.html('<div id="container">'+data.content+'</div>')
			.dialog({
				modal: true,
				zIndex: 2000,
				autoOpen: true,
				width: 'auto',
				height: 'auto',
				minHeight: 'auto',
				position: ['center', 20],
				resizable: false,
				close: function (event, ui) {
					$(this).remove();
				}
			});
		});
	});

	$('body').delegate('button[id$=cancel]', 'click', function () {
		$(this).closest('form').submit();
	});
	
	$('body').delegate('button[name=onclickmenu]', 'click', function () {
		if ($(this).attr('question')) {
			if (!confirm($(this).attr('question'))) {
				return false;
			}
		}
		var baseUrl=$(this).attr('base-url');
		var div=$(this).attr('base-div');
		var action=$(this).attr('data-action');
		var url=$(this).attr('data-url');
		$.ajax({
			url: url,
			method: 'POST',
			dataType: 'json',
			data: {
				base: baseUrl,
				action: action,
				locationId: $(this).attr('data-location'),
				timestamp: $(this).attr('data-timestamp')
			},
		})
		.error(function(jqXHR, status, errorThrown) {
			alert('ajax error');
		})
		.done(function(data) {
			if (data.error.length > 0) {
				alert(data.error);
			} else {
				if (data.js.length > 0) {
					switch (data.js) {
						case 'addrequest' : {
							ajaxAddRequest(data.url, data.base, data.action, data.date, '');
						}
						case 'swaprequest' : {
							ajaxAddRequest(data.url, data.base, data.action, data.date, '');
						}
						case 'self' : {
							$('#popupDiv').html('<iframe id="myIframe"></iframe>');
							document.getElementById('myIframe').src=data.url;
						}
						case 'editphoto' : {
							if (data.content.length > 0) {
								$('<div id="dialog" title="'+data.title+'"></div>').appendTo('body')
								.html('<div id="container">'+data.content+'</div>')
								.dialog({
									modal: true,
									zIndex: 2000,
									autoOpen: true,
									width: 'auto',
									height: 'auto',
									minHeight: 'auto',
									position: ['center', 20],
									resizable: false,
									close: function (event, ui) {
										$(this).remove();
									}
								});
							} else {
								$('#popupDiv').fadeOut(250);
								$('#container').html('');
							}
							break;
						}
						case 'redirect' : {
							window.location=data.url;
							break;
						}
					}
				} else {
					ajaxRefresh(div, '', '', '');
				}
			}
		});
	});

});

function dragOver(ev) {
	if (ev.preventDefault) ev.preventDefault(); // allows us to drop
	if ($(ev.target).attr('name') == 'dropStyle') {
		$(ev.target).addClass('over');
	}
	return false;
}

function dragLeave(ev) {
	if ($(ev.target).attr('name') == 'dropStyle') {
		$(ev.target).removeClass('over');
	}
	return false;
}

function drag(ev) {
	ev.dataTransfer.setData("text", ev.target.id);
	var dragIcon = document.createElement('img');
	dragIcon.width = 100;
	ev.dataTransfer.setDragImage(dragIcon, -10, -10);
}

function drop(ev) {
    ev.preventDefault();
    $(ev.target).removeClass('over');
    
    var data = ev.dataTransfer.getData("text");

	var t1=nodeToString( ev.target );
	var t2=t1.substr(t1.indexOf('shft')+4, t1.length);
	var targetId=t2.substr(0, t2.indexOf('"'));
	var t=targetId.split('_');

    ajaxSchedule('add', targetId, t[0], t[1], t[2], data);
}


function ajaxSchedule(action, targetId, date, locationId, shiftId, userId) {
	if (parseInt(targetId) > 0 && $('#shft'+targetId).length>0) {
		$.ajax({
			url: $('#scheduleurl').val(),
			method: 'POST',
			dataType: 'json',
			data: {
				action: action,
				date: date,
				locationId: locationId,
				shiftId: shiftId,
				userId: userId
			},
			beforeSend: function() {
				$('#shft'+targetId).css({ opacity: 0.5 });
			}
		})
		.error(function(jqXHR, status, errorThrown) {
			alert('ajax error');
		})
		.done(function(data) {
			if (data.error.length) {
				$('<div id="dialog" title="Error"></div>').appendTo('body')
				.html('<div id="container">'+data.error+'</div>')
				.dialog({
					modal: true,
					zIndex: 2000,
					autoOpen: true,
					width: 400,
					height: 'auto',
					minHeight: 'auto',
					position: ['center', 20],
					resizable: false,
			   		buttons: {
			   			'OK': function () {
			   				$(this).dialog("close");
			   			}
			   		},
					close: function (event, ui) {
						$(this).remove();
					}
				});
			}
			$('#shft'+targetId).html(data.content)
				.css({ opacity: 1 });
			// if we have showhide return value as | separated string
			// use this to check the divs display values (by id and for the buttons
			// add 'button' to the id and save the html value
			var tmpKey=[];
			var tmpValue=[];
			var tmpButton=[];
			if (typeof data.showhide != 'undefined' && data.showhide.length>0) {
				var sh=data.showhide.split('|');
				$.each(sh, function(indx, ch) {
					tmpKey.push(ch);
					tmpValue.push($('#'+ch).css('display'));
					tmpButton.push($('#'+ch+'button').html());
				});
			}
			$('#loc'+locationId).html(data.location);
			// then save it back
			if (tmpKey.length > 0) {
				for (i=0; i<tmpKey.length; i++) {
					$('#'+tmpKey[i]).css('display', tmpValue[i]);
					$('#'+tmpKey[i]+'button').html(tmpButton[i]);
				}
			}
			
			$('#loc'+locationId).css({ opacity: 1 });
			if (data.dayProblem==true) {
				$('#dayInfo'+data.dayId).removeClass('noProblem');
			} else {
				$('#dayInfo'+data.dayId).addClass('noProblem');
			}
		});
	}
}

function nodeToString ( node ) {
	var tmpNode = document.createElement( "div" );
	tmpNode.appendChild( node.cloneNode( true ) );
	var str = tmpNode.innerHTML;
	tmpNode = node = null; // prevent memory leaks in IE
	
	return str;
}

function holidayTypeChanged(id) {
	var ht_comment='<br>';
	var ht_date1='block';
	var ht_date2='block';
	var ht_time1='table-cell';
	var ht_time2='table-cell';
	if (id>0) {
		var ht_comment=$('#ht_'+id).attr('data-comment');
		if ($('#ht_'+id).attr('data-fullday') == 1) {
			var ht_time1='none';
			var ht_time2='none';
		} else {
			if ($('#ht_'+id).attr('data-bothtime') == 0) {
				var ht_date2='none';
				var ht_time2='none';
			} else if ($('#ht_'+id).attr('data-bothtime') == -1) {
				var ht_date1='none';
				var ht_time1='none';
			} else {
				var ht_date2='none';
			}

		}
	}
	$('#ht_comment').html(ht_comment);
	$('#hr_startDate').css('display', ht_date1);
	$('#hr_finishDate').css('display', ht_date2);
	$('#hr_startTime').css('display', ht_time1);
	$('#hr_finishTime').css('display', ht_time2);
}


function postForm( $form, callback ){
	/*
	 * Get all form values
	 */
	var values = {};
	$.each( $form.serializeArray(), function(i, field) {
		values[field.name] = field.value;
	});
	/*
	 * Throw the form values to the server!
	 */
	$.ajax({
		type    : $form.attr( 'method' ),
		url     : $form.attr( 'action' ),
		data    : values,
		success : function(data) {
			callback( data );
		}
	});
}

function ajaxApproveDeny(url, userAction, id, userComment, returnUrl, refresh) {
	$.ajax({
		url: url,
		method: 'POST',
		dataType: 'json',
		data: {
			action: userAction,
			id: id,
			comment: userComment,
			refresh: refresh
		},
	})
	.error(function(jqXHR, status, errorThrown) {
		alert('ajax error');
	})
	.done(function(data) {
		if (data.success) {
			$('table#approval tr#'+data.id).remove();
			var rowCount = $('table#approval tr').length;
			if (rowCount <= 1) {
				$('#popupDiv').fadeOut(250);
			}
			if (typeof data.refresh != 'undefined' && data.refresh.length>0) {
				if (data.refresh.indexOf('/') == -1) {
					ajaxRefresh(data.refresh, '', '', '');
				} else {
					window.location=data.refresh;
				}
			}
		} else {
			alert(data.error);
		}
	});
}

function ajaxSwapApproveDeny(url, userAction, id, returnUrl) {
	$.ajax({
		url: url,
		method: 'POST',
		dataType: 'json',
		data: {
			action: userAction,
			id: id
		},
	})
	.error(function(jqXHR, status, errorThrown) {
		alert('ajax error');
	})
	.done(function(data) {
		if (data.success) {
			window.location=returnUrl;
		} else {
			alert(data.error);
		}
	});
}

function ajaxRefresh(div, func, usersearch, selectedUserId) {
	var url=$('#'+div).attr('data-url');
	var userId=$('#'+div).attr('data-userid');
	var locationId=$('#'+div).attr('data-locationid');
	var timestamp=$('#'+div).attr('data-timestamp');

	removeMessage();
	$('#'+div).fadeOut(100);
	$('#'+div).html('<p>Please wait... <img src="'+assetsBaseDir+'images/ajax-loader.gif" alt="Please wait..."></p>').fadeIn(100);
	$.ajax({
		url: url,
		method: 'POST',
		dataType: 'json',
		data: {
			userId: userId,
			locationId: locationId,
			timestamp: timestamp,
			usersearch: usersearch,
			selectedUserId: selectedUserId,
			func: func
		},
	})
	.error(function(jqXHR, status, errorThrown) {
		alert('ajax error');
		$('#'+div).fadeIn(100);
	})
	.done(function(data) {
		if (typeof data.error !== 'undefined' && data.error.length > 0) {
			alert(data.error);
		}
		if (typeof $('#dialog') != 'undefined') {
			$('#dialog').dialog('close');
		}
		$('#'+div).html(data.content).fadeIn(100);
	});
}


function ajaxAddPunch(url, typeId, userId, date, time, comment, origdatetime) {
	$.ajax({
		url: url,
		method: 'POST',
		async: false,
		dataType: 'json',
		data: {
			userId: userId,
			typeId: typeId,
			date: date,
			time: time,
			comment: comment,
			origdatetime: origdatetime
		},
	})
	.error(function(jqXHR, status, errorThrown) {
		alert('ajax error');
	})
	.done(function(data) {
		if (data.error.length > 0) {
			alert(data.error);
		}
		if (data.content.length > 0) {
			alert('content:'+data.content);
		}
	});
}

function showMessage(message) {
	$('<div></div>').appendTo('body')
	.html('<div><h3>'+(message)+'</h3></div>')
	.dialog({
		modal: true,
		zIndex: 2000,
		autoOpen: true,
		width: 'auto',
		resizable: false,
		buttons: {
			'OK': function () {
				$(this).dialog("close");
				$(this).remove();
			}
		},
		close: function (event, ui) {
			$(this).remove();
		}
	});
}

function ajaxAddRequest(dataUrl, dataBase, dataAction, dataDate, dataId) {
	$.ajax({
		url: dataUrl,
		method: 'POST',
		dataType: 'json',
		data: {
			base: dataBase,
			action: dataAction,
			date: dataDate,
			id: dataId
		},
	})
	.error(function(jqXHR, status, errorThrown) {
		alert('ajax error');
	})
	.done(function(data) {

		$('<div id="dialog" title="'+data.title+'"></div>').appendTo('body')
		.html('<div id="container">'+data.content+'</div>')
		.dialog({
			modal: true,
			zIndex: 2000,
			autoOpen: true,
			width: 'auto',
			height: 'auto',
			minHeight: 'auto',
			position: ['center', 20],
			resizable: false,
			close: function (event, ui) {
				$(this).remove();
			}
		});
	});
}

function ajaxTimesheetCheck(ajaxurl, date, userid, comment) {
	$.ajax({
		url: ajaxurl,
		method: 'POST',
		dataType: 'json',
		async: false,
		data: {
			action: 'save',
			date: date,
			userid: userid,
			comment: comment
		},
	})
	.error(function(jqXHR, status, errorThrown) {
		alert('ajax error');
	})
	.done(function(data) {
		if (data.error.length > 0) {
			alert(data.error);
		}
		ajaxRefresh('timesheetDiv', '', '', '');
	});
}

function addWaitPopup() {
	$('<div id="waitPopup"></div>').append('body')
		.html('<p>Please wait... <img src="'+assetsBaseDir+'images/ajax-loader.gif" alt="Please wait..."></p>')
		.dialog({
			modal: true,
			title: 'Please wait...',
			zIndex: 2000,
			autoOpen: true,
			width: 'auto',
			resizable: false,
			close: function (event, ui) {
				$(this).remove();
			}
		});
}
function removeWaitPopup() {
	$('#waitPopup').remove();
}
function removeMessage() {
	$('div[class=message]').html('');
}

window.addEventListener('scroll', function() {
	if ($('span.scrollTop') != 'undefined') {
	    if (window.pageYOffset >= 100) {
	    	$('span.scrollTop').css('display', 'inline');
	    } else {
	    	$('span.scrollTop').css('display', 'none');
	    }
	}
});

function getLocation(divId, formatText, domainid, ajaxurl, lat, long, enableDiv) {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
        	var ref={latitude: 0, longitude: 0};
        	ref.latitude = position.coords.latitude;
        	ref.longitude = position.coords.longitude;
        	if (typeof(domainid) != 'undefined' && typeof(ajaxurl) != 'undefined') {
	        	$.ajax({
	        		url: ajaxurl,
	        		method: 'POST',
	        		dataType: 'json',
	        		async: true,
	        		data: {
	        			action: 'position',
	        			domainid: domainid,
	        			ref: ref
	        		},
	        	})
	        	.error(function(jqXHR, status, errorThrown) {
	        		if (typeof('enableDiv') != 'undefined')  {
        				$('#'+enableDiv).hide();
	        		}
	        		alert('ajax error');
	        	})
	        	.done(function(data) {
	        		if (data.error.length > 0) {
	        			alert(data.error);
	        		}
	            	if (divId) {
	            		$('#'+divId).html(formatText.replace(/%lat/g, ref.latitude).replace(/%lon/g, ref.longitude)+', distance to '+data.location+' is '+data.distance+' m');
	            	}
	        		if (typeof('lat') != 'undefined') $('#'+lat).val(ref.latitude);
	        		if (typeof('long') != 'undefined') $('#'+long).val(ref.longitude);
	        		if (typeof('enableDiv') != 'undefined')  {
	        			if (data.distance != 'Unknown') {
	        				$('#'+enableDiv).show();
	        			} else {
	        				$('#'+enableDiv).hide();
	        			}
	        		}
	        		
	        	});
        	} else if (typeof(domainid) != 'undefined' && typeof(ajaxurl) == 'undefined') {
            	if (divId) {
            		$('#'+divId).html(formatText.replace(/%lat/g, ref.latitude).replace(/%lon/g, ref.longitude));
            	}
        	}
        	return ref;
        });
    } else {
    	if (typeof(divId) != 'undefined') {
    		$('#'+divId).html('<p style="color:red">Oops! This browser does not support HTML5 Geolocation</p>');
    	} else {
    		alert('Oops! This browser does not support HTML5 Geolocation')
    	}
    }
    return null;
}
function calculateDistance(lat1, lat2) {
	var φ1 = lat1.toRadians(), φ2 = lat2.toRadians(), Δλ = (lon2-lon1).toRadians(), R = 6371e3; // gives d in metres
	var d = Math.acos( Math.sin(φ1)*Math.sin(φ2) + Math.cos(φ1)*Math.cos(φ2) * Math.cos(Δλ) ) * R;
	return d;
}