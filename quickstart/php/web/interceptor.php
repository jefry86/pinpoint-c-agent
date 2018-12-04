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
$dsn = 'mysql:dbname=xin;host=172.16.98.17';
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
echo "PDOStatement::execute 2 done.<br/>";

$ch = curl_init();
echo "curl_init done.<br/>";

curl_setopt($ch, CURLOPT_URL, 'http://www.baidu.com');
echo "curl_setopt CURLOPT_URL done.<br/>";

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$output = curl_exec($ch);
curl_close($ch);
echo "curl_exec done.<br/>";







