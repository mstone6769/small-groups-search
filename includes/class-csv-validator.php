<?php

class SGS_CSV_Validator {

    private static array $required_columns = [
        'LifeGroup Name',
        'Name',
        'Display Email',
        'Display Phone',
        'Description',
        'Meeting Days',
        'Location of LifeGroup',
        'Form Link',
        'Filter Days',
        "Childcare\nCheckbox",
        'Online/Zoom Checkbox',
        'Hidden',
    ];

    // The sheet has used both short-form and long-form names for these columns.
    // At least one from each pair must be present.
    private static array $aliased_column_groups = [
        ['Demographic Filter', 'Demographic (HOW OLD ARE THE PEOPLE?)'],
        ['Category', 'Category (WHO GATHERS TOGETHER)'],
        ['Target | Gray Text', 'Target | Gray Text (WHO SHOULD SIGN UP)'],
        ['Type Filter', 'Group Type (WHAT HAPPENS IN GROUP)'],
    ];

    public static function validate( array $headers ): array {
        $warnings = [];

        $missing = array_values( array_filter(
            self::$required_columns,
            fn( $col ) => ! in_array( $col, $headers, true )
        ) );

        if ( ! empty( $missing ) ) {
            $quoted     = array_map( fn( $c ) => '"' . $c . '"', $missing );
            $warnings[] = 'Missing required columns: ' . implode( ', ', $quoted );
        }

        foreach ( self::$aliased_column_groups as $group ) {
            $found = array_filter( $group, fn( $col ) => in_array( $col, $headers, true ) );
            if ( empty( $found ) ) {
                $quoted     = array_map( fn( $c ) => '"' . $c . '"', $group );
                $warnings[] = 'Missing all variants of column: ' . implode( ' or ', $quoted );
            }
        }

        return $warnings;
    }

    public static function split_and_trim( ?string $value ): array {
        if ( $value === null || $value === '' ) return [];
        return array_map( 'trim', explode( ',', $value ) );
    }
}
