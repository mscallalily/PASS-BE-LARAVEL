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
    public function test_conflicting_strong_number_reads_force_review(): void
    {
        $reader=new \App\Services\Registration\InstitutionalIdReader(new \App\Services\LicenseVerification\LicenseDocumentReader);
        $one=['text'=>'2024-00123','words'=>[['text'=>'2024-00123','confidence'=>94]],'confidence'=>94];
        $two=['text'=>'2024-00128','words'=>[['text'=>'2024-00128','confidence'=>90]],'confidence'=>90];
        $this->assertFalse($reader->numberConflict([$one,$one],'college'));
        $this->assertTrue($reader->numberConflict([$one,$two],'college'));
        $two['words'][0]['confidence']=40; $two['confidence']=40;
        $this->assertFalse($reader->numberConflict([$one,$two],'college'));
    }

    public function test_name_parser_preserves_compound_names_and_separates_suffix_from_initial(): void
    {
        $this->assertSame(['suffix'=>'Jr.','last_name'=>'DELA CRUZ'],InstitutionalIdReader::nameFields('DELA CRUZ, JR.','last_name'));
        $this->assertSame(['suffix'=>'III','last_name'=>'DE LOS SANTOS'],InstitutionalIdReader::nameFields('DE LOS SANTOS III','last_name'));
        $this->assertSame(['suffix'=>'Sr.','middle_initial'=>'P.','first_name'=>'JUAN MIGUEL'],InstitutionalIdReader::nameFields('JUAN MIGUEL P. SR.','first_name'));
        $this->assertSame(['middle_initial'=>'V.','first_name'=>'JUAN'],InstitutionalIdReader::nameFields('JUAN V.','first_name'));
        $this->assertSame(['middle_initial'=>'X.','first_name'=>'JUAN'],InstitutionalIdReader::nameFields('JUAN X.','first_name'));
        $this->assertSame(['middle_initial'=>'I.','first_name'=>'JUAN'],InstitutionalIdReader::nameFields('JUAN I.','first_name'));
        $this->assertSame(['last_name'=>'DE LA CRUZ'],InstitutionalIdReader::nameFields("DE LA\nCRUZ",'last_name'));
    }

}
