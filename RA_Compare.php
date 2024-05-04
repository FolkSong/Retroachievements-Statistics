<?php

namespace RA;

$filename2 = 'output_Nov30_2023.csv';
$filename1 = 'output_Nov01_2022.csv';

$infile1 = fopen($filename1, 'r');
$infile2 = fopen($filename2, 'r');

$outfile = fopen('output_recent.csv', 'w');

//$array1 = array_map('str_getcsv', file($filename1));
//$array2 = array_map('str_getcsv', file($filename2));

//Read CSV into array
$array1 = array();
$header1 = fgetcsv($infile1);
while ($row = fgetcsv($infile1)) {
  $array1[] = array_combine($header1, $row);
}

//Read CSV into array
$array2 = array();
$header2 = fgetcsv($infile2);
while ($row = fgetcsv($infile2)) {
  $array2[] = array_combine($header2, $row);
}

//Add extra column and write header
array_push($header2, 'Newly Added');
fputcsv($outfile,$header2);

//Process data
$array_out = array();
foreach ($array2 as $key => $value2) { // Step through all games in second file

	$findmatch = array_search($value2['GameID'], array_column($array1, 'GameID')); 
	
	if (!($findmatch === false)) { //return value of '0' should not be treated as false
		// Game exists in both old and new arrays
		$value1 = $array1[$findmatch];
		$value2['Total Players (HC)'] = $value2['Total Players (HC)'] - $value1['Total Players (HC)'];
		$value2['Total Players'] = $value2['Total Players'] - $value1['Total Players'];
		$value2['Rarest Cheevo (HC)'] = $value2['Rarest Cheevo (HC)'] - $value1['Rarest Cheevo (HC)'];
		$value2['Rarest Cheevo'] = $value2['Rarest Cheevo'] - $value1['Rarest Cheevo'];		
		if (array_key_exists('Total Cheevs', $value1)) {
		// oldest scrapes won't include this (added Jan 29, 2022)
		$value2['Total Cheevs (HC)'] = $value2['Total Cheevs (HC)'] - $value1['Total Cheevs (HC)'];
		$value2['Total Cheevs'] = $value2['Total Cheevs'] - $value1['Total Cheevs'];		
		}
		$value2['Newly Added'] = 0;
	} else {
		// identify games that were added since the original scrape
		$value2['Newly Added'] = 1;
	}
	array_push($array_out, $value2); //adjusted if $findmatch was true, unchanged if $findmatch was false
	
	//print_r($value2);
	//echo "\nPress any key to continue\n";
	//$name = fgets(STDIN); 
}

//print_r($array_out);

//Write data to file
foreach ($array_out as $fields) { //write output file
    fputcsv($outfile, $fields);
}

fclose($outfile);  
fclose($infile1);
fclose($infile2);
 
?> 