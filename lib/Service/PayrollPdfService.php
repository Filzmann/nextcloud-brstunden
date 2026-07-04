<?php

declare(strict_types=1);

namespace OCA\BrStunden\Service;

use OCA\BrStunden\Model\HourEntry;
use OCA\BrStunden\Store\HourEntryStore;

class PayrollPdfService {
    private const PAGE_WIDTH = 595.28;
    private const PAGE_HEIGHT = 841.89;

    public function __construct(
        private BrMemberService $members,
        private HourEntryStore $entries,
        private CalendarService $calendar
    ) {
    }

    public function createForCurrentUser(int $year, int $month): array {
        $this->members->assertCurrentUserIsMember();
        $this->calendar->assertValidYear($year);
        $this->calendar->assertValidMonth($month);
        if ($this->calendar->isFutureMonth($year, $month)) {
            throw new \InvalidArgumentException('Zukuenftige Monate koennen noch nicht abgerechnet werden.');
        }

        $uid = $this->members->currentUserId();
        $entry = $this->entries->findForUserMonth($uid, $year, $month);
        if (!$entry instanceof HourEntry) {
            throw new \InvalidArgumentException('Bitte den eigenen Eintrag zuerst speichern, bevor die Abrechnung erzeugt wird.');
        }

        $member = $this->currentMember($uid);
        $filename = sprintf('br-stunden-abrechnung-%s-%04d-%02d.pdf', $uid, $year, $month);

        return [
            'filename' => $filename,
            'content' => $this->renderPdf($member, $entry),
            'contentType' => 'application/pdf',
        ];
    }

    private function currentMember(string $uid): array {
        foreach ($this->members->members() as $member) {
            if (($member['uid'] ?? '') === $uid) {
                return $member;
            }
        }

        return ['uid' => $uid, 'displayName' => $uid, 'email' => ''];
    }

    private function renderPdf(array $member, HourEntry $entry): string {
        $commands = [];
        $headerPath = __DIR__ . '/../../img/abrechnung-header.jpg';
        $hasHeader = is_file($headerPath);

        if ($hasHeader) {
            $commands[] = $this->image('Im1', 72, 790, 452, 35);
        }

        $monthLabel = $this->calendar->monthLabel($entry->month) . ' ' . $entry->year;
        $name = (string)($member['displayName'] ?: $member['uid']);
        $brHours = $this->formatHours($entry->minutes);
        $fobiHours = $this->formatHours($entry->fobiMinutes);
        $totalHours = $this->formatHours($entry->minutes + $entry->fobiMinutes);
        $today = $this->calendar->today()->format('d.m.Y');

        $commands[] = $this->textCentered(297.64, 754, 'Abrechnung - Betriebsrat', 20, true);
        $commands[] = $this->textCentered(285, 724, 'fuer den Monat:', 13, true);
        $commands[] = $this->text(386, 724, $monthLabel, 12);

        $commands[] = $this->text(43, 686, 'Name, Vorname:', 12, true);
        $commands[] = $this->text(155, 686, $name, 12);
        $commands[] = $this->text(43, 671, 'Adresse:', 12, true);
        $commands[] = $this->text(155, 671, $this->members->currentUserAddress(), 11);

        $commands[] = $this->text(43, 600, 'Stunden Betriebsrat', 17, true);
        $commands[] = $this->text(270, 600, 'Summe der Stunden:', 12);
        $commands[] = $this->line(395, 597, 459, 597);
        $commands[] = $this->line(395, 595, 459, 595);
        $commands[] = $this->textRight(455, 603, $brHours, 12);
        $commands[] = $this->line(478, 597, 540, 597);
        $commands[] = $this->line(478, 595, 540, 595);

        $commands[] = $this->text(188, 554, 'davon freigestellt', 12);
        $commands[] = $this->rect(343, 544, 80, 20);
        $commands[] = $this->text(188, 524, 'Mehrarbeitsstunden', 12);
        $commands[] = $this->rect(343, 514, 80, 20);

        $commands[] = $this->text(43, 455, 'Fortbildungen', 17, true);
        $commands[] = $this->line(43, 423, 313, 423);
        $commands[] = $this->text(326, 426, 'FoBi-Stunden', 12);
        $commands[] = $this->line(395, 423, 462, 423);
        $commands[] = $this->line(395, 421, 462, 421);
        $commands[] = $this->textRight(458, 429, $fobiHours, 12);
        $commands[] = $this->line(477, 423, 542, 423);
        $commands[] = $this->line(477, 421, 542, 421);

        $commands[] = $this->line(43, 395, 316, 395);
        $commands[] = $this->text(331, 398, 'Stunden', 12);
        $commands[] = $this->line(395, 395, 462, 395);
        $commands[] = $this->line(395, 393, 462, 393);
        $commands[] = $this->line(477, 395, 542, 395);
        $commands[] = $this->line(477, 393, 542, 393);

        $commands[] = $this->text(250, 366, 'Summe der Stunden', 13, true);
        $commands[] = $this->line(250, 363, 368, 363);
        $commands[] = $this->line(395, 364, 462, 364);
        $commands[] = $this->textRight(458, 370, $fobiHours, 12);
        $commands[] = $this->line(477, 364, 542, 364);

        $commands[] = $this->text(43, 304, 'Sonstiges', 17, true);
        $commands[] = $this->line(43, 272, 316, 272);
        $commands[] = $this->text(331, 276, 'Stunden', 12);
        $commands[] = $this->line(477, 272, 542, 272);

        $commands[] = $this->text(43, 211, 'Stunden insgesamt', 17, true);
        $commands[] = $this->rect(463, 220, 80, 20);
        $commands[] = $this->textRight(538, 226, $totalHours, 12);

        $commands[] = $this->text(64, 159, 'Berlin, ' . $today, 12);
        $commands[] = $this->text(64, 74, 'Unterschrift', 12);

        return $this->pdf(implode("\n", $commands) . "\n", $hasHeader ? $headerPath : null);
    }

