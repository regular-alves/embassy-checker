<?php

namespace EmbassyChecker\ChainLinks;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;

class Authentication extends Handler {
    public function handle( RemoteWebDriver $driver = null, array $data ) {
        $userEmailField = WebDriverBy::id( 'user_email' );

        $this->waitForPresence( $driver, $userEmailField );

        $driver->findElement( $userEmailField )
            ->sendKeys( $_ENV['USR_EMAIL'] );

        $driver->findElement( WebDriverBy::id( 'user_password' ) )
            ->sendKeys( $_ENV['USR_PASSWD'] );

        $driver->findElement( WebDriverBy::cssSelector( 'label[for="policy_confirmed"]' ) )
            ->click();

        $driver->findElement( WebDriverBy::id( 'sign_in_form' ) )
            ->submit();
        
        return $this->callNext( $driver, $data );
    }
}