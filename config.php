<?php
	// dev mode
	define('DEV_MODE', false);
	
	// errors
	define('PRINT_PHP_ERRORS', (defined('DEV_MODE') && DEV_MODE));
	define('PRINT_SQL_ERRORS', (defined('DEV_MODE') && DEV_MODE));
	
	// timezone
	define('DATE_TIMEZONE', "US/Central");
	define('DATE_TIMEZONE_SHORT', "CST");
	
	// set timezone
	ini_set('date.timezone', DATE_TIMEZONE);
	date_default_timezone_set(DATE_TIMEZONE);
	
	// site details
	define('SITE_NAME', "DestinyDev");
	define('SITE_BRAND', "DestinyDev");
	
	// template
	define('TEMPLATE_DIR_NAME', "main");
	
	// database
	define('DB_HOST',		"localhost");
	define('DB_USER',		"root");
	define('DB_PASSWORD',	"");
	define('DB_NAME',		"dev_destiny");
	
	// memcached
	if(defined('DEV_MODE') && DEV_MODE) {
		//define('CACHE_CAN_CACHE', !isset($_REQUEST['_force']));
		define('CACHE_CAN_CACHE', false);
	} else {
		define('CACHE_CAN_CACHE', true);
	}
	
	define('CACHE_MEMCACHE_IP', "127.0.0.1");
	define('CACHE_MEMCACHE_PORT', "11211");
	define('CACHE_HASH_PREFIX', "destiny.dev|");
	
	// directory names
	define('CDN_DIR_NAME', "cdn");
	define('STATIC_DIR_NAME', "static");
	define('TMP_DIR_NAME', "tmp");
	define('DATA_DIR_NAME', "data");
	
	// directory paths
	define('ABS_DIR', __DIR__ ."/");
	define('VENDOR_DIR', ABS_DIR ."vendor/");
	define('INCLUDE_DIR', ABS_DIR ."includes/");
	define('CLASS_DIR', INCLUDE_DIR ."classes/");
	define('LIB_DIR', INCLUDE_DIR ."libs/");
	define('TEMPLATE_DIR', ABS_DIR ."templates/");
	define('UPLOAD_DIR', ABS_DIR . CDN_DIR_NAME ."/");
	define('CURRENT_UPLOAD_PATH', date("Y/m"));
	define('CURRENT_UPLOAD_DIR', UPLOAD_DIR . CURRENT_UPLOAD_PATH ."/");
	define('TMP_DIR', ABS_DIR . TMP_DIR_NAME ."/");
	define('DATA_DIR', ABS_DIR . DATA_DIR_NAME ."/");
	define('MANIFEST_DIR', DATA_DIR ."manifest/");
	define('STATIC_DIR', ABS_DIR . STATIC_DIR_NAME ."/");
	define('STATIC_JS_DIR', STATIC_DIR ."/js/");
	define('STATIC_CSS_DIR', STATIC_DIR ."/css/");
	
	// URI addresses
	define('BASE_DOMAIN', getenv("HTTP_HOST"));
	
	define('ABS_URI', "http://". BASE_DOMAIN ."/");
	define('TMP_URI', ABS_URI . TMP_DIR_NAME ."/");
	define('CDN_URI', "http://". BASE_DOMAIN ."/". CDN_DIR_NAME ."/");
	define('UPLOAD_URI', CDN_URI);
	define('STATIC_URI', "http://". BASE_DOMAIN ."/". STATIC_DIR_NAME ."/");
	define('ADMIN_ABS_URI', "http://". BASE_DOMAIN ."/admin/");
	
	// server software paths
	define('FFMPEG_PATH', "/usr/bin/ffmpeg");
	
	// admin
	// more constants found in includes/admin.php
	define('ADMIN_COOKIE_NAME',		"dd_account_area");
	define('ADMIN_COOKIE_EXPIRES',	30);   // expire in 30 days
	define('ADMIN_COOKIE_PATH',		"/");
	define('ADMIN_COOKIE_DOMAIN',	BASE_DOMAIN);
	
	// dates
	define('DATE_FORMAT_CONTENT', "D, M j, Y");   // Mon, Feb 01, 2016 -- php.net/date
	
	// tags allowed in descriptions
	define('DESCRIPTIONS_ALLOWED_HTML', "<a><b><strong><i><em><br><s><u><strike><strikethrough>");
	
	// destiny api settings
	define('DESTINY_API_KEY', "");
	define('DESTINY_API_BASE', "https://www.bungie.net/Platform/Destiny/");
	define('DESTINY_CONTENT_BASE', "https://www.bungie.net/");
	
	// xbox api settings
	define('XBOX_API_KEY', "");
	define('XBOX_API_BASE', "https://xboxapi.com/");
	
	// recaptcha
	define('RECAPTCHA_KEY',		"");
	define('RECAPTCHA_SECRET',	"");
	
	// contact email addresses
	define('CONTACT_EMAIL', "");
	define('SEND_EMAIL_FROM', "");
	
	define('SMTP_HOST', "");
	define('SMTP_PORT', 587);
	define('SMTP_USERNAME', "");
	define('SMTP_PASSWORD', "");
	define('SMTP_AUTH', true);   // boolean
	define('SMTP_SECURE', "tls");   // boolean
	define('SMTP_DEBUG_LEVEL', 0);   // 0=off, 1=client messages, 2=client and server messages
	
	// google cloud
	define('GOOGLE_CLOUD_PROJECT_ID', "");
	define('GOOGLE_CLOUD_SERVICE_ACCOUNT_JSON', "");
	define('GOOGLE_CLOUD_SERVICE_ACCOUNT_ID', "");
	define('GOOGLE_CLOUD_SERVICE_ACCOUNT_KEY', "");
	define('GOOGLE_CLOUD_BUCKET_CONTENT', "");
	define('GOOGLE_CLOUD_BUCKET_THUMBS', "");
	
	//putenv("GOOGLE_APPLICATION_CREDENTIALS=". GOOGLE_CLOUD_SERVICE_ACCOUNT_JSON);
	
	// google analytics
	define('GOOGLE_ANALYTICS_ID', "");
	define('GOOGLE_ANALYTICS_DOMAIN', "auto");
	define('GOOGLE_ANALYTICS_APP_NAME', "DestinyDev Analytics Reporter");
	define('GOOGLE_ANALYTICS_SERVICE_EMAIL', GOOGLE_CLOUD_SERVICE_ACCOUNT_ID);
	define('GOOGLE_ANALYTICS_SERVICE_KEY', GOOGLE_CLOUD_SERVICE_ACCOUNT_JSON);
	
	require_once(VENDOR_DIR ."autoload.php");
	
	// errors
	if(defined('PRINT_PHP_ERRORS') && PRINT_PHP_ERRORS) {
		@include_once(INCLUDE_DIR ."errors.php");
	}
	
	// start session
	session_start();
	
	// include code
	@include_once(INCLUDE_DIR ."kitchen_sink.php");
