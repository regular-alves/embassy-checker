<?php 

namespace EmbassyChecker\ChainLinks;

use Facebook\WebDriver\Remote\RemoteWebDriver;

class QuitDriver extends Handler {
    public function handle( RemoteWebDriver $driver, array $data ) {
        $driver->quit();

        return $this->callNext( $driver, $data );
    }
}