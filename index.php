<?php

use Dotenv\Dotenv;
use EmbassyChecker\Models\TelegramSender;
use Facebook\WebDriver\Exception\TimeoutException;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverWait;

require 'vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

if ( !isset( $_ENV['USR_EMAIL'], $_ENV['USR_PASSWD'], $_ENV['TELEGRAM_TOKEN'], $_ENV['WEBDRIVER_LOCATION'], $_ENV['TELEGRAM_USER_ID'] ) ) {
  throw new \Exception( 'Please, check your env file.' );
}

$notifyOnlyDates = $_ENV['NOTIFY_ONLY_DATES'] ?? false;
$url = 'https://ais.usvisa-info.com/pt-br';

$telegram = new TelegramSender();
$driver = RemoteWebDriver::create(
  $_ENV['WEBDRIVER_LOCATION'], 
  DesiredCapabilities::chrome()
);

$driver->get( "$url/niv/users/sign_in" );

#login
$driver->findElement( WebDriverBy::id( 'user_email' ) )
  ->sendKeys( $_ENV['USR_EMAIL'] );

$driver->findElement( WebDriverBy::id( 'user_password' ) )
  ->sendKeys( $_ENV['USR_PASSWD'] );

$driver->findElement( WebDriverBy::cssSelector( 'label[for="policy_confirmed"]' ) )
  ->click();

$driver->findElement( WebDriverBy::id( 'sign_in_form' ) )
  ->submit();

# opening appointment
$appointment = WebDriverBy::cssSelector( '.attend_appointment .actions .button.primary' );

( new WebDriverWait( $driver, 2 ) )->until(
  WebDriverExpectedCondition::presenceOfAllElementsLocatedBy( $appointment ) 
);

$innerHtml = $driver->findElement( WebDriverBy::cssSelector( '.application.attend_appointment.success p.asc-appt' ) )
  ->getText();

if ( preg_match( '/(\d+) ([\w]+), (\d+)/', $innerHtml, $matches ) ) {
  $ptToEn = [
    'january' => 'janeiro',
    'february' => 'fevereiro',
    'march' => 'março',
    'april' => 'abril',
    'may' => 'maio',
    'june' => 'junho',
    'july' => 'julho',
    'august' => 'agosto',
    'september' => 'setembro',
    'october' => 'outubro',
    'november' => 'novembro',
    'december' => 'dezembro',
  ];

  $appointmentDate = str_replace( 
    $ptToEn,
    array_keys( $ptToEn ),
    strtolower( "$matches[3]-$matches[2]-$matches[1]" )
  );

  $appointmentDate = strtotime( $appointmentDate );
}

$driver->findElement( $appointment )
  ->click();

# moving to reschedule option
$rescheduleOption = WebDriverBy::cssSelector('#forms .accordion .accordion-item a .fa-calendar-minus' );

( new WebDriverWait( $driver, 2 ) )->until(
  WebDriverExpectedCondition::presenceOfAllElementsLocatedBy( $rescheduleOption ) 
);

$driver->findElement( $rescheduleOption )
  ->click();

sleep(2);

$driver->findElement( WebDriverBy::cssSelector('#forms .accordion .accordion-item.is-active .accordion-content .button.primary' ) )
  ->click();

# starting rescheduling
( new WebDriverWait( $driver, 2 ) )->until(
  WebDriverExpectedCondition::presenceOfAllElementsLocatedBy( WebDriverBy::cssSelector('.mainContent form')) 
);

$driver->findElement( WebDriverBy::cssSelector('.mainContent form' ) )
  ->submit();

# opening calendar
$calendar = WebDriverBy::id( 'appointments_consulate_appointment_date' );

try {
  ( new WebDriverWait( $driver, 2 ) )->until(
    WebDriverExpectedCondition::elementToBeClickable( $calendar ) 
  );
}catch(TimeoutException $exception) {
  if ( ! $notifyOnlyDates ) {
    $telegram->sendMessage('Reagendamento não está disponível');
  }

  $driver->quit();
  die();
}

$driver->findElement( $calendar )->click();

$foundDate = false;
$tries = 0;

while( ! $foundDate && $tries < 20 ) {
  try {
    $foundDate = $driver
      ->findElements( 
        WebDriverBy::cssSelector( '#ui-datepicker-div .ui-datepicker-calendar tbody td:not(.ui-state-disabled)' ) 
      );
  }catch(Exception $exception) {
    $foundDate = false;
  }

  if ( $foundDate ) {
    $seenDate = strtotime(
      $foundDate[0]->getText() .
      $driver
        ->findElement( WebDriverBy::cssSelector( '#ui-datepicker-div .ui-datepicker-group-last .ui-datepicker-header .ui-datepicker-title') )
        ->getText()
    );

    break;
  }

  $next = WebDriverBy::cssSelector( '#ui-datepicker-div .ui-datepicker-group-last .ui-datepicker-next' );

  ( new WebDriverWait( $driver, 2 ) )->until(
    WebDriverExpectedCondition::elementToBeClickable( $next ) 
  );

  $driver->findElement( $next )->click();

  sleep( 1 );
}

$isSooner = $seenDate < $appointmentDate;

if ( ! $notifyOnlyDates && ! $isSooner ) {
  $telegram->sendMessage( 'Não encontrei datas mais recentes' );
}

if ( $isSooner ) {
  $telegram->sendMessage(
    'Encontrei vagas para ' . date( 'd/m/Y', $seenDate ) . ".\n" . $url,
    true
  );
}

$driver->quit();