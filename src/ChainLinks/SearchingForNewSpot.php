<?php

namespace EmbassyChecker\ChainLinks;

use Exception;
use EmbassyChecker\Models\TelegramSender;
use Facebook\WebDriver\Exception\TimeoutException;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;

class SearchingForNewSpot extends Handler {
    private $messenger = null;
    private $notifyEverything = true;

    public function __construct() {
        $this->messenger = new TelegramSender();
        $this->notifyEverything = $_ENV['NOTIFY_ONLY_DATES'] ?? false;
    }

    public function handle( RemoteWebDriver $driver, array $data ) {
        $calendar = WebDriverBy::id( 'appointments_consulate_appointment_date' );

        try {
            $this->waitForBeClickable( $driver, $calendar );
        }catch(TimeoutException $exception) {
            if ( ! $this->notifyEverything ) {
                $this->messenger
                    ->sendMessage('Reagendamento não está disponível');
            }

            return $this->callNext( $driver, $data );
        }

        $driver->findElement( $calendar )->click();

        $availableDate = 0;
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
                $availableDay = $foundDate[0]->getText();
                $availableMonthYear = $driver
                    ->findElement(
                        WebDriverBy::cssSelector(
                            '#ui-datepicker-div .ui-datepicker-group-last .ui-datepicker-header .ui-datepicker-title'
                        )
                    )
                    ->getText();
                
                $availableDate = strtotime( "$availableDay$availableMonthYear" );
                break;
            }

            $nextMonth = WebDriverBy::cssSelector( '#ui-datepicker-div .ui-datepicker-group-last .ui-datepicker-next' );

            $this->waitForBeClickable( $driver, $nextMonth );
            $driver->findElement( $nextMonth )->click();

            // waiting for js animation finishes
            sleep( 1 );
            $tries++;
        }

        $isSooner = $availableDate <
            ( $data['appointment-date'] ?? PHP_INT_MAX );

        if ( ! $this->notifyEverything && ! $isSooner ) {
            $this->messenger->sendMessage( 'Não encontrei datas mais recentes' );
        }

        if ( $isSooner ) {
            $this->messenger->sendMessage(
                sprintf( "Encontrei vagas para %s.\n%s", date( 'd/m/Y', $availableDate ), $data['url'] ),
                true
            );
        }

        return $this->callNext( $driver, $data );
    }
}