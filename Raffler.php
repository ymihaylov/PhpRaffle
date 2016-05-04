<?php
namespace PhpJsRaffler;
// use PhpJsRaffler\CsvReader;
// use PhpJsRaffler\CsvWriter;

// require_once "CsvReader.php";
// require_once "CsvWriter.php";

class Raffler
{
	// TODO: Make this not depend on session, just read the winners file on init, load into private variable
	const SESSION_KEY			= 'raffler_winners';

	private $_attendees 		= [];
	private $_winners 			= [];
	private $_awards 			= [];
	private $_allDrawn 			= [];
	private $_csvHeadConfig 	= [
		'id'			=> 'Registration ID',
		'email'			=> 'Email',
		'name'			=> 'Name',
		'first_name'	=> null,
		'last_name'		=> null,
	];
	private $_inputFileName;
	private $_awardsFileName;
	private $_winnersFileName;
	private $_noshowFileName;
	
	public function __construct( $options )
	{
		$this->_inputFilename = isset( $options['inputFilename'] ) ? $options['inputFilename'] : 'attendees.csv'; 
		$this->_awardsFilename = isset( $options['awardsFilename'] ) ? $options['awardsFilename'] : 'awards.csv'; 
		$this->_winnersFilename = isset( $options['winnersFilename'] ) ? $options['winnersFilename'] : 'winners.csv'; 
		$this->_nowshowFilename = isset( $options['noshowFilename'] ) ? $options['noshowFilename'] : 'noshow.csv'; 
	}

	public function setInputFilename( $filename )
	{
		$this->_inputFileName = $filename;
	}

	public function setAwardsFilename( $filename )
	{
		$this->_awardsFileName = $filename;
	}

	public function setWinnersFilename( $filename )
	{
		$this->_awardsFileName = $filename;
	}

	public function setNoshowFilename( $filename )
	{
		$this->_nowshowFileName = $filename;
	}

	public function init()
	{
		$this->_loadWinners();
		$this->_loadNoShow();
		$this->_loadAwards();
	}

	private function readCsvFileToArray( $filename, $head = null )
	{
		if ( !is_readable( $filename ) )
		{
			throw new Exception( "File ({$filename}) not readible!" );
		}

		$csvReader 	= new CsvReader( $filename );
		if ( isset( $head ) )
			$csvReader->setHead( $head );
		
		if ( $csvReader->openFile() )
		{	
			$arr = $csvReader->readToArray();
			$csvReader->closeFile();
			return $arr;
		}

		throw new Exception( "File ({$filename}) could not be processed!" );
	}

	private function _loadWinners()
	{
		$this->_winners = $this->_readCsvFileToArray( $this->_winnersFilename );
	}

	private function _loadAwards()
	{
		$awardsArr = $this->_readCsvFileToArray( $this->_awardsFilename );

		// Load just the remaining not drawn awards
		$awardsArr = array_slice( $awardsArr, count( $this->_winners ) );
		
		$this->_awards = $awardsArr;
	}
}


