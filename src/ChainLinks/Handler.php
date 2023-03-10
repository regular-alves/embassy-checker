<?php

namespace EmbassyChecker\ChainLinks;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverWait;

abstract class Handler
{
    protected ?self $next = null;

    abstract public function handle(RemoteWebDriver $driver, array $data);

    public function setNext(self $next)
    {
        $this->next = $next;
    }

    /**
     * @throws NoSuchElementException
     * @throws TimeoutException
     */
    protected function waitForPresence(RemoteWebDriver $driver, WebDriverBy $el)
    {
        $waiter = new WebDriverWait($driver, 2);

        $waiter->until(
            WebDriverExpectedCondition::presenceOfAllElementsLocatedBy($el)
        );

        return;
    }

    /**
     * @throws NoSuchElementException
     * @throws TimeoutException
     */
    protected function waitForBeClickable(RemoteWebDriver $driver, WebDriverBy $el)
    {
        $waiter = new WebDriverWait($driver, 2);

        $waiter->until(
            WebDriverExpectedCondition::elementToBeClickable($el)
        );

        return;
    }

    public function callNext(RemoteWebDriver $driver, array $data)
    {
        sleep( rand( 2, 15 ) );

        if ($this->next) {
            return $this
                ->next
                ->handle($driver, $data);
        }

        return;
    }
}
