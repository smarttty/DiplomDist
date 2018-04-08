<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: X-Requested-With");
header('Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE');
include './config.php';
function rrmdir($src) {
    $dir = opendir($src);
    while(false !== ( $file = readdir($dir)) ) {
        if (( $file != '.' ) && ( $file != '..' )) {
            $full = $src . '/' . $file;
            if ( is_dir($full) ) {
                rrmdir($full);
            }
            else {
                unlink($full);
            }
        }
    }
    closedir($dir);
    rmdir($src);
}
class Building{
	public $id;
	public $name;
	public $floors;
}
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$name = urldecode($_POST["name"]);
	$sql = "SELECT * from buildings where Name='".$name."'";
	$rows = $conn->query($sql);
	if ($rows->num_rows >0){
		echo "Ошибка: Данная запись уже существует!";
	}
	else{
		$sql = "INSERT INTO buildings(name) VALUES ('".$name."')";
		if ($conn->query($sql) === TRUE) {
			$sql = "SELECT ID FROM buildings where Name='".$name."'";
			$result=$conn->query($sql);
			$id=-1;
			while ($row = $result->fetch_assoc()) {
				$id=$row["ID"];
			}
			if(!file_exists('./images/buldings/'.strval($id).'/')&& $id!=-1){
				mkdir("./images/buildings/".strval($id)."/",0700);
			}
    			echo "New record created successfully";
		}
		else {
    			echo "Error: " . $sql . "<br>" . $conn->error;
		}
	}
}
elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
	$sql = "select s1.ID,s1.Name,IFNULL(s2.floors,0) as floors  from buildings s1 LEFT JOIN (select BuildingID, count(*) as floors from floors GROUP BY BuildingID) s2 ON s1.ID = s2.BuildingID";
	$data = array();
	if($result = $conn->query($sql)){
		while ($row = $result->fetch_assoc()) {
			$building = new Building();
                        $building->id=$row["ID"];
                        $building->name=$row["Name"];
                        $building->floors=$row["floors"];
                        $data[] = $building;
    		}
		$data = json_encode($data, JSON_UNESCAPED_UNICODE);
		echo $data;
	}

}
elseif($_SERVER['REQUEST_METHOD'] === 'DELETE'){
	$buildingId=$_GET["id"];
	$sqlcheckoff = "SET FOREIGN_KEY_CHECKS=0;";
	$conn->query($sqlcheckoff);
	$sql="DELETE buildings, floors FROM buildings LEFT JOIN floors ON buildings.ID=floors.BuildingID where buildings.ID=".$buildingId;
	if($conn->query($sql)==TRUE){
		rrmdir("./images/buildings/".$buildingId);
		echo "Deleted";
	}
	else{
		echo "Error: " . $sql . "<br>" . $conn->error;
	}
	$sqlcheckon = "SET FOREIGN_KEY_CHECKS=1;";
	$conn->query($sqlcheckon);
}
 $conn->close();
?>
