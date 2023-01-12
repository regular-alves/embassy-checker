<?php

use Dotenv\Dotenv;
use EmbassyChecker\Models\TelegramSender;
use EmbassyChecker\ChainLinks\{
  Authentication,
  OpenAppointment,
  OpenSite,
  PrintNewSchedule,
  SearchingForNewSpot,
  SelectLastTimeOptions,
  StartingReschedule,
  SubmitPeopleForm,
  SubmitReschedule,
};
use EmbassyChecker\Exceptions\{
  RescheduleNotAvailable,
  TimeSpotSoonerNotAvailable,
};
use Facebook\WebDriver\Exception\{
  NoSuchElementException,
  TimeoutException,
};
use Facebook\WebDriver\Remote\{
  DesiredCapabilities,
  RemoteWebDriver,
};

require 'vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$envVarsAvailable = isset(
    $_ENV['USR_EMAIL'],
    $_ENV['USR_PASSWD'],
    $_ENV['TELEGRAM_TOKEN'],
    $_ENV['WEBDRIVER_LOCATION'],
    $_ENV['TELEGRAM_USER_ID']
);

if (!$envVarsAvailable) {
    throw new \Exception('Please, check your env file.');
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
$searchingForNewSpot = new SearchingForNewSpot('appointments_consulate_appointment_date');
$selectingTimeSpot = new SelectLastTimeOptions('appointments_consulate_appointment_time');
$searchingForNewCasvSpot = new SearchingForNewSpot('appointments_asc_appointment_date');
$selectingCasvTimeSpot = new SelectLastTimeOptions('appointments_asc_appointment_time');
$submitReschedule = new SubmitReschedule();
$printNewSchedule = new PrintNewSchedule();

$openSite->setNext($authentication);
$authentication->setNext($openAppointment);
$openAppointment->setNext($startingReschedule);
$startingReschedule->setNext($submitPeopleForm);
$submitPeopleForm->setNext($searchingForNewSpot);

if ($_ENV['AUTOMATIC_RESCHEDULE']) {
    $searchingForNewSpot->setNext($selectingTimeSpot);
    $selectingTimeSpot->setNext($searchingForNewCasvSpot);
    $searchingForNewCasvSpot->setNext($selectingCasvTimeSpot);
    $selectingCasvTimeSpot->setNext($submitReschedule);
    $submitReschedule->setNext($printNewSchedule);
}

// here we go
$messenger = new TelegramSender();
$notifyEverything = !($_ENV['NOTIFY_ONLY_DATES'] ?? false);

try {
    $openSite->handle($driver, []);
} 
catch (RescheduleNotAvailable | TimeSpotSoonerNotAvailable $exception) {
    if ($notifyEverything) {
        $messenger->sendMessage($exception->getMessage());
    }
} 
catch (NoSuchElementException | TimeoutException $exception) {
    $messenger->sendMessage('Encontrei um erro na execução');    
} 
finally {
    $driver->quit();
}
