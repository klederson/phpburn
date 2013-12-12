#!/usr/bin/env php
<?php
/**
 * Setting-up the PhpBURN information
 */
$basePath = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
$fullStructurePath = $basePath . DIRECTORY_SEPARATOR . 'structure' . DIRECTORY_SEPARATOR;

echo "Wellcome to PhpBURN structure generator \r\n";
echo "This tool will help you to generate the basics structure to create a PhpBURN based system\r\n";
echo "\r\n";
echo "Please type the path to your system\r\n";
echo "(if it does not exists will be created and all files and subfolders will be deleted - BE CAREFUL):\r\n ";
$handle = fopen ("php://stdin","r");
$endPath = trim(fgets($handle));
echo "\r\n";
echo "Creating Files...\r\n";
$creatingFolder = mkdir($endPath,0755,TRUE);
$files = recurse_copy($fullStructurePath,$endPath);

function recurse_copy($src,$dst) { 
    $dir = opendir($src); 
    @mkdir($dst); 
    while(false !== ( $file = readdir($dir)) ) { 
        if (( $file != '.' ) && ( $file != '..' )) { 
            if ( is_dir($src . DS . $file) ) { 
                recurse_copy($src . DS . $file,$dst . DS . $file); 
            } 
            else { 
                copy($src . DS . $file,$dst . DS . $file); 
            } 
        } 
    } 
    closedir($dir); 
} 
?>