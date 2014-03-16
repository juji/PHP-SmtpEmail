<?php
	
	function debug($str){
		print '<pre>'. htmlentities($str).'</pre>';
	}
	
	function pp($var){
		debug(print_r($var,true));
	}
	
	require_once 'SmtpEmail.php';
	
	$mailgun = array(
		'host'=>'smtp.mailgun.org',
		'port'=>'587',
		'auth'=>'tls',
		'user'=>'someone@domain.com',
		'password'=>'32874956',
	);
	
	$gmail = array(
		'host'=>'smtp.gmail.com',
		'port'=>'587',
		'auth'=>'tls',
		'user'=>'someone@gmail.com',
		'password'=>'asdfiyut',
	);
	
	$email = new SmtpEmail($mailgun);
	//$email = new SmtpEmail($gmail);
	
	$email->setFrom('dude@domain.com');
	$email->addTo('man@domain.com');
	$email->addCc('Some Guy <guy@yahoo.com>');
	$email->addBcc('Some Dude <dude@mail.com>');
	$email->setSubject('Example Subject');
	$email->setHtml('Example <b>Content</b>');
	$email->setText('Example Content');
	$email->addAttachment('apache.gif','image/gif');
	
	//debug($email->generate());
	//die();
	
	$email->send();
	if($email->failed) foreach($email->failed as $v) print $v.'<br />';

	debug(file_get_contents('smtp'));
	
?>
