<?php 
/*****
 * Author: Ethan Gruber
 * Modified: December 2018
 * Function: Extract unique IDs for rulers and their associated corporate entity and date range
 *****/

$data = generate_json('https://docs.google.com/spreadsheets/d/e/2PACX-1vSXfF6zZil4UsAOycPzNNfCmjOTGPoN8N7zTUAZIk2QtjZ6O3nKuQrwTdO68EJlJ5vtou7IOmvO8t7K/pub?output=csv');
$rulers = array();

foreach ($data as $row){
	if (strlen(trim($row['ruler'])) > 0){
		$ruler = trim($row['ruler']);
		$id = md5($ruler);
		
		if (!array_key_exists($id, $rulers)){
			$rulers[$id] = array($ruler, trim($row['authority']), trim($row['years of rule']));
		}
	}
}

//write to CSV
$fp = fopen('rulers.csv', 'w');
fputcsv($fp, array('prefLabel_de','org','years'));
foreach ($rulers as $fields) {
	fputcsv($fp, $fields);
}
fclose($fp);


/***** FUNCTIONS *****/
function generate_json($doc){
	$keys = array();
	$geoData = array();
	
	$data = csvToArray($doc, ',');
	
	// Set number of elements (minus 1 because we shift off the first row)
	$count = count($data) - 1;
	
	//Use first row for names
	$labels = array_shift($data);
	
	foreach ($labels as $label) {
		$keys[] = $label;
	}
	
	// Bring it all together
	for ($j = 0; $j < $count; $j++) {
		$d = array_combine($keys, $data[$j]);
		$geoData[$j] = $d;
	}
	return $geoData;
}

// Function to convert CSV into associative array
function csvToArray($file, $delimiter) {
	if (($handle = fopen($file, 'r')) !== FALSE) {
		$i = 0;
		while (($lineArray = fgetcsv($handle, 4000, $delimiter, '"')) !== FALSE) {
			for ($j = 0; $j < count($lineArray); $j++) {
				$arr[$i][$j] = $lineArray[$j];
			}
			$i++;
		}
		fclose($handle);
	}
	return $arr;
}


?>