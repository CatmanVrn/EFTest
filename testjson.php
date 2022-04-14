<?
$SERVICEDEEP=4;
$UF_TASK_COUNT=109;
$mysqli = new mysqli("localhost", "cu26205_db", "cu26205TheTest", "cu26205_db");
$Query1=" SELECT b_user.NAME, b_iblock_section.NAME as Section
 					 FROM b_utm_user  
					 INNER JOIN b_user ON b_user.ID=b_utm_user.VALUE_ID
					 INNER JOIN b_iblock_section ON b_iblock_section.ID=b_utm_user.VALUE_INT
					 WHERE (b_iblock_section.DEPTH_LEVEL=$SERVICEDEEP)
					 UNION ALL
					 SELECT UName, b_iblock_section.NAME as Section
					 FROM b_iblock_section 
					 INNER JOIN (SELECT b_user.NAME UName, b_iblock_section.IBLOCK_SECTION_ID Parent_ID
					 						 FROM b_utm_user  
					 						 INNER JOIN b_user ON b_user.ID=b_utm_user.VALUE_ID
					 						 INNER JOIN b_iblock_section ON b_iblock_section.ID=b_utm_user.VALUE_INT
											 WHERE (b_iblock_section.DEPTH_LEVEL=$SERVICEDEEP+1))AS C ON C.Parent_ID =b_iblock_section.ID";

$res = $mysqli->query($Query1);
while ($line=$res->fetch_array())
$ResultArr[$line['NAME']]['depart'][]=$line['Section'];

$Query1="SELECT b_user.NAME, SUM(b_utm_user.VALUE) as Summ
 					 FROM b_utm_user  
					 INNER JOIN b_user ON b_user.ID=b_utm_user.VALUE_ID
		 WHERE b_utm_user.FIELD_ID=$UF_TASK_COUNT 
		 GROUP BY b_user.NAME";
$res = $mysqli->query($Query1);

while ($count=$res->fetch_array())
$ResultArr[$count['NAME']]['summ']=number_format($count['Summ'], 2);
//vivod($ResultArr);

$JsonArray = array();
foreach ($ResultArr as $Consult =>$Data) {

    $JsonArray[] = array(
        "consult"=>array(
        	"name"=>$Consult,
        	"department"=>$Data['depart'],
        	"task_count"=>$Data['summ']
    ));
}
 header('Content-type: application/json');
 echo json_encode($JsonArray, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK); 
//header("location:" . $_SERVER['PHP_SELF']);
//exit();






?>
