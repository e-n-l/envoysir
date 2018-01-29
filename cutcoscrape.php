<?php
header('Access-Control-Allow-Origin: *');
 
// Includes: 
// csv filter:
include "csvify.php";
// box compacting bits:
include "boxcom.php";
// establish database connection:
include_once "dbinit.php";
// DB authorization function:
include "dbAuth.php";

 
// initialize & set variables as null / 0:
$order_num = 0;
$error = $ditch1002s = $test = $reload = $identifier = $zip_code = $last_name = $format = $params = "";
 
 
// sift through / assign URL variables:
// set the order number
if(isset($_GET['o']) && is_numeric($_GET['o']))
    $order_num = $_GET['o'];
// set the format
if(isset($_GET['f']))
    $format = $_GET['f'];
// search by last name
if(isset($_GET['l']))
    // if it's a last name with numbers in it, it's actually a zipcode
    if (strcspn($_GET['l'], '0123456789') != strlen($_GET['l']))
        $identifier = $zip_code = $_GET['l'];
    else
        $identifier = $last_name = $_GET['l'];
// search by zip
if(isset($_GET['z']))
    $identifier = $zip_code = $_GET['z'];
// ignore stored version, if exists, and reload from Cutco?
if(isset($_GET['r']))
    $reload = true;
// wait, is this test?
if(isset($_GET['t']))
    $test = true;
// throw out item #1002, cutlery guides
if(isset($_GET['1002']))
    $ditch1002s = $_GET['1002'];
 
if ($test)
    $content = csvify(file_get_contents('./sample.html' ));
