<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('draw a random attendee');
$I->amOnPage('/');
$I->see('Draw');
$I->click('button[name=draw]');
$I->wait(15);
$I->see("Let's see who's next!");
