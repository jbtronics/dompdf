<?php
namespace Dompdf\Tests\Canvas;

use Dompdf\Adapter\CPDF;
use Dompdf\Canvas;
use Dompdf\Dompdf;
use Dompdf\FontMetrics;
use Dompdf\Tests\TestCase;
use DateTime;

class CPDFTest extends TestCase
{
    public function testImage(): void
    {
        $basePath = realpath(__DIR__ . "/..");
        $imagePath = "$basePath/_files/red-dot.png";

        $dompdf = new Dompdf();
        $canvas = new CPDF([0, 0, 200, 200], "portrait", $dompdf);
        $canvas->new_page();
        $canvas->image($imagePath, 0, 0, 5, 5);
        $output = $canvas->output();
        $this->assertNotSame("", $output);
    }

    public function testPageScript(): void
    {
        global $called;
        $called = 0;

        $dompdf = new Dompdf();
        $canvas = new CPDF([0, 0, 200, 200], "portrait", $dompdf);
        $canvas->new_page();

        $canvas->page_script(function (
            int $pageNumber,
            int $pageCount,
            Canvas $canvas,
            FontMetrics $fontMetrics
        ) use (&$called) {
            $called++;
            $font = $fontMetrics->getFont("Helvetica");
            $canvas->text(40, 20, "Page $pageNumber of $pageCount", $font, 12);
            $canvas->line(200, 0, 0, 200, [0, 0, 0], 1);
        });
        $canvas->page_script('
            global $called;
            $called++;
            $font = $fontMetrics->getFont("Helvetica");
            $pdf->text(20, 0, "Page $PAGE_NUM of $PAGE_COUNT", $font, 12);
        ');

        $output = $canvas->output();

        $this->assertNotSame("", $output);
        $this->assertSame(4, $called);
    }

    public function testPageText(): void
    {
        $dompdf = new Dompdf();
        $canvas = new CPDF([0, 0, 200, 200], "portrait", $dompdf);
        $canvas->new_page();

        $font = $dompdf->getFontMetrics()->getFont("Helvetica");
        $canvas->page_text(60, 40, "Page {PAGE_NUM} of {PAGE_COUNT}", $font, 12);

        $output = $canvas->output();
        $this->assertNotSame("", $output);
    }

    public function testPageLine(): void
    {
        $dompdf = new Dompdf();
        $canvas = new CPDF([0, 0, 200, 200], "portrait", $dompdf);
        $canvas->new_page();

        $canvas->page_line(0, 0, 200, 200, [0, 0, 0], 1);

        $output = $canvas->output();
        $this->assertNotSame("", $output);
    }

    public static function fontSupportsCharProvider(): array
    {
        return [
            // Core fonts
            // ASCII and ISO-8859-1
            ["Helvetica", "A", true],
            ["Helvetica", "{", true],
            ["Helvetica", "Æ", true],
            ["Helvetica", "÷", true],

            // Part of Windows-1252, but not ISO-8859-1
            ["Helvetica", "€", true],
            ["Helvetica", "‚", true],
            ["Helvetica", "ƒ", true],
            ["Helvetica", "„", true],
            ["Helvetica", "…", true],
            ["Helvetica", "†", true],
            ["Helvetica", "‡", true],
            ["Helvetica", "ˆ", true],
            ["Helvetica", "‰", true],
            ["Helvetica", "Š", true],
            ["Helvetica", "‹", true],
            ["Helvetica", "Œ", true],
            ["Helvetica", "Ž", true],
            ["Helvetica", "‘", true],
            ["Helvetica", "’", true],
            ["Helvetica", "“", true],
            ["Helvetica", "”", true],
            ["Helvetica", "•", true],
            ["Helvetica", "–", true],
            ["Helvetica", "—", true],
            ["Helvetica", "˜", true],
            ["Helvetica", "™", true],
            ["Helvetica", "š", true],
            ["Helvetica", "›", true],
            ["Helvetica", "œ", true],
            ["Helvetica", "ž", true],
            ["Helvetica", "Ÿ", true],
            ["Helvetica", "ÿ", true],

            // Unicode outside Windows-1252
            ["Helvetica", "Ā", false],
            ["Helvetica", "↦", false],
            ["Helvetica", "∉", false],
            ["Helvetica", "能", false],

            // DejaVu
            ["DejaVu Sans", "A", true],
            ["DejaVu Sans", "{", true],
            ["DejaVu Sans", "Æ", true],
            ["DejaVu Sans", "÷", true],
            ["DejaVu Sans", "Œ", true],
            ["DejaVu Sans", "—", true],
            ["DejaVu Sans", "↦", true],
            ["DejaVu Sans", "∉", true],
            ["DejaVu Sans", "能", false],
        ];
    }

    /**
     * @dataProvider fontSupportsCharProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('fontSupportsCharProvider')]
    public function testFontSupportsChar(string $font, string $char, bool $expected): void
    {
        $dompdf = new Dompdf();
        $canvas = new CPDF("letter", "portrait", $dompdf);
        $fontFile = $dompdf->getFontMetrics()->getFont($font);

        $this->assertSame($expected, $canvas->font_supports_char($fontFile, $char));
    }

    public function testGetXmpMetadata(): void
    {
        $dompdf = new Dompdf();
        $canvas = new CPDF([0, 0, 200, 200], "portrait", $dompdf);

        $canvas->get_cpdf()->addInfo('CreationDate', 'aa20250208195048');
        $canvas->get_cpdf()->addInfo('ModDate', 'aa20250208195048Z');
        $canvas->get_cpdf()->addInfo('Producer', 'Dompdf Tests');

        $data = implode("\n", [
            '<?xpacket begin="﻿" id="W5M0MpCehiHzreSzNTczkc9d"?>',
            '<x:xmpmeta xmlns:x="adobe:ns:meta/">',
            '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">',
            '',
            '<rdf:Description xmlns:pdfaid="http://www.aiim.org/pdfa/ns/id/" rdf:about="">',
            '<pdfaid:part>3</pdfaid:part>',
            '<pdfaid:conformance>B</pdfaid:conformance>',
            '</rdf:Description>',
            '',
            '<rdf:Description xmlns:dc="http://purl.org/dc/elements/1.1/" rdf:about="">',
            '</rdf:Description>',
            '<rdf:Description xmlns:pdf="http://ns.adobe.com/pdf/1.3/" rdf:about="">',
            '<pdf:Producer>Dompdf Tests</pdf:Producer>',
            '</rdf:Description>',
            '<rdf:Description xmlns:xmp="http://ns.adobe.com/xap/1.0/" rdf:about="">',
            '<xmp:CreateDate>2025-02-08T19:50:48+00:00</xmp:CreateDate>',
            '<xmp:ModifyDate>2025-02-08T19:50:48+00:00</xmp:ModifyDate>',
            '</rdf:Description>',
            '</rdf:RDF>',
            '</x:xmpmeta>',
            '<?xpacket end="w"?>',
        ]);

        $this->assertSame($data, $canvas->get_cpdf()->getXmpMetadata());
    }

    public function testSetAdditionalXmpRdf(): void
    {
        $dompdf = new Dompdf();
        $canvas = new CPDF([0, 0, 200, 200], "portrait", $dompdf);

        $canvas->get_cpdf()->addInfo('CreationDate', 'aa20250208195048');
        $canvas->get_cpdf()->addInfo('ModDate', 'aa20250208195048Z');
        $canvas->get_cpdf()->addInfo('Producer', 'Dompdf Tests');
        $additionalXmpRdf = implode("\n", [
            '',
            '<rdf:Description rdf:about="" xmlns:zf="urn:ferd:pdfa:CrossIndustryDocument:invoice:1p0#">',
            '  <zf:DocumentType>INVOICE</zf:DocumentType>',
            '  <zf:DocumentFileName>ZUGFeRD-invoice.xml</zf:DocumentFileName>',
            '  <zf:Version>1.0</zf:Version>',
            '  <zf:ConformanceLevel>BASIC</zf:ConformanceLevel>',
            '</rdf:Description>',
        ]);

        $canvas->get_cpdf()->setAdditionalXmpRdf($additionalXmpRdf);

        $data = implode("\n", [
            '<?xpacket begin="﻿" id="W5M0MpCehiHzreSzNTczkc9d"?>',
            '<x:xmpmeta xmlns:x="adobe:ns:meta/">',
            '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">',
            '',
            '<rdf:Description xmlns:pdfaid="http://www.aiim.org/pdfa/ns/id/" rdf:about="">',
            '<pdfaid:part>3</pdfaid:part>',
            '<pdfaid:conformance>B</pdfaid:conformance>',
            '</rdf:Description>',
            '',
            '<rdf:Description xmlns:dc="http://purl.org/dc/elements/1.1/" rdf:about="">',
            '</rdf:Description>',
            '<rdf:Description xmlns:pdf="http://ns.adobe.com/pdf/1.3/" rdf:about="">',
            '<pdf:Producer>Dompdf Tests</pdf:Producer>',
            '</rdf:Description>',
            '<rdf:Description xmlns:xmp="http://ns.adobe.com/xap/1.0/" rdf:about="">',
            '<xmp:CreateDate>2025-02-08T19:50:48+00:00</xmp:CreateDate>',
            '<xmp:ModifyDate>2025-02-08T19:50:48+00:00</xmp:ModifyDate>',
            '</rdf:Description>',
            '<rdf:Description rdf:about="" xmlns:zf="urn:ferd:pdfa:CrossIndustryDocument:invoice:1p0#">',
            '  <zf:DocumentType>INVOICE</zf:DocumentType>',
            '  <zf:DocumentFileName>ZUGFeRD-invoice.xml</zf:DocumentFileName>',
            '  <zf:Version>1.0</zf:Version>',
            '  <zf:ConformanceLevel>BASIC</zf:ConformanceLevel>',
            '</rdf:Description>',
            '</rdf:RDF>',
            '</x:xmpmeta>',
            '<?xpacket end="w"?>',
        ]);

        $this->assertSame($data, $canvas->get_cpdf()->getXmpMetadata());
    }

    public function testUtf8toUtf16BESupplementaryCharacters(): void
    {
        $dompdf = new Dompdf();
        $canvas = new CPDF([0, 0, 200, 200], "portrait", $dompdf);
        $cpdf = $canvas->get_cpdf();

        $text = "A\u{1F600}\u{10FFFF}";
        $this->assertSame([0x41, 0x1F600, 0x10FFFF], $cpdf->utf8toCodePointsArray($text));
        $this->assertSame("\x00\x41\xD8\x3D\xDE\x00\xDB\xFF\xDF\xFF", $cpdf->utf8toUtf16BE($text, false));
    }

    public static function supplementaryCharactersProvider(): array
    {
        return [
            "with subsetting" => [true],
            "without subsetting" => [false],
        ];
    }

    /**
     * @dataProvider supplementaryCharactersProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('supplementaryCharactersProvider')]
    public function testSupplementaryCharacters(bool $subsetting): void
    {
        $dompdf = new Dompdf();
        $dompdf->getOptions()->setIsFontSubsettingEnabled($subsetting);
        $canvas = new CPDF([0, 0, 200, 200], "portrait", $dompdf);
        $canvas->new_page();

        $font = $dompdf->getFontMetrics()->getFont("DejaVu Sans");
        // U+1F600 and U+1F603 are part of DejaVu Sans, U+1D400 is not
        $text = "a \u{1F600} b \u{1F603}\u{1D400}\u{1F600}";
        $canvas->text(10, 10, $text, $font, 12, [0, 0, 0], 2.0);

        // Supplementary characters must have the width of their glyph
        $this->assertGreaterThan(
            $canvas->get_text_width("a b ", $font, 12),
            $canvas->get_text_width("a \u{1F600} b ", $font, 12)
        );

        $output = $canvas->get_cpdf()->output(true);

        // Text is encoded using CIDs from the surrogate range, in order of first use
        $this->assertStringContainsString(
            "(\x00a\x00 )\x00 -167\x00 (\xD8\x00\x00 )\x00 -167\x00 (\x00b\x00 )\x00 -167\x00 (\xD8\x01\xD8\x02\xD8\x00)",
            $output
        );

        // ToUnicode CMap maps the CIDs back to the characters
        $this->assertStringContainsString("<0000> <D7FF> <0000>\n<E000> <FFFF> <E000>", $output);
        $this->assertStringContainsString(
            "3 beginbfchar\n<D800> <D83DDE00>\n<D801> <D83DDE03>\n<D802> <D835DC00>\nendbfchar",
            $output
        );

        // Widths of the CIDs
        $this->assertMatchesRegularExpression('/\/W \[.*\b55296 \[[1-9]/', $output);
        $this->assertMatchesRegularExpression('/\/W \[.*\b55297 \[[1-9]/', $output);

        // Glyphs of the CIDs in the CIDToGIDMap
        $this->assertSame(1, preg_match('/\/CIDToGIDMap (\d+) 0 R/', $output, $matches));
        $this->assertSame(1, preg_match("/\n{$matches[1]} 0 obj\n<<(.*?)>>\nstream\n(.*?)\nendstream/s", $output, $map));
        $cidtogid = strpos($map[1], "/FlateDecode") !== false ? gzuncompress($map[2]) : $map[2];
        $gid = function (int $cid) use ($cidtogid): int {
            return (ord($cidtogid[$cid * 2]) << 8) | ord($cidtogid[$cid * 2 + 1]);
        };
        $this->assertNotSame(0, $gid(0xD800));
        $this->assertNotSame(0, $gid(0xD801));
        $this->assertNotSame($gid(0xD800), $gid(0xD801));
        $this->assertSame(0, $gid(0xD802));
        if (!$subsetting) {
            // Glyph IDs of the original font
            $this->assertSame(5857, $gid(0xD800));
            $this->assertSame(5860, $gid(0xD801));
        }
    }
}
