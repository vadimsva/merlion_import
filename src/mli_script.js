var $ = jQuery;
$(function(){
	$('#catList label').click(function() {
		if($(this).hasClass('active')) {
			$(this).removeClass('active');
			$('#catList label[data-subcat*="'+$(this).attr('data-cat')+'"]').removeClass('show active');
			$('#catList div[data-subcat*="'+$(this).attr('data-cat')+'"]').remove();
		} else {
			$(this).addClass('active');
			$('#catList label[data-subcat="'+$(this).attr('data-cat')+'"]:eq(0)').before('<div class="cat" data-subcat="'+$(this).attr('data-cat')+'">'+$(this).text()+'</div>');
			$('#catList label[data-subcat="'+$(this).attr('data-cat')+'"]').addClass('show');
		}
	});
	
	$('.selectedDate_box input[name="date"]').click(function() {
		var elem = $('.selectedDate_box input[name="selected_date"]');
		$(this).val() == 'selected' ?	elem.removeAttr('disabled').datepicker({dateFormat: "yy-mm-dd"}) : elem.attr('disabled', 'true').datepicker("destroy");
	});
	
	$('.importSchedule_box input[name="schedule_date"]').click(function() {
		var elem = $('.schedule_plan_box');
		$(this).val() == 'weekly' ?	elem.removeClass('disabled') : elem.addClass('disabled');
	});
	
	$('.save_btn').click(function() {
		var ar = [];
		$('#catList label.active').each(function() {
			ar.push($(this).attr('data-cat'));
		});
		if (ar.length != 0) {
			var cats = [];
			ar = ar.reverse();
			$.each(ar, function(i, item){
				var found = cats.find(function(el) { return 0 <= el.search(item); });
				if (!found) {
					cats.push(item);
				}
			});
			
			var shipment = $('.shipmentType_box input[name="shipment"]:checked').val();
			var only_avail_input = $('.shipmentType_box input[name="only_avail"]');
			only_avail_input.is(':checked') ? avail_items = only_avail_input.val() : avail_items = '0';
			$('.selectedDate_box input[name="date"]:checked').val() == 'selected' ?	date = $('.selectedDate_box input[name="selected_date"]').val()+'T00:00:00' :	date = '';
			if ($('.priceSettings_box input[name="round_price"]:checked')) {
				var round_price = '1';
			} else {
				var round_price = '0';
			}
			var exrate = $('.priceSettings_box input[name="exrate"]').val();
			if ($('.priceSettings_box input[name="price_empty"]:checked')) {
				var price_empty = '1';
			} else {
				var price_empty = '0';
			}
			var schedule = $('.importSchedule_box input[name="schedule_date"]:checked').val();
			var schedule_full_run = $('.importSchedule_box select[name="schedule_full_run"]').val();
			var schedule_price_run = $('.importSchedule_box select[name="schedule_price_run"]').val();
			if (schedule == 'no') {
				schedule_full_run = '';
				schedule_price_run = '';
			}

			$.post(mli_path+'mli_core.php', {categories: cats, shipment_type: shipment, only_avail: avail_items, selected_date: date, round_price: round_price, exrate: exrate, price_empty: price_empty, schedule_date: schedule+','+schedule_full_run+','+schedule_price_run},
				function(response,status){
					window.location.reload(true);
				}).fail(function(){
					notifyPopup('Произошла ошибка, повторите запрос позже...', true);
				});
		} else {
			notifyPopup('Выберите категорию', true);
		}
	});
	
	$('.import_btn').click(function() {
		$.post(mli_path+'mli_core.php', {import: 'import'},
			function(response,status){

			}).fail(function(){
				notifyPopup('Произошла ошибка, повторите запрос позже...', true);
			});
		notifyPopup('Запрос отправлен, идет импорт...');
	});
	
	$('.import_price_btn').click(function() {
		$.post(mli_path+'mli_core.php', {import_price: 'import'},
			function(response,status){

			}).fail(function(){
				notifyPopup('Произошла ошибка, повторите запрос позже...', true);
			});
		notifyPopup('Запрос отправлен, идет импорт...');
	});
	
	function notifyPopup(text, error) {
		if (error) $('.notify_popup').addClass('err');
		$('.notify_popup').text(text).show();
		setTimeout(function() {
			$('.notify_popup').hide().removeClass('err').text('');
		},3000);
	}
	
});