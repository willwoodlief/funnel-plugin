<?php
//need to rename this to private_envs.php and supply valid info
putenv("APP_ENV_VERSION=May-25-2018:10:05");
//this file is not included in version control and should be added manually to new install

//aws account willwoodlief_dev on tom@feedlead.com account
putenv("AWS_ACCESS_KEY_ID=");
putenv("AWS_SECRET_ACCESS_KEY=");
putenv("AWS_REGION=us-east-1");

//sns arn for alert messages
putenv("SNS_ALERT_ARN=");
putenv("EMERGANCY_EMAILS=");