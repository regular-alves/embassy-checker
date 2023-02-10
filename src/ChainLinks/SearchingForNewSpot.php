<?php

namespace EmbassyChecker\ChainLinks;

use Exception;
use EmbassyChecker\Exceptions\RescheduleNotAvailable;
use EmbassyChecker\Exceptions\TimeSpotSoonerNotAvailable;
use EmbassyChecker\Models\TelegramSender;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Exception\{NoSuchElementException, TimeoutException};

class SearchingForNewSpot extends Handler
{
    private $messenger = null;
    private $notifyEverything = true;
    private $automaticSchedule = false;
    private $rescheduleAfter = false;
    private $rescheduleBefore = false;
    private $fieldId;

    public function __construct(string $fieldId)
    {
        $this->messenger = new TelegramSender();
        $this->notifyEverything = $_ENV['NOTIFY_ONLY_DATES'] ?? false;
        $this->automaticSchedule = $_ENV['AUTOMATIC_RESCHEDULE'] ?? false;
        $this->rescheduleAfter = isset( $_ENV['RESCHEDULE_AFTER_DATE'] ) ? strtotime( $_ENV['RESCHEDULE_AFTER_DATE'] ) : false;
        $this->rescheduleBefore = isset( $_ENV['RESCHEDULE_BEFORE_DATE'] ) ? strtotime( $_ENV['RESCHEDULE_BEFORE_DATE'] ) : false;
        $this->fieldId = $fieldId;
    }

    public function handle(RemoteWebDriver $driver, array $data)
    {
        $calendar = WebDriverBy::id($this->fieldId);

        try {
            $this->waitForBeClickable($driver, $calendar);
        } catch (TimeoutException | NoSuchElementException $exception) {
            throw new RescheduleNotAvailable('Reagendamento não está disponível');
        }

        $driver->findElement($calendar)->click();

        $availableDate = 0;
        $foundDate = false;
        $tries = 0;
        
        while ( ! $foundDate && $tries < 20 ) {
            try {
                $foundDate = $driver
                    ->findElements(
                        WebDriverBy::cssSelector(
                            '#ui-datepicker-div .ui-datepicker-calendar tbody td:not(.ui-state-disabled)'
                        )
                    );
            } catch (Exception $exception) {
                $foundDate = false;
            }

            if ( ! $foundDate ) {
                $nextMonth = WebDriverBy::cssSelector('#ui-datepicker-div .ui-datepicker-group-last .ui-datepicker-next');

                $this->waitForBeClickable($driver, $nextMonth);
                $driver->findElement($nextMonth)->click();

                // waiting for js animation finishes
                sleep(1);
                
                $tries++;
                continue;
            }

            $availableDay = $foundDate[0]->getText();
            $availableMonthYear = $driver
                ->findElement(
                    WebDriverBy::cssSelector(
                        '#ui-datepicker-div .ui-datepicker-group-last .ui-datepicker-header .ui-datepicker-title'
                    )
                )
                ->getText();

            $availableDate = strtotime("$availableDay$availableMonthYear");
            
            if( $this->rescheduleAfter && $this->rescheduleAfter > $availableDate ) {
                $foundDate = false;
                
                $tries++;
                continue;
            }

            if( $this->rescheduleBefore && $this->rescheduleBefore < $availableDate ) {
                $foundDate = false;

                $tries++;
                continue;
            }

            break;
        }

        if ( $availableDate >= ($data['appointment-date'] ?? PHP_INT_MAX)) {
            if ($this->notifyEverything ) {
                throw new TimeSpotSoonerNotAvailable('Não encontrei datas mais recentes');
            }

            return;
        }

        if ( ! $this->automaticSchedule ) {
            $this->messenger->sendMessage(
                sprintf("Encontrei vagas para %s.\n%s", date('d/m/Y', $availableDate), $data['url']),
                true
            );

            return;
        }
        
        $foundDate[0]->click();

        return $this->callNext($driver, $data);
    }
}
