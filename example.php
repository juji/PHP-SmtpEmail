<?php
	
	require_once 'SmtpEmail.php';
	
	$email = new SmtpEmail(array(
		'host'=>'smtp.gmail.com',
		'port'=>'587',
		'auth'=>'tls',
		'user'=>'someone@gmail.com',
		'password'=>'thepassword',
	));
	
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

	$email->debug();
	
?>
