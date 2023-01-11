<?php 

namespace EmbassyChecker\ChainLinks;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;

class SubmitReschedule extends Handler {
    public function handle(RemoteWebDriver $driver = null, array $data)
    {
        $driver
            ->findElement( WebDriverBy::id( 'appointments_submit' ) )
            ->click();

        $submitReal = WebDriverBy::cssSelector( '.reveal-overlay .button.alert' );

        $this->waitForBeClickable( $driver, $submitReal );

        $driver
            ->findElement( $submitReal )
            ->click();

        return $this->callNext( $driver, $data );
    }
}