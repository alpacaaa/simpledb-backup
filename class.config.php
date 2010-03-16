<?php

	class Config {
		
		private static $servers;
		
		public static function addServer($name, $config = array())
		{
			self::$servers[$name] = $config;
		}
		
		public static function get($name, $opt, $default = null)
		{
			if (isset(self::$servers[$name][$opt]))
				return self::$servers[$name][$opt];
			
			return $default;
		}
	}