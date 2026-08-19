<?php

class SGS_CSV_Parser {

    // Order matters: later duplicate output keys override earlier ones.
    private static array $picked_fields = [
        'LifeGroup Name',
        'Name',
        'Display Email',
        'Display Phone',
        'Target | Gray Text',
        'Description',
        'Meeting Days',
        'Location of LifeGroup',
        'Form Link',
        'Category',
        'Demographic Filter',
        'Type Filter',
        'Filter Days',
        'Demographic (HOW OLD ARE THE PEOPLE?)',
        'Category (WHO GATHERS TOGETHER)',
        'Target | Gray Text (WHO SHOULD SIGN UP)',
        'Group Type (WHAT HAPPENS IN GROUP)',
        "Childcare\nCheckbox",
        'Online/Zoom Checkbox',
        'Group Name',
        'Leaders',
        'Email',
        'Phone',
        'Target',
        'Location',
        'Demographic',
        'Group Type',
        'Childcare',
        'Online/Zoom',
        'Demographic (WHAT IS THE AGE LIFEGROUP DESIGNED FOR)',
        'Category (HOW IS THE GROUP STRUCTURED RELATIONALLY)',
        'Target  (WHO IS THIS GROUP FOR?)',
    ];

    private static array $key_map = [
        'LifeGroup Name'                          => 'name',
        'Name'                                    => 'leaders',
        'Display Email'                           => 'email',
        'Display Phone'                           => 'phone',
        'Demographic Filter'                      => 'filterDemographic',
        'Category'                                => 'filterCategory',
        'Target | Gray Text'                      => 'target',
        'Type Filter'                             => 'filterType',
        'Description'                             => 'description',
        'Meeting Days'                            => 'meetsOn',
        "Childcare\nCheckbox"                     => 'childcareAvailable',
        'Filter Days'                             => 'filterDays',
        'Location of LifeGroup'                   => 'location',
        'Form Link'                               => 'formLink',
        'Online/Zoom Checkbox'                    => 'online',
        'Demographic (HOW OLD ARE THE PEOPLE?)'   => 'filterDemographic',
        'Category (WHO GATHERS TOGETHER)'         => 'filterCategory',
        'Target | Gray Text (WHO SHOULD SIGN UP)' => 'target',
        'Group Type (WHAT HAPPENS IN GROUP)'      => 'filterType',
        'Group Name'                                      => 'name',
        'Leaders'                                         => 'leaders',
        'Email'                                           => 'email',
        'Phone'                                           => 'phone',
        'Target'                                          => 'target',
        'Location'                                        => 'location',
        'Demographic'                                     => 'filterDemographic',
        'Group Type'                                      => 'filterType',
        'Childcare'                                       => 'childcareAvailable',
        'Online/Zoom'                                     => 'online',
        'Demographic (WHAT IS THE AGE LIFEGROUP DESIGNED FOR)' => 'filterDemographic',
        'Category (HOW IS THE GROUP STRUCTURED RELATIONALLY)'  => 'filterCategory',
        'Target  (WHO IS THIS GROUP FOR?)'                => 'target',
    ];

    public static function get_headers( string $file_path ): array {
        if ( ! is_readable( $file_path ) ) return [];
        $handle = fopen( $file_path, 'r' );
        $row    = fgetcsv( $handle );
        fclose( $handle );
        return $row ?: [];
    }

    public static function parse( string $file_path ): array {
        if ( ! is_readable( $file_path ) ) return [];
        $handle = fopen( $file_path, 'r' );

        $headers = fgetcsv( $handle );
        if ( ! $headers ) {
            fclose( $handle );
            return [];
        }

        $rows = [];
        while ( ( $row = fgetcsv( $handle ) ) !== false ) {
            if ( count( $row ) === count( $headers ) ) {
                $rows[] = array_combine( $headers, $row );
            }
        }
        fclose( $handle );

        return self::map_groups( $rows );
    }

    private static function map_groups( array $rows ): array {
        $picked  = self::$picked_fields;
        $key_map = self::$key_map;

        $groups = [];
        foreach ( $rows as $row ) {
            $has_name    = ! empty( $row['LifeGroup Name'] ) || ! empty( $row['Group Name'] );
            $has_leaders = ! empty( $row['Name'] ) || ! empty( $row['Leaders'] );
            if ( ! $has_name || ! $has_leaders ) continue;
            if ( isset( $row['Hidden'] ) && $row['Hidden'] === 'Yes' ) continue;

            $group = [];
            foreach ( $picked as $csv_col ) {
                $out_key = $key_map[ $csv_col ] ?? null;
                if ( $out_key === null ) continue;
                if ( ! array_key_exists( $csv_col, $row ) ) continue;
                $val = $row[ $csv_col ];
                if ( is_string( $val ) ) {
                    $val = str_replace( [ "\r\n", "\r", "\n" ], '', $val );
                }
                // Later iterations intentionally overwrite earlier ones for aliased columns.
                $group[ $out_key ] = $val;
            }

            $group['filterDemographic'] = SGS_CSV_Validator::split_and_trim( $group['filterDemographic'] ?? null );
            $group['filterCategory']    = SGS_CSV_Validator::split_and_trim( $group['filterCategory']    ?? null );
            $group['filterDays']        = SGS_CSV_Validator::split_and_trim( $group['filterDays']        ?? null );
            $group['filterType']        = SGS_CSV_Validator::split_and_trim( $group['filterType']        ?? null );
            $group['email']             = strtolower( $group['email'] ?? '' );
            $url                        = $group['formLink'] ?? '';
            $group['formLink']          = preg_match( '/^https?:\/\//i', $url ) ? $url : '';

            $groups[] = $group;
        }

        return $groups;
    }
}
