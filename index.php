<?php

require('libs/excel-reader/php-excel-reader/excel_reader2.php');
require('libs/excel-reader/SpreadsheetReader.php');

//Define constants
define("excelFile", 'LegoWear-PS18.xlsx'); //Index of Excel Sheet to Read
define("sheetToRead", 4); //Index of Excel Sheet to Read


$msg = "#1 - Read Excel<br>";
echo $msg;
logData($msg);

//#1 - Read Excel
$products = EXCEL_reader();

print_r(json_encode($products));









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
    public $ivaincl1 = true;
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