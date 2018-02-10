<?php
error_reporting(E_ERROR | E_PARSE);

require('libs/excel-reader/php-excel-reader/excel_reader2.php');
require('libs/excel-reader/SpreadsheetReader.php');

//Define constants
define("excelFile", 'LegoWear-PS18.xlsx'); //Index of Excel Sheet to Read
define("sheetToRead", 4); //Index of Excel Sheet to Read
define("backendUrl", "https://sis04.drivefx.net/E252DC0A/PHCWS/REST");//TODO change for client
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

$msg = "#1 - Read Excel<br>";
echo $msg;
logData($msg);

//#1 - Read Excel
$products = EXCEL_reader();

$msg = "#2 - Start import<br>";
echo $msg;
logData($msg);

//#2 - Start import
foreach($products as $product){

    //#1 - check if ref is greater than 18 chars
    if(sizeof($product->ref) > 18){
        $msg = "Product with ref - ". $product->ref ." is too long (>18 chars)<br><br>";
        echo $msg;
        logData($msg);
        continue;
    }

    $msg = "Sync: <br>". json_encode($product) ."<br>";
    echo $msg;
    logData($msg);

    //#2 - Get a new instance of an St
    $newInstanceSt = DRIVE_getNewInstance("St", 0);
    if($newInstanceSt == null){
        $msg = "Error on getting new instance ST. <br><br>";
        echo $msg;
        logData($msg);
        continue;
    }

    //#3 - fulfill properties
    $newInstanceSt['ref']           = $product->ref;
    $newInstanceSt['codigo']        = $product->codigo;
    $newInstanceSt['design']        = $product->design;
    $newInstanceSt['desctec']       = $product->desctec;
    $newInstanceSt['epv1']          = $product->epv1;
    $newInstanceSt['iva1incl']      = $product->iva1incl;
    $newInstanceSt['familia']       = $product->familia;
    $newInstanceSt['usr2']          = $product->modelo;
    $newInstanceSt['usr1']          = $product->marca;

    //#3.1 - fulfill extensions fields
    $newInstanceSt['u6526_st_extra_fields']['ref_base']     = $product->refBase;
    $newInstanceSt['u6526_st_extra_fields']['design_en']    = $product->designEN;    
    $newInstanceSt['u6526_st_extra_fields']['desctec_en']   = $product->desctecEN;


    //#4 - an sync entity
    $newInstanceSt = DRIVE_actEntiy("St", $newInstanceSt);
    if($newInstanceSt == null){
        $msg = "Error on act entity for product name = " .$product->design . " <br><br>";
        echo $msg;
        logData($msg);
        continue;
    }

    //#5 - Save product
    $newInstanceSt = DRIVE_saveInstance("St", $newInstanceSt);
    if($newInstanceSt == null){
        $msg = "Error on save for product name = " .$product->design . " <br><br>";
        echo $msg;
        logData($msg);
        continue;
    }

    $msg = "Product created with ref = " .$newInstanceSt['ref']. " <br><br>";
    echo $msg;
    logData($msg);

    exit(1);    
}





/**
 * DRIVE WS
 */
//Get New Instance (Entity= Cl , Bo, St)
function DRIVE_getNewInstance($entity, $ndos){

	global $ch;

	$url = backendUrl . "/".$entity."WS/getNewInstance";
	$params =  array('ndos' => $ndos);

	$response=DRIVE_Request($ch, $url, $params);

	if(empty($response)){
		return null;
	}
	if(isset($response['messages'][0]['messageCodeLocale'])){
		return null;
	}


	return $response['result'][0];
}

//Sync entity Instance (Entity= Cl , Bo, St)
function DRIVE_actEntiy($entity, $itemVO){

	global $ch;

	$url = backendUrl . "/".$entity."WS/actEntity";
	$params =  array('entity' => json_encode($itemVO),
					 'code' => 0,
					 'newValue' => json_encode([])
				);

	$response=DRIVE_Request($ch, $url, $params);

	//echo json_encode( $response );
	if(empty($response)){
		return null;
	}
	if(isset($response['messages'][0]['messageCodeLocale']) && $response['messages'][0]['messageCode'] != 'messages.Business.Stocks.InvalidRefAutoCreate'){
		$msg = $response['messages'][0]['messageCodeLocale'];
		logData($msg);
		return null;
	}


	return $response['result'][0];
}

//save Instance (Entity= Cl , Bo, St)
function DRIVE_saveInstance($entity, $itemVO){

	global $ch;

	$url = backendUrl .  "/".$entity."WS/Save";
	$params =  array('itemVO' => json_encode($itemVO),
					 'runWarningRules' => 'false'
				);

	$response=DRIVE_Request($ch, $url, $params);

	//echo json_encode( $response );
	if(empty($response)){
		$msg = "Empty save";
		logData($msg);
		return null;
	}
	if(isset($response['messages'][0]['messageCodeLocale'])){
		$msg = $response['messages'][0]['messageCodeLocale'];
		logData($msg);
		return null;
	}


	return $response['result'][0];
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


/**
 * EXCEL functions
 */
function EXCEL_reader(){
    $products = array();

    //#1 - instance new reader
    $Reader = new SpreadsheetReader(excelFile);
    $Sheets = $Reader -> Sheets();

    //#2 - select sheet to read
    $Reader -> ChangeSheet(sheetToRead);
    //#3 - iterate rows
    foreach ($Reader as $index=>$Row)
    {   
        //#4 - ignore the header
        if($index === 0) 
            continue;
        //#5 - instantiate a new product
        $newProduct = new Product($Row);
        
        //#6 - store it into result collection
        $products[] = $newProduct;
        
    }

    //#7 - return collection
    return $products;
        
}

/**
 * Class Product
 */
class Product {

    //properties (DRIVE_ST)
    public $ref = '';
    public $codigo = '';
    public $design = '';    
    public $desctec = '';    
    public $epv1 = 0;
    public $iva1incl = true;
    public $familia = '';
    public $modelo = '';
    public $marca = '';    
    
    //properties (DRIVE_ST_EXT)
    public $refBase = '';
    public $designEN = '';
    public $desctecEN = '';

    //New instance
    public function __construct($excelRow) {
        $this->refBase      = (string)$excelRow[0]; //Drive EXT
        $this->ref          = $excelRow[0] .'|'. $excelRow[2] .'|'. $excelRow[3];
        $this->codigo       = (string)$excelRow[4];
        $this->design       = (string)$excelRow[5];
        $this->designEN     = (string)$excelRow[6]; //Drive EXT
        $this->desctec      = (string)$excelRow[7];
        $this->desctecEN    = (string)$excelRow[8]; //Drive EXT
        $this->epv1         = $excelRow[9];
        $this->familia      = (string)$excelRow[10];
        $this->modelo       = (string)$excelRow[12];
        $this->marca        = (string)$excelRow[13];        
    }

}

/* Log Errors and data to Log */
function logData($data){

	$file = 'log.txt';
	// Open the file to get existing content
	$current = file_get_contents($file);
	// Append a new person to the file
	$current .=  "\n\n----------------------" . date("Y-m-d H:i:s") . "----------------------\n" . $data ;
	// Write the contents back to the file
	file_put_contents($file, $current);

}

?>