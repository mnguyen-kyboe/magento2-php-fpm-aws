# Description

Run magento 2 in **AWS Elastic Beanstalk** with autoscaling and tons of features.

AWS Notes:

1. Create VPC: http://docs.aws.amazon.com/elasticbeanstalk/latest/dg/vpc-rds.html#vpc-rds-create-env to allow multi varnish communication.
2. Launch a Elastic Beanstalk application within the above VPC.


This image is built from the official [`php`](https://hub.docker.com/_/php/) repository and contains PHP configurations for Magento 2.

The image is based on code from [Mageinferno Magento 2 repo](https://github.com/mageinferno/docker-magento2-php). But is highly customized for production usage in Amazon Beanstalk.

# What's in this image?

- php-fpm
- php 7
- mysql-client
- aws php-7 elasticache client 
- deployment routine ( on docker container start )
- multi varnish cache-aware ec2's. If there are 10 servers, 10 varnish cache hosts is cleared. 


This image is configured to run with the following amazon services:

- Elastic Beanstalk
- Elasticache
- RDS
- S3 (for media files)


It's supposed to be used with Amazon Beanstalk Docker Environments.

# How to use this image?


## Create a structure


```
.
├── Dockerrun.aws.json
├── server_env
│   ├── proxy
│   └── varnish
└── web
    ├── app
    ├── bin
    ├── CHANGELOG.md
    ├── composer.json
    ├── composer.lock
    ├── .gitignore
```


- Dockerrun.aws.json is AWS Beanstalk compose file for docker.
- server_env contains configuration for nginx and varnish
- web contains a magento2 installation. The installation should be done from composer ( see installing magento 2 with composer )


## The Dockerrun.aws.json:

This is sample configuration file, it includes and configures:

- Nginx
- Varnish
- And this docker image.

```
{
  "AWSEBDockerrunVersion": 2,
  "volumes": [
    {
      "name": "php-app",
      "host": {
        "sourcePath": "/var/app/current/web"
      }
    },
    {
      "name": "nginx-proxy-conf",
      "host": {
        "sourcePath": "/var/app/current/server_env/proxy/conf.d"
      }
    },
    {
      "name": "varnish-conf",
      "host": {
        "sourcePath": "/var/app/current/server_env/varnish/default.vcl"
      }
    },
    {
      "name": "varnish-lib",
      "host": {
        "sourcePath": "/var/lib/varnish"
      }
    }

  ],
  "containerDefinitions": [
    {
      "name": "nginx-proxy",
      "image": "nginx",
      "essential": true,
      "memory": 128,
      "links": [
        "php-app"
      ],
      "portMappings": [
        {
          "hostPort": 8080,
          "containerPort": 8080
        }
      ],
      "environment": [
        {
          "name": "NGINX_PORT",
          "value": "8080"
        }
      ],
      "mountPoints": [
        {
          "sourceVolume": "php-app",
          "containerPath": "/src"
        },
        {
          "sourceVolume": "awseb-logs-nginx-proxy",
          "containerPath": "/var/log/nginx"
        },
        {
          "sourceVolume": "nginx-proxy-conf",
          "containerPath": "/etc/nginx/conf.d",
          "readOnly": true
        }
      ]
    },
    {
      "name": "varnish",
      "hostname": "varnish",
      "image": "newsdev/varnish:4.1.0",
      "essential": true,
      "memory": 128,
      "portMappings": [
        {
          "hostPort": 80,
          "containerPort": 80
        }
      ],
      "links": [
        "nginx-proxy",
        "php-app"
      ],
      "mountPoints": [
        {
          "sourceVolume": "varnish-lib",
          "containerPath": "/var/lib/varnish",
          "readOnly": true
        },
        {
          "sourceVolume": "awseb-logs-varnish",
          "containerPath": "/var/log/varnish"
        },
        {
          "sourceVolume": "varnish-conf",
          "containerPath": "/etc/varnish/default.vcl",
          "readOnly": true
        }
      ]
    },
    {
      "name": "php-app",
      "image": "peec/magento2-php-fpm-aws",
      "essential": true,
      "memory": 1024,
      "environment": [
        {
          "name": "AWS_ACCESS_KEY_ID",
          "value": "xxxxx"
        },
        {
          "name": "AWS_SECRET_ACCESS_KEY",
          "value": "yyyyyy"
        },
        {
          "name": "GITHUB_OAUTH_TOKEN",
          "value": "zzzzz"
        },
        {
          "name": "MAGENTO_REP_USERNAME",
          "value": "aaaa"
        },
        {
          "name": "MAGENTO_REP_PASSWORD",
          "value": "bbbbb"
        },
        {
          "name": "SSMTP_ROOT",
          "value": "ccccc@gmail.com"
        },
        {
          "name": "SSMTP_AUTHUSER",
          "value": "ddddd@gmail.com"
        },
        {
          "name": "SSMTP_AUTHPASS",
          "value": "eeeee"
        }
      ],
      "mountPoints": [
        {
          "sourceVolume": "php-app",
          "containerPath": "/src"
        }
      ]
    }
  ]
}
```


## Initialize beanstalk

```
eb init
```


## Create environment

- `--vpc` because we need cross comunication between ec2 instances to clear varnish
- `-i t2.small` because it's the minimum instance with enough memory for composer installs. See pricing in aws pages.

```
eb create my-env --database  -i t2.small
```


## Deploy

```
git add --all && git commit -m "Initial commit"
eb deploy
``` 


## Go to aws console

!THIS IS NEEDED SO ALL VARNISH HOSTS GETS CLEARED WHEN UPDATING CONTENT IN MAGENTO!

Now we need to add extra rule to Security Group that was generated for the environment.

1. Go to VPC > Security Groups
1. Find the security group for the Elastic Beanstalk environment ( not Load balanceder / ELB )
1. Add extra rule: port: 80 , source: itself . So that EC2's can communicate between eachother on port 80.



# Variables

## Required variables

These variables are required when you start the docker image.


- `GITHUB_OAUTH_TOKEN`: (default ``) 
- `MAGENTO_REP_USERNAME`: (default ``) 
- `MAGENTO_REP_PASSWORD`: (default ``) 




## Optional variables


### PHP environment

The following variables may be set to control the PHP environment:

- `PHP_MEMORY_LIMIT`: (default `2048M`) Set the memory_limit of php.ini
- `PHP_PORT`: (default: `9000`) Set a custom PHP port
- `PHP_PM`: (default `dynamic`) Set the process manager
- `PHP_PM_MAX_CHILDREN`: (default: `10`) Set the max number of children processes
- `PHP_PM_START_SERVERS`: (default: `4`) Set the default number of servers to start at runtime
- `PHP_PM_MIN_SPARE_SERVERS`: (default `2`) Set the minumum number of spare servers
- `PHP_PM_MAX_SPARE_SERVERS`: (default: `6`) Set the maximum number of spare servers
- `APP_MAGE_MODE`: (default: `default`) Set the MAGE_MODE
- `PHP_SENDMAIL_PATH`: (default `/usr/sbin/ssmtp -t`) 


### Composer configuration

- `COMPOSER_HOME`: (default `/home/composer`)


### Magento specific variables

- `MAGENTO_BACKEND_FRONTNAME`: (default `admin`)
- `MAGENTO_LANGUAGE`: (default `en_US`)
- `MAGENTO_TIMEZONE`: (default `Europe/Oslo`)
- `MAGENTO_CURRENCY`: (default `NOK`)
- `MAGENTO_ADMIN_FIRSTNAME`: (default `Admin`)
- `MAGENTO_ADMIN_LASTNAME`: (default `Admin`)
- `MAGENTO_ADMIN_EMAIL`: (default `admin@example.com`)
- `MAGENTO_ADMIN_USERNAME`: (default `admin`)
- `MAGENTO_ADMIN_PASSWORD`: (default `Admin321123`)
- `MAGENTO_USE_REWRITES`: (default `1`)

- `MEDIA_S3_WEBSITE_URL`: (default ``) Url to media storage (s3). 
- `MEDIA_S3_SECURE_URL`: (default ``) Url to media storage (s3 secure). 



### Elasticache for backend cache

The connection to your elasticache. If not added filesystem will be used.

- `ELASTICACHE_CONNECTION`: (default ``)


### S3 for media

Note: these variables must be configured manually. AWS Beanstalk does not do this for you like with RDS..

We use the [S3 extension](https://github.com/arkadedigital/magento2-s3) to save media files. these variables should be configured after you add a S3 resource manually.

- `MEDIA_S3_ACCESS_KEY`: (default ``) Falls back to AWS_ACCESS_KEY_ID
- `MEDIA_S3_SECRET_KEY`: (default ``) Falls back to AWS_SECRET_ACCESS_KEY
- `MEDIA_S3_BUCKET`: (default ``)
- `MEDIA_S3_REGION`: (default `eu-west-1`)

### RDS

**Note: these variables are automatically added when you add RDS to the beanstalk environment. So you don't need to configure these.**

- `RDS_HOSTNAME`: (default ``) 
- `RDS_DB_NAME`: (default `ebdb`) 
- `RDS_USERNAME`: (default ``) 
- `RDS_PASSWORD`: (default ``) 


### SSMTP

SSMTP is used for sending email, configure SSMTP to work with any mail provider. By default its configured to use Gmail.


- `SSMTP_ROOT`: (default `google@gmail.com`) 
- `SSMTP_MAILHUB`: (default `smtp.gmail.com:587`) 
- `SSMTP_HOSTNAME`: (default `smtp.gmail.com:587`) 
- `SSMTP_USE_STARTTLS`: (default `YES`) 
- `SSMTP_AUTHUSER`: (default `google@gmail.com`) 
- `SSMTP_AUTHPASS`: (default `mysecretpass`) 
- `SSMTP_AUTHMETHOD`: (default `LOGIN`) 
- `SSMTP_FROM_LINE_OVERRIDE`: (default `YES`) 


### Without composer

- `AVOID_COMPOSER`: (default ``) Set to "1" to avoid composer install step. Useful if you commit vendor directory for faster instance boot and not have to worry about repositories being available.



# Advanced Configuration


## Composer home as a volume

If you don't map composer home to a volume, all vendors are downloaded for every deploy: meaning much more time to deploy.

This can be solved by adding composer home as a volume, however then you also need to create `auth.json` manually!

### Create composer home
 
```
mkdir -p /home/composer
touch /home/composer/auth.json
```

/home/composer/auth.json ( replace with your valid tokens ):

```
{
  "github-oauth": {
    "github.com": "GITHUB_OAUTH_TOKEN"
  },
  "http-basic": {
    "repo.magento.com": {
      "username": "MAGENTO_REP_USERNAME",
      "password": "MAGENTO_REP_PASSWORD"
    }
  }
}
```


### Dockerrun.aws.json

```
{
  "AWSEBDockerrunVersion": 2,
  "volumes": [
    .....
    {
      "name": "composer-home",
      "host": {
        "sourcePath": "/home/composer"
      }
    }

  ],
  "containerDefinitions": [
    ....
    {
      "name": "php-app",
      ....
      "mountPoints": [
        {
          "sourceVolume": "composer-home",
          "containerPath": "/home/composer"
        }
      ]
    }
  ]
}
```


# One-off containers

This image can run one-off PHP commands, such as:

`docker run --rm --name php-test peec/docker-magento2-aws echo "Hello world"`

By default, you should place application code in `/src`, or attach a volume at that location. When doing so, you can then run Magento-specific commands such as the Magento CLI tool:

`docker run --rm --name mysite -v /Users/username/Sites/mysite/src:/src peec/docker-magento2-aws ./bin/magento`

