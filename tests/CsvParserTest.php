<?php

use PHPUnit\Framework\TestCase;

class CsvParserTest extends TestCase {

    /** Write rows to a temp file and return its path. */
    private function make_csv( array $rows ): string {
        $path   = tempnam(sys_get_temp_dir(), 'sgs_test_');
        $handle = fopen($path, 'w');
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);
        return $path;
    }

    /** Minimal valid header row used across most tests. */
    private function headers(): array {
        return [
            'LifeGroup Name', 'Name', 'Display Email', 'Display Phone',
            'Target | Gray Text', 'Description', 'Meeting Days',
            'Location of LifeGroup', 'Form Link', 'Category',
            'Demographic Filter', 'Type Filter', 'Filter Days',
            "Childcare\nCheckbox", 'Online/Zoom Checkbox', 'Hidden',
        ];
    }

    /** Minimal valid data row aligned to headers(). */
    private function row( array $overrides = [] ): array {
        $defaults = [
            'LifeGroup Name'        => 'Test Group',
            'Name'                  => 'Jane Smith',
            'Display Email'         => 'Jane@Example.COM',
            'Display Phone'         => '555-123-4567',
            'Target | Gray Text'    => 'everyone',
            'Description'           => 'A group for testing.',
            'Meeting Days'          => 'Sundays at 10am',
            'Location of LifeGroup' => 'Room 101',
            'Form Link'             => 'https://example.com/join',
            'Category'              => 'co-ed',
            'Demographic Filter'    => 'adults',
            'Type Filter'           => 'bible study',
            'Filter Days'           => 'Sunday',
            "Childcare\nCheckbox"   => 'No',
            'Online/Zoom Checkbox'  => 'No',
            'Hidden'                => 'No',
        ];
        return array_merge($defaults, $overrides);
    }

    // ── get_headers() ─────────────────────────────────────────────────────────

    public function test_get_headers_returns_first_row(): void {
        $path = $this->make_csv([$this->headers(), array_values($this->row())]);
        $this->assertSame($this->headers(), SGS_CSV_Parser::get_headers($path));
        unlink($path);
    }

    public function test_get_headers_returns_empty_for_missing_file(): void {
        $this->assertSame([], SGS_CSV_Parser::get_headers('/does/not/exist.csv'));
    }

    // ── parse() — field mapping ───────────────────────────────────────────────

    public function test_parse_maps_fields_to_output_keys(): void {
        $path   = $this->make_csv([$this->headers(), array_values($this->row())]);
        $groups = SGS_CSV_Parser::parse($path);
        unlink($path);

        $this->assertCount(1, $groups);
        $g = $groups[0];
        $this->assertSame('Test Group', $g['name']);
        $this->assertSame('Jane Smith', $g['leaders']);
        $this->assertSame('jane@example.com', $g['email']); // lowercased
        $this->assertSame('555-123-4567', $g['phone']);
        $this->assertSame('everyone', $g['target']);
        $this->assertSame('A group for testing.', $g['description']);
        $this->assertSame('Sundays at 10am', $g['meetsOn']);
        $this->assertSame('Room 101', $g['location']);
        $this->assertSame('https://example.com/join', $g['formLink']);
        $this->assertSame('No', $g['childcareAvailable']);
        $this->assertSame('No', $g['online']);
    }

    public function test_parse_splits_filter_fields_into_arrays(): void {
        $path = $this->make_csv([
            $this->headers(),
            array_values($this->row([
                'Filter Days'        => 'Sunday, Wednesday',
                'Demographic Filter' => 'adults, young adults | 18-35',
                'Category'           => 'co-ed, men',
                'Type Filter'        => 'bible study, prayer',
            ])),
        ]);
        $groups = SGS_CSV_Parser::parse($path);
        unlink($path);

        $g = $groups[0];
        $this->assertSame(['Sunday', 'Wednesday'], $g['filterDays']);
        $this->assertSame(['adults', 'young adults | 18-35'], $g['filterDemographic']);
        $this->assertSame(['co-ed', 'men'], $g['filterCategory']);
        $this->assertSame(['bible study', 'prayer'], $g['filterType']);
    }

    public function test_parse_lowercases_email(): void {
        $path   = $this->make_csv([$this->headers(), array_values($this->row(['Display Email' => 'LEADER@CHURCH.ORG']))]);
        $groups = SGS_CSV_Parser::parse($path);
        unlink($path);
        $this->assertSame('leader@church.org', $groups[0]['email']);
    }

    public function test_parse_strips_newlines_from_field_values(): void {
        $path   = $this->make_csv([$this->headers(), array_values($this->row(['Description' => "Line one\nLine two"]))]);
        $groups = SGS_CSV_Parser::parse($path);
        unlink($path);
        $this->assertSame('Line oneLine two', $groups[0]['description']);
    }

    // ── parse() — row filtering ───────────────────────────────────────────────

    public function test_parse_skips_hidden_groups(): void {
        $path = $this->make_csv([
            $this->headers(),
            array_values($this->row(['Hidden' => 'Yes'])),
            array_values($this->row(['LifeGroup Name' => 'Visible Group'])),
        ]);
        $groups = SGS_CSV_Parser::parse($path);
        unlink($path);
        $this->assertCount(1, $groups);
        $this->assertSame('Visible Group', $groups[0]['name']);
    }

    public function test_parse_skips_rows_missing_group_name(): void {
        $path = $this->make_csv([
            $this->headers(),
            array_values($this->row(['LifeGroup Name' => ''])),
        ]);
        $groups = SGS_CSV_Parser::parse($path);
        unlink($path);
        $this->assertCount(0, $groups);
    }

    public function test_parse_skips_rows_missing_leader_name(): void {
        $path = $this->make_csv([
            $this->headers(),
            array_values($this->row(['Name' => ''])),
        ]);
        $groups = SGS_CSV_Parser::parse($path);
        unlink($path);
        $this->assertCount(0, $groups);
    }

    public function test_parse_returns_empty_array_for_missing_file(): void {
        $this->assertSame([], SGS_CSV_Parser::parse('/does/not/exist.csv'));
    }

    // ── parse() — alias override ──────────────────────────────────────────────

    public function test_long_form_alias_overwrites_short_form(): void {
        // When both short and long form columns are present, long form wins.
        $headers = array_merge($this->headers(), [
            'Demographic (HOW OLD ARE THE PEOPLE?)',
            'Category (WHO GATHERS TOGETHER)',
        ]);
        $row = array_merge(array_values($this->row()), ['seniors', 'women']);

        $path   = $this->make_csv([$headers, $row]);
        $groups = SGS_CSV_Parser::parse($path);
        unlink($path);

        $this->assertSame(['seniors'], $groups[0]['filterDemographic']);
        $this->assertSame(['women'], $groups[0]['filterCategory']);
    }
}
