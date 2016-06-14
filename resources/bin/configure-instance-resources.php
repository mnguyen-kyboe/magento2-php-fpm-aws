<?php
// Configures the generated resources with environment variables
// Such as:
// - S3 for media
// - Elasticache


// For this to work you need to modify the beanstalk role in AWS IAM
// Add this ( IAM -> Roles -> aws-elasticbeanstalk-ec2-role -> Inline Policies -> Create Role Policy -> Custom Policy ) and here is what you add:
/*
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Action": [
        "ec2:Describe*",
        "cloudformation:Describe*"
      ],
      "Effect": "Allow",
      "Resource": "*"
    }
  ]
}
*/


$Outputs = array(
    'ElastiCachePort' => null,
    'ElastiCacheAddress' => null,


    'MediaWebsiteURL' => null,
    'MediaSecureURL' => null,
    'MediaDomainName' => null,
    'MediaBucketName' => null
);

if (!getenv('AWS_ACCESS_KEY_ID') || !getenv('AWS_SECRET_ACCESS_KEY')) {
    echo "Skipping automatic configuration of resources.";
    return $Outputs;
}


// curl http://169.254.169.254/latest/meta-data/instance-id
// aws ec2 describe-instances --instance-ids i-b40a053c --region eu-west-1

$ec2InstanceId = file_get_contents('http://169.254.169.254/latest/meta-data/instance-id');
$ec2Region = file_get_contents('http://169.254.169.254/latest/meta-data/region');
// Allow to fail...
if ($ec2InstanceId) {
    echo "Describing instances for $ec2InstanceId\n";
    // We need to find the stack name  of the beanstalk environment. This is located on the ec2 info.
    $shell = shell_exec("aws ec2 describe-instances --instance-ids $ec2InstanceId --region $ec2Region");
    $json = json_decode($shell, true);

    if ($json) {
        foreach ($json['Reservations'][0]['Instances'][0]['Tags'] as $tag) {
            // Stack name found.
            if ($tag['Key'] === 'aws:cloudformation:stack-name') {
                $stackName = $tag['Value'];

                // Then we list all the outputs of the stack ( we describe this in resources ).

                echo "Describing stack $stackName\n";
                $shell = shell_exec("aws cloudformation describe-stacks --region=$ec2Region --stack-name $stackName");
                $json = json_decode($shell, true);
                if ($json) {
                    $stack = $json['Stacks'][0];
                    // Keys like:
                    // ElastiCachePort
                    // ElastiCacheAddress
                    // MediaWebsiteURL
                    foreach ($stack['Outputs'] as $output) {
                        $Outputs[$output['OutputKey']] = $output['OutputValue'];

                        if ($output['OutputKey'] == 'MediaDomainName') {
                            $exp = explode('.', $output['OutputValue']);
                            $Outputs['MediaBucketName'] = $exp[0];
                        }
                    }
                }
            }
        }
    }


    // now we can use configuration

}


return $Outputs;

