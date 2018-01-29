<?php

// add a row to the boxCompactor array
function boxcom_add($qty,$sku,$clr,$price,$order,$boxCompactor,$ditch1002s = false) {
	// initialize the array if it doesn't exist yet
	if (!$boxCompactor)
		$boxCompactor = [[],[],[],[],[],[],[],[]];
	// final category : free stuff
	// 	(don't consolidate free items)
	if ($price == 0)
		$bcp = 7;
	// category 1: pieces that fit in 2118
	else if ($sku == 1768 || $sku == 2166 || $sku == 1721 || $sku == 3721)
		$bcp = 1;
	// category 2: 2118s
	else if ($sku == 2118)
		$bcp = 2;
	// category 3: pieces that fit in 2029 (1766)
	else if ($sku == 1766)
		$bcp = 3;
	// category 4: 2029
	else if ($sku == 2029)
		$bcp = 4;
	// category 5: pieces that fit 1584 (77, 1501)
	else if ($sku == 77 || $sku == 1501)
		$bcp = 5;
	// category 6: 1584
	else if ($sku == 1584)
		$bcp = 6;
	// category 0: everything else
	else 
		$bcp = 0;
	
	if ($sku == 1002 && $ditch1002s)
		return $boxCompactor;
	// consolidation algorithm:
	// initalize match (false)
	$match = 0;
	$category = [];
	// if the category is empty, put this item line in it.
	if (sizeof($boxCompactor[$bcp])==0)
		array_push($boxCompactor[$bcp],[$qty,$sku,$clr,"UE",$price,$order]);
	// otherwise...
	else {
		// check this line against all existing lines
		foreach($boxCompactor[$bcp] as $entry){
			// if the sku & color of this line matches an existing line, it's a match.
			// update the qty & price
			if ($entry[1]==$sku && $entry[2]==$clr){
				// increment... 
				$match = 1;
				$entry[0]+=$qty;
				$entry[4]+=$price;
			}
			if($entry[0] != 0)
				$category[] = $entry;
		}
		$boxCompactor[$bcp] = $category;
		// if it's not a match, then add it to the item list.
		if(!$match){
			array_push($boxCompactor[$bcp],[$qty,$sku,$clr,"UE",$price,$order]);
		}
	}
	return $boxCompactor;
}

// create the new row for a "B" version of the individual piece. This is an unofficial SKU for an individual piece in a gift box.
function boxcom_initB($row){
	$newRow = $row;
	$newRow[4]=0;
	$newRow[0]=0;
	$newRow[1].="B";
	return $newRow;
}


// do the work of compacting the boxes with individual pieces.
function boxcom_compact($boxCompactor){
	// sort the compactables by qty
	foreach ($boxCompactor as &$compactables)
		rsort($compactables);
	
	// reconcile the compactables
	for ($bcp=1;$bcp<6;$bcp+=2){
		if ($boxCompactor[$bcp]){
			
			// reset the row selector to the top of the list.
			$itemRow = 0;
			// make a temp row of box price included "B items"
			$tempRow = boxcom_initB($boxCompactor[$bcp][$itemRow]);
			// set the price per item
			$ppi = $boxCompactor[$bcp][$itemRow][4]/$boxCompactor[$bcp][$itemRow][0];

			foreach($boxCompactor[$bcp+1] as &$boxen){
				// whats the price per box?
				$ppb = $boxen[4]/$boxen[0];
				// for every box... 
				for ($i=$boxen[0]; $i>=0; $i--){
					// current item row empty? find the next non-empty row
					while(!array_key_exists($bcp, $boxCompactor) || 
						  !array_key_exists($itemRow, $boxCompactor[$bcp]) || 
						  $boxCompactor[$bcp][$itemRow][4]==0){
						unset($boxCompactor[$bcp][$itemRow]);
						// on to the next row
						$itemRow++;
						// add the finished row to the end of the array
						array_push($boxCompactor[0],$tempRow);
						// out of stuff to change? on to the next stuff then...
						if ($itemRow > sizeOf($boxCompactor[$bcp]))
							break;
						// set tempRow to the next row
						$tempRow = boxcom_initB($boxCompactor[$bcp][$itemRow]);
						// reset the price per item
						$ppi = $boxCompactor[$bcp][$itemRow][4]/$boxCompactor[$bcp][$itemRow][0];
					}
					// out of stuff to change? on to the next stuff then...
					if ($itemRow > sizeOf($boxCompactor[$bcp])){
						if (sizeOf($boxCompactor[$bcp])==0)
							unset($boxCompactor[$bcp]);
						break;
					} else {
					// update the b items
					$tempRow[4]+=$ppb+$ppi; // price
					$tempRow[0]++;			// qty
					// update the items
					$boxCompactor[$bcp][$itemRow][4]-=$ppi; // price
					$boxCompactor[$bcp][$itemRow][0]--;		// qty
					// update the boxes
					$boxen[0]--; 	 // qty
					$boxen[4]-=$ppb; // price
					// out of boxes?
					if ($boxen[0]==0)
						// destroy the empty box category
						unset($boxCompactor[$bcp+1]);
					}
					if($boxCompactor[$bcp][$itemRow][0] == 0)
						unset($boxCompactor[$bcp][$itemRow]);
				}
			}
		}
	}
	return $boxCompactor;
}

// boxcom_join
// The box compactor is created while order items attributes (qty, sku, color, engraved, price, sort code) are in an array.
// Those arrays are in an array of item rows, which were sorted by quantity for compacting.
// This function turns that array into a pipe "|" delimited string list, for later use as a CSV file.
// It also puts the sort code back at the front to allow proper sorting, now that the compacting is done. 
function boxcom_join($arrayOfarrayOfStrings){
	$tempArr=[];
	foreach ($arrayOfarrayOfStrings as &$line)
		foreach ($line as &$linett){
			$linett[0] = $linett[5].str_pad($linett[4], 10, '0', STR_PAD_LEFT)."#".$linett[0];
			array_push($tempArr,join("|",array_slice($linett,0,5)));
		}
	return $tempArr;
}

?>