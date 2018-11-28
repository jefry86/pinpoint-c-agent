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

class __pinpoint_pdo_util
{
    static protected $__dsnMap = [];

    static protected $__statementMap = [];

    static public function setDsn($obj, $dsn)
    {
    	self::$__dsnMap[(string) $obj]['dsn'] = $dsn;
    }

    static public function getDsn($obj)
    {
    	if (isset(self::$__dsnMap[(string) $obj]['dsn'])) {
    		return self::$__dsnMap[(string) $obj]['dsn'];
    	}
    }

    static public function setStatement($obj, $pdo_obj, $sql)
    {
        self::$__statementMap[(string)$obj]['pdo'] = $pdo_obj;
        self::$__statementMap[(string)$obj]['sql'] = $sql;
    }

    static public function setStatementParam($obj, $param)
    {
    	self::$__statementMap[(string)$obj]['param'] = $param;
    }

    static public function getStatement($obj)
    {
    	if (isset(self::$__statementMap[(string)$obj])) {
    		return self::$__statementMap[(string)$obj];
    	}
    	return null;
    }

    static public function getServiceType($dsn)
    {
        $st = 2050;
        $dsn = explode(':', $dsn);
        if (strtolower(trim($dsn[0])) == 'mysql') {
            $st = 2101;
        }
        return $st;
    }

    static public function getDest($dsn)
    {
        $dsn = explode(':', $dsn);

        if (empty($dsn[1])) {
            return $dsn;
        }

        $dest = [];
        $dsn[1] = explode(';', $dsn[1]);
        foreach ($dsn[1] as $d)
        {
            $d = explode('=', $d);
            if ('dbname' == $d[0]) {
                $dest[] = 'db:' . $d[1];
            } else if ('unix_socket' == $d[0]) {
                $dest[] = 'ux:' . $d[1];
            } else if ('charset' == $d[0]) {
                $dest[] = 'char:' . $d[1];
            } else {
                $dest[] = $d[0] . ':' . $d[1];
            }
        }
        $dest = implode("\n", $dest);

        return $dest;
    }

}

class __pinpoint_pdo_interceptor extends \Pinpoint\Interceptor
{
    var $apiId = -1;
    public function __construct()
    {
        $this->apiId = pinpoint_add_api('PDO::__construct', -1);
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
        $event->setServiceType(__pinpoint_pdo_util::getServiceType($args[0]));
        $event->setDestinationId(__pinpoint_pdo_util::getDest($args[0]));

        $tmp = [];
        if (isset($args[0])) {
        	$tmp['dsn'] = $args[0];
        }
        if (isset($args[1])) {
        	$tmp['user'] = $args[1];
        }
        if (isset($args[2])) {
        	$tmp['pwd'] = $args[2];
        }

        $event->addAnnotation(PINPOINT_ANNOTATION_ARGS, json_encode($tmp));
    }

    public function onEnd($callId, $data)
    {
        $trace = pinpoint_get_current_trace();
        if ($trace) {
            $retArgs = $data['result'];
            $event = $trace->getEvent($callId);
            if ($event) {
            	__pinpoint_pdo_util::setDsn($this->getSelf(), $data['args'][0]);
                $event->addAnnotation(PINPOINT_ANNOTATION_RETURN, (array) $this->getSelf());
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

class __pinpoint_pdo_exec_interceptor extends \Pinpoint\Interceptor
{
    var $apiId = -1;
    public function __construct()
    {
        $this->apiId = pinpoint_add_api('PDO::exec', -1);
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

    	$dsn = __pinpoint_pdo_util::getDsn($this->getSelf());

        $event = $trace->traceBlockBegin($callId);
        $event->markBeforeTime();
        $event->setApiId($this->apiId);
        $event->setServiceType(__pinpoint_pdo_util::getServiceType($dsn));
        $event->setDestinationId(__pinpoint_pdo_util::getDest($dsn));

        $event->addAnnotation(PINPOINT_ANNOTATION_ARGS, json_encode(['sql' => $args[0]]));
    }

    public function onEnd($callId, $data)
    {
        $trace = pinpoint_get_current_trace();
        if ($trace) {
            $retArgs = $data['result'];
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

class __pinpoint_pdo_prepare_interceptor extends \Pinpoint\Interceptor
{
    var $apiId = -1;
    public function __construct()
    {
        $this->apiId = pinpoint_add_api('PDO::prepare', -1);
    }

    public function onBefore($callId, $args)
    {

    }

    public function onEnd($callId, $data)
    {
        __pinpoint_pdo_util::setStatement($data['result'], $this->getSelf(), $data['args'][0]);
    }

    public function onException($callId, $exceptionStr)
    {

    }
}

class __pinpoint_pdo_query_interceptor extends \Pinpoint\Interceptor
{
    var $apiId = -1;
    public function __construct()
    {
        $this->apiId = pinpoint_add_api('PDO::query', -1);
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

        $dsn = __pinpoint_pdo_util::getDsn($this->getSelf());

        $event = $trace->traceBlockBegin($callId);
        $event->markBeforeTime();
        $event->setApiId($this->apiId);
        $event->setServiceType(__pinpoint_pdo_util::getServiceType($dsn));
        $event->setDestinationId(__pinpoint_pdo_util::getDest($dsn));

        $event->addAnnotation(PINPOINT_ANNOTATION_ARGS, json_encode($args));
    }

    public function onEnd($callId, $data)
    {
        $trace = pinpoint_get_current_trace();
        if ($trace) {
            $retArgs = $data['result'];
            $event = $trace->getEvent($callId);
            if ($event) {
            	__pinpoint_pdo_util::setStatement($data['result'], $this->getSelf(), $data['args'][0]);
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

class __pinpoint_pdo_statement_bind_param_interceptor extends \Pinpoint\Interceptor
{
    var $apiId = -1;
    public function __construct()
    {
        $this->apiId = pinpoint_add_api('PDOStatement::bindParam', -1);
    }

    public function onBefore($callId, $args)
    {

    }

    public function onEnd($callId, $data)
    {
    	if ($data['result']) {
    		__pinpoint_pdo_util::setStatementParam($this->getSelf(), $data['args']);
    	}
    }

    public function onException($callId, $exceptionStr)
    {

    }
}

class __pinpoint_pdo_statement_execute_interceptor extends \Pinpoint\Interceptor
{
    var $apiId = -1;
    public function __construct()
    {
        $this->apiId = pinpoint_add_api('PDOStatement::execute', -1);
    }

    public function onBefore($callId, $args)
    {
    	$trace = pinpoint_get_current_trace();
    	if (! $trace) {
    		return;
    	}

    	$statement = __pinpoint_pdo_util::getStatement($this->getSelf());

    	if (! $statement) {
    		return;
    	}

        $dsn = __pinpoint_pdo_util::getDsn($statement['pdo']);

        $event = $trace->traceBlockBegin($callId);
        $event->markBeforeTime();
        $event->setApiId($this->apiId);
        $event->setServiceType(__pinpoint_pdo_util::getServiceType($dsn));
        $event->setDestinationId(__pinpoint_pdo_util::getDest($dsn));

        $event->addAnnotation(PINPOINT_ANNOTATION_ARGS, json_encode($args));
    }

    public function onEnd($callId, $data)
    {
        $trace = pinpoint_get_current_trace();
        if ($trace) {
            $retArgs = $data['result'];
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
