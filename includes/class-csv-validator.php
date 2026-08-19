<?php

class SGS_CSV_Validator {

    private static array $required_columns = [
        'Description',
        'Meeting Days',
        'Form Link',
        'Filter Days',
    ];

    // At least one variant from each group must be present.
    private static array $aliased_column_groups = [
        ['LifeGroup Name', 'Group Name'],
        ['Name', 'Leaders'],
        ['Display Email', 'Email'],
        ['Display Phone', 'Phone'],
        ['Location of LifeGroup', 'Location'],
        ["Childcare\nCheckbox", 'Childcare'],
        ['Online/Zoom Checkbox', 'Online/Zoom'],
        ['Demographic Filter', 'Demographic', 'Demographic (HOW OLD ARE THE PEOPLE?)', 'Demographic (WHAT IS THE AGE LIFEGROUP DESIGNED FOR)'],
        ['Category', 'Category (WHO GATHERS TOGETHER)', 'Category (HOW IS THE GROUP STRUCTURED RELATIONALLY)'],
        ['Target | Gray Text', 'Target', 'Target | Gray Text (WHO SHOULD SIGN UP)', 'Target  (WHO IS THIS GROUP FOR?)'],
        ['Type Filter', 'Group Type', 'Group Type (WHAT HAPPENS IN GROUP)'],
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
