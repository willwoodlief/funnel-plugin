<?php
require_once 'private_envs.php';  #need to add this file if pulling from repo, see private_envs.example.php for example
//loads in environmental variables, some of which are used directly for aws

#test to make sure environmental variables are set
if (!getenv('APP_ENV_VERSION')) {
    print "<h1>Need to set private_envs.php</h1>";
    die();
}


#########################
####### CONFIG ##########
#########################


define("SNS_ALERT_ARN",getenv('SNS_ALERT_ARN'));
define("EMERGANCY_EMAILS",getenv('EMERGANCY_EMAILS'));













