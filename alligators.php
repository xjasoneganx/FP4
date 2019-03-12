<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ics325safedb";
$nav_selected = "PIPLANNING";
$left_buttons = "YES"; 
$left_selected = "CEREMONIES";


include("./nav.php");
global $db;



// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 
?>

<!DOCTYPE HTML>
<html>

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="description" content="Agile Release Train">
	<meta name="keywords" content="HTML,CSS,XML,JavaScript">
	<meta name="author" content="Aaron Kaase">
	<link rel="stylesheet" type="text/css" href="css/pitable.css">
	<title>Program Increment (PI) Summary Table</title>
</head>

<script src="scripts/script.js" defer></script>

<body>
<h2>
	Program Increment (PI) Summary Table 
</h2>

<div id="form_content">
<form action="table.php" method="post">
	<table id="input_table">
		<tr>
			<td>Base URL:</td>
			<td>
			<input type="text" id="baseURL" name="baseURL" value="https://metro">
			</td>
		</tr>
		<tr>
			<td>Program Increment ID:</td>
			<td>
<?php
$piidDefault = $conn->query("SELECT PI_id,MIN(end_date) FROM cadence WHERE end_date >= CURDATE() GROUP BY PI_id LIMIT 1");
$piidResult = $conn->query("SELECT DISTINCT PI_id FROM cadence WHERE PI_id != ''");

if ($piidDefault->num_rows > 0) {
  while($row = $piidDefault->fetch_assoc()) {
    $DefaultPiid = $row["PI_id"];
  }
} else {
  $DefaultPiid = "0 results";
}

echo '<select id="piid" name="piid" value="' . $DefaultPiid . '">' . "\n";
if ($piidResult->num_rows > 0) {
  while($row = $piidResult->fetch_assoc()) {
    echo '<option value="'. $row["PI_id"] . '"';
      if ($row["PI_id"] == $DefaultPiid) {
        echo ' selected="selected">';
        	} else {
        echo '>';}
    echo $row["PI_id"] . '</option>' . "\n";
  }
} else {
    echo "0 results";
}
echo '</select>';
?>

			</td>
		</tr>
		<tr>
			<td>Agile Release Train (ART):</td>
			<td>
			<div id="divArtSelector"></div>

<?php
$query0 = $conn->query("SELECT * FROM trains_and_teams WHERE trim(type)='ART'");
$query1 = $conn->query("SELECT * FROM trains_and_teams WHERE trim(type)='AT'");
$query2 = $conn->query("SELECT PI_id,iteration_id FROM cadence WHERE PI_id != ''");
$query3 = $conn->query("SELECT team_id FROM trains_and_teams WHERE trim(type)='ART'");

$ARTresults = array();
$ATresults = array();
$PIterations = array();
$ARTteam_id = array();

while($line = mysqli_fetch_assoc($query0)){
    $ARTresults[] = $line;
}

while($line = mysqli_fetch_assoc($query1)){
    $ATresults[] = $line;
}

while($line = mysqli_fetch_assoc($query2)){
    $PIterations[] = $line;
}

while($line = mysqli_fetch_assoc($query3)){
    $ARTteam_id[] = $line;
}
?>

<script>
	var ART = JSON.parse('<?php echo json_encode($ARTresults,JSON_HEX_TAG|JSON_HEX_APOS); ?>');
	var AT = JSON.parse('<?php echo json_encode($ATresults,JSON_HEX_TAG|JSON_HEX_APOS); ?>');
	var PIterations = JSON.parse('<?php echo json_encode($PIterations,JSON_HEX_TAG|JSON_HEX_APOS); ?>')
    var ARTinCookie = document.cookie.replace(/(?:(?:^|.*;\s*)art\s*\=\s*([^;]*).*$)|^.*$/, "$1");
</script>

			</td>
		</tr>
		<tr>
			<td>Names of the Teams:</td>
			<td>			
			<input type="text" id="namesOfTeams" name="namesOfTeams" readonly>
			</td>
		</tr>
		<tr>
			<td>
			</td>
			<td>
				<button id="generate" type="button">Generate (JS)</button>
				<button type="submit">Generate (PHP)</button>
			</td>
		</tr>
	</table>
</form>
</div>

<div id="table_content">
</div>



</body>



</html>

<?php
$conn->close();
?>
<?php include("./footer.php"); ?>