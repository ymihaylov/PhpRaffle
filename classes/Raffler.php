<?php
namespace PhpRaffle;

// use PhpRaffle\CsvReader;
// use PhpRaffle\CsvWriter;
// use PhpRaffle\AllDrawnException;
// use PhpRaffle\NoMoreAwardsException;

// TODO: Replace this with a nice PSR-4 style autoloading!!!
require_once "CsvReader.php";
require_once "CsvWriter.php";
require_once "AllDrawnException.php";
require_once "NoMoreAwardsException.php";

class Raffler
{
    private $attendees      = [];
    private $winners        = [];
    private $noshows        = [];
    private $awards         = [];
    private $allDrawn       = [];
    private $csvHeadConfig  = [];

    private $attendeesFilename;
    private $awardsFilename;
    private $winnersFilename;
    private $nowshowFilename;

    public function __construct($options = [])
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

    public function setAttendees($attendees) {
        $this->attendees = $attendees;
    }

    public function setWinners($winners) {
        if (!empty($this->winners)) {
            throw new Exception("Cannot set winners more than once in the object's lifetime");
        }

        $this->winners  = $winners;
        $this->allDrawn += $winners;
    }

    public function setNoshows($noshows) {
        if (!empty($this->nowshows)) {
            throw new Exception("Cannot set noshows more than once in the object's lifetime");
        }

        $this->noshows  = $noshows;
        $this->allDrawn += $noshows;
    }

    public function setAwards($awards) {
        // If there are already as many winners as awards (or more) drawn, set awards to an empty array.
        if (count($awards) <= count($this->winners)) {
            $this->awards = [];
            return;
        }

        // Load just the remaining not drawn awards
        $awards = array_slice($awards, count($this->winners));

        $this->awards = $awards;
    }

    private function readCsvFileToArray($filename)
    {
        if (!is_readable($filename)) {
            throw new Exception("File ({$filename}) not readible!");
        }

        $csvReader    = new CsvReader($filename);
        if (! empty($this->csvHeadConfig)) {
            $csvReader->setHead($this->csvHeadConfig);
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
        $this->winners = $this->readCsvFileToArray($this->winnersFilename);

        // Add all the drawn winners to the allDrawn array
        foreach ($this->winners as $line) {
            $this->allDrawn[ $this->getPrimaryKey($line) ] = $line;
        }
    }

    private function loadNoShow()
    {
        $this->noshows = $this->readCsvFileToArray($this->noshowFilename);

        // Add all the no-shows to the allDrawn array
        foreach ($this->noshows as $line) {
            $this->allDrawn[ $this->getPrimaryKey($line) ] = $line;
        }
    }

    private function loadAttendees()
    {
        $this->attendees = $this->readCsvFileToArray($this->attendeesFilename);
    }

    private function loadAwards()
    {
        $awardsArr = $this->readCsvFileToArray($this->awardsFilename);
        $this->setAwards($awardsArr);
    }

    public function draw()
    {
        if (count($this->allDrawn) >= count($this->attendees)) {
            throw new AllDrawnException("Everybody has been drawn");
        }

        if (empty($this->awards)) {
            throw new NoMoreAwardsException("All awards have been drawn");
        }

        do {
            $drawn = array_rand($this->attendees);
            $key = $this->getPrimaryKey($drawn);
        } while (isset($this->allDrawn[$key]));

        return $drawn;
    }
}
