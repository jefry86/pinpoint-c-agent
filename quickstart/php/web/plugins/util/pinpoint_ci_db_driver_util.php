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

include_once(dirname(__FILE__) . '/pinpoint_util.php');

class __pinpoint_ci_db_driver_util extends __pinpoint_util
{
    const DRIVER_PDO = 'pdo';
    const DRIVER_MYSQL = 'mysql';
    const DRIVER_MYSQLI = 'mysqli';

    const METHOD_SIMPLE_QUERY = 'simple_query';

    static public $__driver = [
        self::DRIVER_PDO => 'CI_DB_pdo_driver',
        self::DRIVER_MYSQL => 'CI_DB_mysql_driver',
        self::DRIVER_MYSQLI => 'CI_DB_mysqli_driver',
    ];

    static public function getDriverName($driver)
    {
        if (isset(static::$__driver[$driver])) {
            return static::$__driver[$driver];
        }
        return null;
    }

    static public function parseDsn($dsn)
    {
        $res = [
            'type' => null,
            'host' => null,
            'port' => null,
            'db' => null,
            'charset' => null,
            'unix_socket' => null
        ];

        $dsn = explode(':', $dsn);

        $res['type'] = $dsn[0];

        $dsn = explode(';', $dsn[1]);

        foreach ($dsn as $val)
        {
            $val = explode('=', $val);

            switch ($val[0])
            {
                case 'host' :
                    $res['host'] = $val[1]; break;
                case 'port' :
                    $res['port'] = $val[1]; break;
                case 'dbname' :
                    $res['db'] = $val[1]; break;
                case 'charset' :
                    $res['char'] = $val[1]; break;
                case 'unix_socket' :
                    $res['ux'] = $val[1]; break;
            }
        }

        return $res;
    }

    static public function getServiceType($obj, & $data = null)
    {
        $data = static::getConnectParam($obj);

        if ('mysql' == $data['type']) {
            return 2101;
        }

        return 2050;
    }

    static public function getConnectParam($obj)
    {
        $data = [
            'type' => null,
            'host' => $obj->hostname,
            'port' => $obj->port,
            'db' => $obj->database,
            'char' => $obj->char_set,
            'ux' => null,
        ];

        if (! empty($obj->dsn)) {
            $data = static::parseDsn($obj->dsn);
        } else if ($obj->dbdriver == static::DRIVER_PDO and strpos($obj->hostname, 'mysql') === FALSE) {
            $data['type'] = 'mysql';
        }

        $data['user'] = $obj->username;
        $data['pwd'] = $obj->password;

        return $data;
    }


}
