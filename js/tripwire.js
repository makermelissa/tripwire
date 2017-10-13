$(document).ready(function(){
	if ($('.date-mask').length > 0) {
		default_val = $('.date-mask').attr('value');
		//console.log(default_val);
		$('.date-mask').mask('99/99/9999');
	}
	if ($('.phone-mask').length > 0) {
		default_val = $('.phone-mask').attr('value');
		//console.log(default_val);
		$('.phone-mask').mask('(999) 999-9999');
	}
	if ($('.zip-mask').length > 0) {
		default_val = $('.date-mask').attr('value');
		//console.log(default_val);
		$('.zip-mask').mask('99999');
	}
	
	function get_sw_object() {
		try {
			sw_object = JSON.parse($('input[name="software"]').val());
		} catch (err) {
			sw_object = {"software": {}};
		}
		return sw_object;
	}

	function formatCurrency(num) {
		num = num.toString().replace(/\$|\,/g, '');
		if (isNaN(num)) num = "0";
		var sign = (num == (num = Math.abs(num)));
		num = Math.floor(num * 100 + 0.50000000001);
		var cents = num % 100;
		num = Math.floor(num / 100).toString();
		if (cents < 10) cents = "0" + cents;
		for (var i = 0; i < Math.floor((num.length - (1 + i)) / 3); i++) {
			num = num.substring(0, num.length - (4 * i + 3)) + ',' + num.substring(num.length - (4 * i + 3));
		}
		
		return ((sign) ? '' : '-') + '$' + num + '.' + cents;
	}

	function formatDate(date) {
		date = date.split(/[\/\.-]/g); // Look for d/m/y, d-m-y, or d.m.y formats
		
		if (date[0].length == 1) date[0] = '0' + date[0];
		if (date[1].length == 1) date[1] = '0' + date[1];
		if (date[2] < 70) { date[2] = parseInt(date[2]) + 2000 }
		else if (date[2] < 100) { date[2] = parseInt(date[2]) + 1900 };

		return date.join('.');	
	}
	
	$("#tabs").tabify();
	
	$('#tabs').on("click load", 'a', function() {
		updateCategory($(this).attr('href'));
	});
	
	function updateCategory(hash) {
		var tabtype = hash.substring(1);
		tabtype = tabtype.substring(0, tabtype.length - 4);
		$('#type').val(tabtype);
		//alert(tabtype);
	}
	
	updateCategory(window.location.hash ? window.location.hash : '#for-sale-tab');
	
	function buildAreaBubbles(arealist, removalid) {
		$.post('/ajax/prune_list', {list: arealist}, function(list) {
			console.log(list);
			var htmlList = '';
			var newList = new Array();
			$.each(list, function(index, element) {
				if (element != '' && element.id != removalid) {
					htmlList += '<div id="areaid-'+element.id+'" class="area-item"><div class="area-remove"></div>'+element.value+'</div>';
					newList.push(element);
				}
			});
			// Add a location to a list
			$('#area-list').html(htmlList);
			// Add the location ID to a hidden field named #area

		$('#area').val(JSON.stringify(newList));
		}, 'json');
	}
	
	$(document).on('click', '.area-remove', function() {
		// get the area id
		var areaid = $(this).parent().attr('id').replace('areaid-', '');
		// Remove the parent div
		$(this).parent().remove();

		// Loop through the $('#area') value and remove the object with the parent div id
		var list = JSON.parse($('#area').val());
		buildAreaBubbles(list, areaid);
	});
	
	if ($('#location').length) {
		$('#location').autocomplete({
			source: "/ajax/get_location_list",
			select: function( event, ui ) {
				var list;
				if ($('#area').val() == '') {
					list = new Array();
				} else {
					list = JSON.parse($('#area').val());
				}
				if (list.length == 20) {
					popupMessage('Don\'t be greedy! In order to reduce the load on our servers, you can only enter up to 20 areas per search. If you need more areas, create a new search.', 'Error');
				} else {
					// Add a new area
					list.push(ui.item);
					buildAreaBubbles(list);
					$('#location').val('');
					return false;
				}
			},
			minLength: 1
		}).data( "ui-autocomplete" )._renderItem = function(ul, item) {
			return $( '<li class="location-item"></li>' )
			.data( "ui-autocomplete-item", item )
			.append( '<a><span style="width: 300px;">'+item.label+'</span><span style="width: 100px;">'+item.sublabel+'</span></a>' )
			.appendTo( ul );
		};
		
		$.validateExtend({
			area : {
				required : true,
				conditional : function(value) {
					var location_id = $('#area').val();
					$.post('/ajax/location_id_valid', {location_id: location_id}, function(data) {
						return data.valid;
					}, 'json');
					
					return false;
				}
			}
		});
	}
	
	/*$('a#copy-share-link-btn').zclip({
		path:'/js/ZeroClipboard.swf',
		copy:function(){return $('input#share-link').val();}
	});*/

	$(function(){
		ZeroClipboard.setMoviePath( '/js/ZeroClipboard.swf' );
		var clip = new ZeroClipboard.Client();
		clip.setText( $('input#share-link').val() );
		clip.addEventListener( 'onComplete', function(){
			if( ! $("#copy-share-link-btn").hasClass('copied') ){
				$("#copy-share-link-btn").addClass('copied').text('Copied');
				$('#share-link').addClass('copied');
			}
		});
		$("#copy-share-link-btn").append(clip.getHTML( 100, 42 ));
		
		$("#share-link").click(function(){
			$(this).focus().select();
		});
	});

	function popupMessage(message, title, label) {
		var dialogButtons = { }
		buttonLabel = (label === undefined || label == '') ? 'Ok' : label;
		dialogButtons[buttonLabel] = function() {
										$(this).dialog("close");
									}
		
		if (title === undefined || title == '') title = 'Message';
		$("#js_message_wrapper").prop('title', title);
		$("#js_message_wrapper").html(message);
		$("#js_message_wrapper").dialog({
			resizable: false,
			autoOpen: false,
			modal: true,
			buttons: dialogButtons
		});
		$("#js_message_wrapper").dialog("open");
	}
	
	if ($("#suggestion").length > 0) { 
		$("#survey_wrapper").dialog({
			show: {
				effect: "clip",
				duration: 300
			},
			hide: {
				effect: "clip",
				duration: 300
			},
			open: function( event, ui ) {
				$("textarea#suggestion").val('');
				$("#captcha-status").html('');
				Recaptcha.reload();
			},
			close: function(event, ui) {
				allFields.val( "" ).removeClass("error-state");
			},
			width: 350,
			resizable: false,
			autoOpen: false,
			modal: true,
			draggable: false,
			buttons: {
				"Submit": function() {
					var bValid = true;
					allFields.removeClass("error-state");
					bValid = bValid && checkLength( email, "email", 6, 80 );
					// From jquery.validate.js (by joern), contributed by Scott Gonzalez: http://projects.scottsplayground.com/email_address_validation/
          			bValid = bValid && checkRegexp( email, /^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i, "eg. ui@jquery.com" );
          
					if ( bValid ) {
						if (validateCaptcha()) {
							$(this).dialog("close");
						}
					}
				},
				Cancel: function() {
					$(this).dialog("close");	
				}
			}
	   });
	
		$('#show_survey').on('click', function(event) {
			$("#survey_wrapper").dialog("open");
			return false;
		});
		
		$('form#suggest').on('submit', function(e) {
			$(this).parentsUntil('.ui-dialog').parent().find('.ui-dialog-buttonset').children().each( function( index ) {
				if ($(this).find('.ui-button-text').html() == 'Submit') {
					$(this).click();
						
				}
			});
			e.preventDefault();
		});
		
		var	email = $("#suggest_email"),
		allFields = $([]).add(email),
		tips = $(".validateTips");
	
		function updateTips(t) {
			tips
			.text(t)
			.addClass("ui-state-highlight");
			setTimeout(function() {
				tips.removeClass("ui-state-highlight", 1500);
			}, 500 );
		}
	
 		function checkLength(o, n, min, max) {
			if (o.val().length > max || o.val().length < min) {
				o.addClass("error-state");
				updateTips("Length of " + n + " must be between " +
					min + " and " + max + ".");
				return false;
			} else {
				return true;
			}
		}
	 
		function checkRegexp(o, regexp, n) {
			if (!(regexp.test(o.val()))) {
				o.addClass("error-state");
				updateTips(n);
				return false;
			} else {
				return true;
			}
		}

		//Validate the Recaptcha' Before continuing with POST ACTION
		function validateCaptcha() {
			var challengeField = $("input#recaptcha_challenge_field").val();
			var responseField = $("input#recaptcha_response_field").val();
		
			var response = $.ajax({
				type: "POST",
				url: "ajax/recaptcha_validate",
				data: {
					recaptcha_challenge_field: challengeField,
					recaptcha_response_field: responseField 
				},
				async: false
			}).responseText;
	
			var suggestionField = $("textarea#suggestion").val();
			var emailField = $("#suggest_email").val();
			if(response == "success") {
				var sent = $.ajax({
					type: "POST",
					url: "ajax/send_suggestion",
					data: {
						suggestion: suggestionField,
						email: emailField
					},
					async: false
				}).responseText;
	
				if(sent == "success") {
					popupMessage('Thank you! Your suggestion has been sent.', 'Success', 'Awesome');
					//$("#captcha-status").html("<p class=\"green bold\">Thank you! Your suggestion has been sent.</p>");
				} else if (sent = "mail_error") {
					popupMessage('There was an issue sending out your message.', 'Mail Error');
				} else if (sent = "address_error") {
					popupMessage('We were unable to validate your email address.', 'Email Address Error');
				} else if (sent == "error") {
					popupMessage('You need to be validated to send a suggestion.', 'Error');
				} else {
					popupMessage('There was an unknown response from the server.', 'Error');
				}
				return true;
			} else {
				$("#captcha-status").html("The security code you entered did not match. Please try again.<br /><br />");
				Recaptcha.reload();
				return false;
			}
		}
	}
});

