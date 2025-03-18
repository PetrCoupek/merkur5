<?php
/** lib_mail.php  - merkur5 mail capability
 * 
 * @author Petr Čoupek
 * @package merkur5
 * @version 1.0
 * @date 16.08.2023
 */

/** The function sends an-email notification to one or more target addresses
 * @param string $muser - mail user name - responsible user 'scott'
 * @param string $from  - from e-mail address
 * @param string $mto   - to e-mail address
 * @param string $subject - e-mail subject
 * @param string $text - e-mail text
 * @param string $mailserver server adress ('mail.example.com')
 * @return boolean true on success, false on fail */

function sendmail($muser,$from,$mto,$subj,$text,$mailserver){
  $lib="vendor/mail/class.phpmailer.php";
  if (!file_exists($lib)) {
    //deb(__DIR__.'/'.$lib);
    return false;
  } 
  
  require_once $lib;
  
  $file='';
  $mail = new PHPMailer();
  $mail->IsSMTP();
  $mail->Host = $mailserver;  // specify main and backup server
  $mail->SMTPAuth = true;     // turn on SMTP authentication
  $mail->IsHTML(true); //nastaveni ze mail  je html tex
  $mail->WordWrap=20;  //zalomeni
  $mail->CharSet="UTF-8"; //charset
  $mail->From = $from;
  $mail->FromName = $from;
  //$mail->AddAddress($mto,"");
  foreach (explode(',',$mto) as $adresa){
    $mail->AddAddress($adresa);
  }  
  $mail->WordWrap = 50;
  if($file!=''){
    $mail->AddAttachment("".$file,"");  /* optional name */
  }   
  $mail->Subject = $subj;  /* add subject */
  $mail->Body    = str_replace("\n",'<br>',$text);
  if(!$mail->Send()){
    return false;     
  }else{
    return true;
  }
}  
?>