<?php

	require_once 'class.integrity.php';
	$integrity = new Integrity();
	$error = 'clean-error';	$ok = 'clean-ok';
	
	$dirs = glob('done/*');
	$integrity->setDays(5);
	
	$resolve = $_GET['resolve'];
	if ($resolve) $integrity->resolve($resolve);

	$log = $_GET['log'];
	if ($log) die($integrity->getLog($log));

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" dir="ltr">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<link rel="stylesheet" href="ui/style.css" />
	<link rel="stylesheet" href="ui/tipsy.css" />
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
	<script type="text/javascript" src="ui/tipsy.js"></script>
	<script type="text/javascript">
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

					var parent = self.parents('li');
					var date = parent.find('span').text();
					var file = parent.text().replace(date, '').trim();

					date = self.attr('alt');
					file = file + '|' + date;


					$.getJSON('?log=' + file, function(data){
						var div = $('<div>').attr('id', id);
						var ul = $('<ul>');
						$(data).each(function(){
							var li = $('<li>');
							this.success ?
								li.addClass('success') :
								li.addClass('failure');

							var time = new Date(this.time * 1000);
								time = [time.getHours(), time.getMinutes(), time.getSeconds()];
								time = time.map(function(el){
									el = el.toString();
									return el.length == 1 ? el + '0' : el;
								});

							var html = '<span>[' + time.join(':') + ']</span>' + this.msg;
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
	</script>
	<title>Backup Status</title>
</head>
<body>
	
	<h1>Backup Status</h1>
	<ul>
	<?php
	$i = 1;
	foreach ($dirs as $dir):
			$integrity->setDir($dir);
			$domain = $integrity->check();

			$success = true;
			$date = current($domain)->date;
			reset($domain);

			foreach ($domain as $file => $obj){
				if ($obj->success !== true){
					$success = false;
					$date = $obj->date;
					break;
				}
			}
	?>
		<li class="<?php echo $success == true ? $ok : $error; ?>">
			<span><?php echo $date; ?></span><?php echo $obj->domain; ?>
			<ul class="notibar">

				<?php foreach ($domain as $file => $obj): ?>
				<li class="noti <?php echo $obj->success == true ? 'success' : 'failure'; ?>"
				alt="<?php echo $obj->date; ?>" id="noti<?php echo $i++; ?>">
				&nbsp;
				</li>
				<?php endforeach; ?>
			</ul>
		</li>
	<?php endforeach; ?>
</body>
</html>
