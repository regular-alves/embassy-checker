<?php

namespace EmbassyChecker\ChainLinks;

use Exception;
use EmbassyChecker\Models\TelegramSender;
use EmbassyChecker\Helpers\DateTimeChecker;
use EmbassyChecker\Exceptions\{ RescheduleNotAvailable, TimeSpotSoonerNotAvailable };
use Facebook\WebDriver\{WebDriverBy, WebDriverSelect};
use Facebook\WebDriver\Remote\{ RemoteWebDriver, RemoteWebElement };
use Facebook\WebDriver\Exception\{NoSuchElementException, TimeoutException};

class SearchingForNewSpot extends Handler
{
    private $messenger = null;
    private bool $notifyEverything = true;
    private bool $automaticSchedule = false;
    private ?int $rescheduleAfter;
    private ?int $rescheduleBefore;
    private string $dateFieldId;
    private string $timeFieldId;
    private int $preferredAfter;

    public function __construct(string $dateFieldId, string $timeFieldId)
    {

        $this->dateFieldId = $dateFieldId;
        $this->timeFieldId = $timeFieldId;

        $this->messenger = new TelegramSender();
        
        $this->notifyEverything = !($_ENV['NOTIFY_ONLY_DATES'] ?? false);
        $this->automaticSchedule = $_ENV['AUTOMATIC_RESCHEDULE'] ?? false;
        $this->rescheduleAfter = isset( $_ENV['RESCHEDULE_AFTER_DATE'] ) ? strtotime( $_ENV['RESCHEDULE_AFTER_DATE'] ) : null;
        $this->rescheduleBefore = isset( $_ENV['RESCHEDULE_BEFORE_DATE'] ) ? strtotime( $_ENV['RESCHEDULE_BEFORE_DATE'] ) : null;
        $this->preferredAfter = (int) env('RESCHEDULE_AFTER_HOURS') ?? 0;
    }

    public function handle(RemoteWebDriver $driver, array $data)
    {
        $this->openCalendar( $driver );

        $spotFound = false;
        $tries = 0;
        
        while ( ! $spotFound && $tries < 20 ) {
            try {
                $foundDate = $this->getDay( $driver );
            } catch (Exception $exception) {
                $this->nextMonth( $driver );
                
                $tries++;
                continue;
            }

            $availableDay = $foundDate->getText();
            $availableMonthYear = $this->getMonthYear( $driver );
            $availableFullDate = strtotime("$availableDay$availableMonthYear");
            
            if( ! DateTimeChecker::isDateBetween( $availableFullDate, $this->rescheduleAfter, $this->rescheduleBefore ) ) {
                $driver->executeScript(
                    "arguments[0].classList.add('ignore');",
                    [ $foundDate ],
                );

                $tries++;
                continue;
            }

            if ( $availableFullDate >= ($data['appointment-date'] ?? PHP_INT_MAX)) {
                if ( $this->notifyEverything ) {
                    throw new TimeSpotSoonerNotAvailable('Não encontrei datas mais recentes');
                }

                return;
            }

            if ( ! $this->automaticSchedule ) {
                $this->messenger->sendMessage(
                    sprintf("Encontrei vagas para %s.\n%s", date('d/m/Y', $availableFullDate), $data['url']),
                    true
                );
    
                return;
            }
            
            $foundDate->click();

            $timeOptions = WebDriverBy::cssSelector("#{$this->timeFieldId} option:not(:empty)");

            $this->waitForPresence($driver, $timeOptions);

            $timeSpots = array_map(
                fn( $item ) => $item->getText(),
                $driver->findElements( $timeOptions )
            );

            $availableSpots = DateTimeChecker::getTimesBetween( $timeSpots, $this->preferredAfter );

            if ( ! $availableSpots ) {
                // $this->openCalendar( $driver );

                $driver->executeScript(
                    "arguments[0].classList.add('ignore');",
                    [ $foundDate ],
                );

                $tries++;
                continue;
            }

            $lastTimeSpot = array_pop( $availableSpots );

            $field = new WebDriverSelect(
                $driver->findElement( WebDriverBy::id( $this->timeFieldId ) )
            );

            $field->selectByVisibleText( $lastTimeSpot );

            $data['new-schedule'] = $availableFullDate;
            $spotFound = true;
        }

        return $spotFound 
            ? $this->callNext($driver, $data)
            : null;
    }

    private function openCalendar( RemoteWebDriver $driver ): void {
        $calendar = WebDriverBy::id($this->dateFieldId);

        try {
            $this->waitForBeClickable($driver, $calendar);
        } catch (TimeoutException | NoSuchElementException $exception) {
            throw new RescheduleNotAvailable('Reagendamento não está disponível');
        }

        $driver->findElement( $calendar )
            ->click();
    }

    private function getDay( RemoteWebDriver $driver ): RemoteWebElement {
        return $driver->findElement(
            WebDriverBy::cssSelector(
                '#ui-datepicker-div .ui-datepicker-calendar tbody ' .
                'td:not(.ui-state-disabled):not(.ignore)'
            )
        );
    }

    private function getMonthYear( $driver ): string {
        return $driver
            ->findElement(
                WebDriverBy::cssSelector(
                    '#ui-datepicker-div .ui-datepicker-group-last .ui-datepicker-header .ui-datepicker-title'
                )
            )
            ->getText();
    }

    private function nextMonth( RemoteWebDriver $driver ): void {
        $nextMonth = WebDriverBy::cssSelector('#ui-datepicker-div .ui-datepicker-group-last .ui-datepicker-next');

        $this->waitForBeClickable($driver, $nextMonth);
        $driver->findElement($nextMonth)->click();

        // waiting for js animation finishes
        sleep(1);
    }
}
