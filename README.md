php-SmtpEmail
=========

<br />
SMTP Email delivery in PHP


```php

require_once 'SmtpEmail.php';

$email = new SmtpEmail(array(
    'host'=>'smtp.mailgun.org',
	'port'=>'587',
	'auth'=>'tls',
	'user'=>'someone@domain.com',
	'password'=>'32874956',
));

$email->setFrom('dude@domain.com');
$email->addTo('man@domain.com');
$email->addCc('Some Guy <guy@yahoo.com>');
$email->addBcc('Some Dude <dude@mail.com>');
$email->setSubject('Example Subject');
$email->setHtml('Example <b>Content</b>');
$email->setText('Example Content');
$email->addAttachment('apache.gif','image/gif');

$email->send();
if($email->failed) foreach($email->failed as $v) print 'failed: '. $v.'<br />';

$email->reset();

```
