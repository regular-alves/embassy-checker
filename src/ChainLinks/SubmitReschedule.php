<?php

namespace EmbassyChecker\ChainLinks;

use EmbassyChecker\Models\TelegramSender;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;

class SubmitReschedule extends Handler
{
    private $messenger = null;
    
    public function __construct()
    {
        $this->messenger = new TelegramSender();
    }

    public function handle(RemoteWebDriver $driver, array $data)
    {
        $driver
            ->findElement(WebDriverBy::id('appointments_submit'))
            ->click();

        $submitReal = WebDriverBy::cssSelector('.reveal-overlay .button.alert');

        $this->waitForBeClickable($driver, $submitReal);

        $driver
            ->findElement($submitReal)
            ->click();

        $this->messenger->sendMessage(
            'Reagendado para ' . date( 'd/m/Y', $data['new-schedule'] ),
            true
        );

        return $this->callNext($driver, $data);
    }
}
