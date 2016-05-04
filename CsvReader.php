<?php

class CsvReader
{
	private $fname;
	private $fh;

	public $head;
	public $delimiter 	= ',';
	public $enclosure 	= '"';
	public $escape 		= '\\';  //a backslash - default - not used

    function __construct( $fname )
    {
    	$this->setFName( $fname );
    }

    public function setFName( $fname )
    {
    	$this->fname = $fname;
    }

    public function setHead( $head )
    {
    	$this->head = $head;
    }

    public function readToArray()
    {
    	if( !$this->openFile( $this->fname ) )
    		return false;

    	$arr = array();
    	$i = 0;
    	while( $linearr = $this->readLine() )
    		$arr[ $i++ ] = $linearr;

    	$this->closeFile();
    	return $arr;
    }

    public function readLine()
    {
    	$linearr = fgetcsv( $this->fh, 0, $this->delimiter, $this->enclosure );

		if( $linearr && !is_null( $this->head ) ) //replace numeric indices with ones from head, if any
		{
			foreach( $linearr as $key => $value )
			{
				$linearr[ $this->head[ $key ] ] = $value;
				unset( $linearr[ $key ] );
			}
		}

		return $linearr;
	}

	private function isFileOpen()
	{
		return is_resource( $this->fh );
	}

	public function openFile()
	{
		return ( $this->fh = fopen( $this->fname, 'r' ) ) ? true : false;
	}

	public function closeFile()
	{
		return fclose( $this->fh );
	}
}