<?php 
require_once("ErrorManager.class.php");
require_once("Mysql.class.php"); 

function daddslashes($string, $force = 0) {
   if(!$GLOBALS['magic_quotes_gpc'] || $force) {
     if(is_array($string)) {
       foreach($string as $key => $val) {
         $string[$key] = daddslashes($val, $force);
       }
     } else {
       $string = addslashes($string);
     }
   }
   return $string;
}

function tdo(){

try { // <<<<-------------- try

	$mysql = new Mysql();
	$mysql->connect("xxer2info2wp.db.8339784.hostedresource.com", "xxer2info2wp", "xxer2info2wp", "xxer@Ftp28"); // change this line here

	$query = "select sid from `crontab` ORDER BY `sid` DESC limit 1"; // and table name here
	
	$result = $mysql->query($query);

	$r=$mysql->fetchAll($result);
	
	$sid=intval($r[0]['sid'])+1;
	
	$get=json_decode(file_get_contents('http://www.xxer.info/tools/index.php/api/wp?sid='.$sid));
	//print_r($get);die;
	
	if(isset($get->status)&&isset($get->m)){
		$mysql->query(
"INSERT INTO `crontab` (
`id` ,
`sid` ,
`status` ,
`m` ,
`error` ,
`time`
)
VALUES (
NULL , ".daddslashes($sid).", '".daddslashes($get->status)."', '".daddslashes($get->m)."', '',
CURRENT_TIMESTAMP
);");
		echo $get->m.' post sid::'.$sid;
		if($get->status==true){
			return true;
		}
	}else{
		$mysql->query(
"INSERT INTO `crontab` (
`id` ,
`sid` ,
`status` ,
`m` ,
`error` ,
`time`
)
VALUES (
NULL , ".daddslashes($sid).", '', '', '".daddslashes(serialize($get))."',
CURRENT_TIMESTAMP
);");
		echo 'post sid::'.$sid.' error';		
	}
	
	return false;

} catch (Exception $e) { // <<<<-------------- catch 
	
	$mysql->query(
"INSERT INTO `crontab` (
`id` ,
`sid` ,
`status` ,
`m` ,
`error` ,
`time`
)
VALUES (
NULL , ".daddslashes($sid).", '', '', '".daddslashes(serialize(ErrorManager::getInstance()->getErrors()))."',
CURRENT_TIMESTAMP
);");
	echo 'post sid::'.$sid.' some error';
	return false;
	
}



$i=1;
while ($i <= 5) {
	if(tdo()){
		break;
	}	
	$i++;
}

}

?>