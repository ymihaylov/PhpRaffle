<?php
// TODO: Replace this with a nice PSR-4 style autoloading!!!
require_once '../classes/Raffler.php';

use PhpRaffle\Raffler;
use PhpRaffle\AllDrawnException;
use PhpRaffle\NoMoreAwardsException;

class RafflerTest extends PHPUnit_Framework_TestCase
{
    public function getCsvConfAndLine()
    {
        // TODO: Replace with Faker values
        $randId = rand(10000, 99999);
        return [
            // Case when ID set
            [
                [
                    'id'        => 'ID',
                    'name'      => 'Name',
                ],
                [
                    'ID'        => $randId,
                    'Name'      => 'John Smith',
                ],
                $randId
            ],
            // Case when no ID, but email set
            [
                [
                    'email'     => 'Email',
                    'name'      => 'Name',
                ],
                [
                    'Email'     =>  $randId . '@yourdomain.com',
                    'Name'      => 'John Smith',
                ],
                $randId . '@yourdomain.com'
            ],
            // If none set, hash expected
            [
                [
                    'name'      => 'Name',
                    'something' => 'Whatever',
                ],
                [
                    'Name'      => 'John Smith',
                    'Whatever'  => 'Bla bla lorem ipsum',
                ],
                md5('John Smith'.'Bla bla lorem ipsum')
            ],
        ];
    }

    /**
     * @dataProvider getCsvConfAndLine
     */
    public function testGetPrimaryKey($csvConfig, $mockLine, $expKey)
    {
        $options = ['csvHead' => $csvConfig];
        $raffler = new Raffler($options);

        // Otherwise. if email is set, it will be the P.K.
        $this->assertEquals($expKey, $raffler->getPrimaryKey($mockLine));
    }

    public function testAllDrawnException()
    {
        $this->expectException(AllDrawnException::class);

        $mockWinners = $this->getThreeMockAttendees();

        $raffler = new Raffler;
        $raffler->setWinners($mockWinners);
        $raffler->setAttendees($mockWinners);

        $raffler->draw();
    }

    public function testAllAwardsDrawnException()
    {
        $this->expectException(NoMoreAwardsException::class);

        $i = 0;
        $mockWinners    = $this->getThreeMockAttendees();
        $mockAttendees  = $mockWinners + ['name' => 'Gancho'];

        $raffler = new Raffler;
        $raffler->setWinners($mockWinners);
        $raffler->setAttendees($mockAttendees);
        $raffler->setAwards($this->getThreeMockAwards());

        $raffler->draw();
    }

    private function getThreeMockAttendees()
    {
        $i = 0;
        return [
            ['name' => 'Gosho'],
            ['name' => 'Pesho'],
            ['name' => 'Tosho'],
        ];
    }

    private function getThreeMockAwards()
    {
        return [
            'panica',
            'lazhica',
            'tigan',
        ];
    }

    public function testDrawSuccess()
    {
        $mockAttendees  = $this->getThreeMockAttendees();
        $mockAwards     = $this->getThreeMockAwards();

        $raffler = new Raffler;
        $raffler->setAttendees($mockAttendees);
        $raffler->setAwards($mockAwards);

        $i              = 0;
        $expectedAward  = $mockAwards[$i];
        $award          = null;

        $drawn  = $raffler->draw($award);
        $key    = $raffler->getPrimaryKey($drawn);

        $this->assertTrue((bool) $drawn);
        $this->assertEquals($expectedAward, $award);
    }
}
