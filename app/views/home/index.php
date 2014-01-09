<?php
/**
 * Copyright (c) Art&Logic 2013, All Rights Reserved.
 * $Id$
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1">
    <title>The Crowd Effect</title>

    <link href="<?php echo BASE_URL.APP_DIR; ?>static/lib/bootstrap/css/bootstrap.min.css" rel="stylesheet" />
    <link href="<?php echo BASE_URL.APP_DIR; ?>static/css/common.css" rel="stylesheet" />
    <link href="<?php echo BASE_URL.APP_DIR; ?>static/lib/bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet" />
    <link href="<?php echo BASE_URL.APP_DIR; ?>static/lib/bselect/bootstrap-select.min.css" rel="stylesheet" />

    <script type="text/javascript" src="<?php echo BASE_URL; ?>app/static/lib/jquery/jquery-1.9.1.min.js"></script>
    <script type="text/javascript" src="<?php echo BASE_URL; ?>app/static/lib/bootstrap/js/bootstrap.min.js"></script>
    <script type="text/javascript" src="<?php echo BASE_URL; ?>app/static/lib/bselect/bootstrap-select.min.js"></script>

    <script type="text/javascript" src="<?php echo BASE_URL; ?>app/static/js/Util.js"></script>
    <script type="text/javascript" src="<?php echo BASE_URL; ?>app/static/js/Ui.js"></script>
    <script type="text/javascript" src="<?php echo BASE_URL; ?>app/static/js/main.js"></script>
</head>
<body>
<div id="wrap">
    <div id="content" class="container-fluid">
        <div class="row-fluid">
            <div class="span10 offset1">
                <h1 class="title text-center">Join. <span class="highlight">Watch.</span> Create.</h1>
                <div id="coverwrap" class="coverwrap section active">
                    <div class="coverflow">
                        <div class="flowme">
                            <img src="<?php echo BASE_URL; ?>app/static/img/covers/japan1.jpg" />
                        </div>
                        <div class="flowme">
                            <img src="<?php echo BASE_URL; ?>app/static/img/covers/landscape1.jpg" />
                        </div>
                        <div class="flowme">
                            <img src="<?php echo BASE_URL; ?>app/static/img/covers/nexus1.jpg" />
                        </div>
                        <div class="flowme">
                            <img src="<?php echo BASE_URL; ?>app/static/img/covers/reflect1.jpg" />
                        </div>
                        <div class="flowme">
                            <img src="<?php echo BASE_URL; ?>app/static/img/covers/water1.jpg" />
                        </div>
                        <div class="flowme">
                            <img src="<?php echo BASE_URL; ?>app/static/img/covers/japan1.jpg" />
                        </div>
                        <div class="flowme">
                            <img src="<?php echo BASE_URL; ?>app/static/img/covers/landscape1.jpg" />
                        </div>
                        <div class="flowme">
                            <img src="<?php echo BASE_URL; ?>app/static/img/covers/nexus1.jpg" />
                        </div>
                        <div class="flowme">
                            <img src="<?php echo BASE_URL; ?>app/static/img/covers/reflect1.jpg" />
                        </div>
                        <div class="flowme">
                            <img src="<?php echo BASE_URL; ?>app/static/img/covers/water1.jpg" />
                        </div>
                        <div class="flowme">
                            <img src="<?php echo BASE_URL; ?>app/static/img/covers/japan1.jpg" />
                        </div>
                        <div class="flowme">
                            <img src="<?php echo BASE_URL; ?>app/static/img/covers/landscape1.jpg" />
                        </div>
                        <div class="flowme">
                            <img src="<?php echo BASE_URL; ?>app/static/img/covers/nexus1.jpg" />
                        </div>
                        <div class="flowme">
                            <img src="<?php echo BASE_URL; ?>app/static/img/covers/reflect1.jpg" />
                        </div>
                        <div class="flowme">
                            <!--<iframe width="500" height="300"
                                    src="http://www.youtube.com/embed/ZWmrfgj0MZI?wmode=transparent"
                                    frameborder="0" allowfullscreen></iframe>-->
                            <img src="<?php echo BASE_URL; ?>app/static/img/covers/water1.jpg" />
                        </div>
                    </div>
                    <div class="coverbar">
                        <div class="slider">
                            <div class="sliderLine"></div>
                        </div>
                    </div>
                </div>
                <div id="actionwrap" class="section active">
                    <select id="selectintent" class="selectpicker intent">
                        <option selected="selected">I Want To...</option>
                    </select>
                    <select id="selectverb" class="selectpicker verb">
                        <option value="0" selected="selected">Accept Invite</option>
                        <option value="1">Sign In</option>
                        <option value="2">View Countdown</option>
                        <option value="3">Go Home</option>
                    </select>
                    <button id="actionGo" class="btn btn-mega btn-info"
                            style="margin-top:-10px; color:#fff;">GO</button>
                </div>
                <div id="signinwrap" class="section">
                    <form id="signingform">
                        <div class="input-prepend">
                            <span class="add-on"><i class="icon-user"></i></span>
                            <input type="email" id="signin-email" placeholder="email" required />
                        </div>
                        <div class="input-prepend">
                            <span class="add-on"><i class="icon-lock"></i></span>
                            <input type="password" id="signin-password" placeholder="password" required
                                   pattern=".{7,}" />
                        </div>
                        <div class="btn-group" style="margin-top:-10px;">
                            <button id="signinLogButton" type="submit" class="btn btn-info">Login</button>
                            <button id="signinRemButton" class="btn btn-info" data-toggle="tooltip"
                                    title="Remember you?">
                                <i class="icon-stop"></i></button>
                        </div>
                        <button id="signinCancelButton" class="btn btn-danger"
                                style="margin-top:-10px;">Cancel</button>
                    </form>
                </div>
                <div id="countdownwrap" class="section">

                </div>
            </div>
            <div id="regwrap" class="span6 offset3 section">
                <form id="regform" class="form-horizontal">
                    <h3>User Account</h3>
                    <div class="well">
                        <div class="input-prepend">
                            <span class="add-on"><i class="icon-envelope"></i></span>
                            <input type="email" id="reg-email" name="email_address" placeholder="email"
                                   required />
                        </div>
                        <div class="input-prepend">
                            <span class="add-on"><i class="icon-lock"></i></span>
                            <input type="password" id="reg-pass" name="password" pattern=".{7,}"
                                   placeholder="password" required />
                        </div>
                        <div class="input-prepend">
                            <span class="add-on"><i class="icon-lock"></i></span>
                            <input type="password" id="reg-pass-confirm"
                                   pattern=".{7,}" placeholder="confirm password" required />
                        </div>
                    </div>
                    <h3>Personal Details</h3>
                    <div class="well">
                        <div class="input-prepend">
                            <span class="add-on"><i class="icon-user"></i></span>
                            <input type="text" pattern="[A-Za-z]+" id="reg-firstname"
                                   name="first_name" placeholder="First name" />
                        </div>
                        <div class="input-prepend">
                            <span class="add-on"><i class="icon-user"></i></span>
                            <input type="text" pattern="[A-Za-z]+" id="reg-lastname"
                                   name="last_name" placeholder="Last name" />
                        </div>
                    </div>
                    <h3>Interests</h3>
                    <div class="well">
                        <select class="selectpicker interests" multiple title="Select Interests"
                                data-selected-text-format="count > 2">
                            <?php
                            foreach($interests as $interest)
                            {
                            ?>
                            <option value="<?php echo $interest['interest_id']; ?>">
                                <?php echo $interest['description']; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>
                    <h3>Invite Code</h3>
                    <div class="well">
                        <div class="input-prepend">
                            <span class="add-on"><i class="icon-star"></i></span>
                            <input type="text" pattern="[A-Za-z0-9!]+" id="reg-invitecode"
                                   name="invite_code" placeholder="Invite Code"
                                   data-invitecode="<?php echo $inviteCode['invite_code']; ?>" />
                        </div>
                        <p><small>Don't have an invite code? Fill out your details to receive one.</small></p>
                    </div>
                    <button type="submit" class="btn btn-mega btn-info" style="color:#fff;">Register</button>
                </form>
            </div>
            <div id="regFinish" class="span6 offset3 section">
                <h3>Registration Complete!</h3>
                <div class="well">
                    <p>Congratulations <span class="first_name"></span>!</p>
                    <p>You're now registered as part of the Crowd! Please be sure to check your e-mail;
                    you'll need to follow the confirmation link in it to activate your account before
                    you can log in.</p>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="progressModal" class="modal hide fade" role="dialog" aria-hidden="true">
    <div class="modal-header">
        <h3>Please Wait...</h3>
    </div>
    <div class="modal-body">
        <div class="progress progress-striped active">
            <div class="bar" style="width:100%"></div>
        </div>
    </div>
    <div class="modal-footer hide fade">
        <button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>
    </div>
</div>
