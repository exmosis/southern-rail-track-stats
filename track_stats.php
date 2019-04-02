<?php

/** 
 * Really graunchy script to scrape the Southern Rail daily perfomance site and store the data
 * in an ongoing JSON file, stored by date and service. 
 * Feel free to fork and re-use.
 *
 * Github home: https://github.com/exmosis/southern-rail-track-stats
 * 
 * Graham Lally <janus-trackstats@exmosis.net>
 * Twitter: @6loss
 * http://exmosis.net/
 *
 */

date_default_timezone_set('Europe/London');

$stats_url = 'https://www.southernrailway.com/about-us/how-were-performing/daily-performance-report';
$stats_track_file = 'southern-rail-performance.json';

// These are the fields we'll output to the CSV file (along with date), which need to match
// the headers in the incoming table.
$output_headers = array(
			'Route',
			'PPM',
			'On Time' // Changed April 2nd. NB March 31st stats show this as 100%. April fool?
		  );

$raw = getRawHtml($stats_url);
$raw_table = getPerformanceTableHtml($raw);
$headers = processHeaders($raw_table);
$header_map = validateHeadersAndGetMap($output_headers, $headers);
$data = processRawData($raw_table, $header_map);
$headed_data = convertToKeyedArray($data, $header_map);
$headed_data = stripPercentages($headed_data, array( 'PPM', 'Right Time' ));

$yesterday_date_string = date("Y-m-d", time() - (60*60*24));

$existing_data = getExistingData($stats_track_file);
$existing_data = attemptToAddNewData($existing_data, $yesterday_date_string, $headed_data);

saveToFile(json_encode($existing_data), $stats_track_file);

exit;


/**
 * Save data to file
 */
function saveToFile($data, $file, $take_backup = true) {
	if (file_exists($file) && $take_backup) {

		$core_backup_name = $file . date("Y-m-d", time());
		$backup_name = $core_backup_name;
		$backup_check_attempt = 1;

		// Don't override existing backups
		while (file_exists($backup_name)) {
			$backup_name .= '.' . $backup_check_attempt;
			$backup_check_attempt++;
		}

		rename($file, $backup_name);
	}

	if (file_put_contents($file, $data) === false) {
		echo 'Error: Could not write to file "' . $file . '" - check permissions.' . "\n";
		exit;
	}

	echo 'Data written to ' . $file . "\n";

}
	
/**
 * Try to add data by key to existing data, but fail if already there.
 */
function attemptToAddNewData($existing_data, $key, $new_data) {
	if (array_key_exists($key, $existing_data)) {
		echo 'Error: key "' . $key . '" already exists in existing data. Script has already run?' . "\n";
		exit;
	}

	$existing_data[$key] = $new_data;

	return $existing_data;
}

/**
 * Load existing data from JSON file, if it exists. Otherwise return empty array.
 */
function getExistingData($json_file) {
	if (file_exists($json_file)) {
		$content = file_get_contents($json_file);
		$content = json_decode($content, true); // TODO: error checking...
		return $content;
	} else {
		return array();
	}
}

/**
 * Remove percentage signs from data for requested columns.
 */
function stripPercentages($headed_data, $columns_to_check) {

	$row_i = 0;

	foreach ($headed_data as $data_row) {
		foreach ($columns_to_check as $column) {
			if (array_key_exists($column, $data_row)) {
				$new_value = preg_replace('/%/', '', $data_row[$column]);
				$headed_data[$row_i][$column] = $new_value;
			}
		}
		$row_i++;
	}

	return $headed_data;

}

/**
 * Turn correctly-ordered data array into keyed data array. 
 */
function convertToKeyedArray($data, $header_map) {

	$keyed_content = array();

	foreach ($data as $data_row) {
		$keyed_row = array();
		foreach ($header_map as $header => $column_n) {
			$keyed_row[$header] = $data_row[$column_n];
		}
		$keyed_content[] = $keyed_row;
	}

	return $keyed_content;
}

/**
 * Grab the raw data for the given headers as an array from the table. Focuses on tbody only.
 * Return array will be:
 * [ [ header_1_data, header_2_data, ... ], [ header_1_data, header_2_data, ... ], ...  ]
 */
