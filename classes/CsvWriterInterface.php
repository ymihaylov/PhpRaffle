<?php
namespace PhpRaffle;

interface CsvWriterInterface
{
    public function __construct($fname, $mode);

    public function setHead($head);

    public function writeFromArray($arr);

    public function writeLine($line);

    public function openFile();

    public function closeFile();
}
