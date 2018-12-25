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

class __pinpoint_mysql_util
{
    const LIMIT = 2;

    const SAMPLE = 0;

    const MAX = 100;

    static protected $__objMap = [];

    static protected $__queryObjMap = [];

    static protected $__objConst = [];

    static public function serializeObj($obj)
    {
        ob_start();
        var_dump($obj);
        return ob_get_clean();
    }

    static public function getMaxTxt($string)
    {
        if (strlen($string) > self::MAX) {
            return substr($string, 0, self::MAX) . '...';
        }
        return $string;
    }

    static public function now()
    {
        list($usec, $sec) = explode(' ', microtime());
        return strval((float) $usec + (float) $sec);
    }

    static public function setObj($obj, $param)
    {
        $data = [
            'host' => null,
            'user' => null,
            'pwd' => null,
            'new' => false,
            'flag' => null,
            'time' => self::now(),
            'db' => null,
            'org_obj' => $obj,
        ];
        if (isset($param[0])) {
            $data['host'] = $param[0];
        }
        if (isset($param[1])) {
            $data['user'] = $param[1];
        }
        if (isset($param[2])) {
            $data['pwd'] = $param[2];
        }
        if (isset($param[3])) {
            $data['new'] = $param[3];
        }
        if (isset($param[4])) {
            $data['flag'] = $param[4];
        }
        self::$__objMap[self::serializeObj($obj)] = $data;
    }

    static public function getObj($obj)
    {
        if (isset(self::$__objMap[self::serializeObj($obj)])) {
            return self::$__objMap[self::serializeObj($obj)];
        }
        return null;
    }

    static public function getNewObj()
    {
        $time = '';
        $res = null;
        foreach (self::$__objMap as $key => $obj)
        {
            if ($obj['time'] > $time) {
                $res = $obj;
                $time = $obj['time'];
            }
        }
        return $res;
    }

    static public function getNewOrgObj()
    {
        $mysqlObj = __pinpoint_mysql_util::getNewObj();
        if (! empty($mysqlObj['org_obj'])) {
            $mysqlObj = $mysqlObj['org_obj'];
        } else {
            $mysqlObj = null;
        }
        return $mysqlObj;
    }

    static public function setObjDb($obj, $db)
    {
        if (isset(self::$__objMap[self::serializeObj($obj)])) {
            self::$__objMap[self::serializeObj($obj)]['db'] = $db;
        }
    }

    static public function setQueryObj($queryObj, $mysqlObj, $sql, $db = null)
    {
        if (! empty(self::$__objMap[self::serializeObj($mysqlObj)])) {
            $mysqlObj = self::$__objMap[self::serializeObj($mysqlObj)];
            if (empty($db)) {
                $db = $mysqlObj['db'];
            }
        }
        self::$__queryObjMap[self::serializeObj($queryObj)] = [
            'mysql' => $mysqlObj,
            'sql' => $sql,
            'db' => $db,
        ];
    }

    static public function getQueryObj($obj)
    {
        if (isset(self::$__queryObjMap[self::serializeObj($obj)])) {
            return self::$__queryObjMap[self::serializeObj($obj)];
        }
        return null;
    }

    static public function setQueryObjDb($queryObj, $db)
    {
        if (isset(self::$__queryObjMap[self::serializeObj($queryObj)])) {
            self::$__queryObjMap[self::serializeObj($queryObj)]['db'] = $db;
        }
    }

    static public function judgeIgnore($obj)
    {
        if (isset(self::$__objConst[self::serializeObj($obj)])) {
            self::$__objConst[self::serializeObj($obj)] ++;
        } else {
            self::$__objConst[self::serializeObj($obj)] = 1;
        }

        if (self::LIMIT > 0) {
            return self::$__objConst[self::serializeObj($obj)] >= self::LIMIT;
        }

        if (self::SAMPLE > 0) {
            return (self::$__objConst[self::serializeObj($obj)] - 1) % self::SAMPLE != 0;
        }

        return false;
    }

    static public function judgeIgnoreByQueryObj($obj)
    {
        $mysqlObj = self::getQueryObj($obj);
        if (empty($mysqlObj)) {
            return true;
        }
        return self::judgeIgnore($mysqlObj['mysql']);
    }

