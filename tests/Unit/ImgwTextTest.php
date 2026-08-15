<?php

namespace Tests\Unit;

use App\Support\ImgwText;
use Tests\TestCase;

class ImgwTextTest extends TestCase
{
    public function test_decodes_visibility_entities(): void
    {
        $this->assertSame('≥ 10', ImgwText::decode('&ge; 10'));
        $this->assertSame('≤ 10', ImgwText::decode('&le; 10'));
        $this->assertSame('> 10', ImgwText::decode('&gt; 10'));
        $this->assertSame('< 10', ImgwText::decode('&lt; 10'));
    }

    public function test_leaves_plain_text_unchanged(): void
    {
        $this->assertSame('50', ImgwText::decode('50'));
        $this->assertSame('≥ 10', ImgwText::decode('≥ 10'));
        $this->assertSame('', ImgwText::decode(null));
    }
}
