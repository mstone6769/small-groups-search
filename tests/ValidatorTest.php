<?php

use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase {

    private static array $full_headers = [
        'LifeGroup Name', 'Name', 'Display Email', 'Display Phone',
        'Description', 'Meeting Days', 'Location of LifeGroup', 'Form Link',
        'Filter Days', "Childcare\nCheckbox", 'Online/Zoom Checkbox', 'Hidden',
        'Demographic Filter', 'Category', 'Target | Gray Text', 'Type Filter',
    ];

    // ── validate() ────────────────────────────────────────────────────────────

    public function test_no_warnings_when_all_columns_present(): void {
        $this->assertEmpty(SGS_CSV_Validator::validate(self::$full_headers));
    }

    public function test_warns_on_single_missing_required_column(): void {
        $headers = array_values(array_diff(self::$full_headers, ['Description']));
        $warnings = SGS_CSV_Validator::validate($headers);
        $this->assertCount(1, $warnings);
        $this->assertStringContainsString('"Description"', $warnings[0]);
    }

    public function test_warns_on_multiple_missing_required_columns(): void {
        $headers = array_values(array_diff(self::$full_headers, ['Description', 'Form Link', 'Hidden']));
        $warnings = SGS_CSV_Validator::validate($headers);
        $this->assertCount(1, $warnings);
        $this->assertStringContainsString('"Description"', $warnings[0]);
        $this->assertStringContainsString('"Form Link"', $warnings[0]);
        $this->assertStringContainsString('"Hidden"', $warnings[0]);
    }

    public function test_accepts_long_form_demographic_alias(): void {
        $headers = array_values(array_diff(self::$full_headers, ['Demographic Filter']));
        $headers[] = 'Demographic (HOW OLD ARE THE PEOPLE?)';
        $this->assertEmpty(SGS_CSV_Validator::validate($headers));
    }

    public function test_accepts_long_form_category_alias(): void {
        $headers = array_values(array_diff(self::$full_headers, ['Category']));
        $headers[] = 'Category (WHO GATHERS TOGETHER)';
        $this->assertEmpty(SGS_CSV_Validator::validate($headers));
    }

    public function test_accepts_long_form_target_alias(): void {
        $headers = array_values(array_diff(self::$full_headers, ['Target | Gray Text']));
        $headers[] = 'Target | Gray Text (WHO SHOULD SIGN UP)';
        $this->assertEmpty(SGS_CSV_Validator::validate($headers));
    }

    public function test_accepts_long_form_type_alias(): void {
        $headers = array_values(array_diff(self::$full_headers, ['Type Filter']));
        $headers[] = 'Group Type (WHAT HAPPENS IN GROUP)';
        $this->assertEmpty(SGS_CSV_Validator::validate($headers));
    }

    public function test_warns_when_all_variants_of_aliased_column_missing(): void {
        $headers = array_values(array_diff(self::$full_headers, ['Demographic Filter']));
        $warnings = SGS_CSV_Validator::validate($headers);
        $this->assertCount(1, $warnings);
        $this->assertStringContainsString('Demographic', $warnings[0]);
    }

    public function test_no_warning_when_both_alias_variants_present(): void {
        $headers   = self::$full_headers;
        $headers[] = 'Demographic (HOW OLD ARE THE PEOPLE?)';
        $this->assertEmpty(SGS_CSV_Validator::validate($headers));
    }

    public function test_empty_headers_produces_all_required_warnings(): void {
        $warnings = SGS_CSV_Validator::validate([]);
        $this->assertNotEmpty($warnings);
    }

    // ── split_and_trim() ──────────────────────────────────────────────────────

    public function test_split_trims_whitespace(): void {
        $this->assertSame(
            ['Sunday', 'Monday', 'Tuesday'],
            SGS_CSV_Validator::split_and_trim('Sunday, Monday , Tuesday')
        );
    }

    public function test_split_single_value(): void {
        $this->assertSame(['Sunday'], SGS_CSV_Validator::split_and_trim('Sunday'));
    }

    public function test_split_empty_string_returns_empty_array(): void {
        $this->assertSame([], SGS_CSV_Validator::split_and_trim(''));
    }

    public function test_split_null_returns_empty_array(): void {
        $this->assertSame([], SGS_CSV_Validator::split_and_trim(null));
    }

    public function test_split_preserves_internal_spaces(): void {
        $result = SGS_CSV_Validator::split_and_trim('young adults | 18-35, anyone | all ages');
        $this->assertSame(['young adults | 18-35', 'anyone | all ages'], $result);
    }
}