    static public function getServiceType()
    {
        return 2101;
    }

    static public function getDest($host)
    {
        return 'db:' . $host;
    }

}

class __pinpoint_mysql_connect_interceptor extends \Pinpoint\Interceptor
{
    var $apiId = -1;
    public function __construct($p = false)
    {
        $api = $p ? 'mysql_pconnect' : 'mysql_connect';
        $this->apiId = pinpoint_add_api($api, -1);
    }

    public function onBefore($callId, $args)
    {
        $trace = pinpoint_get_current_trace();
        if (! $trace) {
            return;
        }

        if (empty($args[0])) {
            return;
        }

        $event = $trace->traceBlockBegin($callId);
        $event->markBeforeTime();
        $event->setApiId($this->apiId);
        $event->setServiceType(__pinpoint_mysql_util::getServiceType());
        $event->setDestinationId(__pinpoint_mysql_util::getDest($args[0]));

        $tmp = [];
        if (isset($args[0])) {
            $tmp['host'] = $args[0];
        }
        if (isset($args[1])) {
            $tmp['user'] = $args[1];
        }
        if (isset($args[2])) {
            $tmp['pwd'] = $args[2];
        }
        if (isset($args[3])) {
            $tmp['new'] = $args[3];
        }
        if (isset($args[4])) {
            $tmp['flag'] = $args[4];
        }

        $event->addAnnotation(PINPOINT_ANNOTATION_ARGS, json_encode($tmp));
    }

    public function onEnd($callId, $data)
    {
        if (empty($data['args'][0])) {
            return ;
        }

        $trace = pinpoint_get_current_trace();
        if ($trace) {
            $event = $trace->getEvent($callId);
            if ($event) {
                __pinpoint_mysql_util::setObj($data['result'], $data['args']);
                $event->addAnnotation(PINPOINT_ANNOTATION_RETURN, __pinpoint_mysql_util::serializeObj($data['result']));
                $event->markAfterTime();
                $trace->traceBlockEnd($event);
            }
        }
    }

    public function onException($callId, $exceptionStr)
    {
        $trace = pinpoint_get_current_trace();
        if ($trace) {
            $event = $trace->getEvent($callId);
            if ($event) {
                $event->markAfterTime();
                $event->setExceptionInfo($exceptionStr);
            }
        }
    }
}

class __pinpoint_mysql_query_interceptor extends \Pinpoint\Interceptor
{
    var $apiId = -1;
    public function __construct()
    {
        $this->apiId = pinpoint_add_api('mysql_query', -1);
    }

    public function onBefore($callId, $args)
    {
        $trace = pinpoint_get_current_trace();
        if (! $trace) {
            return;
        }

        if (empty($args[0])) {
            return;
        }

        if (empty($args[1])) {
            $args[1] = __pinpoint_mysql_util::getNewOrgObj();
        }

        $mysqlObj = __pinpoint_mysql_util::getObj($args[1]);
        if (empty($mysqlObj)) {
            return;
        }

        if (__pinpoint_mysql_util::judgeIgnore($args[1])) {
            return;
        }

        $event = $trace->traceBlockBegin($callId);
        $event->markBeforeTime();
        $event->setApiId($this->apiId);
        $event->setServiceType(__pinpoint_mysql_util::getServiceType());
        $event->setDestinationId(__pinpoint_mysql_util::getDest($mysqlObj['host']));

        $event->addAnnotation(PINPOINT_ANNOTATION_ARGS, __pinpoint_mysql_util::getMaxTxt(json_encode($args)));
    }

    public function onEnd($callId, $data)
    {
        $trace = pinpoint_get_current_trace();
        if ($trace) {
            $event = $trace->getEvent($callId);
            if ($event) {
                if (empty($data['args'][1])) {
                    $data['args'][1] = __pinpoint_mysql_util::getNewOrgObj();
                }
                __pinpoint_mysql_util::setQueryObj($data['result'], $data['args'][1], $data['args'][0]);
                $event->addAnnotation(PINPOINT_ANNOTATION_RETURN, __pinpoint_mysql_util::serializeObj($data['result']));
                $event->markAfterTime();
                $trace->traceBlockEnd($event);
            }
        }
    }

