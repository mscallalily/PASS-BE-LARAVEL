<?php

namespace Tests\Unit;

use App\Services\LicenseVerification\LicenseDocumentReader;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class LicenseWordMetadataTest extends TestCase
{
    public function test_tesseract_words_keep_the_same_printed_line_groups_as_returned_text(): void
    {
        $tsv = "level\tpage_num\tblock_num\tpar_num\tline_num\tword_num\tleft\ttop\twidth\theight\tconf\ttext\n"
            ."5\t1\t1\t1\t1\t1\t0\t0\t20\t10\t94.5\tLicense\n"
            ."5\t1\t1\t1\t1\t2\t25\t0\t10\t10\t92.0\tNo.\n"
            ."5\t1\t2\t1\t1\t1\t0\t20\t120\t10\t86.3\tN01-23-456789\n";
        Process::fake(['*' => Process::result(output: $tsv)]);
        $result = (new LicenseDocumentReader)->readTextImage('synthetic.png', 6, 3);
        $this->assertSame("License No.\nN01-23-456789", $result['text']);
        $this->assertSame(['1-1-1', '1-1-1', '2-1-1'], array_column($result['words'], 'line'));
        $this->assertSame([94.5, 92.0, 86.3], array_column($result['words'], 'confidence'));
        $this->assertSame(90.9, $result['confidence']);
    }
}
