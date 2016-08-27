<?php
// TODO: Replace this with a nice PSR-4 style autoloading!!!
require_once '../classes/Raffler.php';

use PhpRaffle\Raffler;

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
}
