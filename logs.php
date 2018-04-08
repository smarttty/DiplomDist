<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: X-Requested-With");
header('Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE');
include './config.php';
$conn = new mysqli($servername, $username, $password, $dblogname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$search_mac = $_GET["mac"];
$offset = $_GET["offset"];
$sql = "select DeviceReportedTime, message from SystemEvents where message RLIKE '".$search_mac."' ORDER BY DeviceReportedTime DESC limit 30 offset ".$offset;
$messages = array();
if($result = $conn->query($sql)){
	while($row = $result->fetch_assoc()){
		$messages[]=[$row["DeviceReportedTime"], $row["message"]];
	}
	$messages = json_encode($messages, JSON_UNESCAPED_UNICODE);
	echo $messages;
}
else{
	echo "Error: " . $sql . "<br>" . $conn->error;
}


