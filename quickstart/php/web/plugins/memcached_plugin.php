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

class __pinpoint_memcached_util
{
    const LIMIT = 0;

    const SAMPLE = 0;

    const SERVICE_TYPE = 8050;

    static protected $__hostMap = [];

    static protected $__hostConst = [];

    static public function setHostMap($obj, $ip, $port, $weight = null)
    {
        if (self::$__hostMap[(string) $obj] and is_array(self::$__hostMap[(string) $obj])) {
            self::$__hostMap[(string) $obj][] = ['ip' => $ip, 'port' => $port, 'weight' => $weight];
        } else {
            self::$__hostMap[(string) $obj] = [['ip' => $ip, 'port' => $port, 'weight' => $weight]];
        }
    }

    static public function getDest($ip, $port, $weight)
    {
        $ip .= ':' . $port;
        if ($weight) {
            $ip .= '[' . $weight . ']';
        }
        return $ip;
    }

    static public function getDestByObj($obj)
    {
        if (empty(self::$__hostMap[(string) $obj])) {
            return 'N/A';
        }

        $result = [];

        foreach (self::$__hostMap[(string) $obj] as $h)
        {
            $result[] = self::getDest($h['ip'], $h['port'], $h['weight']);
        }

        return implode("\n", $result);
    }

    static public function judgeIgnore($obj)
    {
        if (isset(self::$__hostConst[(string) $obj])) {
            self::$__hostConst[(string) $obj] ++;
        } else {
            self::$__hostConst[(string) $obj] = 1;
        }

        if (self::LIMIT > 0) {
            return self::$__hostConst[(string) $obj] >= self::LIMIT;
        }

        if (self::SAMPLE > 0) {
            return (self::$__hostConst[(string) $obj] - 1) % self::SAMPLE != 0;
        }

        return false;
    }

    static public function makeAnnotationArgs($keyMap, $args)
    {
        $tmp = [];
        foreach ($keyMap as $argsKey => $strKey)
        {
            if (isset($args[$argsKey])) {
                $tmp[$strKey] = $args[$argsKey];
            }
        }
        return json_encode($tmp);
    }
}

class __pinpoint_memcached_add_server_interceptor extends \Pinpoint\Interceptor
{
    var $apiId = -1;
    public function __construct()
    {
        $this->apiId = pinpoint_add_api('Memcached::addServer', -1);
    }

    public function onBefore($callId, $args)
    {

    }

    public function onEnd($callId, $data)
    {
        if (empty($data['args'][0])) {
            return ;
        }

        if (isset($data['args'][1])) {
            $data['args'][1] = intval($data['args'][1]);
        } else {
            $data['args'][1] = 0;
        }

        if (isset($data['args'][2])) {
            $data['args'][2] = intval($data['args'][2]);
        } else {
            $data['args'][2] = 0;
        }

        __pinpoint_memcached_util::setHostMap($this->getSelf(), $data['args'][0], $data['args'][1], $data['args'][2]);
    }

    public function onException($callId, $exceptionStr)
    {

    }
}

class __pinpoint_memcached_add_servers_interceptor extends \Pinpoint\Interceptor
{
    var $apiId = -1;
    public function __construct()
    {
        $this->apiId = pinpoint_add_api('Memcached::addServers', -1);
    }

    public function onBefore($callId, $args)
    {

    }

    public function onEnd($callId, $data)
    {
        if (empty($data['args'][0]) or ! is_array($data['args'][0])) {
            return;
        }

        foreach ($data['args'][0] as $host)
        {
            if (empty($host[0])) {
                continue;
            }

            if (isset($host[1])) {
                $host[1] = intval($host[1]);
            } else {
                $host[1] = 0;
            }

            if (isset($host[2])) {
                $host[2] = intval($host[2]);
            } else {
                $host[2] = 0;
            }

            __pinpoint_memcached_util::setHostMap($this->getSelf(), $host[0], $host[1], $host[2]);
        }
    }

    public function onException($callId, $exceptionStr)
    {

    }
}

class __pinpoint_memcached_interceptor extends \Pinpoint\Interceptor
{
    var $apiId = -1;

    public $paramKey = [];

    public function __construct($api, $paramKey = [])
    {
        $this->apiId = pinpoint_add_api('Memcached::' . $api, -1);
        $this->paramKey = $paramKey;
    }

    public function onBefore($callId, $args)
    {
        $trace = pinpoint_get_current_trace();
        if (! $trace) {
            return;
        }

        if (__pinpoint_memcached_util::judgeIgnore($this->getSelf())) {
            return;
        }

        $event = $trace->traceBlockBegin($callId);
        $event->markBeforeTime();
        $event->setApiId($this->apiId);
        $event->setServiceType(__pinpoint_memcached_util::SERVICE_TYPE);
        $event->setDestinationId(__pinpoint_memcached_util::getDestByObj($this->getSelf()));

        if ($args and $this->paramKey) {
            $event->addAnnotation(
                PINPOINT_ANNOTATION_ARGS,
                __pinpoint_memcached_util::makeAnnotationArgs($this->paramKey, $args)
            );
        }
    }

    public function onEnd($callId, $data)
    {
        $trace = pinpoint_get_current_trace();
        if ($trace) {
            $event = $trace->getEvent($callId);
            if ($event) {
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

class __pinpoint_memcached_plugin extends \Pinpoint\Plugin
{
    public function __construct()
    {
        parent::__construct();

        $i = new __pinpoint_memcached_add_server_interceptor();
        $this->addInterceptor($i, 'Memcached::addServer', basename(__FILE__));

        $i = new __pinpoint_memcached_add_servers_interceptor();
        $this->addInterceptor($i, 'Memcached::addServers', basename(__FILE__));

        $i = new __pinpoint_memcached_interceptor('get', ['key', 'cache_cb', 'flags']);
        $this->addInterceptor($i, 'Memcached::get', basename(__FILE__));
        $i = new __pinpoint_memcached_interceptor('set', ['key', 'value', 'exp']);
        $this->addInterceptor($i, 'Memcached::set', basename(__FILE__));
        $i = new __pinpoint_memcached_interceptor('delete', ['key', 'time']);
        $this->addInterceptor($i, 'Memcached::delete', basename(__FILE__));
    }
}

