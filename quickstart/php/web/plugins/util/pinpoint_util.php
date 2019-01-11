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

class __pinpoint_util
{
    const LIMIT = 2;

    const SAMPLE = 0;

    const MAX = 100;

    static protected $__handleMap = [];

    static protected $__handleConst = [];

    static public function serializeObj($obj)
    {
        ob_start();
        var_dump($obj);
        $content = ob_get_clean();
        return substr($content, 0, 50);
    }

    static public function getMaxTxt($string)
    {
        if (strlen($string) > self::MAX) {
            return substr($string, 0, self::MAX) . '...';
        }
        return $string;
    }

    static public function getServiceType()
    {
        return PINPOINT_PHP_REMOTE;
    }

    static public function now()
    {
        list($usec, $sec) = explode(' ', microtime());
        return strval((float) $usec + (float) $sec);
    }

    static public function makeDataMap($keyMap, $data)
    {
        $tmp = [];
        foreach ($keyMap as $argsKey => $strKey)
        {
            if (isset($args[$argsKey])) {
                $tmp[$strKey] = $data[$argsKey];
            }
        }
        return $tmp;
    }

    static public function decompDataMap($map)
    {
        return ['key' => array_keys($map), 'val' => array_values($map)];
    }

    static public function makeAnnotationArgs($keyMap, $args)
    {
        return json_encode(static::makeDataMap($keyMap, $args));
    }

    static public function getDest($keyMap, $param)
    {
        $res = [];
        $tmp = static::makeDataMap($keyMap, $param);
        foreach ($tmp as $key => $val)
        {
            if (empty($val)) continue;
            $res[] = $key . ':' . $val;
        }
        if (empty($res)) {
            return 'N/A';
        }
        return implode("\n", $res);
    }

    static public function setHandle($obj, $keyMap, $param, $parent = null)
    {
        $data = static::makeDataMap($keyMap, $param);
        $data['parent'] = $parent;
        static::$__handleMap[static::serializeObj($obj)] = $data;
    }

    static public function getHandle($obj)
    {
        if (isset(static::$__handleMap[static::serializeObj($obj)])) {
            return static::$__handleMap[static::serializeObj($obj)];
        }
        return null;
    }

    static public function getParentHandle($obj)
    {
        if (! empty(static::$__handleMap[static::serializeObj($obj)]['parent'])) {
            return static::$__handleMap[static::serializeObj($obj)]['parent'];
        }
        return null;
    }

    static public function getRootHandle($obj)
    {
        $res = null;
        while ($parent = static::getParentHandle($obj))
        {
            $obj = $res = $parent;
        }
        return $res;
    }

    static public function updateHandleData($obj, $key, $val)
    {
        static::$__handleMap[static::serializeObj($obj)][$key] = $val;
    }

    static public function mergeDataData($obj, $key, $val)
    {
        if (empty(static::$__handleMap[static::serializeObj($obj)][$key])) {
            static::updateHandleData($obj, $key, $val);
        }

        $dest = static::$__handleMap[static::serializeObj($obj)][$key];

        if (! is_array($dest)) {
            $dest = [$dest];
        }

        if (! is_array($val)) {
            $val = [$val];
        }

        static::$__handleMap[static::serializeObj($obj)][$key] = array_merge($dest, $val);
    }

    static public function judgeIgnore($obj)
    {
        if (isset(self::$__handleConst[self::serializeObj($obj)])) {
            self::$__handleConst[self::serializeObj($obj)] ++;
        } else {
            self::$__handleConst[self::serializeObj($obj)] = 1;
        }

        if (self::LIMIT > 0) {
            return self::$__handleConst[self::serializeObj($obj)] > self::LIMIT;
        }

        if (self::SAMPLE > 0) {
            return (self::$__handleConst[self::serializeObj($obj)] - 1) % self::SAMPLE != 0;
        }

        return false;
    }
}
