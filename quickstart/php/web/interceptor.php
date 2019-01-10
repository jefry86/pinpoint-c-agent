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

//pdo
/*$dsn = 'mysql:dbname=xin;host=172.16.98.17';
$user = 'xin';
$password = '48sdf37EB7';

$dbh = new PDO($dsn, $user, $password);
echo "PDO::__construct done.<br/>";

$count = $dbh->exec('UPDATE `city_copy` SET `shortname`="BJ" WHERE `shortname`="BJ"');
echo "PDO::exec 1 done.<br/>";

$count = $dbh->exec('UPDATE `city_copy` SET `shortname`="AA" WHERE `shortname`="AA"');
echo "PDO::exec 2 done.<br/>";

$sth = $dbh->query('SELECT * FROM `city_copy` WHERE `shortname`="BJ"');
echo "PDO::query 1 done.<br/>";

$sth = $dbh->query('SELECT * FROM `city_copy` WHERE `shortname`="AA"');
echo "PDO::query 2 done.<br/>";

$sth = $dbh->prepare('SELECT * FROM `city_copy` WHERE `shortname`=:sn', array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
echo "PDO::prepare done.<br/>";

$sth->execute(array(':sn' => 'BJ'));
echo "PDOStatement::execute 1 done.<br/>";

$sth->execute(array(':sn' => 'BJ'));
echo "PDOStatement::execute 2 done.<br/>";*/

//mysql
/*$link = mysql_connect('172.16.98.17:3306', 'xin', '48sdf37EB7');
echo "mysql_connect done.<br/>";

mysql_select_db ('xin', $link);
echo "mysql_select_db done.<br/>";

mysql_query('SELECT * FROM `city_copy` WHERE `shortname`="BJ"', $link);
echo "mysql_query done.<br/>";

mysql_close($link);*/

//mysqli
/*$mysqli = new mysqli('172.16.98.17:3306', 'xin', '48sdf37EB7', 'xin');
echo "mysqli::__construct done.<br/>";

$mysqli->query('SELECT * FROM `city_copy` WHERE `shortname`="BJ"');
echo "mysqli::query done.<br/>";

$stmt = $mysqli->prepare('SELECT * FROM `city_copy` WHERE `shortname`=?');
echo "mysqli::prepare done.<br/>";

$city = 'AA';
$stmt->bind_param('s', $city);
echo "mysqli_stmt::bind_param done.<br/>";

$stmt->execute();
echo "mysqli_stmt::execute done.<br/>";*/

//mysql func
/*$link = mysqli_connect("172.16.98.17:3306", "xin", "48sdf37EB7", "xin");
echo "mysqli_connect done.<br/>";

mysqli_query($link, 'SELECT * FROM `city_copy` WHERE `shortname`="BJ"');
echo "mysqli_query done.<br/>";

$stmt = mysqli_prepare($link, 'SELECT * FROM `city_copy` WHERE `shortname`=?');
echo "mysqli_prepare done.<br/>";

mysqli_stmt_bind_param($stmt, "s", $city);
echo "mysqli_stmt_bind_param done.<br/>";

mysqli_stmt_execute($stmt);
echo "mysqli_stmt_execute done.<br/>";*/

//curl
$ch = curl_init();
echo "curl_init done.<br/>";

curl_setopt($ch, CURLOPT_URL, 'http://www.baidu.com');
echo "curl_setopt CURLOPT_URL done.<br/>";

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$output = curl_exec($ch);
curl_close($ch);
echo "curl_exec done.<br/>";

//redis
$redis = new Redis();
$redis->pconnect('172.16.98.12', 6379, 2.5);
echo "Redis::pconnect done.<br/>";

$redis->set('__pinpoint_redis_test',1, 1800);
echo "Redis::set done.<br/>";

$redis->get('__pinpoint_redis_test');
echo "Redis::get done.<br/>";

$redis->incr('__pinpoint_redis_test');
echo "Redis::incr done.<br/>";

$redis->decr('__pinpoint_redis_test');
echo "Redis::decr done.<br/>";

$redis->delete('__pinpoint_redis_test');
echo "Redis::delete done.<br/>";

//memcached
$mc = new Memcached();
$mc->addServer('localhost', 11211);
echo "Memcached::addServer done.<br/>";

$mc->set('foo', 'Hello!');
echo "Memcached::set done.<br/>";

$mc->get('foo');
echo "Memcached::get done.<br/>";

$mc->delete('foo');
echo "Memcached::delete done.<br/>";


