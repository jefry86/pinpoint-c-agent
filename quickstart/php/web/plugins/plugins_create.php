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

//   include_once("quickstart_plugin_bak.php");

$path=dirname(__FILE__);
foreach (glob($path ."/*plugin.php") as  $value) {
    include_once($value);
}

//$p = new QuickStartPlugin();
//pinpoint_add_plugin($p, "quickstart_plugin_bak.php");

$p = new __pinpoint_curl_plugin();
pinpoint_add_plugin($p, 'curl_plugin.php');

$p = new __pinpoint_memcached_plugin();
pinpoint_add_plugin($p, 'memcached_plugin.php');

//$p = new __pinpoint_pdo_plugin();
//pinpoint_add_plugin($p, 'pdo_plugin.php');

$p = new __pinpoint_redis_plugin();
pinpoint_add_plugin($p, 'redis_plugin.php');

//$p = new __pinpoint_mysqli_plugin();
//pinpoint_add_plugin($p, 'mysqli_plugin.php');

//$p = new __pinpoint_mysqli_func_plugin();
//pinpoint_add_plugin($p, 'mysqli_func_plugin.php');

//$p = new __pinpoint_mysql_plugin();
//pinpoint_add_plugin($p, 'mysql_plugin.php');

$p = new __pinpoint_ci_db_driver_plugin();
pinpoint_add_plugin($p, 'ci_db_driver_plugin.php');

?>