<?php

use Dotenv\Dotenv;
use EmbassyChecker\ChainLinks\{
  CheckAndSetupEnvironment,
  Authentication,
  OpenAppointment,
  OpenSite,
  PrintNewSchedule,
  QuitDriver,
  SearchingForNewSpot,
  SelectLastTimeOptions,
  StartingReschedule,
  SubmitPeopleForm,
  SubmitReschedule,
};

require 'vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Setup-ing chain 
$CheckAndSetupEnvironment = new CheckAndSetupEnvironment();
$openSite = new OpenSite();
$authentication = new Authentication();
$openAppointment = new OpenAppointment();
$startingReschedule = new StartingReschedule();
$submitPeopleForm = new SubmitPeopleForm();
$searchingForNewSpot = new SearchingForNewSpot( 'appointments_consulate_appointment_date' );
$selectingTimeSpot = new SelectLastTimeOptions( 'appointments_consulate_appointment_time' );
$searchingForNewCasvSpot = new SearchingForNewSpot( 'appointments_asc_appointment_date' );
$selectingCasvTimeSpot = new SelectLastTimeOptions( 'appointments_asc_appointment_time' );
$submitReschedule = new SubmitReschedule();
$printNewSchedule = new PrintNewSchedule();
$quitDriver = new QuitDriver();

$CheckAndSetupEnvironment->setNext( $openSite );
$openSite->setNext( $authentication );
$authentication->setNext( $openAppointment );
$openAppointment->setNext( $startingReschedule );
$startingReschedule->setNext( $submitPeopleForm );
$submitPeopleForm->setNext( $searchingForNewSpot );

if ( $_ENV['AUTOMATIC_RESCHEDULE'] ) {
  $searchingForNewSpot->setNext( $selectingTimeSpot );
  $selectingTimeSpot->setNext( $searchingForNewCasvSpot );
  $searchingForNewCasvSpot->setNext( $selectingCasvTimeSpot );
  $selectingCasvTimeSpot->setNext( $submitReschedule );
  $submitReschedule->setNext( $printNewSchedule );
  $printNewSchedule->setNext( $quitDriver );
}else{
  $searchingForNewSpot->setNext( $quitDriver );
}

// here we go
$CheckAndSetupEnvironment->handle( null, [] );