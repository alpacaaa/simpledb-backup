<?php

	require_once 'class.integrity.php';
	$integrity = new Integrity();
	$error = 'clean-error';	$ok = 'clean-ok';
	
	$dirs = glob('done/*');
	$integrity->setDays(5);
	
	$resolve = $_GET['resolve'];
	if ($resolve) $integrity->resolve($resolve);
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" dir="ltr">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<link rel="stylesheet" href="ui/style.css" />
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
	<script type="text/javascript">
		$(document).ready(function() {
			$('.show').click(function(){
				$(this).parent().find('div ul').toggle('slide');
				msg = $(this).html() == 'show log' ? 'hide log' : 'show log';
				$(this).html(msg);
			});
		});
	</script>
	<title>Backup Status</title>
</head>
<body>
	
	<h1>Backup Status</h1>
	<ul>
	<?php
	foreach ($dirs as $dir):
			$integrity->setDir($dir);
			$domain = $integrity->check();
	?>
	<?php foreach ($domain as $file => $obj): ?>
		<li class="<?php echo $obj->success == true ? $ok : $error; ?>">
			<span><?php echo $obj->date; ?></span><?php echo $obj->domain; ?><a class="show clean-yellow">show log</a>
			<div>
			<ul>
				<?php
					$log = $dir. '/'. $obj->date. '-backup.log';
					$log = file_exists($log) ? simplexml_load_file($log) : array();
					if (!$log):
				?>
				<?php if ($integrity->isResolved($file)): ?>
					<li class="success">Failed backup has been resolved</li>
				<?php else: ?>
					<li class="failure">Log not available.</li>
					<li class="mark"><a href="?resolve=<?php echo $file; ?>">Stop Notify</a></li>
				<?php
					endif;
					endif;
					foreach ($log as $el):
				?>
				<li class="<?php echo $el->success ? 'success' : 'failure'; ?>">
					<span>[<?php echo date('h:i:s', intval($el->time)); ?>]</span>
					<?php echo $el->msg; ?>
				</li>
				<?php endforeach; ?>
			</ul>
			</div>
		</li>
		<?php endforeach; ?>
	<?php endforeach; ?>
</body>
</html>
