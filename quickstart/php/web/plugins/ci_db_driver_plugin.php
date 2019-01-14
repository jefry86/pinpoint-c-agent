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

include_once(dirname(__FILE__) . '/util/pinpoint_ci_db_driver_util.php');

class __pinpoint_ci_db_driver_simple_query_interceptor extends \Pinpoint\Interceptor
{
    public $apiId = -1;

    public $isInit = false;

    public $driver = null;

    public $ignore = false;

    public function __construct($driver)
    {
        $driver = __pinpoint_ci_db_driver_util::getDriverName($driver);

        if (empty($driver)) return;

        $api = $driver . '::' . __pinpoint_ci_db_driver_util::METHOD_EXECUTE;

        $this->apiId = pinpoint_add_api($api, -1);

        $this->isInit = true;
    }

    public function onBefore($callId, $args)
    {
        if (! $this->isInit) return;

        if (empty($args[0])) return;

        if (! ($trace = pinpoint_get_current_trace())) return;

        if (! ($event = $trace->traceBlockBegin($callId))) return;

        if (__pinpoint_ci_db_driver_util::judgeIgnore($this->getSelf())) {
            $this->ignore = true;
            return;
        }

        $event->markBeforeTime();

        $event->setApiId($this->apiId);

        $event->setServiceType(__pinpoint_ci_db_driver_util::getCiDbServiceType($this->getSelf(), $param));

        $param = __pinpoint_util::decompDataMap($param);

        $event->setDestinationId(__pinpoint_util::getDest($param['key'], $param['val']));

        $args = __pinpoint_util::getMaxTxt(__pinpoint_util::makeAnnotationArgs(['sql'], [$args[0]]));

        $event->addAnnotation(PINPOINT_ANNOTATION_ARGS, $args);
    }

    public function onEnd($callId, $data)
    {
        if (! $this->isInit) return;

        if ($this->ignore) {
            $this->ignore = false;
            return;
        }

        if (! ($trace = pinpoint_get_current_trace())) return;

        if (! ($event = $trace->getEvent($callId))) return;

        if (! $data['result']) {
            return;
        }

        $param = __pinpoint_util::decompDataMap(__pinpoint_ci_db_driver_util::getConnectParam($this->getSelf()));

        __pinpoint_ci_db_driver_util::setHandle($this->getSelf(), $param['key'], $param['val']);

        $ret = __pinpoint_util::getMaxTxt(__pinpoint_util::serializeObj($data['result']));

        $event->addAnnotation(PINPOINT_ANNOTATION_RETURN, $ret);

        $event->markAfterTime();

        $trace->traceBlockEnd($event);
    }

    public function onException($callId, $exceptionStr)
    {
        if (! ($trace = pinpoint_get_current_trace())) return;

        if (! ($event = $trace->getEvent($callId))) return;

        $event->markAfterTime();
        $event->setExceptionInfo($exceptionStr);
    }
}

class __pinpoint_ci_db_driver_plugin extends \Pinpoint\Plugin
{
    public function __construct()
    {
        parent::__construct();

        $i = new __pinpoint_ci_db_driver_simple_query_interceptor(__pinpoint_ci_db_driver_util::DRIVER_PDO);
        $this->addInterceptor($i, 'CI_DB_pdo_driver::_execute', basename(__FILE__));

        $i = new __pinpoint_ci_db_driver_simple_query_interceptor(__pinpoint_ci_db_driver_util::DRIVER_MYSQL);
        $this->addInterceptor($i, 'CI_DB_mysql_driver::_execute', basename(__FILE__));

        $i = new __pinpoint_ci_db_driver_simple_query_interceptor(__pinpoint_ci_db_driver_util::DRIVER_MYSQLI);
        $this->addInterceptor($i, 'CI_DB_mysqli_driver::_execute', basename(__FILE__));
    }
}
