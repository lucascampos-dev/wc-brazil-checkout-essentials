<?php
/**
 * PHPUnit bootstrap.
 *
 * The unit suite deliberately does NOT load WordPress: every class under
 * test (src/Validation, src/Rules, src/Support) is framework-free.
 *
 * @package BrazilCheckoutEssentials
 */

declare(strict_types=1);

require_once dirname( __DIR__ ) . '/vendor/autoload.php';
