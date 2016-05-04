<?php
require_once "CsvReader.php";
require_once "CsvWriter.php";
/*
$attendees = [
	12134 => 'John Doe',
	12135 => 'Bill Doe',
	12136 => 'John Smith',
	12137 => 'John Denver',
	12138 => 'Johnny Cash',
	12139 => 'Crazy Horse',
	12140 => 'Red Cloud',
	12141 => 'Jerronimo',
	12142 => 'Sitting Bull',
];
*/
$attendees = array();

$inputFile 	= 'oct_meeting_attendees.csv';
$outputFile = str_replace( '.csv', '.winners.csv', $inputFile );

$attendeesEmails = array();
$attendees = loadAttendees( $inputFile, $attendeesEmails );

session_start();

if ( !isset( $_SESSION['winners'] ) )
	$_SESSION['winners'] = array();

if ( count( $attendees ) == 0 )
	die ( json_encode( ['message' => 'No attendees!' ] ) );

if ( count( $_SESSION['winners'] ) == count( $attendees ) )
	die ( json_encode( ['message' => 'Everybody won!' ] ) );

$awards = array();
$awardsFile = "awards.csv";
if ( is_readable( $awardsFile ) )
{
	$awards = loadAwards( $awardsFile, $_SESSION['winners'] );
	if ( !count( $awards ) )
		die( json_encode( ['message' => "All awards have been drawn" ] ) );
}


if ( isset( $_GET['getRandom'] ) )
{
	$number = (int) $_GET['getRandom'];
	$randomAttendees = drawRandomN( $number, $attendees );

	echo json_encode( array_values( $randomAttendees ) );
	exit;
}


do
{
	$winnerId = array_rand( $attendees );
}
while ( isset( $_SESSION['winners'][$winnerId] ) );

// Commend this out for multiple same winner or make it optional with param
$_SESSION['winners'][$winnerId] = $attendees[ $winnerId ];

$awardDesc = null;
writeWinner( $winnerId, $attendees[ $winnerId ], $attendeesEmails[ $winnerId ], $outputFile, $awards, $awardDesc );
$outputArr = [ 'name' => $attendees[ $winnerId ] ];

if ( $awardDesc )
	$outputArr['award'] = $awardDesc;

if ( $attendeesEmails[ $winnerId ] )
	$outputArr['email'] = obfuscateEmailAddress( $attendeesEmails[ $winnerId ] );

echo json_encode( $outputArr );
exit;


function loadAttendees( $inputFile, &$attendeesEmails )
{
	$attendees = array();
	$csvReader 	= new CsvReader( $inputFile );
	$csvReader->delimiter = ',';
	$csvReader->enclosure = '"';

	if ( $csvReader->openFile() )
	{	
		$head = $csvReader->readLine();
		$csvReader->setHead( $head );
		while( $line = $csvReader->readLine() )
		{
			$line = array_map('trim', $line);
			$attendees[ $line['Registration ID'] ] = $line['Name'];
			$attendeesEmails[ $line['Registration ID'] ] = $line['Email'];
		}

		$csvReader->closeFile();
	}

	return $attendees;
}

function loadAwards( $inputFile, $winners )
{
	$awards = array();
	$csvReader 	= new CsvReader( $inputFile );

	if ( $csvReader->openFile() )
	{	
		$awardsArr = $csvReader->readToArray();
		$awardsArr = array_slice( $awardsArr, count( $winners ) );
		foreach ( $awardsArr as $awardLine )
			$awards[] = $awardLine[0];

		$csvReader->closeFile();
	}

	return $awards;
}

function drawRandomN( $number, $attendees )
{
	$randomAttendees = array();
	if ( $number )
	{
		if ( $number > count( $attendees ) )
			$number = count( $attendees );

		if ( $number == count( $attendees ) )
			return $attendees;

		do
		{
			$randomId = array_rand( $attendees );
			if ( !isset( $randomAttendees[ $randomId ] ) )
				$randomAttendees[ $randomId ] = $attendees[ $randomId ];
		}
		while ( count( $randomAttendees ) < $number );		
	}

	return $randomAttendees;
}


function writeWinner( $winnerId, $winnerName, $winnerEmail, $outputFile, $awards = null, &$awardDec = null )
{
	$csvWriter = new CsvWriter( $outputFile, 'a' );
	$csvWriter->delimiter = ',';
	$csvWriter->enclosure = '"';

	if ( $csvWriter->openFile() )
	{
		$line = array( $winnerId, $winnerName, $winnerEmail );
		if ( is_array( $awards ) && count( $awards ) )
		{
			$awardDec = array_shift( $awards );
			$line[] = $awardDesc;
		}

		$csvWriter->writeLine( $line );
		$csvWriter->closeFile();
		return true;
	}

	return false;
}

function obfuscateEmailAddress( $email )
{ 
	$parts = explode( '@', $email );
	$parts[0] = str_pad( substr( $parts[0], 0, 1 ), strlen( $parts[0] ) - 2, '*' ) . substr( $parts[0], -1 );
	// $part[1] = preg_replace( '/^.*(\..*)$/', '\1', $email );

	return $parts[0] . '@' . $parts[1];
	// return preg_replace( '/^(.).*(.)@(.).*(.)(\..*)$/', '\1', $email );
}
