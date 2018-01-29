<?php 
function authorizeRep($rep_name, $order_number, $client_last_name, $con){
	// check if order exists already
	$sql = "SELECT id FROM logs WHERE order_number = " . $order_number . " LIMIT 1";
	$order_id = $con->query($sql)->fetch_array(MYSQLI_ASSOC)["id"];

	// find rep name in DB
	$sql = "SELECT rep_number, geo, status, created as started FROM users WHERE rep_name = '".$rep_name."' AND status != 'inactive' LIMIT 1";
	$result = $con->query($sql)->fetch_array(MYSQLI_ASSOC);

	// we have a rep:
	if(is_array($result)){
		$rep_num = $result["rep_number"];
		$geo = $result["geo"];
		$status = $result["status"];
	}
	// else not found (insert?)

	
	// part of a team:
	if(stripos($status, 'team_') !== false){
		$lead_id = substr($status, stripos($status, '_')+1);
		if(is_numeric($lead_id)){
			$sql = "SELECT status FROM users WHERE id = " . $lead_id;
			$lead_result = $con->query($sql)->fetch_array(MYSQLI_ASSOC)["status"];
			if($lead_result == 'inactive')
				$result = false;
		} else $result = false;
	}
	
	// trial -> active:
	if(substr($status,0,5) == 'trial'){
		if(is_numeric(substr($status,6)))
			$limit = substr($status,6);
		else
			$limit = 30;
		$sql = "SELECT count(*) as total FROM logs WHERE rep_name ='" . $rep_name ."'";
		$trial_uses = $con->query($sql)->fetch_array(MYSQLI_ASSOC)["total"];
		if($result["started"] < (date('Y-m', strtotime("-1 month"))."-01 00:00:00") && $trial_uses > $limit){
			$sql = "UPDATE users SET status = 'active', modified = now() WHERE rep_name ='" . $rep_name ."'";
			$con->query($sql);
		}
	}
	
	// update logs
	if (!$order_id)
		$sql = "INSERT INTO `logs`(`rep_id`, `rep_name`, `order_number`, `client_last`) 
			VALUES (".$rep_num.",'".$rep_name."',".$order_number.",'".ucfirst($client_last_name)."')";
	else {
		$sql = "UPDATE `logs` SET `qty` = `qty`+1, `modified` = now() WHERE `id` =".$order_id;
	}
	$con->query($sql);

	// exit if unauthorized
	if (!$result){
		echo "Error: You are not authorized to use this app. Go to http://qgs.biz/invoice to register.";
		return false;
	}
	else
		return $geo;
}
?>