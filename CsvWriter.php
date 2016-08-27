<?php

class CsvWriter
{
	private $fname;
	private $mode = 'w';
	private $fh;
	private $lcounter = 0;

	public $delimiter 	= ',';
	public $enclosure 	= '"';
	public $escape 		= '\\';  //a backslash - default - not used

    function __construct( $fname, $mode = 'w' )
    {
    	$this->fname = $fname;
    	$this->mode = $mode;
    }

    function __destruct()
   	{
   		if ( $this->isFileOpen() )
   			$this->closeFile();
   	}

    public function getLineCount()
    {
    	return $this->lcounter;
    }

    public static function writeFromArray( $arr, $fname, $mode )
    {
    	$writer = new self( $fname, $mode );
    	if( !$writer->openFile() )
    		return false;

    	foreach( $arr as $line )
    		$writer->writeLine( $line );

    	$writer->closeFile();
    	return true;
    }

    public function writeLine( $line )
    {
    	if( !$res = fputcsv( $this->fh, $line, $this->delimiter, $this->enclosure ) )
    		return false;

    	$this->lcounter++;
    	return $res;
	}

	private function isFileOpen()
	{
		return is_resource( $this->fh );
	}

	public function openFile()
	{
		$this->lcounter = 0;
		return ( $this->fh = fopen( $this->fname, $this->mode ) ) ? true : false;
	}

	public function closeFile()
	{
		return fclose( $this->fh );
	}

	public static function arrayToCsv($rows)
	{
		$csvHead = array();
		if (isset($rows[0])) {
			foreach($rows[0] as $colName=>$colValue) {
				$csvHead[] = $colName;
			}
		}

		$return = '';
		$return .= self::_fputcsv2($csvHead);

		foreach($rows as $index=>$row)
		{
			$return .= self::_fputcsv2($row);
		}

		return $return;
	}

	private static function _fputcsv2( array $fields, $delimiter = ',', $enclosure = '"', $mysql_null = false)
	{
		$delimiter_esc = preg_quote($delimiter, '/');
		$enclosure_esc = preg_quote($enclosure, '/');
		$output = array();
		foreach ($fields as $field)
		{
			if ($field === null && $mysql_null)
			{
				$output[] = 'NULL';
				continue;
			}

			$output[] = preg_match( "/(?:{$delimiter_esc}|{$enclosure_esc}|\s)/", $field )
				? ( $enclosure . str_replace( $enclosure, $enclosure . $enclosure, $field ) . $enclosure )
				: $field;
		}

		return join($delimiter, $output) . "\n";
	}

	public function outputCsv( $csvarray )
	{
		header( "Content-type: text/csv" );
		header( "Cache-Control: no-store, no-cache");
		header( "Content-Disposition: attachment; filename=\"{$this->fname}\"" );
		$outstream = fopen("php://output", 'w');

		foreach( $csvarray as $row )
			fputcsv( $outstream, $row, $this->delimiter, $this->enclosure );

		fclose( $outstream );
		exit();
	}
}