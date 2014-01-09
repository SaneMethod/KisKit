<?php
/**
 * Copyright (c) 2013 Crowd Entertainment Group, All Rights Reserved.
 *
 * Email Confirmation view.
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Crowd Effect - Account Confirmation</title>

    <link href="<?php echo BASE_URL.APP_DIR; ?>static/lib/bootstrap/css/bootstrap.min.css" rel="stylesheet" />
    <link href="<?php echo BASE_URL.APP_DIR; ?>static/css/common.css" rel="stylesheet" />
    <link href="<?php echo BASE_URL.APP_DIR; ?>static/lib/bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet" />
</head>
<body>
    <div id="wrap">
        <div class="container-fluid">
            <div class="row-fluid">
                <div class="span8 offset2 hero-unit" style="margin-top:100px; margin-bottom:-100px;">
                    <?php if ($confirmed === TRUE){
                    ?>
                        <h1>Account Activated!</h1>
                        <p>Welcome to The Crowd Effect, <?php echo $member['first_name']; ?>! We're glad to have you.</p>
                        <p>Your account is now active - <a href="<?php echo BASE_URL; ?>">head home</a> to sign in.</p>
                    <?php }else{
                    ?>
                        <h1>Confirmation Failed</h1>
                        <p>Sorry, it looks like the confirmation code you entered is incorrect.</p>
                        <p>Please refer back to the email sent to the account you signed up with, and follow the link
                        inside to confirm your account.</p>
                    <?php }
                    ?>
                </div>
            </div>
        </div>
    </div>