<?php
interface IException
{
	public function log($message);
	public function debug($message);
	public function error($message);
	public function warning($message);
}
?>
