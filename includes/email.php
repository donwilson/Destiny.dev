<?php
	function send_out_email($subject, $body, $to=false, $from=false) {
		require_once(LIB_DIR ."PHPMailer/PHPMailerAutoload.php");
		
		if(empty($to)) {
			$to = CONTACT_EMAIL;
		}
		
		if(empty($from)) {
			$from = SEND_EMAIL_FROM;
		}
		
		$body_text = $mail_body;
		$body_text = str_replace(array("<br>", "<br/>", "<br />"), "\n\n", $body_text);
		$body_text = strip_tags($body_text);
		
		try {
			$mail = new PHPMailer;
			
			if(defined('SMTP_HOST') && SMTP_HOST && defined('SMTP_USERNAME') && SMTP_USERNAME && defined('SMTP_PASSWORD') && SMTP_PASSWORD) {
				$mail->isSMTP();
				
				if(defined('SMTP_DEBUG_LEVEL')) {
					// SMTP debugging
					$mail->SMTPDebug = SMTP_DEBUG_LEVEL;
				}
				
				if(defined('SMTP_AUTH')) {
					//Whether to use SMTP authentication
					$mail->SMTPAuth = SMTP_AUTH;
				}
				
				if(defined('SMTP_SECURE') && SMTP_SECURE) {
					$mail->SMTPSecure = SMTP_SECURE;
				}
				
				//$mail->Priority = 1;
				//$mail->CharSet = "UTF-8";
				//$mail->Encoding = "8bit";
				
				// Set the hostname of the mail server
				//$mail->Host = gethostbyname(SMTP_HOST);
				$mail->Host = SMTP_HOST;
				
				if(defined('SMTP_PORT') && SMTP_PORT) {
					//Set the SMTP port number - likely to be 25, 465 or 587
					$mail->Port = SMTP_PORT;
				}
				
				// Username to use for SMTP authentication
				$mail->Username = SMTP_USERNAME;
				
				// Password to use for SMTP authentication
				$mail->Password = SMTP_PASSWORD;
			}
			
			$mail->setFrom($from, SITE_NAME ." - No Reply");
			$mail->addAddress($to);
			$mail->addReplyTo($from);
			
			$mail->isHTML(true);
			
			$mail->Subject = $subject;
			$mail->Body = $body;
			$mail->AltBody = $body_text;
			
			if(!$mail->send()) {
				throw new Exception($mail->ErrorInfo);
			}
		} catch(Exception $e) {
			return array(false, $e->getMessage());
		}
		
		return array(true, "Successfully sent your message");
	}
