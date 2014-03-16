php-SmtpEmail
=========

<br />
SMTP Email delivery in PHP


```php

require_once 'SmtpEmail.php';

$email = new SmtpEmail(array(
    'host'=>'smtp.gmail.com',
	'port'=>'587',
	'auth'=>'tls',    // 'tls' | 'ssl' | false
	'user'=>'someone@gmail.com',
	'password'=>'32874956',
));

$email->setFrom('dude@domain.com');
$email->addTo('man@domain.com');
$email->addCc('Some Guy <guy@yahoo.com>');
$email->addBcc('Some Dude <dude@mail.com>');
$email->setSubject('Example Subject');
$email->setHtml('Example <b>Content</b>');    // utf-8
$email->setText('Example Content');    // utf-8
$email->addAttachment('apache.gif','image/gif');

$email->send();
if($email->failed) foreach($email->failed as $v) print 'failed: '. $v.'<br />';

$email->debug();    // print debug message

$email->reset();

```
