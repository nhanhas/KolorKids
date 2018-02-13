<?php
error_reporting(E_ERROR | E_PARSE);

require('libs/excel-reader/php-excel-reader/excel_reader2.php');
require('libs/excel-reader/SpreadsheetReader.php');

//Define constants
define("excelFile", 'LegoWear-PS18.xlsx'); //Index of Excel Sheet to Read
define("sheetToRead", 4); //Index of Excel Sheet to Read
define("backendUrl", "https://sis05.drivefx.net/D265FFB9/PHCWS/REST");//TODO change for client
$_SESSION['driveCredentials'] = array(
	userCode=>"suporte",
	password=>"12345678",
	applicationType=>"HYU45F-FKEIDD-K93DUJ-ALRNJE",
	company=>""
);


$ch = curl_init();

//First Login at Drive
$loginResult = DRIVE_userLogin();
if($loginResult == false){
	$msg = "Error on Drive Login.<br>";
	echo $msg;
	logData($msg);
	exit(1);
}

$productsToJson = array();

$productsList = DRIVE_getProductList();
foreach($productsList as $product){
    $ref_base = $product['u6526_st_extra_fields']['ref_base'];


    if(!array_key_exists($ref_base, $productsToJson)){   

        $aux = array(  $ref_base => array(           $product         ));
        $productsToJson = $productsToJson + $aux;
   }else{        
        $productsToJson[$ref_base][] =  $product;
    }

}

print_r(json_encode($productsToJson));
exit(1);



//Call Drive to return an order by observation Id
function DRIVE_getProductList(){
    global $ch;
    // #1 - get Order By Id
    $url = backendUrl . '/SearchWS/QueryAsEntities';
    $params =  array('itemQuery' => '{
                                        "entityName": "St",
                                        "distinct": false,
                                        "lazyLoaded": false,
                                        "SelectItems": [],
                                        "filterItems": [
                                        {
                                            "filterItem": "u6526_st_extra_fields.ref_base",
                                            "valueItem": "",
                                            "comparison": 1,
                                            "groupItem": 1
                                        }
                                        ],
                                        "orderByItems": [],
                                        "JoinEntities": [],
                                        "groupByItems": []
                                    }');
    $response=DRIVE_Request($ch, $url, $params);
    if(empty($response)){
        return false;
    } else if(count($response['result']) == 0 ){
        return null;
    }
    return $response['result'];
}




// Drive Generic call
function DRIVE_Request($ch, $url,$params){

	// Build Http query using params
	$query = http_build_query ($params);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, false);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

	curl_setopt($ch, CURLOPT_BINARYTRANSFER, false);


	$response = curl_exec($ch);
	// send response as JSON
	return json_decode($response, true);
}

//Call Login
function DRIVE_userLogin(){
	global $ch;

	$url = backendUrl . '/UserLoginWS/userLoginCompany';

	// Create map with request parameters
	$params = $_SESSION['driveCredentials'];

	// Build Http query using params
	$query = http_build_query ($params);
	//initial request with login data

	//URL to save cookie "ASP.NET_SessionId"
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/32.0.1700.107 Chrome/32.0.1700.107 Safari/537.36');
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	//Parameters passed to POST
	curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_COOKIESESSION, true);
	curl_setopt($ch, CURLOPT_COOKIEJAR, '');
	curl_setopt($ch, CURLOPT_COOKIEFILE, '');
	$response = curl_exec($ch);

	// send response as JSON
	$response = json_decode($response, true);
	if (curl_error($ch)) {
		return false;
	} else if(empty($response)){
		return false;
	} else if(isset($response['messages'][0]['messageCodeLocale'])){
		echo $response['messages'][0]['messageCodeLocale']."<br>";
		echo "Error in login. Please verify your username, password, applicationType and company." ;
		return false;
	}
	return true;
}




?>