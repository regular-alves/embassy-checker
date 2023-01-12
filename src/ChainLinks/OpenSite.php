<?php

namespace EmbassyChecker\ChainLinks;

use Facebook\WebDriver\Remote\RemoteWebDriver;

class OpenSite extends Handler
{
    public function handle(RemoteWebDriver $driver, array $data)
    {
        $data['url'] = 'https://ais.usvisa-info.com/pt-br';

        $driver->get("$data[url]/niv/users/sign_in");

        $this->callNext($driver, $data);
    }
}
