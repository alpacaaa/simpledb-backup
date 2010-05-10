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
			$log = Config::get($this->local, 'logfile', date("Y-m-d"). '-backup.log');			
			$file = Config::get($this->local, 'filename', date("Y-m-d"). '.zip');
			$archiveDir = Config::get($this->local, 'archive-dir', 'done');
			
			$this->dumpDb($archiveDir. '/'. $file);
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
		
		protected function dumpDb($filename)
		{
			$this->log('Dumping databases');
			
			$user  = Config::get($this->local, 'user');
			$pass  = Config::get($this->local, 'pass');
			
			$dumper = new Mysqldumper('localhost', $user, $pass);
			$dbs = $dumper->listDbs();
			// $this->log('Databases: %s', implode(', ', $dbs));
			
			$zip = new ZipArchive();
			$zip->open($filename, ZIPARCHIVE::CREATE);
			
			foreach ($dbs as $db)
			{
				if (empty($db)) continue;
				
				$this->log('Dump db %s', $db);
				
				$dumper->setDBname($db);
				$dump = $dumper->createDump();
				$zip->addFromString($db.'.sql', $dump);
			}
			
			$this->log('Dump Finished');
			$this->log('Zipped Files: %s', $zip->numFiles);
			$zip->close();
		}
		
		protected function createLog($file, $format = 'json')
		{
			$format = trim(strtolower($format));

			if ($format == 'json')
			{
				$content = json_encode($this->logs);
			}
			elseif ($format == 'xml')
			{
				$xml = new SimpleXMLElement('<logs></logs>');
				foreach ($this->logs as $log)
				{
					self::obj2Xml($log, $xml, 'log');
				}
				$content = $xml->asXML();
			}
			else
				throw new Exception('Log format not supported');
			
			file_put_contents($file, $content);
		}
		
		protected function sendFile($file, $log, $archiveDir)
		{
			$this->createLog($archiveDir. '/'. $log);
			$this->log('Ready to send files');

			$url = Config::get($this->remote, 'url');
			
			include('Net/SFTP.php');
			$sftp = new Net_SFTP($url);
			$this->log('Connecting to remote server', $sftp);
			
			$user  = Config::get($this->remote, 'user');
			$pass  = Config::get($this->remote, 'pass');
			
			$login = $sftp->login($user, $pass);
			$this->log('Login to remote server', $login);
			
			$sftp->pwd();
			
			$dir = Config::get($this->remote, 'dir');
			if ($dir) {
				$res = $sftp->chdir($dir);
				$this->log('Change dir', $res);
			}
			
			$files = glob($archiveDir. '/*');
			$success = true;
			
			foreach ($files as $file)
			{
				$clean = array_pop(explode('/', $file));
				$upload = $sftp->put($clean, $file, NET_SFTP_LOCAL_FILE);
				$this->log('Uploading '. $clean, $upload);

				if (!$upload) $success = false;
			}
			
			$this->log('Backup Completed');

			$file = $archiveDir. '/'. $log;
			$this->createLog($file);
			$sftp->put($log, $file, NET_SFTP_LOCAL_FILE);

			if ($success) $this->purge($archiveDir);
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
