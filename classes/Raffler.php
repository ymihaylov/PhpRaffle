<?php
namespace PhpRaffle;

// use PhpRaffle\CsvReader;
// use PhpRaffle\CsvWriter;

// TODO: Replace this with a nice PSR-4 style autoloading!!!
require_once "CsvReader.php";
require_once "CsvWriter.php";

class Raffler
{
    // TODO: Make this not depend on session, just read the winners file on init, load into private variable
    const SESSION_KEY            = 'raffler_winners';

    private $attendees      = [];
    private $winners        = [];
    private $noshows        = [];
    private $awards         = [];
    private $allDrawn       = [];
    private $csvHeadConfig  = [
        'id'            => 'Registration ID',
        'email'            => 'Email',
        'name'            => 'Name',
        'first_name'    => null,
        'last_name'        => null,
    ];

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
