<?php

namespace EmbassyChecker\Helpers;

use DateTime;

class DateTimeChecker {
    static function isDateBetween( int $found, ?int $after = null, ?int $before = null ) {
        if( $after && $after > $found ) {
            return false;
        }

        if( $before && $before < $found ) {
            return false;
        }

        return true;
    }

    /**
     * @param int[] $list List of available times 
     */
    static function getTimesBetween( array $list, ?int $after = null, ?int $before = null ): array {
        return array_filter(
            $list,
            function( $item ) use ( $after, $before ) {
                preg_match( '/^(\d+)\:(\d+)$/', $item, $match );

                if( $after && $after > $match[1] ) {
                    return false;
                }
        
                if( $before && $before < $match[1] ) {
                    return false;
                }
        
                return true;
            }
        );
    }
}