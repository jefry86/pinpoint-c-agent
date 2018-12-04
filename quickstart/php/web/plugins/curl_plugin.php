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

class __pinpoint_curl_util
{
    const LIMIT = 2;

    const SAMPLE = 0;

    const MAX = 20;

    static protected $__optMap = [];

    static protected $__optConst = [];

    static public function setOpt($obj, $key, $val)
    {
        self::$__optMap[(string) $obj][$key] = $val;
    }

    static public function getOpt($obj, $key = null)
    {
        if ($key and isset(self::$__optMap[(string) $obj][$key])) {
            return self::$__optMap[(string) $obj][$key];
        }
        if (isset(self::$__optMap[(string) $obj])) {
            return self::$__optMap[(string) $obj];
        }
        return null;
    }

    static public function getDest($obj)
    {
        if (! isset(self::$__optMap[(string) $obj][CURLOPT_URL])) {
            return null;
        }

        $url_array = parse_url(self::$__optMap[(string) $obj][CURLOPT_URL]);

        if (empty($url_array)) {
            return null;
        }

        return $url_array['host'] . ':' . (empty($url_array['port']) ? 80 : $url_array['port']);
    }

    static public function judgeIgnore($dest)
    {
        if (isset(self::$__optConst[$dest])) {
            self::$__optConst[$dest] ++;
        } else {
            self::$__optConst[$dest] = 1;
        }

        if (self::LIMIT > 0) {
            return self::$__optConst[$dest] >= self::LIMIT;
        }

        if (self::SAMPLE > 0) {
            return (self::$__optConst[$dest] - 1) % self::SAMPLE != 0;
        }

        return false;
    }
}

class __pinpoint_curl_init_interceptor extends \Pinpoint\Interceptor
{
    var $apiId = -1;
    public function __construct()
    {
        $this->apiId = pinpoint_add_api('curl_init', -1);
    }

    public function onBefore($callId, $args)
    {

    }

    public function onEnd($callId, $data)
    {
        if (! empty($data['args'][0])) {
            __pinpoint_curl_util::setOpt($this->getSelf(), CURLOPT_URL, $data['args'][0]);
        }
    }

    public function onException($callId, $exceptionStr)
    {

    }
}

class __pinpoint_curl_setopt_interceptor extends \Pinpoint\Interceptor
{
    var $apiId = -1;
    public function __construct()
    {
        $this->apiId = pinpoint_add_api('curl_setopt', -1);
    }

    public function onBefore($callId, $args)
    {

    }

    public function onEnd($callId, $data)
    {
        if ($data['args'][0] and $data['args'][1] and $data['args'][2]) {
            __pinpoint_curl_util::setOpt($data['args'][0], $data['args'][1], $data['args'][2]);
        }
    }

    public function onException($callId, $exceptionStr)
    {

    }
}

class __pinpoint_curl_exec_interceptor extends \Pinpoint\Interceptor
{
    var $apiId = -1;
    public function __construct()
    {
        $this->apiId = pinpoint_add_api('curl_exec', -1);
    }

    public function onBefore($callId, $args)
    {
        if (empty($args[0])) return;

        $url = __pinpoint_curl_util::getDest($args[0]);
        if (empty($url)) return;

        if (__pinpoint_curl_util::judgeIgnore($url)) return;

        $trace = pinpoint_get_current_trace();

        $opt_header = __pinpoint_curl_util::getOpt($args[0], CURLOPT_HTTPHEADER);
        if (empty($opt_header) or ! is_array($opt_header)) {
            $opt_header = [];
        }

        if (! $trace) {
            $opt_header[] = PINPOINT_SAMPLE_HTTP_HEADER . ':' . PINPOINT_SAMPLE_FALSE;
            curl_setopt($args[0], CURLOPT_HTTPHEADER, $opt_header);
            return ;
        }

        $event = $trace->traceBlockBegin($callId);

        $span_id = -1;
        $attachedHeader = array();
        if($trace->getNextSpanInfo($attachedHeader, $span_id)) {
            $opt_header = array_merge($attachedHeader, $opt_header);
            $opt_header[] = 'Pinpoint-Host:' . $url;
            curl_setopt($args[0], CURLOPT_HTTPHEADER, $opt_header);
            $event->setNextSpanId($span_id);
        }

        $event->addAnnotation(PINPOINT_ANNOTATION_ARGS, json_encode(curl_getinfo($args[0], CURLINFO_EFFECTIVE_URL)));
        $event->markBeforeTime();
        $event->setApiId($this->apiId);
        $event->setServiceType(PINPOINT_PHP_REMOTE);
        $event->setDestinationId($url);
    }

    public function onEnd($callId, $data)
    {
        $trace = pinpoint_get_current_trace();
        if ($trace) {
            $retArgs = substr($data['result'], 0, __pinpoint_curl_util::MAX) . '...';
            $event = $trace->getEvent($callId);
            if ($event) {
                $event->addAnnotation(PINPOINT_ANNOTATION_RETURN, htmlspecialchars($retArgs, ENT_QUOTES));
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

class __pinpoint_curl_plugin extends \Pinpoint\Plugin
{
    public function __construct()
    {
        parent::__construct();
        $i = new __pinpoint_curl_setopt_interceptor();
        $this->addInterceptor($i, 'curl_setopt', basename(__FILE__));

        $i = new __pinpoint_curl_exec_interceptor();
        $this->addInterceptor($i, 'curl_exec', basename(__FILE__));

        $i = new __pinpoint_curl_init_interceptor();
        $this->addInterceptor($i, 'curl_init', basename(__FILE__));
    }
}