else {
    // for realsies
    // is this a repeat?
    if ((file_exists( './orders/' . $order_num . '.txt' )) !== FALSE && !$reload){
        $content = file_get_contents('./orders/' . $order_num . '.txt');
        $reload = 0;
    }
    // first time, eh?
    else {
        // cutco's "api" endpoint:
        $url = 'http://www.cutco.com/orderStatus.do';
 
        if ($order_num)
            $params = "?orderNumber=".$order_num;
        else
            $error .= "Error: Not enough information provided to lookup order: Missing order number.\n";
        if ($zip_code)
            $params .= "&zipCode=".$zip_code;
        else if ($last_name)
            $params .= "&lastName=".$last_name;
        else
            $error .= "Error: Not enough information provided to lookup order - need either zip or last name.\n";
         
        if(!$error){
        $ch = curl_init($url . $params);
        // Setting proxy option for cURL
        if (isset($proxy)) {    // If the $proxy variable is set, then
            curl_setopt($ch, CURLOPT_PROXY, $proxy);    // Set CURLOPT_PROXY with proxy in $proxy variable
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
        $curl_scraped_page = curl_exec($ch);
        curl_close($ch);
        // turn results into a csv file with just the stuff we want:
        $content = csvify($curl_scraped_page);
 
        // get rep name from content
        $rep_name = @explode("|",str_replace("\n","|",$content))[array_search("Rep Name",explode("|",str_replace("\n","|",$content)))+1];
 
        // dump into a txt file:
        if ($rep_name){
            $outstream = fopen('./orders/' . $order_num . '.txt', 'w');
            fputs($outstream,$content);
            fclose($outstream);
        }
        else
            $error .="Error: No data returned.\n";
        }
    }
}
 
if ($error){
    exit($error);
}
 
// DB authorization
// check rep_name against the database of reps, and log this access:
if($reload === "") // we're not loading this from a file, so actually log it.
    $geo = authorizeRep($rep_name, $order_num, $identifier, $con);
else{ // check, but don't log:
    $rep_name = @explode("|",str_replace("\n","|",$content))[array_search("Rep Name",explode("|",str_replace("\n","|",$content)))+1];
    $geo = authorizeRep($rep_name, $order_num, $identifier, $con);
}

// authorization failed.
if($geo === false)
    exit;
 
// separate item data from other order data
$itemslice =0;
if (strripos($content,"Status|\n")>0)
    $itemslice = strripos($content,"Status|\n")+strlen("Status|\n");
 
$items = substr( $content , $itemslice);
$content = substr( $content , 0, $itemslice);
 
// break the item section into an array of strings; each one is a line /row
$items = explode("\n", $items);
// use this for getting rid of duplicate values (not an issue so much anymore since CUTCO cleaned their DB out...)
$dupecheck = 0;
 
// cycle through every line of items
foreach($items as &$line){
    // make a copy to create the calc version
    $temp = $line;
 
    // reorder the line item attributes for calc version
    // normal: sku, desc, qty, total price
    // calc: qty, sku, eng, free (total price)
    $temp = explode("|",$temp);
    // only work on full / real lines
    if(count($temp)>4){
        // separate sku into color and item #
        $sku=$temp[0];
        $charcount = count_chars($sku,0);
        // red handles
        if($charcount[ord("R")]){
            $clr="R";
            $sku=substr($sku,0,strlen($sku)-1);
        // pearl handles
        }elseif ($charcount[ord("W")] && $sku!=="CGCBOW"){
            $clr="W";
            $sku=substr($sku,0,strlen($sku)-1);
        // classic handles
        }elseif ($charcount[ord("C")]==1){
            $clr="C";
            $sku = substr($sku,0,strlen($sku)-1);
        // these things didn't have a color in the sku:
        // these things are not classic
        }else   if ($sku < 77
            || ($sku >= 1000 && $sku <= 1544)
            || ($sku >= 1574 && $sku <= 1708)
            || ($sku >= 1740 && $sku <= 1753)
            || ($sku >= 1886 && $sku <= 2064)
            || ($sku >= 2066 && $sku <= 2119)
            || $sku == 80
            || $sku == 1587
            || $sku == 1711
            || $sku == 1838
            || $sku>3852)
            $clr='';
        // everything else is classic, because cutco is lazy
        else
            $clr="C";
 
        // assign the rest of the line values.
        $qty = intval($temp[2]);
        if($temp[3]=='')
            $price = 0; 
        else
            // price, just in case it's based on OLD pricing
            // remove all that isn't a number, "." or "-" (preserve negative values)
            $price = floatval(preg_replace("/([^0-9\\.\\-])/i", "",$temp[3]));
         
        // build the reorganized item list:
        //move qty to 1st place
        $temp[0] = $qty;
        // move sku to 2nd place
        $temp[1] = $sku;
        // color to 3rd place
        $temp[2] = $clr;
        // 4th place: engraving is UE, because it's already calculated.
        $temp[3] = "UE";
        // 5th place: free or price
        $temp[4] = $price;
     
     
    // create a sort string. add it to the front, and use that to organize the output arbitrarily.
    // first, sort by price 
    $pricepoint = strpos($line,"$");
    $decimal = strpos($line,".",$pricepoint);
    if ($pricepoint)
        $priced = preg_replace("/[^0-9,.]/", "",substr($line,$pricepoint,$decimal-$pricepoint));
    else if ($format == "calc")
        $priced = $clr;
    else
        $priced = "0";
    // make the price sorter a std length of 10 digits
    $priced = str_pad($priced, 10, '0', STR_PAD_LEFT);
     
    // put the invoiced items first, others last
    if(substr_count($line,"INVOICE"))
        $order="z";
    else if (@explode("|",$line)[2]=='')
        $order="0";
    else
        $order="1";
    // put engraving after invoiced items
    if(substr_count($line,"CGC"))
        $order.="1";
    else
        $order.="2";
    if ($format=="calc")
        $line = implode("|",$temp);
    // insert the sort strings
    $line=$order.$priced."#".$line;
     
    // add the calc version to the boxcompactor array
    $boxCompactor = boxcom_add($qty,$sku,$clr,$price,$order,@$boxCompactor,$ditch1002s);
    }
}
 
// compact the items with the boxes.
$boxCompactor = boxcom_compact($boxCompactor);
 
// put the box compactor lines back together
//  if it's the selected format, set the return value to compacted boxes
if ($format=="boxCalc")
    $items = boxcom_join($boxCompactor);
 
// sort the output
rsort($items);

// removed the sort strings
foreach($items as &$line){
    $line = substr($line,13);
    // canada hack:   
    if($geo != 'US'){
        $line_array = explode("|",$line);
        $line_array[1] .= $geo;
        $line = implode("|",$line_array);
    }
}
// put the items back together
$items=implode("\n",$items);
 
// standardize the rows between items and other data
$space = "";
for($i=0; $i<(25-substr_count($items,"\n"));$i++)
    $space.="\n";

// add the standard spacing between the items and other content.
$content = trim($items.$space.$content);
 
//standardize the number of csv columns
$content = explode("\n", $content);
$max_delims=0;
$i=0;
// find out how many cols the longest row has
foreach($content as $line){
    $i=substr_count($line,"|");
    if ($i>$max_delims) $max_delims=$i;
}
// add empty cols to short rows
foreach($content as &$line){
    for($i=substr_count($line,"|"); $i<$max_delims;$i++)
        $line.="|";
}

echo implode("\n",$content);

?>