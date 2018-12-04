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

class __pinpoint_redis_util
{
    const LIMIT = 3;

    const SAMPLE = 0;

    const SERVICE_TYPE = 8200;

    const MAX = 97;

    static protected $__hostMap = [];

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

    static public function setHostMap($obj, $ip, $port = null)
    {
        self::$__hostMap[self::serializeObj($obj)] = ['ip' => $ip, 'port' => $port];
    }

    static public function getDest($ip, $port = null)
    {
        if ($port) {
            $ip .= ':' . $port;
        }
        return $ip;
    }

    static public function getDestByObj($obj)
    {
        if (! isset(self::$__hostMap[self::serializeObj($obj)])) {
            return 'N/A';
        }

        return self::getDest(self::$__hostMap[self::serializeObj($obj)]['ip'], self::$__hostMap[self::serializeObj($obj)]['port']);
    }

    static public function judgeIgnore($obj)
    {
        if (isset(self::$__hostConst[self::serializeObj($obj)])) {
            self::$__hostConst[self::serializeObj($obj)] ++;
        } else {
            self::$__hostConst[self::serializeObj($obj)] = 1;
        }

        if (self::LIMIT > 0) {
            return self::$__hostConst[self::serializeObj($obj)] > self::LIMIT;
        }

        if (self::SAMPLE > 0) {
            return (self::$__hostConst[self::serializeObj($obj)] - 1) % self::SAMPLE != 0;
        }

        return false;
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

class __pinpoint_redis_connect_interceptor extends \Pinpoint\Interceptor
{
    var $apiId = -1;
    public function __construct($open = false)
    {
        $method = $open ? 'Redis::open' : 'Redis::connect';
        $this->apiId = pinpoint_add_api($method, -1);
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
        $event->setServiceType(__pinpoint_redis_util::SERVICE_TYPE);
        $event->setDestinationId(__pinpoint_redis_util::getDest($args[0], empty($args[1]) ? null : $args[1]));

        /*$event->addAnnotation(PINPOINT_ANNOTATION_ARGS, __pinpoint_redis_util::makeAnnotationArgs([
            'host', 'port', 'timeout', 'reserved', 'retry', 'read_timeout'
        ], $args));*/
    }

    public function onEnd($callId, $data)
    {
        $trace = pinpoint_get_current_trace();
        if ($trace) {
            $event = $trace->getEvent($callId);
            if ($event) {
                __pinpoint_redis_util::setHostMap(
                    $this->getSelf(),
                    $data['args'][0],
                    empty($data['args'][1]) ? null : $data['args'][1]
                );

                $data['args'] = __pinpoint_redis_util::makeAnnotationArgs([
                    'host', 'port', 'timeout', 'reserved', 'retry', 'read_timeout'
                ], $data['args'], true);
                $data['obj'] = __pinpoint_redis_util::serializeObj($this->getSelf());

                $data = __pinpoint_redis_util::getMaxTxt(json_encode($data));

                $event->addAnnotation(PINPOINT_ANNOTATION_RETURN, $data);
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

class __pinpoint_redis_pconnect_interceptor extends \Pinpoint\Interceptor
{
    var $apiId = -1;
    public function __construct($open = false)
    {
        $method = $open ? 'Redis::popen' : 'Redis::pconnect';
        $this->apiId = pinpoint_add_api($method, -1);
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

        if (__pinpoint_redis_util::judgeIgnore($this->getSelf())) {
            return;
        }

        $event = $trace->traceBlockBegin($callId);
        $event->markBeforeTime();
        $event->setApiId($this->apiId);
        $event->setServiceType(__pinpoint_redis_util::SERVICE_TYPE);
        $event->setDestinationId(__pinpoint_redis_util::getDest($args[0], empty($args[1]) ? null : $args[1]));

        /*$event->addAnnotation(PINPOINT_ANNOTATION_ARGS, __pinpoint_redis_util::makeAnnotationArgs([
            'host', 'port', 'timeout', 'pid', 'retry', 'read_timeout'
        ], $args));*/
    }

    public function onEnd($callId, $data)
    {
        $trace = pinpoint_get_current_trace();
        if ($trace) {
            $event = $trace->getEvent($callId);
            if ($event) {
                __pinpoint_redis_util::setHostMap(
                    $this->getSelf(),
                    $data['args'][0],
                    empty($data['args'][1]) ? null : $data['args'][1]
                );

                $data['args'] = __pinpoint_redis_util::makeAnnotationArgs([
                    'host', 'port', 'timeout', 'pid', 'retry', 'read_timeout'
                ], $data['args'], true);
                $data['obj'] = __pinpoint_redis_util::serializeObj($this->getSelf());

                $data = __pinpoint_redis_util::getMaxTxt(json_encode($data));

                $event->addAnnotation(PINPOINT_ANNOTATION_RETURN, $data);
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

class __pinpoint_redis_interceptor extends \Pinpoint\Interceptor
{
    var $apiId = -1;

    public $paramKey = [];

    public function __construct($api, $paramKey = [])
    {
        $this->apiId = pinpoint_add_api('Redis::' . $api, -1);
        $this->paramKey = $paramKey;
    }

    public function onBefore($callId, $args)
    {
        $trace = pinpoint_get_current_trace();
        if (! $trace) {
            return;
        }

        if (__pinpoint_redis_util::judgeIgnore($this->getSelf())) {
            return;
        }

        $event = $trace->traceBlockBegin($callId);
        $event->markBeforeTime();
        $event->setApiId($this->apiId);
        $event->setServiceType(__pinpoint_redis_util::SERVICE_TYPE);
        $event->setDestinationId(__pinpoint_redis_util::getDestByObj($this->getSelf()));

        /*if ($args and $this->paramKey) {
            $event->addAnnotation(
                PINPOINT_ANNOTATION_ARGS,
                __pinpoint_redis_util::makeAnnotationArgs($this->paramKey, $args)
            );
        }*/
    }

    public function onEnd($callId, $data)
    {
        $trace = pinpoint_get_current_trace();
        if ($trace) {
            $event = $trace->getEvent($callId);
            if ($event) {
                if ($data['args'] and $this->paramKey) {
                    $data['args'] = __pinpoint_redis_util::makeAnnotationArgs($this->paramKey, $data['args'], true);
                }
                $data = __pinpoint_redis_util::getMaxTxt(json_encode($data));
                $event->addAnnotation(PINPOINT_ANNOTATION_RETURN, $data);
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

class __pinpoint_redis_plugin extends \Pinpoint\Plugin
{
    public function __construct()
    {
        parent::__construct();
        $i = new __pinpoint_redis_connect_interceptor();
        $this->addInterceptor($i, 'Redis::connect', basename(__FILE__));

        $i = new __pinpoint_redis_pconnect_interceptor();
        $this->addInterceptor($i, 'Redis::pconnect', basename(__FILE__));

        $i = new __pinpoint_redis_interceptor('get', ['key']);
        $this->addInterceptor($i, 'Redis::get', basename(__FILE__));

        $i = new __pinpoint_redis_interceptor('set', ['key', 'value', 'timeout']);
        $this->addInterceptor($i, 'Redis::set', basename(__FILE__));

        //$i = new __pinpoint_redis_interceptor('setEx', ['key', 'ttl', 'value']);
        //$this->addInterceptor($i, 'Redis::setEx', basename(__FILE__));
        //$i = new __pinpoint_redis_interceptor('pSetEx', ['key', 'ttl', 'value']);
        //$this->addInterceptor($i, 'Redis::pSetEx', basename(__FILE__));

        //$i = new __pinpoint_redis_interceptor('setNx', ['key', 'value']);
        //$this->addInterceptor($i, 'Redis::setNx', basename(__FILE__));

        $i = new __pinpoint_redis_interceptor('delete', ['key']);
        $this->addInterceptor($i, 'Redis::delete', basename(__FILE__));

        $i = new __pinpoint_redis_interceptor('incr', ['key', 'value']);
        $this->addInterceptor($i, 'Redis::incr', basename(__FILE__));

        $i = new __pinpoint_redis_interceptor('decr', ['key', 'value']);
        $this->addInterceptor($i, 'Redis::decr', basename(__FILE__));

        //$i = new __pinpoint_redis_interceptor('mGet', ['key']);
        //$this->addInterceptor($i, 'Redis::mGet', basename(__FILE__));

        //$i = new __pinpoint_redis_interceptor('getSet', ['key', 'value']);
        //$this->addInterceptor($i, 'Redis::getSet', basename(__FILE__));

        //$i = new __pinpoint_redis_interceptor('mSet', ['kv']);
        //$this->addInterceptor($i, 'Redis::mSet', basename(__FILE__));
    }
}
