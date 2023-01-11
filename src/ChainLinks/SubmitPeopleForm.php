<?php

namespace EmbassyChecker\ChainLinks;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;

class SubmitPeopleForm extends Handler {
    public function handle(RemoteWebDriver $driver = null, array $data) {
        $form = WebDriverBy::cssSelector('.mainContent form');
        
        $this->waitForPresence( $driver, $form );
        
        $driver->findElement( $form )
            ->submit();

        return $this->callNext( $driver, $data );
    }
}