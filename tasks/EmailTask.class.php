<?php
/**
 * 任务文件
 */
class EmailTask implatement Task
{
	public $member='email';
	public function invoke(){
		$this->send_email();
	}

	public function send_email()
	{
		echo "send email \r\n";
	}
}













?>