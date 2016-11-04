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

        $i = 0;
        $mockWinners = [
            'key' . ++$i  => ['name' => 'Gosho'],
            'key' . ++$i  => ['name' => 'Pesho'],
            'key' . ++$i  => ['name' => 'Tosho'],
        ];
        $raffler = new Raffler;
        $raffler->setWinners($mockWinners);
        $raffler->setAttendees($mockWinners);

        $raffler->draw();
    }

    public function testAllAwardsDrawnException()
    {
        $this->expectException(NoMoreAwardsException::class);

        $i = 0;
        $mockWinners = [
            'key' . ++$i  => ['name' => 'Gosho'],
            'key' . ++$i  => ['name' => 'Pesho'],
            'key' . ++$i  => ['name' => 'Tosho'],
        ];
        $mockAttendees = $mockWinners + [
            'key' . ++$i  => ['name' => 'Gancho'],
        ];

        $raffler = new Raffler;
        $raffler->setWinners($mockWinners);
        $raffler->setAttendees($mockAttendees);

        $raffler->setAwards([
            'panica',
            'lazhica',
            'tigan',
        ]);

        $raffler->draw();
    }
}
