<?php

function code(){
        $code = 'EMP' . rand(101,999);
        return $code;
}

function verification_code(){
        $verification_code = rand(1001,9999);
        return $verification_code;
}

function send_verification_email($verify_code, $lastUserID, $email){
        $message = "Your Activation Code is ".$verify_code."";
        $to=$email;
        $subject="Activation Code For Talkerscode.com";
        $from = 'test.mail.to';
        $body='Your Activation Code is '.$verify_code.' Please Click On This link <a href="">Verify.php?id='.$lastUserID.'&code='.$verify_code.'</a>to activate your account.';
        $headers = "From:".$from;
        mail($to,$subject,$body,$headers);

}
