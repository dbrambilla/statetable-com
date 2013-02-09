<?php include_once("init.php"); ?>

<?php

$country = strtolower(CgiStr('country'));
$time = strtolower(CgiStr('time'));
$occupied = strtolower(CgiStr('occupied'));
$format = strtolower(CgiStr('format'));
$division = strtolower(CgiStr('division'));
$dc = (strtolower(CgiStr('dc')) === "true");

$sourceFile = fopen('state.csv', 'r');

$output = '';

// Process the headers
$headers = fgetcsv($sourceFile);

switch($format)
{
	case 'csv':
		$output .= implode(',', $headers);
		$output .= "\n";
		break;
	case 'sql':
		$output .= "-- State Table courtesy statetable.com\n\n";
		$output .= 'CREATE TABLE state (';
		foreach($headers as $index=>$header)
		{
			if($index > 0) $output .= ', ';
			switch($header)
			{
				case 'id':
				case 'sort':
					$dataType = 'integer';
					break;
				default:
					$dataType = 'varchar(255)';
			}

			$output .= "$header $dataType";
		}
		
		$output .= ");\n\n";
		break;
	case 'select':
		$output .= '<select>';
		break;
	case 'php_array':
		$output .= 'array(';
		break;
    case 'drupal':
        $output = '';
        break;
	default:
		die('invalid format');
}

while($row = fgetcsv($sourceFile))
{
	$assocRow = array();
	foreach($headers as $index=>$header)
		$assocRow[$header] = $row[$index];

	if($country != 'all' && $country != strtolower($assocRow['country']))
		continue;

	if($time != 'all' && $time != strtolower($assocRow['status']))
		continue;

	if($occupied != 'all' && strtolower($assocRow['type']) == 'minor')
		continue;

	if($division != 'all' && strtolower($assocRow['type']) != 'state'  && strtolower($assocRow['type']) != 'province' && strtolower($assocRow['type']) != 'capitol') {
		continue;
	}
	if(!$dc && strtolower($assocRow['type']) == "capitol")
		continue;
		
	switch($format)
	{
		case 'csv':
			$output .= formatCSVRow($headers, $assocRow);
			break;
		case 'sql':
			$output .= formatSQLRow($headers, $assocRow);
			break;
		case 'select':
			$output .= formatSelectRow($headers, $assocRow);
			break;
		case 'php_array':
			$output .= formatPHPArrayRow($headers, $assocRow);
			break;
		case 'drupal':
			$output .= formatDrupalRow($headers, $assocRow);
			break;
		default:
			die('invalid format');
	}
}

switch($format)
{
	case 'csv':
		XCSendRawData($output, 'state_table.csv', $mimetype='text/csv');
		break;
	case 'sql':
		XCSendRawData($output, 'state_table.sql', $mimetype='text/plain');
		break;
	case 'select':
		$output .= '</select>';
		XCSendRawData($output, 'state_table.html', $mimetype='text/html');
		break;
	case 'php_array':
		$output .= ');';
		XCSendRawData($output, 'state_table.php', $mimetype='text/plain');
		break;			
	case 'drupal':
		XCSendRawData($output, 'state_table.txt', $mimetype='text/plain');
		break;			
	default:
		die('invalid format');
}

function formatCSVRow($headers, $data)
{
	$row = array();
	foreach($headers as $index=>$header)
		$row[] = quote($data[$header]);
	$row = implode(',', $row);
	$row .= "\n";
	return $row;
}

function formatSQLRow($headers, $data)
{
	$sql = '';
	$sql .= "INSERT INTO state (";
	$sql .= implode(',', $headers);
	$sql .= ") VALUES (";
	
	$row = array();
	foreach($headers as $index=>$header)
		$row[] = quote(addslashes(trim($data[$header])), false);
	$sql .= implode(',', $row);
	$sql .= ");\n\n";
	return $sql;
}

function formatSelectRow($headers, $data) {
	return '<option value="'.$data['abbreviation'].'">'.$data['name'].'</option>';
}

function formatPHPArrayRow($headers, $data) {
	$output = "'".$data['abbreviation']."' => ";
	$output .= "'".addslashes($data['name'])."',";
	return $output;
}

function formatDrupalRow($headers, $data) {
	$output = $data['abbreviation'].'|'.$data['name']."\n";
	return $output;
}

function quote($value, $double = true)
{
	if($double)
		return '"'.$value.'"';
	else
		return "'".$value."'";
}
?>