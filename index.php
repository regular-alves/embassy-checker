<?php

use Dotenv\Dotenv;
use EmbassyChecker\Models\TelegramSender;
use EmbassyChecker\ChainLinks\{
  Authentication,
  OpenAppointment,
  OpenSite,
  PrintNewSchedule,
  SearchingForNewSpot,
  StartingReschedule,
  SubmitPeopleForm,
  SubmitReschedule,
};
use EmbassyChecker\Exceptions\{
  RescheduleNotAvailable,
  TimeSpotSoonerNotAvailable,
};
use Facebook\WebDriver\Chrome\ChromeDriver;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Exception\{
  NoSuchElementException,
  TimeoutException,
};
use Facebook\WebDriver\Remote\DesiredCapabilities;

require 'vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$cliVars = getopt(
    '',
    [
        'USR_EMAIL::',
        'USR_PASSWD::',
        'TELEGRAM_TOKEN::',
        'TELEGRAM_USER_ID::',
        'WEBDRIVER_LOCATION::',
        'AUTOMATIC_RESCHEDULE::',
        'RESCHEDULE_AFTER_DATE::',
        'RESCHEDULE_BEFORE_DATE::',
        'APPOINTMENT_ID::',
    ]
);

$_ENV = array_merge( 
    $_ENV ?: [],
    $cliVars ?: []
);

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

$ops = new ChromeOptions();
$capabilities = DesiredCapabilities::chrome();

$ops->addArguments(
    [
        '--user-agent=' .
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) ' .
        'AppleWebKit/537.36 (KHTML, like Gecko) ' .
        'Chrome/87.0.4324.104 Safari/537.36'
    ]
);

$ops->setExperimentalOption( 'excludeSwitches', [ 'enable-automation' ] );
$capabilities->setCapability( ChromeOptions::CAPABILITY, $ops );

$driver = ChromeDriver::start( $capabilities );

// Setup-ing chain
$openSite = new OpenSite();
$authentication = new Authentication();
$openAppointment = new OpenAppointment();
$startingReschedule = new StartingReschedule();
$submitPeopleForm = new SubmitPeopleForm();
$searchingForNewSpot = new SearchingForNewSpot( 'appointments_consulate_appointment_date', 'appointments_consulate_appointment_time' );
$searchingForNewCasvSpot = new SearchingForNewSpot( 'appointments_asc_appointment_date', 'appointments_asc_appointment_time' );
$submitReschedule = new SubmitReschedule();
$printNewSchedule = new PrintNewSchedule();

$openSite->setNext($authentication);
$authentication->setNext($openAppointment);
$openAppointment->setNext($startingReschedule);
$startingReschedule->setNext($searchingForNewSpot);
// $startingReschedule->setNext($submitPeopleForm);

if ($_ENV['AUTOMATIC_RESCHEDULE']) {
    $searchingForNewSpot->setNext($searchingForNewCasvSpot);
    $searchingForNewCasvSpot->setNext($submitReschedule);
    $submitReschedule->setNext($printNewSchedule);
}

// here we go
$messenger = new TelegramSender();
$notifyEverything = !($_ENV['NOTIFY_ONLY_DATES'] ?? false);

try {
    $openSite->handle( $driver, [] );
} 
catch (RescheduleNotAvailable | TimeSpotSoonerNotAvailable $exception) {
    if ($notifyEverything) {
        $messenger->sendMessage($exception->getMessage());
    }
} 
catch (NoSuchElementException | TimeoutException $exception) {
    $messenger->sendMessage('Encontrei um erro na execução');    
} 
catch (Throwable $throw) {
    echo $throw->getMessage();
}
finally {
    $driver->quit();
}
