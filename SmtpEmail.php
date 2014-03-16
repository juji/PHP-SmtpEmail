<?php
	
	class Smtp{
		private $host = '';
		private $port = '';
		private $auth = '';
		private $username = '';
		private $password = '';
		private $sock;
		private $lastResp = '';
		
		private $debugMssg = '';
		
		public function debug($return = false){
			if($return) return implode('<br />',$this->debugMssg) . '<br />';
			
			
			print '<pre>';
			print implode('<br />',$this->debugMssg) . '<br />';
			print '</pre>';
		}
		
		private function debMssg($str){
			$this->debugMssg[] = htmlentities($str);
		}
		
		public function __construct($host,$port,$user,$pass,$auth){
			$this->host = $host;
			$this->port = $port;
			$this->user = $user;
			$this->pass = $pass;
			$this->auth = $auth;
			$this->debugMssg = array();
		}
		
		public function connect(){
			if($this->auth == 'ssl') $this->host = 'ssl://' . $this->host;
			if($this->auth == 'tsl') $this->host = 'tsl://' . $this->host;
			$this->sock = fsockopen($this->host,$this->port,$errno,$errstr,60);
			
			$this->debMssg('Connect');
			$this->debMssg('	Host: '.$this->host);
			$this->debMssg('	Port: '.$this->port);
			$this->debMssg('');
			
			if(
				$this->status() == '220' && 
				$this->talk('HELO JUJIYANGASLI') == '250' && 
				$this->secure() && 
				$this->talk('HELO JUJIYANGASLI') == '250'
			) return true;
			return false;
		}
		
		public function talk($str){
			fputs($this->sock,$str."\r\n");
			$this->debMssg('<-- '.$str);
			return $this->status();
		}
		
		public function send($str){
			fputs($this->sock,$str."\r\n.\r\n");
			$this->debMssg('<-- '.$str);
			return $this->status();
		}
		
		public function close(){
			$this->talk('QUIT');
			fclose($this->sock);
		}
		
		public function secure(){
			if(
				($this->auth=='tls' && 
				$this->talk('STARTTLS') == '220' && 
				stream_socket_enable_crypto($this->sock, true,STREAM_CRYPTO_METHOD_TLS_CLIENT)) || 
				$this->auth!='tls'
			) return true;
			
			return false;
		}
		
		public function auth(){
			if(preg_match('/LOGIN/im',$this->lastResp)){
				if(
					$this->talk('AUTH LOGIN') == '334' &&
					$this->talk(base64_encode($this->user)) == '334' &&
					$this->talk(base64_encode($this->pass)) == '235'
				) return true;
			} else if($this->talk('AUTH PLAIN '.base64_encode("\0".$this->user."\0".$this->pass)) == '235') {
				return true;
			}
			return false;
		}
		
		private function response() {
			$this->lastResp = "";
			while($str = fgets($this->sock,4096)) {$this->lastResp .= $str;if(substr($str,3,1) == " ") { break; }}
			$this->debMssg('--> '.$this->lastResp);
			return $this->lastResp;
		}
		
		private function status() {
			return substr($this->response(),0,3);
		}
		
	}
	
	class Email{
		
		protected $from='';
		protected $to='';
		protected $cc='';
		protected $bcc='';
		protected $html='';
		protected $text='';
		protected $attachments='';
		protected $type='';
		protected $headers='';
		protected $subject='';
		
		public function __construct(){$this->reset();}
		public function setFrom($str){$this->from = $str;}
		public function addTo($str){$this->to[] = $str;}
		public function addCc($str){$this->cc[] = $str;}
		public function addBcc($str){$this->bcc[] = $str;}
		public function addHeader($str){$this->headers[] = $str;}
		public function setSubject($str){$this->subject = $str;}
		public function setHtml($str){$this->html = $str;}
		public function setText($str){$this->text = $str;}
		public function addAttachment($filepath,$mimetype){
			$this->attachments[] = array(
				'content'=> file_get_contents($filepath),
				'mime'=> $mimetype.';name='.basename($filepath),
				'encode'=>'base64'
			);
		}
		
		public function generate(){
			
			$attach = sizeof($this->attachments);
			
			$msg  = "MIME-Version: 1.0\r\n";
			$msg .= "Date: ".date('r') . "\r\n";
			$msg .= "From: ".$this->from . "\r\n";
			$msg .= "Reply-To: ".$this->from . "\r\n";
			$msg .= "To: ".implode(', ',$this->to) . "\r\n";
			if(sizeof($this->cc)) $msg .= "CC: ".implode(', ',$this->cc) . "\r\n";
			if(sizeof($this->bcc)) $msg .= "BCC: ".implode(', ',$this->bcc) . "\r\n";
			$msg .= "Subject: " . $this->subject . "\r\n";
			
			$body = '';
			
			if($this->text && $this->html) {
				$body = $this->multipart(
					array(
						array('content'=>$this->text,'mime'=>'text/plain; charset=UTF-8','encode'=>'7bit'),
						array('content'=>$this->html,'mime'=>'text/html; charset=UTF-8','encode'=>'7bit')
					), 'multipart/alternative'
				);
			}else if($this->text){
				
				$body .= "Content-Type: text/plain; charset=UTF-8\r\n";
				$body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
				$body .= $this->text."\r\n";
				
			}else if($this->html){
				
				$body .= "Content-Type: text/html; charset=UTF-8\r\n";
				$body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
				$body .= $this->html."\r\n";
				
			}
			
			if($attach){
				
				$arrr = array_merge( array(array('content'=>$body)), $this->attachments );
				if($body) $body = $this->multipart($arrr,'multipart/mixed');
				
			};
			
			return $msg . $body;
			
		}
		
		private function multipart($contentArr,$type){
			$bound = md5(rand(3452,9879876987686));
			$m = "Content-Type: $type; boundary=$bound\r\n\r\n--$bound";
			foreach($contentArr as $k=>$v){
				if(isset($v['mime'])) $m .= "\r\nContent-Type: ${v['mime']}\r\n";
				if(isset($v['encode'])) $m .= "Content-Transfer-Encoding: ${v['encode']}\r\n";
				if(isset($v['encode']) && $v['encode']=='base64') $v['content'] = base64_encode($v['content']);
				$m .= "\r\n${v['content']}\r\n";
				$m .= "--$bound";
			}
			return $m."--\r\n";
		}
		
		public function clean($str){
			if(property_exists('Email',$str)){
				if(is_array($this->$str)) $this->$str = array();
				else $this->$str = '';
			}
		}
		
		
		public function reset(){
			$this->from='';
			$this->to=array();
			$this->cc=array();
			$this->bcc=array();
			$this->subject='';
			$this->html='';
			$this->text='';
			$this->type='';
			$this->headers=array();
			$this->attachments=array();
		}
	}
	
	
	// use this to send email
	// $email = new SmtpEmail(array('host'=>'','port'=>'','user'=>'','password'=>'','auth'=>'[false|ssl|tls]'));
	// $email->setFrom('jujiyangasli@gmail.com');
	// $email->addTo('him@jujiyangasli.com');
	// $email->addCc('Juji Gunadi <jujiyangasli@yahoo.com>');
	// $email->addBcc('Some Dude <dude@mail.com>');
	// $email->setSubject('Example Subject');
	// $email->setHtml('Example <b>Content</b>');
	// $email->setText('Example Content');
	// $email->addAttachment('path/to/file.ext','mime/type');
	// bool $email->send();
	// if($email->failed) doFailedNotice(Array $email->failed);
	
	class SmtpEmail extends Email{
		
		private $smtp;
		public $failures;
		public function __construct($r){$this->setConnection($r);}
		
		public function setConnection($arr){
			$this->smtp = new Smtp($arr['host'],$arr['port'],$arr['user'],$arr['password'],$arr['auth']);
		}
		
		public function debug($bool=false){
			$this->smtp->debug($bool);
		}
		
		private function connect(){
			if(!$this->smtp->connect()) return false;
			if(!$this->smtp->auth()) return false;
			return true;
		}
		
		public function send(){
			
			//initialize
			$this->failures = false;
			$rcptFail = array();
			$from = '<'.preg_replace('/>$/','',preg_replace('/^.*?</','',$this->from)).'>';
			
			
			if(!$this->connect()) return false;
			if($this->smtp->talk('MAIL FROM: '.$from) != '250') return false;
			
			//TO header
			foreach($this->to as $k=>$v){
				$to = '<'.preg_replace('/>$/','',preg_replace('/^.*?</','',$v)).'>';
				$r = $this->smtp->talk('RCPT TO: '.$to);
				if($r != '250' && $r != '251') $rcptFail[] = $v;
			}
			
			//CC header
			foreach($this->cc as $k=>$v){
				$to = '<'.preg_replace('/>$/','',preg_replace('/^.*?</','',$v)).'>';
				$r = $this->smtp->talk('RCPT TO: '.$to);
				if($r != '250' && $r != '251') $rcptFail[] = $v;
			}
			
			//BCC header
			foreach($this->bcc as $k=>$v){
				$to = '<'.preg_replace('/>$/','',preg_replace('/^.*?</','',$v)).'>';
				$r = $this->smtp->talk('RCPT TO: '.$to);
				if($r != '250' && $r != '251') $rcptFail[] = $v;
			}
			
			$this->failures = sizeof($rcptFail) ? $rcptFail : false;
			
			//send email
			if(	$this->smtp->talk('DATA') != '354' ) return false;
			if($this->smtp->send($this->generate()) == '250') return false;
			
			//close
			$this->smtp->close();
			return true;
		}
		
	}
	
?>
