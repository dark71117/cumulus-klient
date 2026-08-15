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

    public function test_markup_renders_bold_phenomenon_from_entities(): void
    {
        $this->assertSame(
            'słaby <span class="pogrubione">deszcz</span> (ciągły)',
            ImgwText::markup('słaby &lt;span class="pogrubione"&gt;deszcz&lt;/span&gt; (ciągły)')
        );
    }

    public function test_markup_renders_bold_phenomenon_from_raw_html(): void
    {
        $this->assertSame(
            'słaby <span class="pogrubione">deszcz</span> (ciągły)',
            ImgwText::markup('słaby <span class="pogrubione">deszcz</span> (ciągły)')
        );
    }

    public function test_markup_strips_other_html(): void
    {
        $this->assertSame(
            'słaby deszcz',
            ImgwText::markup('słaby <b>deszcz</b>')
        );
    }

    public function test_plain_strips_phenomenon_markup(): void
    {
        $this->assertSame(
            'słaby deszcz (ciągły)',
            ImgwText::plain('słaby &lt;span class="pogrubione"&gt;deszcz&lt;/span&gt; (ciągły)')
        );
    }
}
