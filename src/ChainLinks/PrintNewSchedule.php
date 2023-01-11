<?php

namespace EmbassyChecker\ChainLinks;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use EmbassyChecker\Models\TelegramSender;

class PrintNewSchedule extends Handler
{
    public function handle(?RemoteWebDriver $driver, array $data)
    {
        $this->waitForPresence($driver, WebDriverBy::id('footer'));

        // waiting for the page loading
        sleep(5);

        $sender = new TelegramSender();
        $location = '/tmp/' . date('YmdHis') . '.png';

        takeFullScreenshot($driver, $location);

        $sender->sendFile($location);

        $this->callNext($driver, $data);
    }
}