    private function formatHours(int $minutes): string {
        $value = number_format($minutes / 60, 2, ',', '');

        return rtrim(rtrim($value, '0'), ',');
    }

    private function text(float $x, float $y, string $text, int $size = 11, bool $bold = false): string {
        return sprintf(
            'BT /%s %d Tf %s %s Td (%s) Tj ET',
            $bold ? 'F2' : 'F1',
            $size,
            $this->number($x),
            $this->number($y),
            $this->escape($text)
        );
    }

    private function textRight(float $rightX, float $y, string $text, int $size = 11, bool $bold = false): string {
        $width = $this->textWidth($text, $size, $bold);

        return $this->text($rightX - $width, $y, $text, $size, $bold);
    }

    private function textCentered(float $centerX, float $y, string $text, int $size = 11, bool $bold = false): string {
        $width = $this->textWidth($text, $size, $bold);

        return $this->text($centerX - ($width / 2), $y, $text, $size, $bold);
    }

    private function textWidth(string $text, int $size, bool $bold): float {
        $factor = $bold ? 0.55 : 0.5;

        return strlen($this->encodeText($text)) * $size * $factor;
    }

    private function line(float $x1, float $y1, float $x2, float $y2): string {
        return sprintf(
            'q 0.75 w %s %s m %s %s l S Q',
            $this->number($x1),
            $this->number($y1),
            $this->number($x2),
            $this->number($y2)
        );
    }

    private function rect(float $x, float $y, float $width, float $height): string {
        return sprintf(
            'q 0.75 w %s %s %s %s re S Q',
            $this->number($x),
            $this->number($y),
            $this->number($width),
            $this->number($height)
        );
    }

    private function image(string $name, float $x, float $y, float $width, float $height): string {
        return sprintf(
            'q %s 0 0 %s %s %s cm /%s Do Q',
            $this->number($width),
            $this->number($height),
            $this->number($x),
            $this->number($y),
            $name
        );
    }

    private function pdf(string $content, ?string $imagePath): string {
        $objects = [];
        $resources = '<< /Font << /F1 4 0 R /F2 5 0 R >>';
        if ($imagePath !== null) {
            $resources .= ' /XObject << /Im1 6 0 R >>';
        }
        $resources .= ' >>';

        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[2] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';
        $objects[3] = sprintf(
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %s %s] /Resources %s /Contents %d 0 R >>',
            $this->number(self::PAGE_WIDTH),
            $this->number(self::PAGE_HEIGHT),
            $resources,
            $imagePath === null ? 6 : 7
        );
        $objects[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[5] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';

        $contentObjectId = 6;
        if ($imagePath !== null) {
            $image = file_get_contents($imagePath);
            $size = getimagesize($imagePath);
            if ($image === false || $size === false) {
                throw new \RuntimeException('PDF-Kopf konnte nicht geladen werden.');
            }

            $objects[6] = sprintf(
                "<< /Type /XObject /Subtype /Image /Width %d /Height %d /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length %d >>\nstream\n%s\nendstream",
                (int)$size[0],
                (int)$size[1],
                strlen($image),
                $image
            );
            $contentObjectId = 7;
        }

        $objects[$contentObjectId] = sprintf(
            "<< /Length %d >>\nstream\n%s\nendstream",
            strlen($content),
            $content
        );

        ksort($objects);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0 => 0];
        foreach ($objects as $id => $object) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $object . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $count = max(array_keys($objects)) + 1;
        $pdf .= "xref\n0 " . $count . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($id = 1; $id < $count; $id++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$id] ?? 0);
        }
        $pdf .= "trailer\n<< /Size " . $count . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF\n";

        return $pdf;
    }

    private function escape(string $text): string {
        return str_replace(
            ['\\', '(', ')', "\r", "\n"],
            ['\\\\', '\\(', '\\)', ' ', ' '],
            $this->encodeText($text)
        );
    }

    private function encodeText(string $text): string {
        $encoded = @iconv('UTF-8', 'Windows-1252//TRANSLIT', $text);

        return $encoded === false ? $text : $encoded;
    }

    private function number(float $value): string {
        return number_format($value, 2, '.', '');
    }
}
