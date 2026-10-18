<?php

namespace Tests\Unit;

use App\Services\Registration\InstitutionalIdReader;
use App\Services\Registration\InstitutionalIdRules;
use PHPUnit\Framework\TestCase;

class InstitutionalIdRulesTest extends TestCase
{
    public function test_category_lengths_year_positions_and_leading_zero_are_preserved(): void
    {
        foreach (['022-1234', '0261234', '022-0001'] as $id) {
            $this->assertNull(InstitutionalIdRules::idProblem('employee', $id, 2026));
        }
        foreach (['0271234', '122123', '02212345', 'O221234'] as $id) {
            $this->assertNotNull(InstitutionalIdRules::idProblem('employee', $id, 2026));
        }
        foreach (['2026-00123', '202601234'] as $id) {
            $this->assertNull(InstitutionalIdRules::idProblem('college', $id, 2026));
        }
        foreach (['202701234', '20240123', '2024012345', '000001234'] as $id) {
            $this->assertNotNull(InstitutionalIdRules::idProblem('college', $id, 2026));
        }
        $this->assertSame('022-1234', InstitutionalIdRules::format('employee', '022 1234'));
        $this->assertSame('2026-00123', InstitutionalIdRules::format('college', '202600123'));
        $this->assertNull(InstitutionalIdRules::idProblem('employee', '0271234', 2027));
        $this->assertNull(InstitutionalIdRules::idProblem('shs', 'SHS-24-001', 2026));
    }

    public function test_ocr_number_extraction_does_not_truncate_or_drop_leading_zero(): void
    {
        $this->assertSame(['0220001'], InstitutionalIdReader::numbers('ID No.: O22-0001', 'employee'));
        $this->assertSame(['202600123'], InstitutionalIdReader::numbers('Student No: 2026-00123', 'college'));
        $this->assertSame([], InstitutionalIdReader::numbers('202600123', 'employee'));
        $this->assertSame([], InstitutionalIdReader::numbers('12026001239', 'college'));
    }
}
