<?php
// ── Smart Asset Finder — Environment Configuration ──────────────────────────
// Copy .env.example → .env and fill in your values, OR set these as
// environment variables on your server (recommended for production).
// This file is safe to commit; actual secrets go in .env (which is gitignored).

function saf_env(string $key, $default = null) {
    $val = getenv($key);
    if($val !== false) return $val;
    // Load .env file if present (simple parser, no library needed)
    static $env_loaded = false;
    if(!$env_loaded){
        $env_file = __DIR__.'/.env';
        if(is_file($env_file)){
            foreach(file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line){
                if(strpos(trim($line),'#') === 0) continue;
                if(!str_contains($line,'=')) continue;
                [$k, $v] = explode('=', $line, 2);
                putenv(trim($k).'='.trim($v, " \t\n\r\0\x0B\"'"));
            }
        }
        $env_loaded = true;
    }
    $val = getenv($key);
    return $val !== false ? $val : $default;
}

if(!defined('base_url'))    define('base_url',    saf_env('APP_URL', 'http://localhost/Smart-Asset-Finder/'));
if(!defined('base_app'))    define('base_app',    str_replace('\\', '/', __DIR__).'/');
if(!defined('DB_SERVER'))   define('DB_SERVER',   saf_env('DB_HOST',     'localhost'));
if(!defined('DB_USERNAME')) define('DB_USERNAME', saf_env('DB_USERNAME', 'root'));
if(!defined('DB_PASSWORD')) define('DB_PASSWORD', saf_env('DB_PASSWORD', ''));
if(!defined('DB_NAME'))     define('DB_NAME',     saf_env('DB_NAME',     'lfis_db'));
if(!defined('APP_ENV'))          define('APP_ENV',          saf_env('APP_ENV',     'development'));
if(!defined('PAYSTACK_PUBLIC'))  define('PAYSTACK_PUBLIC',  saf_env('PAYSTACK_PUBLIC_KEY', ''));
if(!defined('PAYSTACK_SECRET'))  define('PAYSTACK_SECRET',  saf_env('PAYSTACK_SECRET_KEY', ''));

// Show errors in development, hide in production
if(APP_ENV === 'production'){
    ini_set('display_errors', 0);
    error_reporting(0);
} else {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}
?>
