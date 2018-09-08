<?php
require_once "autoload.php";

use PhpRaffle\Raffler;

$options = [
    'attendeesFilename'     => 'september_laravel_jetbrains_contestants.csv',
    'csvHead'               => ['Signed At','Name', 'Profile Link'],
];

$raffler = new Raffler($options);
$raffler->init();

// $randomAttendees = $raffler->getRandomAttendees(50);
// var_dump($randomAttendees);
// exit;

if (isset($_GET['getRandom']))
{
    $number = (int) $_GET['getRandom'];
    $randomAttendees = $raffler->getRandomAttendees($number);

    echo json_encode($randomAttendees);
    exit;
}

if (isset($_POST['winner']))
{
    // process post winner array into an obj
    $winner = json_decode($_POST['winner']);
    $raffler->markDrawn($winner);
    echo json_encode(1);
    exit;
}

if (isset($_POST['noshow']))
{
    // process post noshow array into an obj
    $noshow = json_decode($_POST['noshow']);
    $raffler->markNoShow($noshow);
    echo json_encode(1);
    exit;
}

$award  = null;
$winner = $raffler->draw($award);
echo json_encode(['winner' => $winner, 'award' => $award]);
exit;
