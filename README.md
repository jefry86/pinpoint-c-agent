![Pinpoint](images/logo.png)

[![Build Status](https://travis-ci.com/naver/pinpoint-c-agent.svg?branch=dev)](https://travis-ci.com/naver/pinpoint-c-agent)

**Visit [our official web site](http://naver.github.io/pinpoint/) for more information and [Latest updates on Pinpoint](https://naver.github.io/pinpoint/news.html)**  


The current stable version is [v0.1.1](https://github.com/naver/pinpoint-c-agent/releases).

# Pinpoint C Agent

It is an agent written by C/C++ language. And we hope to support other languages by this agent. Until now, it supports PHP language.

## Getting Started

### Requirement

Dependency|Version
---|----
APACHE| 2.2.x 2.4.x
PHP| php 5.4.x 5.5.x 5.6.x 7x
OS| 64bit only
Boost | 1.5.8+
Thirft|0.11.0+
gcc| 4.4.7+
pinpoint| 1.8.0-RC1



### Installation

#### Pre-install
- Download pinpoint-c-agent:  git clone https://github.com/naver/pinpoint-c-agent.git
 

- Install Build-essential

    > Ubuntu 

        sudo apt-get install automake bison flex g++ git libtool make pkg-config openssl libssl-dev 

    > Centos
    
        sudo yum install automake libtool flex bison pkgconfig gcc-c++ openssl-devel

- Install third-library  

    Option 1. Use agent build tools (install boost and Thirft in $PWD/../thirdlibray/var)

        $ cd pinpoint-c-agent/pinpoint_php
        $ ./Build.sh
        $ export LD_LIBRARY_PATH=$PWD/../thirdlibray/var/:$LD_LIBRARY_PATH

    Option 2. Install from source code
    - [Install Boost 1.6.3+](https://www.boost.org/doc/libs/1_63_0/doc/html/bbv2.html#bbv2.installation)
        - wget https://jaist.dl.sourceforge.net/project/boost/boost/1.63.0/boost_1_63_0.tar.gz
        - tar -zxvf boost_1_63_0.tar.gz && cd boost_1_63_0
        - ./bootstrap.sh
        - ./b2 install --prefix=$PREFIX
    
    - [Install Thrift 0.11.0+](http://thrift.apache.org/docs/install/)
        - wget http://apache.fayea.com/thrift/0.11.0/thrift-0.11.0.tar.gz
        - tar zxvf thrift-0.11.0.tar.gz  
        - cd thrift-0.11.0  
        - ./configure CXXFLAGS="-DFORCE_BOOST_SMART_PTR" --with-cpp --with-php=no --with-python=no --with-ruby=no --with-nodejs=no --with-qt4=no --with-java=no -with-boost=$PREFIX
        - make 
        - make install 
     
        - export environment variable

        ```
            export WITH_BOOST_PATH=/boost root path/
            export WITH_THRIFT_PATH=/thrift root path/
            export LD_LIBRARY_PATH=/boost lib/:/thrift/:$LD_LIBRARY_PATH
        ```


     
-  [phpize](http://php.net/manual/en/install.pecl.phpize.php) (In your php installation path)
  
#### Build php-agent

1. Checking phpize and php-config is in your PATH.
    If not, install phpize and export in your system PATH.(eg:export PATH=/path to phpize/:/path to php-config/:$PATH)
2. Run cd pinpoint_php && ./Build.sh  && sudo make install
3. If **_2_** running successfully, agent had installed into php module.

#### Startup 
1. Modifying below options in the "pinpoint_agent.conf" (eg:pinpoint_c_agent/quickstart/config/pinpoint_agent.conf.example)
    ```
          AgentID=uniquely identifies the application instance in which the agent is running on
          ApplicationName= groups a number of identical application instances as a single service
          Collector*= pinpoint collector information  
          LogFileRootPath=/absolute ​path where logging to/
          PluginRootDir​=/absolute path to /web/plugins/​
    ```
    (eg: make sure LogFileRootPath has been created.)
2. Enable pinpoint-agent-php into php.ini, and configuring extension and pinpoint_agent.config_full_name (eg:pinpoint_c_agent/quickstart/config/php.ini.example)
3. Restart php-fpm/Apache
4. After restart php-fpm/Apache, if you meet "xxx pinpoint_api.cpp:158 [INFO] common.AgentID=php_pinpoint ...." in your LogFileRootPath/pinpoint_log.txt, pinpoint-agent-php installed successfully. If not, contract us without hesitation. 

#### Collect result from the Pinpoint 
1. Configure pinpoint_c_agent/quickstart/php/web/ as your web side.
2. Access http://\$serverip:\$port/index.php 
3. Log into pinpoint-web and choose the right ApplicationList 

## Overview

### Distributed Tracking system
![CallStack](images/1.png)

### Call Stack
![CallStack](images/2.png)


## Compatibility

Pinpoint Version | PHP|GCC|Boost| Thrift|
---------------- | ----- | --------- |----|----|
1.8.0-RC1 | 5.3.x <br> 5.4.x <br> 5.5.x <br> 5.6.x <br> 7.x |gcc 4.4.7+|1.5.8+|0.11.0+|

## License
This project is licensed under the Apache License, Version 2.0.
See [LICENSE](LICENSE) for full license text.

```
Copyright 2018 NAVER Corp.

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
```
