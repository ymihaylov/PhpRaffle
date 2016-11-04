<?php
namespace PhpRaffle;

// use PhpRaffle\CsvWriterInterface;

// TODO: Replace this with a nice PSR-4 style autoloading!!!
require_once "CsvWriterInterface.php";

class CsvWriter implements CsvWriterInterface
{
    private $fname;
    private $mode = 'w';
    private $fh;
    private $lcounter = 0;
    private $head;

    public $delimiter    = ',';
    public $enclosure    = '"';
    public $escape        = '\\';  //a backslash - default - not used

    public function __construct($fname = null, $mode = 'w')
    {
        $this->fname = $fname;
        $this->mode = $mode;
    }

    public function __destruct()
    {
        if ($this->isFileOpen()) {
            $this->closeFile();
        }
    }

    public function setHead($head)
    {
        $this->head = $head;
    }

    public function getLineCount()
    {
        return $this->lcounter;
    }

    public function writeFromArray($arr)
    {
        if (! $this->openFile()) {
            return false;
        }

        foreach ($arr as $line) {
            $this->writeLine($line);
        }

        $this->closeFile();
        return true;
    }

    public function writeLine($line)
    {
        if (!$res = fputcsv($this->fh, $line, $this->delimiter, $this->enclosure)) {
            return false;
        }

        $this->lcounter++;
        return $res;
    }

    private function isFileOpen()
    {
        return is_resource($this->fh);
    }

    public function openFile()
    {
        $this->lcounter = 0;
        return ($this->fh = fopen($this->fname, $this->mode)) ? true : false;
    }

    public function closeFile()
    {
        return fclose($this->fh);
    }

    public function outputCsv($csvarray)
    {
        header("Content-type: text/csv");
        header("Cache-Control: no-store, no-cache");
        header("Content-Disposition: attachment; filename=\"{$this->fname}\"");
        $outstream = fopen("php://output", 'w');

        foreach ($csvarray as $row) {
            fputcsv($outstream, $row, $this->delimiter, $this->enclosure);
        }

        fclose($outstream);
        exit();
    }
}
