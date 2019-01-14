<?php
/**
 * Copyright 2018 NAVER Corp.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

//include_once(dirname(__FILE__) . '/util/pinpoint_laravel_db_driver_util.php');

class __pinpoint_laravel_db_driver_simple_query_interceptor extends \Pinpoint\Interceptor
{
    public $apiId = -1;

    public function __construct()
    {
        $this->apiId = pinpoint_add_api("MySqlConnection::select", -1);
    }

    public function onBefore($callId, $args)
    {
        echo "<before----------------------------<br/>";
        var_dump($args);
        echo "----------------------------------><br/>";
    }

    public function onEnd($callId, $data)
    {
        echo "<end----------------------------<br/>";
        var_dump($data);
        echo "--------------------------------><br/>";
    }

    public function onException($callId, $exceptionStr)
    {

    }
}

class __pinpoint_laravel_db_driver_plugin extends \Pinpoint\Plugin
{
    public function __construct()
    {
        parent::__construct();

        $i = new __pinpoint_laravel_db_driver_simple_query_interceptor(__pinpoint_ci_db_driver_util::DRIVER_PDO);
        $this->addInterceptor($i, 'MySqlConnection::select', basename(__FILE__));

    }
}
