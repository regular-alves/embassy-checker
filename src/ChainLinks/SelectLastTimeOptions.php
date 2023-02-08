<?php

namespace EmbassyChecker\ChainLinks;

use EmbassyChecker\Exceptions\TimeSpotSoonerNotAvailable;
use Exception;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverSelect;

class SelectLastTimeOptions extends Handler
{
    private string $fieldId;
    private int $preferredAfter;

    public function __construct(string $fieldId)
    {
        $this->fieldId = $fieldId;
        $this->preferredAfter = (int) env('RESCHEDULE_AFTER_HOURS') ?? 0;
    }

    public function handle(RemoteWebDriver $driver, array $data)
    {
        $lastOption = WebDriverBy::cssSelector("#{$this->fieldId} option:not(:empty)");

        $this->waitForPresence($driver, $lastOption);

        $elements = $driver
            ->findElements($lastOption);

        if ( $this->preferredAfter ) {
            foreach ( $elements as $element ) {
                $timeSpotText = $element->getText();
        
                preg_match( '/^(\d+)\:(\d+)$/', $timeSpotText, $match );

                if ( $this->preferredAfter > $timeSpotText ) {
                    continue;
                }

                $lastTimeSpot = $timeSpotText;
                break;
            }
        }else{
            $element = array_pop($elements);
            $lastTimeSpot = $element->getText();
        }

        if ( ! $lastTimeSpot ) {
            throw new TimeSpotSoonerNotAvailable('Não encontrei horários para o dia');
        }

        $field = new WebDriverSelect(
            $driver->findElement(
                WebDriverBy::id($this->fieldId)
            )
        );

        $field->selectByVisibleText($lastTimeSpot);

        return $this->callNext($driver, $data);
    }
}
