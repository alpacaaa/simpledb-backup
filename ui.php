<?php

	require_once 'class.integrity.php';
	require_once 'class.config.php';
	$integrity = new Integrity(Config::get('DT-server', 'archive-dir', 'done'));

	$dirs = $integrity->discover();
	$integrity->setDays(Config::get('DT-server', 'days', 5));
	
	$resolve = $_GET['resolve'];
	if ($resolve) $integrity->resolve($resolve);

	$log = $_GET['log'];
	if ($log) die($integrity->getLog($log));

	$css = array(
		'ok' => 'clean-ok',
		'error' => 'clean-error',
		'success' => 'success',
		'failure' => 'failure'
	);
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
	<script type="text/javascript" src="ui/log-loader.js"></script>
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
		<li class="<?php echo $success == true ? $css['ok'] : $css['error']; ?>">
			<span><?php echo $date; ?></span><?php echo $obj->domain; ?>
			<ul class="notibar">

				<?php foreach ($domain as $file => $obj): ?>
				<li class="noti <?php echo $obj->success == true ? $css['success'] : $css['failure']; ?> <?php echo $i++; ?>"
				alt="<?php echo $obj->domain. ' - '. $obj->date; ?>">
				&nbsp;
				</li>
				<?php endforeach; ?>
			</ul>
		</li>
	<?php endforeach; ?>
</body>
</html>
