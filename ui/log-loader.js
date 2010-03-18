$(document).ready(function() {

	$('.noti').each(function(){
		$(this).tipsy({title: 'alt', gravity: 's', delayIn: 0.25, delayOut: 0.17});

		$(this).click(function(){

			$('ul li div').each(function(){
				if ($(this).is(':visible'))
					$(this).toggle('slide');
			});

			var self = $(this);
			var id = 'log' + self.attr('id');
			var obj = $('#' + id);

			if (obj.length == 1)
				return obj.toggle('slide');

			var file = self.attr('alt').replace(' - ', '|');

			$.getJSON('?log=' + file, function(data){
				var div = $('<div>').attr('id', id);
				var ul = $('<ul>');
				$(data).each(function(){
					var li = $('<li>');
					this.success ?
						li.addClass('success') :
						li.addClass('failure');

					var time = '';
					if (this.time > 0) {
						time = new Date(this.time * 1000);
						time = [time.getHours(), time.getMinutes(), time.getSeconds()];
						time = time.map(function(el){
							el = el.toString();
							return el.length == 1 ? el + '0' : el;
						});

						time = '<span>[' + time.join(':') + ']</span>';
					}

					var html = time + this.msg;
					li.html(html);
					ul.append(li);
				});

				div.append(ul);

				self.parent().parent().append(div);
				div.toggle('slide');
			});
		});

	});
});
