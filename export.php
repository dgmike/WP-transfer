<?php

// Importing...

// Local to your wp-config.php
include '../code/blog/wp-config.php';

$dba = 'mysql:host='.DB_HOST.';dbname='.DB_NAME;
$con = new PDO($dba, DB_USER, DB_PASSWORD);

if (!$con) {
	die('Nao consegui conectar-me');
}

$tables = $con->query('SHOW TABLES', PDO::FETCH_NUM);

if (!$tables) {
	die('Nao consegui recuperar as tabelas');
}

ob_start();
echo '-- DUMP DO BANCO EM: '.date('r');
foreach($tables as $table):

$table = $table[0];
$query = 'SHOW CREATE TABLE '.$table;
$query = $con->query($query, PDO::FETCH_NUM);
$query = $query->fetch();
if (!$query) {
	die('Nao conseguir recuperar o create table da tabela '.$table);
}
list(, $query) = $query;
$query = str_replace('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS', $query);
$explain = <<<EOF

-- ----------------------------------
-- Table: $table
-- ----------------------------------

EOF;
$query = PHP_EOL.PHP_EOL.$explain.PHP_EOL.'DROP TABLE IF EXISTS `'.$table.'`;'.PHP_EOL.$query.';'.PHP_EOL;
print $query;
$dados = $con->query('SELECT * FROM '.$table, PDO::FETCH_NAMED);
foreach($dados as $data) {
	foreach ($data as $k=>$v) {
		$data[$k] = '\''.str_replace("'", '\\\'', $v).'\'';
	}
	$export = sprintf(PHP_EOL.'INSERT INTO `%s` (%s) VALUES (%s)', $table, implode(', ', array_keys($data)), implode(', ', $data)).';';
	print $export;
}


endforeach; // $tables

$dump = ob_get_contents();

ob_end_clean();

file_put_contents(dirname(__FILE__).DIRECTORY_SEPARATOR.'dump.sql', $dump);