function processRawData($raw_table, $header_map) {

	$content = array();

	$raw_table = preg_replace('/^.*<tbody.*>(.*)<\/tbody>.*$/Usi', '$1', $raw_table);

	$raw_rows = explode('</tr>', $raw_table);

	foreach ($raw_rows as $row) {

		if (trim($row) == '') {
			continue;
		}

		preg_match_all('/<td.*>(.+)<\/td>/Usi', $row, $matches);
		if (count($matches) >= 2 ){
			$matches = $matches[1];
		} else {
			echo 'Cannot find td matches. Dumping row HTML and quitting:' . "\n";
			print_r($row);
			exit;
		}

		$row_content = array();

		// Link up to requested header columns
		foreach ($header_map as $header_name => $column_n) {


			if ($column_n >= count($matches)) {
				echo 'Not enough matched columns to get header "' . $header_name . '" with column number ' . $column_n . '. Dumping row HTML and quitting:' . "\n";
				print_r($row);
				exit;
			}

			$row_content[] = $matches[$column_n];
		}

		$content[] = $row_content;
	}

	return $content;

}
	


/**
 * Compare expected headers to actual incoming headers, and return an array of
 * expected header => column number (in case columns get swapped for some reason).
 */
function validateHeadersAndGetMap($expected_headers, $actual_headers) {

	$header_map = array();

	foreach ($expected_headers as $expected_header) {
		$header_i = 0;
		foreach ($actual_headers as $actual_header) {
			if (trim(strtolower($expected_header)) == trim(strtolower($actual_header))) {
				$header_map[$expected_header] = $header_i;

				// Found, so continue to next expected header
				continue 2;

			}
			// Otherwise keep counting and looking
			$header_i++;
		}

		if (! array_key_exists($expected_header, $header_map)) {

			// If we've got this far, we haven't found the expected header so error and quit
			echo 'Cannot find expected header "' . $expected_header . '". Dumping actual headers and quitting:' . "\n";
			print_r($actual_headers);
			exit;
		}

		// Shouldn't reach here. Should either continue above or exit.
	}

	return $header_map;
}


/**
 * Check table headers are as expected.
 */
function processHeaders($raw_table) {
	if (preg_match('/<thead.*>(.*)<\/thead>/Uis', $raw_table, $matches)) {
		if (count($matches) >= 2) {
			$thead = $matches[1];
			preg_match_all('/<th.*>([^<]+)<\/th>/Usi', $thead, $th_matches);
			if (count($th_matches) > 1) {
				array_shift($th_matches);

				// SUCCESS
				return $th_matches[0];

			} else {
				echo 'Cannot find TH cells in thead. Dumping thead and quitting:';
				print_r($thead);
				exit;
			}
					
		} else {
			echo 'Cannot find thead in raw_table. Dumping HTML and quitting:' . "\n";
			print_r($raw_table);
			exit;
		}
	} else {
		echo 'Cannot find thead in raw_table. Dumping HTML and quitting:' . "\n";
		print_r($raw_table);
		exit;
	}
}


/**
 * Strip raw HTML down to performance stats table element.
 */
function getPerformanceTableHtml($raw_html) {
	if (! preg_match('/<div class="c-performance-info" data-test="performance-info">/Us', $raw_html)) {
		echo 'Cannot find "Daily performance measures" table - has HTML changed?. Dumping HTML and quitting:' . "\n";
		print_r($raw_html);
		exit;
	}

  // Find the surrounding div
	$raw_table = preg_replace('/^.*<div class="c-performance-info" data-test="performance-info">(.*)<\/div>.*$/Us', '$1', $raw_html);
  // Strip out h3 and anything else between the table and the surrounding div
	$raw_table = preg_replace('/^.*(<table(.*)<\/table>).*$/Us', '$1', $raw_table);

	return $raw_table;
}

/**
 * Get raw HTML from webpage. Right now this assumes it works.
 */
function getRawHtml($stats_url) {
	echo 'Getting from ' . $stats_url . "\n";
	$raw = file_get_contents($stats_url);
	return $raw;
}

