<?php

namespace EmbassyChecker\ChainLinks;

use Facebook\WebDriver\Remote\{RemoteWebDriver, DesiredCapabilities};

class CheckAndSetupEnvironment extends Handler {
    public function handle(RemoteWebDriver $driver = null, array $data) {
        if ( !isset( $_ENV['USR_EMAIL'], $_ENV['USR_PASSWD'], $_ENV['TELEGRAM_TOKEN'], $_ENV['WEBDRIVER_LOCATION'], $_ENV['TELEGRAM_USER_ID'] ) ) {
            throw new \Exception( 'Please, check your env file.' );
        }

        $driver = RemoteWebDriver::create(
            $_ENV['WEBDRIVER_LOCATION'], 
            DesiredCapabilities::chrome()
        );

        return $this->callNext( $driver, $data );
    }
}