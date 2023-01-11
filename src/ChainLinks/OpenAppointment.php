<?php

namespace EmbassyChecker\ChainLinks;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;

class OpenAppointment extends Handler {
    private const MONTH_TRANSLATIONS = [
        'january' => 'janeiro',
        'february' => 'fevereiro',
        'march' => 'março',
        'april' => 'abril',
        'may' => 'maio',
        'june' => 'junho',
        'july' => 'julho',
        'august' => 'agosto',
        'september' => 'setembro',
        'october' => 'outubro',
        'november' => 'novembro',
        'december' => 'dezembro',
    ];

    public function handle(RemoteWebDriver $driver = null, array $data) {
        $appointment = WebDriverBy::cssSelector( '.attend_appointment .actions .button.primary' );

        $this->waitForPresence( $driver, $appointment );

        $dateInnerHtml = $driver
            ->findElement( WebDriverBy::cssSelector( '.application.attend_appointment.success p.asc-appt' ) )
            ->getText();

        if ( preg_match( '/(\d+) ([\w]+), (\d+)/', $dateInnerHtml, $matches ) ) {
            $appointmentDate = str_replace( 
                $this::MONTH_TRANSLATIONS,
                array_keys( $this::MONTH_TRANSLATIONS ),
                strtolower( "$matches[3]-$matches[2]-$matches[1]" )
            );

            $data['appointment-date'] = strtotime( $appointmentDate );
        }

        $driver
            ->findElement( $appointment )
            ->click();
        
        return $this->callNext( $driver, $data );
    }
}