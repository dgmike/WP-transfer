<?php

// Change this as you need
define('LOCAL_BASE_URL',  'local.url.com.br');
define('SERVER_BASE_URL',  'url.com.br');

// Config file
$config = file('../code/blog/wp-config.php');

// Importing...

error_reporting(E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_ERROR | E_WARNING | E_PARSE | E_USER_ERROR | E_USER_WARNING);

$_config = array();
foreach ($config as $item) {
    if (preg_match('@^define@i', trim($item))) {
        $_config[] = trim($item);
    }
}
$_config = '<?php'.PHP_EOL.implode(PHP_EOL,$_config);
file_put_contents('_config.php', $_config);

require_once('_config.php');

$dba = 'mysql:host='.DB_HOST.';dbname='.DB_NAME;
$con = new PDO($dba, DB_USER, DB_PASSWORD);

if (!$con) {
	die('Nao consegui conectar-me');
}

$lines = explode(PHP_EOL, file_get_contents('./dump.sql'));
foreach ($lines as $k=>$v) {
    if (preg_match('@^--@', $v)) {
        $lines[$k] = '';
    }
}
array_filter($lines);
$lines = implode(PHP_EOL, $lines);
$lines = preg_split('@;[\n\r]@m', $lines);

// array_walk($lines, create_function('&$a', '$a = trim($a);'));

$regexp = '+'.preg_quote(LOCAL_BASE_URL).'+';
foreach ($lines as $item) {
	if (preg_match('@^INSERT INTO `(\w+)`@', ltrim($item), $matches)) {
	    if (preg_match('@VALUES \((.*)\)$@', $item, $matches)) {
	        if (preg_match($regexp, $matches[1])) {
                $original = $new = $matches[1];
	            $matches = preg_split('@\', \'@', $new);
	            foreach ($matches as $i) {
                    if ($data = unserialize($i)) {
                        $new = str_replace( $i, serialize( replace_urls($data) ), $new );
                    } else {
                        $new = str_replace( $i, str_replace(LOCAL_BASE_URL, SERVER_BASE_URL, $i), $new );
                    }
	            }
                $item = str_replace($original, $new, $item);
	        }
	    }
	}
    $con->query($item, PDO::FETCH_NUM);
}

function replace_urls($item)
{
    foreach ($item as $key => $value) {
        if ('array' === gettype($value)) {
            $item[$key] = replace_urls($value);
        } else {
            $item[$key] = str_replace(LOCAL_BASE_URL, SERVER_BASE_URL, $value);
        }
    }
    return $item;
}

$con->query("UPDATE `wp_posts` 
             SET `guid` = REPLACE (
                `guid`, 
                '".LOCAL_BASE_URL."', 
                '".SERVER_BASE_URL."
           ')");
