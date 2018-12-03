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
        if (empty($_SERVER[(string)$args[0]])) return;

        $params = $_SERVER[(string)$args[0]];

        if (empty($params[CURLOPT_HTTPHEADER])) return;

        $trace = pinpoint_get_current_trace();
        if (empty($trace)) {
            array_push($params[CURLOPT_HTTPHEADER], PINPOINT_SAMPLE_HTTP_HEADER . ':' . PINPOINT_SAMPLE_FALSE);
            curl_setopt($args[0], CURLOPT_HTTPHEADER, $params[CURLOPT_HTTPHEADER]);
        }

        $event = $trace->traceBlockBegin($callId);
        $attachedHeader = array();
        $spanid = -1;
        if($trace->getNextSpanInfo($attachedHeader, $spanid)) {
            $opt_header = array_merge($attachedHeader, $params[CURLOPT_HTTPHEADER]);
            if (!empty($params[CURLOPT_URL])) {
                array_push($opt_header, 'Pinpoint-Host:' . $params[CURLOPT_URL]);
                curl_setopt($args[0], CURLOPT_HTTPHEADER, $opt_header);
            }
            $event->setNextSpanId($spanid);
        }

        $event->addAnnotation(PINPOINT_ANNOTATION_ARGS,json_encode(curl_getinfo($args[0], CURLINFO_EFFECTIVE_URL)));
        $event->markBeforeTime();
        $event->setApiId($this->apiId);
        $event->setServiceType(PINPOINT_PHP_REMOTE);
        $event->setDestinationId($params[CURLOPT_URL]);
    }

    public function onEnd($callId, $data)
    {
        $trace = pinpoint_get_current_trace();
        if ($trace) {
            $args = $data['args'];
            $retArgs = $data['result'];
            $event = $trace->getEvent($callId);
            if ($event) {
                $event->addAnnotation(PINPOINT_ANNOTATION_RETURN, htmlspecialchars(print_r($retArgs,true), ENT_QUOTES));
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

class __pinpoint_curl_setopt_interceptor extends \Pinpoint\Interceptor
{
    var $apiId = -1;
    public function __construct()
    {
        $this->apiId = pinpoint_add_api('curl_setopt', -1);
    }

    public function onBefore($callId, $args)
    {
        if ($args[1] == CURLOPT_URL) {
            $trace = pinpoint_get_current_trace();
            if ($trace) {
                $event = $trace->traceBlockBegin($callId);
                $event->addAnnotation(PINPOINT_ANNOTATION_ARGS, $args[2]);
                $event->markBeforeTime();
                $event->setApiId($this->apiId);
                $event->setServiceType(PINPOINT_PHP_RPC_TYPE);
            }
            $url_array = parse_url($args[2]);

            $_SERVER[(string)$args[0]][$args[1]] = $url_array['host'] . ':' . (empty($url_array['port']) ? 80 : $url_array['port']);
        } else {
            $_SERVER[(string)$args[0]][$args[1]] = $args[2];
        }
    }

    public function onEnd($callId, $data)
    {
        $args = $data['args'];
        $retArgs = $data['result'];

        if ($args[1] != CURLOPT_URL) {
            return ;
        }
        $trace = pinpoint_get_current_trace();

        if ($trace) {
            $event = $trace->getEvent($callId);
            if ($event) {
                $event->addAnnotation(PINPOINT_ANNOTATION_RETURN, htmlspecialchars(print_r($retArgs, true),ENT_QUOTES));
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
        $i = new __pinpoint_curl_exec_interceptor();
        $this->addInterceptor($i, 'curl_exec', basename(__FILE__));
        $i = new __pinpoint_curl_setopt_interceptor();
        $this->addInterceptor($i, 'curl_setopt', basename(__FILE__));
    }
}
