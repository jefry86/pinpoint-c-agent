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

class __pinpoint_mysqli_util
{
    const LIMIT = 2;

    const SAMPLE = 0;

    const MAX = 100;

    static protected $__hostMap = [];

    static protected $__stmtMap = [];

    static protected $__hostConst = [];

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

    static public function setHost($obj, $param)
    {
        $data = [];
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
            $data['db'] = $param[3];
        }
        if (isset($param[4])) {
            $data['port'] = $param[4];
        }
        if (isset($param[5])) {
            $data['sock'] = $param[5];
        }
        self::$__hostMap[self::serializeObj($obj)] = $data;
    }

    static public function getHost($obj)
    {
        if (isset(self::$__hostMap[self::serializeObj($obj)])) {
            return self::$__hostMap[self::serializeObj($obj)];
        }
    }

    static public function setDb($obj, $db)
    {
        if (self::$__hostMap[self::serializeObj($obj)]) {
            self::$__hostMap[self::serializeObj($obj)]['db'] = $db;
        }
    }

    static public function setStmt($obj, $mysqlObj, $sql)
    {
        self::$__stmtMap[self::serializeObj($obj)]['mysql'] = $mysqlObj;
        self::$__stmtMap[self::serializeObj($obj)]['sql'] = $sql;
    }

    static public function setStmtParam($obj, $param)
    {
        if (is_array(self::$__stmtMap[self::serializeObj($obj)]['param'])) {
            self::$__stmtMap[self::serializeObj($obj)]['param'] = array_merge(
                self::$__stmtMap[self::serializeObj($obj)]['param'],
                $param
            );
        }
        if (is_array($param)) {
            self::$__stmtMap[self::serializeObj($obj)]['param'] = $param;
        } else {
            self::$__stmtMap[self::serializeObj($obj)]['param'] = [$param];
        }
    }

    static public function getStmt($obj)
    {
        if (isset(self::$__stmtMap[self::serializeObj($obj)])) {
            return self::$__stmtMap[self::serializeObj($obj)];
        }
        return null;
    }

    static public function judgeIgnore($param)
    {
        $dsn = md5(serialize(array_values($param)));

        if (isset(self::$__hostConst[$dsn])) {
            self::$__hostConst[$dsn] ++;
        } else {
            self::$__hostConst[$dsn] = 1;
        }

        if (self::LIMIT > 0) {
            return self::$__hostConst[$dsn] >= self::LIMIT;
        }

        if (self::SAMPLE > 0) {
            return (self::$__hostConst[$dsn] - 1) % self::SAMPLE != 0;
        }

        return false;
    }

    static public function judgeIgnoreByMysqli($obj)
    {
        $obj = self::getHost($obj);
        if ($obj) {
            return self::judgeIgnore($obj);
        }
        return true;
    }

    static public function judgeIgnoreByStmt($obj)
    {
        $obj = self::getStmt($obj);
        if (empty($obj)) {
            return true;
        }
        return self::judgeIgnoreByMysqli($obj['mysql']);
    }

    static public function getServiceType()
    {
        return 2101;
    }

    static public function getDest($param)
    {
        $dest = [];
        if (isset($param[0])) {
            $tmp = 'host:' . $param[0];
            if (isset($param[4])) {
                $tmp .= ':' . $param[4];
            }
            $dest[] = $tmp;
        }
        if (isset($param[3])) {
            $dest[] = 'db:' . $param[3];
        }
        if (isset($param[5])) {
            $dest[] = 'sock:' . $param[5];
        }
        if (empty($dest)) {
            return 'N/A';
        }

        $dest = implode("\n", $dest);

        return $dest;
    }

    static public function makeAnnotationArgs($keyMap, $args, $org = false)
    {
        $tmp = [];
        foreach ($keyMap as $argsKey => $strKey)
        {
            if (isset($args[$argsKey])) {
                $tmp[$strKey] = $args[$argsKey];
            }
        }

        if ($org) {
            return $tmp;
        }
        return json_encode($tmp);
    }
}

class __pinpoint_mysqli_interceptor extends \Pinpoint\Interceptor
{
    var $apiId = -1;
    public function __construct($c = false)
    {
        $api = $c ? 'mysqli::connect' : 'mysqli::__construct';
        $this->apiId = pinpoint_add_api($api, -1);
    }

    public function onBefore($callId, $args)
    {
        $trace = pinpoint_get_current_trace();
        if (! $trace) {
            return;
        }

        if (empty($args)) {
            return;
        }

        $event = $trace->traceBlockBegin($callId);
        $event->markBeforeTime();
        $event->setApiId($this->apiId);
        $event->setServiceType(__pinpoint_mysqli_util::getServiceType());
        $event->setDestinationId(__pinpoint_mysqli_util::getDest($args));

        $args = __pinpoint_mysqli_util::makeAnnotationArgs(['host', 'user', 'pwd', 'db', 'port', 'sock'], $args);

        $event->addAnnotation(PINPOINT_ANNOTATION_ARGS, $args);
    }

