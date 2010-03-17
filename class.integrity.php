<?php

	class Integrity {
		
		protected $dir, $days, $resolved;
		
		public function __construct(){
			if (file_exists('resolved'))
				$this->resolved = unserialize(file_get_contents('resolved'));
			else
				$this->resolved = array();
		}
		
		public function purge()
		{
			shell_exec(
				sprintf('find %s/* -mtime +%s -exec rm {} \\',
				$this->dir, $this->days
			));
		}
		
		public function check()
		{
			$files = glob($this->dir. '/*.tar.gz');
			$stamp = self::getStamp(intval(date('j') - $this->days));
			$d = $this->days;
			$result = array();
			
			while ($process !== $stamp && $d >= 0)
			{
				$process = self::getStamp($d);
				$date = date('Y-m-d', $process);
				$file = $this->dir. '/'. $date. '.tar.gz';
				
				$obj = new StdClass();
				$obj->success =
					array_search($file, $files) !== false || 
					array_search($file, $this->resolved) !== false;
				$obj->date = str_replace(',', '.', $date);
				$obj->domain = array_pop(explode('/', trim($this->dir, '/')));
				
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
		
		public function setDays($days)
		{
			$this->days = $days;
		}
		
		public function setDir($dir)
		{
			$this->dir = $dir;
		}
		
		public function resolve($file)
		{
			$this->resolved[] = $file;
			file_put_contents('resolved', serialize($this->resolved));
		}
		
		public function isResolved($file)
		{
			return array_search($file, $this->resolved) !== false;
		}

		public function getLog($log)
		{
			$log  = array_map('trim', explode('|', $log));
			$domain = str_replace('..', '', $log[0]);
			$date = str_replace('..', '', $log[1]);

			$file = sprintf('%s/%s-backup.log', 'done/'. $domain, $date);

			if (!file_exists($file)){
				$obj = new StdClass();
				$obj->msg = 'Log file not available.';
				$obj->success = false;

				return json_encode(array($obj));
			}

			return file_get_contents($file);
		}
	}
