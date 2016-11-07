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
    private $noshowFilename;

    private $csvReader;
    private $csvWriter;

    public function __construct($options = [])
    {
        $this->attendeesFilename    = isset($options['attendeesFilename']) ? $options['attendeesFilename'] : 'attendees.csv';
        $this->awardsFilename       = isset($options['awardsFilename']) ? $options['awardsFilename'] : 'awards.csv';
        $this->winnersFilename      = isset($options['winnersFilename']) ? $options['winnersFilename'] : 'winners.csv';
        $this->noshowFilename      = isset($options['noshowFilename']) ? $options['noshowFilename'] : 'noshow.csv';
        $this->csvHeadConfig        = isset($options['csvHead'])
            ? $options['csvHead']
            : [
                'id'            => 'Registration ID',
                'email'         => 'Email',
                'name'          => 'Name',
                'first_name'    => null,
                'last_name'     => null,
            ];

        $this->csvReader = isset($options['csvReader']) ? $options['csvReader'] : new CsvReader;
        $this->csvWriter = isset($options['csvWriter']) ? $options['csvWriter'] : new CsvWriter;
    }

    public function init()
    {
        $this->loadWinners();
        $this->loadNoShow();
        $this->loadAttendees();
        $this->loadAwards();
    }

    public function getWinners()
    {
        return $this->winners;
    }

    public function getAwards()
    {
        return $this->awards;
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

        $csvReader = $this->getCsvReader($filename, $this->csvHeadConfig);

        if ($csvReader->openFile()) {
            $arr = $csvReader->readToArray();
            return $arr;
        }

        throw new Exception("File ({$filename}) could not be processed!");
    }

    private function getCsvReader($filename, $head = null)
    {
        $csvReaderClass = get_class($this->csvReader);
        if (! $csvReaderClass) {
            // TODO: Create a generic base for PhpRaffler exceptions, make NoMore and AllDRawn extend from it
            throw new Exception('No proper csvReader set');
        }

        $csvReader = new $csvReaderClass($filename);
        if (! $csvReader instanceof CsvReaderInterface) {
            throw new Exception('Invalid csvReader set');
        }

        if (! empty($head)) {
            $csvReader->setHead($head);
        }

        return $csvReader;
    }

    private function getCsvWriter($filename, $head = null, $mode = 'w')
    {
        $csvWriterClass = get_class($this->csvWriter);
        if (! $csvWriterClass) {
            // TODO: Create a generic base for PhpRaffler exceptions, make NoMore and AllDRawn extend from it
            throw new Exception('No proper csvWriter set');
        }

        // TODO: Create an interface for csvReader and csvWriter, make them implement it, assert when getting them
        $csvWriter = new $csvWriterClass($filename, $mode);

        if (! empty($head)) {
            $csvWriter->setHead($head);
        }

        return $csvWriter;
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

    public function draw(&$award = null)
    {
        if (count($this->allDrawn) >= count($this->attendees)) {
            throw new AllDrawnException("Everybody has been drawn");
        }

        if (empty($this->awards)) {
            throw new NoMoreAwardsException("All awards have been drawn");
        }

        do {
            $drawn  = $this->attendees[array_rand($this->attendees)];
            $key    = $this->getPrimaryKey($drawn);
        } while (isset($this->allDrawn[$key]));

        $award = current($this->awards);

        return $drawn;
    }

    public function markDrawn($winner)
    {
        $winner['award']    = array_shift($this->awards);
        $this->winners[]    = $winner;
        $this->allDrawn[]   = $winner;

        return $this->writeArrayOffToFile(
            $this->winners,
            $this->winnersFilename
        );
    }

    public function markNoShow($attendee)
    {
        $this->noshows[]    = $attendee;
        $this->allDrawn[]   = $attendee;

        return $this->writeArrayOffToFile(
            $this->noshows,
            $this->noshowFilename
        );
    }

    public function writeArrayOffToFile($array, $filename)
    {
        $csvReader = $this->getCsvReader($filename);
        if (! empty($this->csvHeadConfig)) {
            $csvReader->setHead($this->csvHeadConfig);
        }

        if ($csvReader->openFile()) {
            $arr = $csvReader->readToArray();
            return $arr;
        }

        throw new Exception("File ({$filename}) could not be processed!");
    }
}
