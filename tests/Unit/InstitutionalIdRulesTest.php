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

    public function test_repeated_segmentation_is_one_method_and_low_or_conflicting_number_evidence_does_not_pass(): void
    {
        $make=fn($source,$confidence=49.3,$number='2026-00123')=>['source'=>$source,'number_confidence'=>$confidence,'text'=>$number];
        $signals=\App\Services\Registration\InstitutionalIdSignals::class;
        $this->assertFalse($signals::numberConsensus([$make('broad_gray'),$make('broad_gray'),$make('broad_gray')],'college')['reliable']);
        $r=$signals::numberConsensus([$make('broad_gray'),$make('broad_threshold'),$make('tight_color')],'college');
        $this->assertTrue($r['reliable']);
        $this->assertTrue($signals::confirmsNumber($r,'202600123'));
        $this->assertFalse($signals::confirmsNumber($r,'202600128'));
        $bad=$r;$bad['sources']=['broad_gray','tight_color','unknown_method'];$this->assertFalse($signals::confirmsNumber($bad,'202600123'));
        $this->assertFalse($signals::numberConsensus([$make('broad_gray'),$make('broad_threshold'),$make('tight_color',39.9)],'college')['reliable']);
        $this->assertFalse($signals::numberConsensus([$make('broad_gray'),$make('broad_threshold'),$make('tight_color',49.3,'2026-00128')],'college')['reliable']);
        $this->assertFalse($signals::numberConsensus([$make('broad_gray'),$make('broad_threshold'),$make('full_card')],'college')['reliable']);
    }

    public function test_school_detection_handles_one_domain_glyph_error_but_not_unrelated_or_format_only_text(): void
    {
        $signals=\App\Services\Registration\InstitutionalIdSignals::class;
        foreach(['www.dyci.edu.ph','www.dycl.edu.ph','DYC1.EDU.PH','Dr. Yanga’s C0lleges Inc.','DYCI MAGIS QUAM SCHOLA FAMILIA'] as $text) $this->assertTrue($signals::school($text),$text);
        foreach(['DYCI','MAGIS QUAM SCHOLA FAMILIA','www.dyci.edu.com','Other College Student No. 2026-00123','Juan Dela Cruz 022-0001'] as $text) $this->assertFalse($signals::school($text),$text);
    }

}
