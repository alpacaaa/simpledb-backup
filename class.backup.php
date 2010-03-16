<?php

	set_include_path(get_include_path() . PATH_SEPARATOR . 'phpseclib');

	class Backup {
	
		private $error;
		private $local;
		private $remote;
		private $log = array();
		
		public function __construct($local = 'server', $remote = 'DT-server')
		{
			$this->local = $local;
			$this->remote = $remote;
		}
		
		public function doBackup()
		{
			$dir = Config::get($this->local, 'temp', 'backup');
			$this->tempDir = $dir;
			
			shell_exec(sprintf('rm -rf %s/*', $dir));

			$this->dumpDb();
			
			$log = Config::get($this->local, 'logfile', date("Y-m-d"). '-backup.log');			
			$file = Config::get($this->local, 'filename', date("Y-m-d"). '.tar.gz');
			$archiveDir = Config::get($this->local, 'archive-dir', 'done');
			
			$this->sendFile($file, $log, $archiveDir);
		}
		
		public function isError()
		{
			return $this->error;
		}
		
		public function purge()
		{
			$dir = Config::get($this->local, 'archive-dir', 'done');
			shell_exec(sprintf('rm -rf %s/*', $dir));
		}
		
		protected function dumpDb()
		{
			$this->log('Dump Db');
			
			$mysql = system('which mysql');
			$dump  = system('which mysqldump');
			
			$user  = Config::get($this->local, 'user');
			$pass  = Config::get($this->local, 'pass');
			if ($pass !== '') $pass = '-p'. $pass;
			
			$dbs = explode("\n", shell_exec(
				sprintf("%s -u %s %s -Bse 'show databases'",
				$mysql, $user, $pass
				)));
			
			// $this->log('Databases: %s', implode(', ', $dbs));
			
			$dir = $this->tempDir;
			
			foreach ($dbs as $db)
			{
				if (empty($db)) continue;
				
				$this->log('Dump db %s', $db);
				
				shell_exec(sprintf(
					"%s --opt -h localhost -u %s %s %s | gzip > %s/%s_%s.sql.gz",
					$dump, $user, $pass, $db, $dir, date("Y-m-d"), $db
					));
			}
			
			$this->log('Dump Finished');
		}
		
		protected function createLog($file)
		{
			$xml = new SimpleXMLElement('<logs></logs>');
			foreach ($this->logs as $log)
			{
				self::obj2Xml($log, $xml, 'log');
			}
			
			file_put_contents($file, $xml->asXML());
		}
		
		protected function sendFile($file, $log, $archiveDir)
		{
			$this->log('Compressing');
			
			$dir = $this->tempDir;
			$result = shell_exec(
				sprintf("tar -cvzf %s %s/*",
				$archiveDir. '/'. $file, $dir
				));
			
			// $this->log("Compression output \n%s", $result);
			$this->createLog($log);
			
			$this->log('Sending to ssh server');
			
			$url = Config::get($this->remote, 'url');
			
			include('Net/SFTP.php');
			$sftp = new Net_SFTP($url);
			$this->log('Connect to ssh server', $sftp);
			
			$user  = Config::get($this->remote, 'user');
			$pass  = Config::get($this->remote, 'pass');
			
			$login = $sftp->login($user, $pass);
			$this->log('Login ssh server', $login);
			
			$sftp->pwd();
			
			$dir = Config::get($this->remote, 'dir');
			if ($dir) {
				$res = $sftp->chdir($dir);
				$this->log('Change dir', $res);
			}
			
			$this->createLog($archiveDir. '/'. $log);
			sleep(5);
			$files = glob($archiveDir. '/*');
			
			foreach ($files as $file)
			{
				$clean = array_pop(explode('/', $file));
				$upload = $sftp->put($clean, $file, NET_SFTP_LOCAL_FILE);
				$this->log('Uploading '. $clean, $upload);
			}
			
			$this->log('Backup Completed');
		}
		
		protected function log($msg, $res = null, $replace = '%s')
		{
			$log = new StdClass();
			if (strpos($msg, $replace) !== false) $msg = str_replace($replace, $res, $msg);

			$log->msg = $msg;
			$log->time = time();
			$log->success = !($res === false);
			
			if (!$log->success) $this->error = true;
				
			$this->logs[] = $log;
		}
		
		public static function obj2Xml($obj, $xml, $node = null)
		{
			$node = $node ? $node : get_class($obj);
			$node = $xml->addChild($node);
			
			$vars = get_object_vars($obj);
			
			foreach ($vars as $key => $val)
			{
				$node->addChild($key, $val);
			}
		}
	}
