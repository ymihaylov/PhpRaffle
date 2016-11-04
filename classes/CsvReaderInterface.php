<?php
namespace PhpRaffle;

interface CsvReaderInterface
{
    public function __construct($fname);

    public function setHead($head);

    public function readToArray();

    public function readLine();

    public function openFile();

    public function closeFile();
}
