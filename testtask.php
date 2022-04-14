<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("testtask");


if (is_file($_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/vendor/autoload.php'))
{
    require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/vendor/autoload.php');
}

use Shuchkin\SimpleXLSX; //подключаем библиотеку для чтения xlsx файлов 

//подключение к базе данных mysqli для выполнения множественного запроса
$mysqli = new mysqli("localhost", "cu26205_db", "cu26205TheTest", "cu26205_db");

//Предварительные параметры необходимые при загрузки структуры компании
$ID_BLOCK=3;			  //ID инфолока с Оргструктурой
$SERVICEDEEP=4;			  //Глубина вложености службы
$UF_DEPARTMENT_ID=40;	  //ID Пользовательского поля Подразделений
$UF_TASK_COUNT=109;	  	  //ID Пользовательского поля Количество задач
$GROUP_ID=1;			  //В какую группу будем направлять созданного пользователя
$DATE_CREATE=time();      //Дата создания элементов в базе данных
$LeftMargin=1;			  //Начальная левая граница
$RightMargin=2;			  //Начальная правая граница
$Row=2;					  //Начальная строка для парсинга
$deep=0;				  //начальная глубина для парсинга
$Parent[0]=array('Моя компания','Моя компания');

?>
<div>
<?echo "<h3>Предположения принятые при решении задачи </h3>
<ul>
<li> 1. Организационная структура состоит только из самой организации. Проверок на существование отдела в процессе парсинга не проводися.</li>
<li> 2. Пользоветели, в файле для импорта по умолчанию новые и создаются в базе.</li>
<li> 3. Предполагается, что структура файла корректна и не делалем проверку.</li>
<li> 4. Для  хранения количества задач было создано пользовательское поле UF_TASK_COUNT.</li>

</ul>"
	?>
</div>
<div>
	<h1>Тестирование функционала</h1>

<p> Файл для парсинга  /upload/data1.xlsx  </p>
<form action="" method="post">
    <input type="submit" name="parse" value="Зачитать данные с файла" />
</form>
<br>
<form action="" method="post">
    <input type="submit" name="delete" value="Удалить тестовые данные" />
</form>
<br>
<form action="" method="post">
    <input type="submit" name="json" value="Получить Json ответ" />
</form>
<br>
<form action="" method="post">
    <input type="submit" name="getSQL" value="Показать SQL запрос" />
</form>
<br>
<form action="" method="post">
    <input type="submit" name="clear" value="Очистить запрос" />
</form>
<?

function vivod($txt){
echo '<pre>';
print_r($txt);
echo '</pre>';	
}

//Запрос на удаление тестовых данных
function clearTestDataSQLQuery($mysqli){
	$QUERYDEL="DELETE FROM b_utm_user WHERE ID<>1;".PHP_EOL;
	$QUERYDEL.="DELETE FROM  b_uts_user WHERE VALUE_ID<>1 AND VALUE_ID<>2;".PHP_EOL;
	$QUERYDEL.="DELETE FROM  b_user WHERE ID<>1 AND ID<>2;".PHP_EOL;
	$QUERYDEL.="DELETE FROM  b_iblock_section WHERE ID<>1 AND IBLOCK_ID=3;".PHP_EOL;
	$result = $mysqli->multi_query($QUERYDEL);
}

//Запрос на создание Элемента Структуры
function creatDepartmentSQLQuery($SQLString,$ID_BLOCK){
  	$DATE_CREATE=time();

    $Query="INSERT INTO b_iblock_section (`MODIFIED_BY`, `DATE_CREATE`, `CREATED_BY`, `IBLOCK_ID`, `IBLOCK_SECTION_ID`, `ACTIVE`, `GLOBAL_ACTIVE`, `NAME`, `LEFT_MARGIN`, `RIGHT_MARGIN`, `DEPTH_LEVEL`, `DESCRIPTION`, `SEARCHABLE_CONTENT`)
		SELECT   '1', $DATE_CREATE, '1', '$ID_BLOCK', ID, 'Y', 'Y', '$SQLString[0]', '$SQLString[1]', '$SQLString[2]', '$SQLString[4]','' , '$SQLString[0]' FROM b_iblock_section WHERE b_iblock_section.NAME='$SQLString[5]' AND b_iblock_section.RIGHT_MARGIN>'$SQLString[2]';".PHP_EOL;
		return $Query;
  }

//Запрос на создание Пользователя, Добвление пользователя в группу, Добавление Пользователя в Элемент Структуры, Добавление Пользовательского поля Количество задач
function creatEmploySQLQuery($SQLString,$UF_DEPARTMENT_ID,$UF_TASK_COUNT,$GROUP_ID){
  	$DATE_CREATE=time();

		$Query="INSERT INTO b_user ( `NAME` ,`EMAIL`, `LOGIN`, `ACTIVE`, `PASSWORD`)
 		SELECT   '$SQLString[3]', '$SQLString[5]', '$SQLString[4]', 'Y', '$SQLString[4]';".PHP_EOL;
		$Query.= "INSERT INTO b_user_group (`USER_ID`, `GROUP_ID`)
		SELECT    ID, '$GROUP_ID' FROM b_user WHERE  LOGIN='$SQLString[4]';".PHP_EOL;
		$Query.="INSERT INTO b_utm_user (`VALUE_ID`, `FIELD_ID`, `VALUE`)
 					SELECT   b_user.ID, '$UF_TASK_COUNT',  $SQLString[1]
 					FROM b_user
					WHERE    b_user.NAME='$SQLString[3]';".PHP_EOL;
		$Query.="INSERT INTO b_utm_user (`VALUE_ID`, `FIELD_ID`, `VALUE_INT`)
 		SELECT   b_user.ID, '$UF_DEPARTMENT_ID', b_iblock_section.ID FROM b_user,b_iblock_section 
		WHERE (b_iblock_section.NAME='$SQLString[0]' AND b_iblock_section.RIGHT_MARGIN=$SQLString[2]) AND  b_user.NAME='$SQLString[3]';".PHP_EOL;
		$Query.="INSERT INTO b_uts_user (`VALUE_ID`, `UF_DEPARTMENT`)  
 		SELECT   b_user.ID, CONCAT('a:1:{i:0;i:', b_iblock_section.ID,';}') FROM b_user,b_iblock_section 
		WHERE (b_iblock_section.NAME='$SQLString[0]' AND b_iblock_section.RIGHT_MARGIN=$SQLString[2]) AND  b_user.NAME='$SQLString[3]';".PHP_EOL;
		
		return $Query;
  }

 //Запрос на  Добавление Пользователя в дополнительные Элементы Структуры, Добавление Пользовательского поля Количество задач
function updateEmploySQLQuery($SQLString,$UF_DEPARTMENT_ID,$UF_TASK_COUNT,$CountDepart){
  	$DATE_CREATE=time();
		$Query.="INSERT INTO b_utm_user (`VALUE_ID`, `FIELD_ID`, `VALUE`)
 					SELECT   b_user.ID, '$UF_TASK_COUNT',  $SQLString[1]
 					FROM b_user
					WHERE    b_user.NAME='$SQLString[3]';".PHP_EOL;
		$Query.="INSERT INTO b_utm_user (`VALUE_ID`, `FIELD_ID`, `VALUE_INT`)
 		SELECT   b_user.ID, '$UF_DEPARTMENT_ID', b_iblock_section.ID FROM b_user,b_iblock_section 
		WHERE (b_iblock_section.NAME='$SQLString[0]' AND b_iblock_section.RIGHT_MARGIN=$SQLString[2]) AND  b_user.NAME='$SQLString[3]';".PHP_EOL;
		$Query.="UPDATE b_uts_user 
						LEFT JOIN b_user ON b_user.ID=b_uts_user.VALUE_ID 
						SET  UF_DEPARTMENT =	REPLACE(REPLACE(b_uts_user.UF_DEPARTMENT, CONCAT('a:',$CountDepart-1),CONCAT('a:',$CountDepart)),'}',CONCAT('i:',$CountDepart-1,';i:',(SELECT  b_iblock_section.ID FROM b_iblock_section WHERE (b_iblock_section.NAME='$SQLString[0]' AND b_iblock_section.RIGHT_MARGIN=$SQLString[2])),';}')) 
						WHERE b_user.NAME='$SQLString[3]';".PHP_EOL;
		return $Query;
}

//Перевод Фио на латиницу для  Логина, Пароля и e-mail при создании пользователя
function TranslitName($TheName){
	$arParams = array("replace_space"=>"_","replace_other"=>"_");
	return Cutil::translit($TheName,"ru",$arParams);
}

/*
 *  Рекурсивная функция проходит по всем строкам и заполняет подготовительный массив итогового заапроса

$parent 		 - массив [0] содержит название=номер строки для дальнейшего обращения к нужному элементу итогового запроса, [1] содержит название родителя
$LeftMargin  - отслеживает положение левой границы элемента структуры
$RightMargin - отслеживает положение правой границы элемента структуры
$deep 			 - глубина вложенности элементов структуры
$Row 				 - текущая строка массива полученного из файла excel
$Struction   - массив полученный из файла excel
$SQLQuery 	 - результирующий массив для формирования SQL запроса два вида
	Первый: 																																																								   Образец элемента
		В качестве ключа  массива: Название элемента+номерстроки									[Дочернее14] => Array
		[0] - название текущего элемента структуры														[0] => Дочернее1
		[1] - левая граница элемента структуры															[1] => 3
		[2] - правая граница элемента структуры															[2] => 8
		[3] - Название родителя+строка, для поиска и корректировки правой границы родителя				[3] => Первое3
		[4] - глубина вложенности элемента структуры													[4] => 3
		[5] - название родителя																			[5] => Первое
	Второй
																									    Образец элемента
		В качестве ключа  массива: Строка содержащяя "Сотрудник"+номер строки						 [Сотрудник5] => Array
		[0] - название элемента структуры	кому принадлежит сотрудник		      						[0] => Дочернее 1.2
		[1] - количество задачи																			[1] => 0.1
		[2] - правая граница элемента структуры	кому принадлежит сотрудник							    [2] => 7
		[3] - фио сотрудника 																			[3] => Игнатев Д.С.
		[4] - логин сотрудника для пегистрации															[4] => ignatev_d_s
		[5] - e-mail сотрудника для регистрации															[5] => ignatev_d_s@Hoho.com
*/
/**
 * @param array $parent
 * @param int $LeftMargin
 * @param int $RightMargin
 * @param array $deep
 * @param int $Row 
 * @param array $Struction
 * @param array $SQLQuery 
*/
function parsestruction(&$parent, &$LeftMargin, &$RightMargin,$deep, &$Row, $Struction,&$SQLQuery){

	while ($Struction[$Row][$deep]) { 
		$CurrentDeparent=$Struction[$Row][$deep];
		$LeftMargin+=1;			
		$RightMargin+=1;
		if ($CurrentDeparent && $Struction[$Row][4])  {
			$SQLQuery[$CurrentDeparent.$Row]=array($CurrentDeparent,$LeftMargin,$RightMargin,$parent[$deep][0],$deep+2,$parent[$deep][1]);
			$SQLQuery['Сотрудник'.$Row]=array($CurrentDeparent,$Struction[$Row][5],$RightMargin,$Struction[$Row][4], TranslitName($Struction[$Row][4]),TranslitName($Struction[$Row][4]).'@Hoho.com');
			$Row+=1;
			while ($Struction[$Row][$deep] && $Struction[$Row][4]) {
				$LeftMargin=$RightMargin+1;			
				$RightMargin+=2;
				$SQLQuery[$Struction[$Row][$deep].$Row]=array($Struction[$Row][$deep],$LeftMargin,$RightMargin,$parent[$deep][0],$deep+2,$parent[$deep][1]);
				$SQLQuery['Сотрудник'.$Row]=array($Struction[$Row][$deep],$Struction[$Row][5],$RightMargin,$Struction[$Row][4], TranslitName($Struction[$Row][4]),TranslitName($Struction[$Row][4]).'@Hoho.com');
				$Row+=1;
			}
		}
		else{
			$parent[$deep+1]=array($CurrentDeparent.($Row+1),$CurrentDeparent);
			$Savedeep=$deep;
			$deep+=1;
			$Row+=1;
			$SQLQuery[$CurrentDeparent.$Row]=array($CurrentDeparent,$LeftMargin,$RightMargin,$parent[$Savedeep][0],$deep+1,$parent[$Savedeep][1]);

			parsestruction($parent,$LeftMargin, $RightMargin, $deep,$Row, $Struction,$SQLQuery);
		}

		if (isset($Savedeep)){
	   	while (!$Struction[$Row][$deep] && $deep>$Savedeep){		  
	 		  	$SQLQuery[$parent[$deep][0]][2]=$RightMargin; 
					$deep=$deep-1;
	   		}
		}
		$LeftMargin=$RightMargin;		
		$RightMargin+=1;	
 }     	
}

//Отрабатываю кнопку на удаление тестовых данных
if(isset($_POST["delete"])){
	clearTestDataSQLQuery($mysqli);
	header("location:http://cu26205.tmweb.ru/company/vis_structure.php");
	exit();
}

//Отрабатываю кнопку на парсинг и отображение Запроса тестовых данных
if (isset($_POST["parse"]) || isset($_POST["getSQL"])){
	if ( $xlsx = SimpleXLSX::parse(__DIR__.'/upload/data1.xlsx') ) {
		$Struction=$xlsx->rows();
		parsestruction($Parent,$LeftMargin, $RightMargin,$deep, $Row, $Struction,$SQLQuery);
		$MainRightMargin=$RightMargin;
		//vivod($SQLQuery);
		$existemploer=array();
		$Query='START TRANSACTION;'.PHP_EOL;
		$Query.= "UPDATE b_iblock_section SET  RIGHT_MARGIN=$MainRightMargin WHERE ID=1;".PHP_EOL;
		foreach ($SQLQuery as $key => $SQLString) {
			if (strpos($key, 'Сотрудник') === false) {
			//continue;
			$Query.=creatDepartmentSQLQuery($SQLString,$ID_BLOCK);
			}
			else{
				if (array_key_exists($SQLString[3], $existemploer)){
			 		$existemploer[$SQLString[3]]+=1;
			 		$Query.=updateEmploySQLQuery($SQLString,$UF_DEPARTMENT_ID,$UF_TASK_COUNT,$existemploer[$SQLString[3]]);
				}
				else{
					$existemploer[$SQLString[3]]=1;
					$Query.=creatEmploySQLQuery($SQLString,$UF_DEPARTMENT_ID,$UF_TASK_COUNT,$GROUP_ID);
			 }
			}
		}
		$Query.='COMMIT;';
		if (isset($_POST["getSQL"])){
			vivod($Query);	
		}
		else{
			$result = $mysqli->multi_query($Query);
			header("location:http://cu26205.tmweb.ru/company/vis_structure.php");
			exit();
		}
		
	} else {
		echo SimpleXLSX::parseError();
	}

}
//Очищаем после вывода запроса
if(isset($_POST["clear"])){
	header("location:" . $_SERVER['PHP_SELF']);
	exit();
}

//Переадресация на получение JSON ответа
if(isset($_POST["json"])){
header("location:http://cu26205.tmweb.ru/testjson.php");
exit();
}


?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>