<?php 

namespace EmbassyChecker\ChainLinks;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverSelect;

class SelectLastTimeOptions extends Handler {
    private $fieldId;

    public function __construct( string $fieldId ) {
        $this->fieldId = $fieldId;
    }

    public function handle(RemoteWebDriver $driver = null, array $data) {
        $lastOption = WebDriverBy::cssSelector( "#{$this->fieldId} option:not(:empty)" );

        $this->waitForPresence( $driver, $lastOption );

        $elements = $driver
            ->findElements( $lastOption );

        $element = array_pop( $elements );
        $lastTimeSpot = $element->getText();

        $field = new WebDriverSelect(
            $driver->findElement( WebDriverBy::id( $this->fieldId ) )
        );

        $field->selectByVisibleText( $lastTimeSpot );

        return $this->callNext( $driver, $data );        
    }
}