<?php

use Facebook\WebDriver\Remote\RemoteWebDriver;

/**
 * page full capture for https://github.com/facebook/php-webdriver
 *
 * @param RemoteWebDriver $driver
 * @param string $screenshot_name capture save path
 * @throws Exception
 */
function takeFullScreenshot(RemoteWebDriver $driver, string $screenshot_name): void
{
    $total_width = $driver->executeScript(
        'return Math.max.apply(
            null,
            [
                document.body.clientWidth,
                document.body.scrollWidth,
                document.documentElement.scrollWidth,
                document.documentElement.clientWidth
            ]
        )'
    );

    $total_height = $driver->executeScript(
        'return Math.max.apply(
            null,
            [
                document.body.clientHeight,
                document.body.scrollHeight,
                document.documentElement.scrollHeight,
                document.documentElement.clientHeight
            ]
        )'
    );

    $viewport_width = $driver->executeScript('return document.documentElement.clientWidth');
    $viewport_height = $driver->executeScript('return document.documentElement.clientHeight');

    $driver->executeScript('window.scrollTo(0, 0)');

    $full_capture = imagecreatetruecolor($total_width, $total_height);

    $repeat_x = ceil($total_width / $viewport_width);
    $repeat_y = ceil($total_height / $viewport_height);

    for ($x = 0; $x < $repeat_x; $x++) {
        $x_pos = $x * $viewport_width;

        $before_top = -1;
        for ($y = 0; $y < $repeat_y; $y++) {
            $y_pos = $y * $viewport_height;
            $driver->executeScript("window.scrollTo({$x_pos}, {$y_pos})");

            $scroll_left = $driver->executeScript("return window.pageXOffset");
            $scroll_top = $driver->executeScript("return window.pageYOffset");
            if ($before_top == $scroll_top) {
                break;
            }

            $tmp_name = "{$screenshot_name}.tmp";
            $driver->takeScreenshot($tmp_name);

            if (!file_exists($tmp_name)) {
                throw new Exception('Could not save screenshot');
            }

            $tmp_image = imagecreatefrompng($tmp_name);
            imagecopy($full_capture, $tmp_image, $scroll_left, $scroll_top, 0, 0, $viewport_width, $viewport_height);
            imagedestroy($tmp_image);
            unlink($tmp_name);

            $before_top = $scroll_top;
        }
    }

    imagepng($full_capture, $screenshot_name);
    imagedestroy($full_capture);
}
