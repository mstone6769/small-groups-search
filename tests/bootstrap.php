<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

// Minimal WordPress class stubs required by the classes under test.
class WP_Error {
    public function __construct( private string $code = '', private string $message = '' ) {}
    public function get_error_message(): string { return $this->message; }
    public function get_error_code(): string    { return $this->code; }
}

// Load plugin classes without WordPress being present.
require_once dirname(__DIR__) . '/includes/class-csv-validator.php';
require_once dirname(__DIR__) . '/includes/class-csv-parser.php';
require_once dirname(__DIR__) . '/includes/class-snapshot-cpt.php';
