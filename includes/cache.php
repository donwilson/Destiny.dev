<?php
	global $cache, $memcache_instance;
	
	$cache = array();
	$memcache_instance = false;
	
	if(defined('CACHE_MEMCACHE_IP') && defined('CACHE_MEMCACHE_PORT')) {
		if(class_exists("Memcache")) {
			try {
				$memcache_instance = new Memcache();
				
				$memcache_instance->addServer(CACHE_MEMCACHE_IP, CACHE_MEMCACHE_PORT);
			} catch(Exception $e) {
				$memcache_instance = false;
			}
		} elseif(class_exists("Memcached")) {
			try {
				$memcache_instance = new Memcached();
				
				$memcache_instance->addServer(CACHE_MEMCACHE_IP, CACHE_MEMCACHE_PORT);
			} catch(Exception $e) {
				$memcache_instance = false;
			}
		}
	}
	
	/**
	 * Determine if page has cache abilities turned on
	 * @return bool
	 */
	function can_cache() {
		return (!defined('CACHE_CAN_CACHE') || CACHE_CAN_CACHE);
	}
	
	/**
	 * Hashify a cache key if it's not already formatted properly
	 * @param string $hash Cache key
	 * @return string
	 */
	function cache_hashify($hash) {
		if((strlen($hash) <> 32) || !preg_match("#^[a-f0-9]{32}$#si", $hash)) {
			$hash = md5(strtolower($hash));
		}
		
		return $hash;
	}
	
	/**
	 * Set cache value by processing $callback, returns processed value of callback
	 * @param string $hash Cache key
	 * @param mixed|callable $callback Value/function to process and save
	 * @param int $ttl Seconds until expiry
	 * @return mixed
	 */
	function cache_set($hash, $callback, $ttl=1800) {
		global $cache, $memcache_instance;
		
		$hash = cache_hashify($hash);
		
		if(is_callable($callback)) {
			$callback_value = call_user_func($callback);
		} else {
			$callback_value = $callback;
		}
		
		if(can_cache()) {
			$cache[ $hash ] = $callback_value;
			
			if(false !== $memcache_instance) {
				if(class_exists("Memcache") && ($memcache_instance instanceof Memcache)) {
					$memcache_instance->set((defined('CACHE_HASH_PREFIX')?CACHE_HASH_PREFIX:"") . $hash, $callback_value, 0, $ttl);
				} elseif(class_exists("Memcached") && ($memcache_instance instanceof Memcached)) {
					$memcache_instance->set((defined('CACHE_HASH_PREFIX')?CACHE_HASH_PREFIX:"") . $hash, $callback_value, $ttl);
				}
			} elseif(function_exists("apc_store")) {
				apc_store((defined('CACHE_HASH_PREFIX')?CACHE_HASH_PREFIX:"") . $hash, $callback_value, $ttl);
			}
		}
		
		return $callback_value;
	}
	
	/**
	 * Pull cached value by key, process callback and save cache using ttl if not found
	 * @param string $hash Cache key
	 * @param mixed $callback Optional. Value/function to process and save if not found, unless null then cache isn't saved. Defaults to null
	 * @param int $ttl Optional. Seconds until expiry. Defaults to 1 hour
	 * @param bool $force Optional. Ignore cache and force processing of callback. Defaults to false
	 * @return mixed
	 */
	function cache_get($hash, $callback=null, $ttl=3600, $force=false) {
		global $cache, $memcache_instance;
		
		$hash = cache_hashify($hash);
		
		if(empty($force) && can_cache()) {
			if(isset($cache[ $hash ])) {
				// running script memory cache found, use that
				return $cache[ $hash ];
			}
			
			$cache[ $hash ] = false;
			
			if(false !== $memcache_instance) {
				$cache[ $hash ] = $memcache_instance->get((defined('CACHE_HASH_PREFIX')?CACHE_HASH_PREFIX:"") . $hash);
				
				if(false !== $cache[ $hash ]) {
					return $cache[ $hash ];
				}
			} elseif(function_exists("apc_fetch")) {
				$cache[ $hash ] = apc_fetch((defined('CACHE_HASH_PREFIX')?CACHE_HASH_PREFIX:"") . $hash, $return_status);
				
				if(false !== $return_status) {
					return $cache[ $hash ];
				}
			}
		}
		
		if(null !== $callback) {
			// cache_set does the processing for $callback, no need to use can_cache here
			return cache_set($hash, $callback, $ttl);
		}
		
		return $callback;
	}
	
	/**
	 * Delete a cached value by a cache key
	 * @param string $hash Cache key
	 * @return void
	 */
	function cache_delete($hash) {
		global $cache, $memcache_instance;
		
		$hash = cache_hashify($hash);
		
		if(isset($cache[ $hash ])) {
			unset($cache[ $hash ]);
		}
		
		if(false !== $memcache_instance) {
			$memcache_instance->delete((defined('CACHE_HASH_PREFIX')?CACHE_HASH_PREFIX:"") . $hash);
		} elseif(function_exists("apc_delete")) {
			apc_delete((defined('CACHE_HASH_PREFIX')?CACHE_HASH_PREFIX:"") . $hash);
		}
	}
	
	/**
	 * Check if cache exists for a key
	 * @param string $hash Cache key
	 * @param mixed $default Return value if cache doesn't exist
	 * @return bool|mixed
	 */
	function cache_exists($hash, $default=false) {
		global $memcache_instance;
		
		$hash = cache_hashify($hash);
		
		if(false !== $memcache_instance) {
			if(false !== $memcache_instance->get((defined('CACHE_HASH_PREFIX')?CACHE_HASH_PREFIX:"") . $hash)) {
				return true;
			}
		} elseif(function_exists("apc_exists")) {
			if(apc_exists((defined('CACHE_HASH_PREFIX')?CACHE_HASH_PREFIX:"") . $hash)) {
				return true;
			}
		}
		
		return $default;
	}
	
	/**
	 * Increment a cached item by $increase_by, or set cache to ($starting_value+$increase_by) when cache doesn't exist
	 * @param string $hash Cache key
	 * @param int $starting_value Optional. Starting value if cache doesn't already exist. Defaults to 0
	 * @param int $increase_by Optional. Increment value that's added to the base cache value. Defaults to 1
	 * @param int $ttl Optional. Time to live in seconds. Defaults to 30 minutes.
	 * @return type
	 */
	function cache_increment($hash, $starting_value=0, $increase_by=1, $ttl=1800) {
		global $memcache_instance;
		
		if(!can_cache()) {
			return ($starting_value + $increase_by);
		}
		
		$hash = cache_hashify($hash);
		
		if(class_exists("Memcache") && ($memcache_instance instanceof Memcache)) {
			$memcache_instance->add($hash, $starting_value, false, $ttl);   // Memcache->add() only sets value if cache doesn't exist
			if(false !== ($cache_value = $memcache_instance->increment($hash, $increase_by))) {
				return $cache_value;
			}
		} elseif(class_exists("Memcached") && ($memcache_instance instanceof Memcached)) {
			if(false !== ($cache_value = $memcache_instance->increment($hash, $increase_by, ($starting_value + $increase_by), $ttl))) {
				return $cache_value;
			}
		}
		
		return ($starting_value + $increase_by);
	}