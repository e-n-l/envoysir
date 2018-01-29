<?php
include_once "dbinit.php";

$sql = "SELECT order_number, client_last, rep_name FROM logs";

// HAVING COUNT(DISTINCT order_number)
$result = $con->query($sql);


?>
<html>
<head>
<title>Logs</title>
<link rel="stylesheet" href="main.css">
</head>
<body id="main">
<div id=mid>
<table width=100%><tr><td class=desc>Order Number</td><td class=desc>Client Last Name</td><td class=desc>Rep</td></tr>
<?php
while ($row = $result->fetch_assoc()) {
	if ((file_exists( './orders/' . $row['order_number'] . '.txt' )) !== FALSE)
		$cached = 'C';
	else
		$cached = '';
	echo '<tr><td>'.$row['order_number']. $cached ."</td><td>".$row['client_last']."</td><td>".$row['rep_name']."</td></tr>";
}
?>
</table>
</div>
</body>
</html>