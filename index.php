<?php

use Dotenv\Dotenv;
use EmbassyChecker\ChainLinks\Authentication;
use EmbassyChecker\ChainLinks\OpenAppointment;
use EmbassyChecker\ChainLinks\OpenSite;
use EmbassyChecker\ChainLinks\QuitDriver;
use EmbassyChecker\ChainLinks\SearchingForNewSpot;
use EmbassyChecker\ChainLinks\StartingReschedule;
use EmbassyChecker\ChainLinks\SubmitPeopleForm;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;

require 'vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

if ( !isset( $_ENV['USR_EMAIL'], $_ENV['USR_PASSWD'], $_ENV['TELEGRAM_TOKEN'], $_ENV['WEBDRIVER_LOCATION'], $_ENV['TELEGRAM_USER_ID'] ) ) {
  throw new \Exception( 'Please, check your env file.' );
}

$driver = RemoteWebDriver::create(
  $_ENV['WEBDRIVER_LOCATION'], 
  DesiredCapabilities::chrome()
);

// Setup-ing chain 
$openSite = new OpenSite();
$authentication = new Authentication();
$openAppointment = new OpenAppointment();
$startingReschedule = new StartingReschedule();
$submitPeopleForm = new SubmitPeopleForm();
$searchingForNewSpot = new SearchingForNewSpot();
$quitDriver = new QuitDriver();

$openSite->setNext( $authentication );
$authentication->setNext( $openAppointment );
$openAppointment->setNext( $startingReschedule );
$startingReschedule->setNext( $submitPeopleForm );
$submitPeopleForm->setNext( $searchingForNewSpot );
$searchingForNewSpot->setNext( $quitDriver );

// here we go
$openSite->handle( $driver, [] );