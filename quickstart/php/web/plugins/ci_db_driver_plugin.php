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

class __pinpoint_ci_db_driver_connect_interceptor extends \Pinpoint\Interceptor
{
    var $apiId = -1;
    public function __construct()
    {
        $this->apiId = pinpoint_add_api('CI_DB_driver::db_connect', -1);
    }

    public function onBefore($callId, $args)
    {
        var_dump($this->getSelf()->dbdriver);
    }

    public function onEnd($callId, $data)
    {
        var_dump($data);
    }

    public function onException($callId, $exceptionStr)
    {

    }
}

class __pinpoint_ci_db_driver_plugin extends \Pinpoint\Plugin
{
    public function __construct()
    {
        parent::__construct();
        $i = new __pinpoint_ci_db_driver_connect_interceptor();
        $this->addInterceptor($i, 'CI_DB_driver::db_connect', basename(__FILE__));
    }
}
