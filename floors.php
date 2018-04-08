<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: X-Requested-With");
header('Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE');
include './config.php';
class Floor{
	public $id;
	public $name;
	public $h_url;
	public $p_url;
	public $level;
	public $buildingId;
}

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$name = $_POST["Name"];
	$buildingId = $_POST["BuildingID"];
	$level=$_POST["Level"];
	$uploadUrl = "./images/buildings/".$buildingId."/".$level.".png";	
	$img = $_POST["Image"];
	$width = $_POST["Width"];
	$length = $_POST["Length"];
	$height = $_POST["Height"];
	$pointsurl = './params/buildings/'.$buildingId."/".$level."_points.php";
	$sql = "SELECT * from floors where Level=".$level." AND BuildingID=".$buildingId;
	$rows = $conn->query($sql);
	$sqlupcheck = "SELECT * from floors where Level=".($level+1)." AND BuildingID=".$buildingId;
	$sqldowncheck = "SELECT * from floors where Level=".($level-1)." AND BuildingID=".$buildingId;
	$d_res = $conn->query($sqldowncheck);
	$row = $d_res->fetch_assoc();
	$down_id = $row["ID"];
	$u_res = $conn->query($sqlupcheck);
	$row = $u_res->fetch_assoc();
	$up_id = $row["ID"];	
	if ($rows->num_rows >0){
		echo "Ошибка: Данная запись уже существует!";
	}
	else{
		if (preg_match('/^data:image\/(\w+);base64,/', $img, $type)) {
    			$img = substr($img, strpos($img, ',') + 1);
    			$type = strtolower($type[1]); // jpg, png, gif

    			if (!in_array($type, [ 'jpg', 'jpeg', 'gif', 'png' ])) {
        			throw new \Exception('invalid image type');
    			}
    			$img = base64_decode($img);

    			if ($img === false) {
        			throw new \Exception('base64_decode failed');
    			}
		}
		else {
    			throw new \Exception('did not match data URI with image data');
		}
		file_put_contents($uploadUrl, $img);
		if($up_id && $down_id){
					$sql = "INSERT INTO floors(Name,ImageUrl,BuildingID,Level, PointsUrl, Width, Height, Length, UpFloor, DownFloor) VALUES ('".$name."','".$uploadUrl."',".$buildingId.",".$level.", '".$pointsurl."', ".$width.", ".$height.", ".$length.", ".$up_id.", ".$down_id.")";
		}
		elseif($up_id){
					$sql = "INSERT INTO floors(Name,ImageUrl,BuildingID,Level, PointsUrl, Width, Height, Length, UpFloor) VALUES ('".$name."','".$uploadUrl."',".$buildingId.",".$level.", '".$pointsurl."', ".$width.", ".$height.", ".$length.", ".$up_id.")";		
		}
		elseif($down_id){
					$sql = "INSERT INTO floors(Name,ImageUrl,BuildingID,Level, PointsUrl, Width, Height, Length, DownFloor) VALUES ('".$name."','".$uploadUrl."',".$buildingId.",".$level.", '".$pointsurl."', ".$width.", ".$height.", ".$length.", ".$down_id.")";		
		}
		else{
			$sql = "INSERT INTO floors(Name,ImageUrl,BuildingID,Level, PointsUrl, Width, Height, Length) VALUES ('".$name."','".$uploadUrl."',".$buildingId.",".$level.", '".$pointsurl."',".$width.", ".$height.", ".$length.")";		
		}

		if ($conn->query($sql) === TRUE) {

			$sqlid="SELECT ID FROM floors where BuildingID=".$buildingId." AND Level=".$level;
			if($res = $conn->query($sqlid)){
				$row = $res->fetch_assoc();
				$id=$row["ID"];	
				$sqlup = "UPDATE floors SET Upfloor=".$id." WHERE Level=".($level-1);
				$sqldown = "UPDATE floors SET DownFloor=".$id." WHERE Level=".($level+1);
				if($conn->query($sqlup)===TRUE && $conn->query($sqldown)===TRUE){
					echo "New record created successfully";	
				}
				else{
					echo "Error: " . $sql . "<br>" . $conn->error;
				}
			}
			else{
				echo "Error: " . $sql . "<br>" . $conn->error;			
			}

		}
		else {
    			echo "Error: " . $sql . "<br>" . $conn->error;
		}
	}
}
elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
	$sql = "SELECT * FROM floors";
	$data = array();
	if($result = $conn->query($sql)){
		while ($row = $result->fetch_assoc()) {
			$floor = new Floor();
			$floor->id=$row["ID"];
			$floor->name=$row["Name"];
			$floor->h_url='none';
			$floor->p_url=$row["ImageUrl"];
			$floor->level=$row["Level"];
			$floor->buildingId=$row["BuildingID"];
			$data[] = $floor;
    		}
		$data = json_encode($data, JSON_UNESCAPED_UNICODE);
		echo $data;
	}

}
elseif($_SERVER['REQUEST_METHOD'] === 'DELETE'){
	$link=$_GET["link"];
	$floorId=$_GET["id"];
	$sqlclearUp = "UPDATE floors SET Upfloor=NULL where UpFloor=".$floorId;
	$sqlclearDown = "UPDATE floors SET Downfloor=NULL where DownFloor=".$floorId;
	$conn->query($sqlclearUp);
	$conn->query($sqlclearDown);
	$sql="DELETE FROM floors where ID=".$floorId;
	if($result = $conn->query($sql)){
		unlink($link);
		echo "Deleted";
	}
	else{
		echo "Error: " . $sql . "<br>" . $conn->error;
	}
}
$conn->close();
?>
