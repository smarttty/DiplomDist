<?php
/**
 * Created by PhpStorm.
 * User: lev
 * Date: 14.01.18
 * Time: 20:22
 */;
header("Access-Control-Allow-Origin: *");
include './config.php';
$inf = snmp2_walk($controller_ip, 'levk', '1.3.6.1.4.1.14179.2.2.2.1');
$data = json_encode($inf);
echo $data;