    public function onException($callId, $exceptionStr)
    {
        $trace = pinpoint_get_current_trace();
        if ($trace) {
            $event = $trace->getEvent($callId);
            if ($event) {
                $event->markAfterTime();
                $event->setExceptionInfo($exceptionStr);
            }
        }
    }
}

class __pinpoint_mysql_db_query_interceptor extends \Pinpoint\Interceptor
{
    var $apiId = -1;
    public function __construct()
    {
        $this->apiId = pinpoint_add_api('mysql_db_query', -1);
    }

    public function onBefore($callId, $args)
    {
        $trace = pinpoint_get_current_trace();
        if (! $trace) {
            return;
        }

        if (empty($args[0]) OR empty($args[1])) {
            return;
        }

        if (empty($args[2])) {
            $args[2] = __pinpoint_mysql_util::getNewOrgObj();
        }

        $mysqlObj = __pinpoint_mysql_util::getObj($args[2]);
        if (empty($mysqlObj)) {
            return;
        }

        if (__pinpoint_mysql_util::judgeIgnore($args[2])) {
            return;
        }

        $event = $trace->traceBlockBegin($callId);
        $event->markBeforeTime();
        $event->setApiId($this->apiId);
        $event->setServiceType(__pinpoint_mysql_util::getServiceType());
        $event->setDestinationId(__pinpoint_mysql_util::getDest($mysqlObj['host']));

        $event->addAnnotation(PINPOINT_ANNOTATION_ARGS, __pinpoint_mysql_util::getMaxTxt(json_encode($args)));
    }

    public function onEnd($callId, $data)
    {
        $trace = pinpoint_get_current_trace();
        if ($trace) {
            $event = $trace->getEvent($callId);
            if ($event) {
                if (empty($data['args'][2])) {
                    $data['args'][2] = __pinpoint_mysql_util::getNewOrgObj();
                }
                __pinpoint_mysql_util::setQueryObj($data['result'], $data['args'][2], $data['args'][1], $data['args'][0]);
                $event->addAnnotation(PINPOINT_ANNOTATION_RETURN, __pinpoint_mysql_util::serializeObj($data['result']));
                $event->markAfterTime();
                $trace->traceBlockEnd($event);
            }
        }
    }

    public function onException($callId, $exceptionStr)
    {
        $trace = pinpoint_get_current_trace();
        if ($trace) {
            $event = $trace->getEvent($callId);
            if ($event) {
                $event->markAfterTime();
                $event->setExceptionInfo($exceptionStr);
            }
        }
    }
}

class __pinpoint_mysql_select_db_interceptor extends \Pinpoint\Interceptor
{
    var $apiId = -1;
    public function __construct()
    {
        $this->apiId = pinpoint_add_api('mysql_select_db', -1);
    }

    public function onBefore($callId, $args)
    {

    }

    public function onEnd($callId, $data)
    {
        if (! $data['result']) {
            return;
        }

        if (empty($data['args'][0])) {
            return ;
        }

        if (empty($data['args'][1])) {
            $data['args'][1] = __pinpoint_mysql_util::getNewOrgObj();
        }

        __pinpoint_mysql_util::setObjDb($data['args'][1], $data['args'][0]);
    }

    public function onException($callId, $exceptionStr)
    {

    }
}

class __pinpoint_mysql_plugin extends \Pinpoint\Plugin
{
    public function __construct()
    {
        parent::__construct();
        $i = new __pinpoint_mysql_connect_interceptor();
        $this->addInterceptor($i, 'mysql_connect', basename(__FILE__));
        $i = new __pinpoint_mysql_connect_interceptor(true);
        $this->addInterceptor($i, 'mysql_pconnect', basename(__FILE__));
        $i = new __pinpoint_mysql_query_interceptor();
        $this->addInterceptor($i, 'mysql_query', basename(__FILE__));
        $i = new __pinpoint_mysql_db_query_interceptor();
        $this->addInterceptor($i, 'mysql_db_query', basename(__FILE__));
        $i = new __pinpoint_mysql_select_db_interceptor();
        $this->addInterceptor($i, 'mysql_select_db', basename(__FILE__));
    }
}
