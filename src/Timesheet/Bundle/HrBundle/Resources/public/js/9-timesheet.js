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
  $(this).on('submit',function(e){
    var $form = $(this);

    if ($form.data('submitted') === true) {
      // Previously submitted - don't submit again
      e.preventDefault();
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
//        alert(name+'='+value);
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
            	if (data.error.length) {
            		alert(data.error);
            	} else {
            		window.location=$('#config_url').val();
            	}
            }
        });
    });
    
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
					ajaxRefresh(response.refresh, '', '');
				} else if (typeof response.cause != 'undefined' && response.cause.length>0) {
					$('#popupDiv').fadeIn(250);
					alert(response.cause);
				}
			});
		}
	});
	           
	           
	if ($('div[class=clock]').size()) {
		setInterval(function() {
			showDateTime('div[class=clock]');
		}, 1000);
		showDateTime('div[class=clock]');
	};
	$('body').delegate('input[class=dateInput]', 'focus', function() {
		$(this).datepicker({dateFormat: 'dd/mm/yy'});
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
				break;
			default :
				ga.val('0');
				ga.css('read_only', true);
				ga.attr('disabled', 'disabled');
				la.val('0');
				la.css('read_only', true);
				la.attr('disabled', 'disabled');
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

	$('body').delegate('input[name=userSearch],select[name=groupSearch],select[name=qualificationSearch]', 'keyup change', function () {
		$.ajax({
			url: $(this).attr('data-url'),
			method: 'POST',
			dataType: 'json',
			data: {
				name: $('input[name=userSearch]').val(),
				group: $('select[name=groupSearch]').val(),
				qualification: $('select[name=qualificationSearch]').val(),
				base: $(this).attr('base-url'),
				listType: $(this).attr('data-type')
			},
			beforeSend: function() {
				$('#usersList').css({ opacity: 0.5 });
			}
		})
		.error(function(jqXHR, status, errorThrown) {
//			alert('ajax error...');
		})
		.done(function(data) {
			$('#usersList').html(data.content)
				.css({ opacity: 1 });
			
		});
	});
	
	$('body').delegate('span[class^="addRequest"]', 'click', function () {
		ajaxAddRequest($(this).attr('data-url'), $(this).attr('base-url'), $(this).attr('data-action'), $(this).attr('data-date'), $(this).attr('data-id'));
	});



	$('body').delegate('[class=closeButton]', 'click', function () {
		$(this).parent().fadeOut(250);
	});
	
	$('body').delegate('button[type=submit]', 'click', function () {
//		scroll(0,0);
//		$(this).text('Submitting...');
		$('form').preventDoubleSubmission();
//		$('#lockscreen').addClass('freezePaneOn');
//		$(this).submit();
	});

	$('body').delegate('input[class=buttonApprove],input[class=buttonDeny]', 'click', function () {
		var userAction=(($(this).attr('class')=='buttonApprove')?('approve'):('deny'));
		var id=$(this).attr('id');
		var userComment=$('#comment_'+id).val();
		var ajaxUrl=$('#holidayapproval').attr('data-url');
		var returnUrl=$('#holidayapproval').attr('data-base');
		
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
		    				ajaxApproveDeny(ajaxUrl, 'approve', id, userComment, returnUrl);
		    				$(this).dialog("close");
		    			},
		    			'Deny': function () {
		    				ajaxApproveDeny(ajaxUrl, 'deny', id, userComment, returnUrl);
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
			ajaxApproveDeny(ajaxUrl, userAction, id, userComment, returnUrl);
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
				action: 'confirm',
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
/*
				$('#popupDiv')
					.html('<input type="button" class="closeButton" value="X">'+'<div id="container" style="height: '+(Math.round($(window).height()*0.7))+'px; width: '+(Math.round($(window).width()*0.7))+'px"></div>')
					.css('width', Math.round($(window).width()*0.8))
					.css('height', Math.round($(window).height()*0.8))
					.css('z-index', 1000)
					.center()
					.fadeIn(250);

				$('#container').html(data.content);
*/
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


	$('body').delegate('td[class=addPunch]', 'click', function () {
		var type=$(this).attr('data-type');
		var typeId=$(this).attr('data-typeid');
		var date=$(this).attr('data-date');
		var dateDisplay=$(this).attr('data-datedisplay');
		var userId=$(this).attr('data-userid');
		var username=$(this).attr('data-username');
		var ajaxUrl=$('#addPunch').attr('data-url');

		$('<div></div>').appendTo('body')
		   	.html('<div>'+
   				'<h3>Would you like to add '+type+' for '+username+' on '+dateDisplay+'</h3>'+
   				'<table>'+
   				'<tr><td>Time:</td><td><input type="text" class="timeInput" id="addPunchTime" value=""></td></tr>'+
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
		   				ajaxAddPunch(ajaxUrl, typeId, userId, date, apTime, apComment);
		   				ajaxRefresh('timesheetDiv', '', '');		   				
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

	$('body').delegate('a[class=showRequest]', 'click', function () {
		$.ajax({
			url: $(this).attr('data-url'),
			method: 'POST',
			dataType: 'json',
			data: {
				base: $(this).attr('base-url'),
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
	
	$('body').delegate('input[id=usersearch]', 'change', function () {
		if ($(this).val().length > 1) {
			ajaxRefresh('timesheetDiv', '', $(this).val());
		}
	});

	$('body').delegate('input[id=usersearchButton]', 'click', function () {
		if ($('#usersearch').val().length > 1) {
			ajaxRefresh('timesheetDiv', '', $('#usersearch').val());
		}
	});
	
	$('body').delegate('span[class=switchMonth]', 'click', function () {
		ajaxRefresh($(this).attr('data-div'), $(this).attr('name'), '');
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
//							alert(data.url);
							$('#popupDiv').html('<iframe id="myIframe"></iframe>');
							document.getElementById('myIframe').src=data.url;
						}
					}
				} else {
					ajaxRefresh(div, '', '');
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
//	dragIcon.src = '/web/bundles/timesheethr/images/ajax-loader.gif';
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
// alert(targetId+' - '+((parseInt(targetId)>0)?'true':'false'));
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
				alert(data.error);
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

function ajaxApproveDeny(url, userAction, id, userComment, returnUrl) {
	$.ajax({
		url: url,
		method: 'POST',
		dataType: 'json',
		data: {
			action: userAction,
			id: id,
			comment: userComment
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
//				window.location=returnUrl;
			}
			if (typeof data.refresh != 'undefined' && data.refresh.length>0) {
				ajaxRefresh(data.refresh, '', '');
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

function ajaxRefresh(div, func, usersearch) {
	var url=$('#'+div).attr('data-url');
	var userId=$('#'+div).attr('data-userid');
	var locationId=$('#'+div).attr('data-locationid');
	var timestamp=$('#'+div).attr('data-timestamp');

	$('#'+div).fadeOut(100);
	$('#'+div).html('<h2><img src="/web/bundles/timesheethr/images/ajax-loader.gif" alt="Loading..."></h2>').fadeIn(100);
	$.ajax({
		url: url,
		method: 'POST',
		dataType: 'json',
		data: {
			userId: userId,
			locationId: locationId,
			timestamp: timestamp,
			usersearch: usersearch,
			func: func
		},
	})
	.error(function(jqXHR, status, errorThrown) {
		alert('ajax error');
		$('#'+div).fadeIn(100);
	})
	.done(function(data) {
		if (data.error.length > 0) {
			alert(data.error);
		}
		if (typeof $('#dialog') != 'undefined') {
			$('#dialog').dialog('close');
		}
		$('#'+div).html(data.content).fadeIn(100);
	});
}


function ajaxAddPunch(url, typeId, userId, date, time, comment) {
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
		ajaxRefresh('timesheetDiv', '', '');
	});
}
