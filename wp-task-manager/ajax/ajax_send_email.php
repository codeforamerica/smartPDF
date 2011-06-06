
<?php

$email = filter_input(INPUT_GET, 'emailList', FILTER_SANITIZE_STRING);
$transactionid = filter_input(INPUT_GET, 'transactionid', FILTER_SANITIZE_STRING);
$formName = filter_input(INPUT_GET, 'formName', FILTER_SANITIZE_STRING);

$subject = "$formName - $transactionid";

$BODY_TEXT = "send email feature";

$cmd = "echo '$BODY_TEXT' | mutt -s '$subject' '$email'";
$result = shell_exec( $cmd );
print $result;
?>



