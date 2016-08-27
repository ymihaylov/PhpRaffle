<?php
namespace PhpRaffle;

// use PhpRaffle\CsvReader;
// use PhpRaffle\CsvWriter;

// TODO: Replace this with a nice PSR-4 style autoloading!!!
require_once "CsvReader.php";
require_once "CsvWriter.php";

class Raffler
{
    private $attendees      = [];
    private $winners        = [];
    private $noshows        = [];
    private $awards         = [];
    private $allDrawn       = [];
    private $csvHeadConfig  = [];

    private $attendeesFilename;
    private $awardsFileName;
    private $winnersFileName;
    private $noshowFileName;

    public function __construct($options)
    {
        $this->attendeesFilename    = isset($options['attendeesFilename']) ? $options['attendeesFilename'] : 'attendees.csv';
        $this->awardsFilename       = isset($options['awardsFilename']) ? $options['awardsFilename'] : 'awards.csv';
        $this->winnersFilename      = isset($options['winnersFilename']) ? $options['winnersFilename'] : 'winners.csv';
        $this->nowshowFilename      = isset($options['noshowFilename']) ? $options['noshowFilename'] : 'noshow.csv';
        $this->csvHeadConfig        = isset($options['csvHead'])
            ? $options['csvHead']
            : [
                'id'            => 'Registration ID',
                'email'         => 'Email',
                'name'          => 'Name',
                'first_name'    => null,
                'last_name'     => null,
            ];
    }

    public function init()
    {
        $this->loadWinners();
        $this->loadNoShow();
        $this->loadAttendees();
        $this->loadAwards();
    }

    private function readCsvFileToArray($filename, $head = null)
    {
        if (!is_readable($filename)) {
            throw new Exception("File ({$filename}) not readible!");
        }

        $csvReader    = new CsvReader($filename);
        if (isset($head)) {
            $csvReader->setHead($head);
        }

        if ($csvReader->openFile()) {
            $arr = $csvReader->readToArray();
            return $arr;
        }

        throw new Exception("File ({$filename}) could not be processed!");
    }

    public function getPrimaryKey($line)
    {
        if (isset($this->csvHeadConfig['id']) && isset($line[$this->csvHeadConfig['id']])) {
            return $line[$this->csvHeadConfig['id']];
        }

        if (isset($this->csvHeadConfig['email']) && isset($line[$this->csvHeadConfig['email']])) {
            return $line[$this->csvHeadConfig['email']];
        }

        // Otherwise calculate a hash based on the whole line's content
        return md5(implode($line));
    }

    private function loadWinners()
    {
        $this->winners = $this->readCsvFileToArray($this->winnersFilename, $this->csvHeadConfig);
    }

    private function loadNoShow()
    {
        $this->noshows = $this->readCsvFileToArray($this->noshowFilename, $this->csvHeadConfig);
    }

    private function loadAttendees()
    {
        $this->attendees = $this->readCsvFileToArray($this->attendeesFilename, $this->csvHeadConfig);
    }

    private function loadAwards()
    {
        $awardsArr = $this->readCsvFileToArray($this->awardsFilename);

        // Load just the remaining not drawn awards
        $awardsArr = array_slice($awardsArr, count($this->winners));

        $this->awards = $awardsArr;
    }
}
