<?php

	class Integrity {
		
		protected $dir;
		protected $root;
		protected $days;
		protected $resolved = array();
		
		public function __construct($root = 'DT-server'){
			if (file_exists('resolved'))
				$this->resolved = unserialize(file_get_contents('resolved'));
			else
				$this->resolved = array();

			$this->root = $root. '/';
		}
		
		public function purge()
		{
			shell_exec(
				sprintf('find %s/* -mtime +%s -exec rm {} \\',
				$this->dir, $this->days +1
			));
		}
		
		public function check()
		{
			$files = glob($this->dir. '/*.zip');
			$stamp = self::getStamp(intval(date('j') - $this->days));
			$d = $this->days;
			$result = array();
			
			while ($process !== $stamp && $d >= 0)
			{
				$process = self::getStamp($d);
				$date = date('Y-m-d', $process);
				$file = $this->dir. '/'. $date. '.zip';
				
				$obj = new StdClass();
				$obj->success =
					array_search($file, $files) !== false || 
					$this->isResolved($file);
				$obj->date = str_replace(',', '.', $date);
				$obj->domain = self::cleanUp($this->dir);
				
				$result[$file] = $obj;
				$d--;
			}
			
			krsort($result);
			return $result;
		}
		
		public static function getStamp($day)
		{
			$day = intval(date('j') - $day);
			return mktime(
				0,0,0, intval(date('n')),
				$day, intval(date('Y'))
			);
		}
		
		public static function cleanUp($string)
		{
			return array_pop(explode('/', trim($string, '/')));
		}

		public function setDays($days)
		{
			$this->days = $days -1;
		}
		
		public function setDir($dir)
		{
			$this->dir = $dir;
		}
		
		public function resolve($file)
		{
			if ($this->isResolved($file)) return;

			$this->resolved[] = self::cleanUp($file);
			file_put_contents('resolved', serialize($this->resolved));
		}
		
		public function isResolved($file)
		{
			return array_search(self::cleanUp($file), $this->resolved) !== false;
		}

		public function getLog($log)
		{
			$log  = array_map('trim', explode('|', $log));
			$domain = str_replace('..', '', $log[0]);
			$date = str_replace('..', '', $log[1]);

			$file = sprintf('%s/%s-backup.log', $this->root. $domain, $date);

			if (!file_exists($file)){
				$result = array();

				$obj = new StdClass();
				$obj->msg = 'Log file not available.';
				$obj->success = false;
				$obj->time = 0;
				$result[] = $obj;

				$obj = new StdClass();
				$file = str_replace('-backup.log', '.zip', $file);

				$this->isResolved($file) ?
					$obj->msg = 'Notifications for this failed backup are off.' :
					$obj->msg = '<a href="?resolve='. $file. '">Stop notify</a>';

				$obj->success = true;
				$obj->time = 0;
				$result[] = $obj;

				return json_encode($result);
			}

			return file_get_contents($file);
		}

		public function discover()
		{
			return glob($this->root. '*', GLOB_ONLYDIR);
		}
	}
