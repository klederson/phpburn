<?php
interface IException
{
	public function debug(PhpBURN_Message $msg = null);
	public function error(PhpBURN_Message $msg = null);
	public function warning(PhpBURN_Message $msg = null);
}
?>