    public function onEnd($callId, $data)
    {
        if (empty($data['args'])) {
            return ;
        }

        $trace = pinpoint_get_current_trace();
        if ($trace) {
            $event = $trace->getEvent($callId);
            if ($event) {
                __pinpoint_mysqli_util::setHost($this->getSelf(), $data['args']);
                $event->addAnnotation(PINPOINT_ANNOTATION_RETURN, __pinpoint_mysqli_util::serializeObj($this->getSelf()));
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

class __pinpoint_mysqli_query_interceptor extends \Pinpoint\Interceptor
{
    var $apiId = -1;
    public function __construct()
    {
        $this->apiId = pinpoint_add_api('mysqli::query', -1);
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

        if (__pinpoint_mysqli_util::judgeIgnoreByMysqli($this->getSelf())) {
            return;
        }

        $host = __pinpoint_mysqli_util::getHost($this->getSelf());

        $event = $trace->traceBlockBegin($callId);
        $event->markBeforeTime();
        $event->setApiId($this->apiId);
        $event->setServiceType(__pinpoint_mysqli_util::getServiceType());
        $event->setDestinationId(__pinpoint_mysqli_util::getDest($host));

        $event->addAnnotation(PINPOINT_ANNOTATION_ARGS, __pinpoint_mysqli_util::getMaxTxt(json_encode($args)));
    }

    public function onEnd($callId, $data)
    {
        $trace = pinpoint_get_current_trace();
        if ($trace) {
            $event = $trace->getEvent($callId);
            if ($event) {
                $event->addAnnotation(PINPOINT_ANNOTATION_RETURN, true);
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

class __pinpoint_mysqli_prepare_interceptor extends \Pinpoint\Interceptor
{
    var $apiId = -1;
    public function __construct()
    {
        $this->apiId = pinpoint_add_api('mysqli::prepare', -1);
    }

    public function onBefore($callId, $args)
    {

    }

    public function onEnd($callId, $data)
    {
        __pinpoint_mysqli_util::setStmt($data['result'], $this->getSelf(), $data['args'][0]);
    }

    public function onException($callId, $exceptionStr)
    {

    }
}

class __pinpoint_mysqli_select_db_interceptor extends \Pinpoint\Interceptor
{
    var $apiId = -1;
    public function __construct()
    {
        $this->apiId = pinpoint_add_api('mysqli::select_db', -1);
    }

    public function onBefore($callId, $args)
    {

    }

    public function onEnd($callId, $data)
    {
        if ($data['result']) {
            __pinpoint_mysqli_util::setDb($this->getSelf(), $data['args'][0]);
        }
    }

    public function onException($callId, $exceptionStr)
    {

    }
}

class __pinpoint_mysqli_stmt_bind_param_interceptor extends \Pinpoint\Interceptor
{
    var $apiId = -1;
    public function __construct()
    {
        $this->apiId = pinpoint_add_api('mysqli_stmt::bind_param', -1);
    }

    public function onBefore($callId, $args)
    {

    }

    public function onEnd($callId, $data)
    {
        if ($data['result']) {
            __pinpoint_mysqli_util::setStmtParam($this->getSelf(), $data['args']);
        }
    }

    public function onException($callId, $exceptionStr)
    {

    }
}

class __pinpoint_mysqli_stmt_execute_interceptor extends \Pinpoint\Interceptor
{
    var $apiId = -1;
    public function __construct()
    {
        $this->apiId = pinpoint_add_api('mysqli_stmt::execute', -1);
    }

    public function onBefore($callId, $args)
    {
        $trace = pinpoint_get_current_trace();
        if (! $trace) {
            return;
        }

        if (__pinpoint_mysqli_util::judgeIgnoreByStmt($this->getSelf())) {
            return;
        }

        $stmt = __pinpoint_mysqli_util::getStmt($this->getSelf());
        if (! $stmt) {
            return;
        }

        $host = __pinpoint_mysqli_util::getHost($stmt['mysql']);

        $event = $trace->traceBlockBegin($callId);
        $event->markBeforeTime();
        $event->setApiId($this->apiId);
        $event->setServiceType(__pinpoint_mysqli_util::getServiceType());
        $event->setDestinationId(__pinpoint_mysqli_util::getDest($host));

        $args = ['sql' => $stmt['sql']];

        $event->addAnnotation(PINPOINT_ANNOTATION_ARGS, __pinpoint_mysqli_util::getMaxTxt(json_encode($args)));
    }

    public function onEnd($callId, $data)
    {
        $trace = pinpoint_get_current_trace();
        if ($trace) {
            $event = $trace->getEvent($callId);
            if ($event) {
                $event->addAnnotation(PINPOINT_ANNOTATION_RETURN, json_encode($data['result']));
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

class __pinpoint_mysqli_plugin extends \Pinpoint\Plugin
{
    public function __construct()
    {
        parent::__construct();
        $i = new __pinpoint_mysqli_interceptor();
        $this->addInterceptor($i, 'mysqli::__construct', basename(__FILE__));
        $i = new __pinpoint_mysqli_interceptor(true);
        $this->addInterceptor($i, 'mysqli::connect', basename(__FILE__));
        $i = new __pinpoint_mysqli_query_interceptor();
        $this->addInterceptor($i, 'mysqli::query', basename(__FILE__));
        $i = new __pinpoint_mysqli_prepare_interceptor();
        $this->addInterceptor($i, 'mysqli::prepare', basename(__FILE__));
        $i = new __pinpoint_mysqli_select_db_interceptor();
        $this->addInterceptor($i, 'mysqli::select_db', basename(__FILE__));
        $i = new __pinpoint_mysqli_stmt_bind_param_interceptor();
        $this->addInterceptor($i, 'mysqli_stmt::bind_param', basename(__FILE__));
        $i = new __pinpoint_mysqli_stmt_execute_interceptor();
        $this->addInterceptor($i, 'mysqli_stmt::execute', basename(__FILE__));
    }
}
