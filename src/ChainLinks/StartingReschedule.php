<?php

namespace EmbassyChecker\ChainLinks;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;

class StartingReschedule extends Handler
{
    public function handle(?RemoteWebDriver $driver, array $data)
    {
        $rescheduleOption = WebDriverBy::cssSelector('#forms .accordion .accordion-item a .fa-calendar-minus');

        $this->waitForPresence($driver, $rescheduleOption);

        $driver->findElement($rescheduleOption)
            ->click();

        // waiting for js animation finishes
        sleep(2);

        $driver
            ->findElement(
                WebDriverBy::cssSelector(
                    '#forms .accordion .accordion-item.is-active .accordion-content .button.primary'
                )
            )
            ->click();

        return $this->callNext($driver, $data);
    }
}
