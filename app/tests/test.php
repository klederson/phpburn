<?php

$include_path = '--include-path '.escapeshellcmd(realpath(dirname(__FILE__).'/..'));
$bootstrap = '--bootstrap '.escapeshellcmd(realpath(dirname(__FILE__).'/bootstrap.php'));

$files = (array)glob("./cases/*.php");
foreach ($files as $file) {
	print "Testing $file...\n";
	$cmd = sprintf("phpunit %s %s %s",
			$include_path,
			$bootstrap,
			$file);
	print shell_exec($cmd);
}

?>
