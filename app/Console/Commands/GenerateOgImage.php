<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Imagick;
use ImagickDraw;
use ImagickPixel;

class GenerateOgImage extends Command
{
    protected $signature   = 'og:generate';
    protected $description = 'Generate public/images/og.jpeg for social sharing';

    private const W     = 1200;
    private const H     = 630;
    private const YEL   = '#FFE000';
    private const INK   = '#1A1A1A';
    private const RED   = '#E8001C';
    private const CREAM = '#FFF8ED';
    private const TEAL  = '#00C49A';

    public function handle(): int
    {
        $img = new Imagick();
        $img->newImage(self::W, self::H, new ImagickPixel(self::YEL));
        $img->setImageFormat('png');

        // ── Halftone dots (top-right decoration) ─────────────────────────────
        $dots = new ImagickDraw();
        $dots->setFillColor(new ImagickPixel(self::INK));
        $dots->setFillOpacity(0.12);
        for ($row = 0; $row < 10; $row++) {
            for ($col = 0; $col < 10; $col++) {
                $r = 8 - $row * 0.6;
                if ($r < 2) continue;
                $x = self::W - 260 + $col * 28;
                $y = 30 + $row * 28;
                $dots->circle($x, $y, $x + $r, $y + $r);
            }
        }
        $img->drawImage($dots);

        // ── Left black panel ──────────────────────────────────────────────────
        $panel = new ImagickDraw();
        $panel->setFillColor(new ImagickPixel(self::INK));
        $panel->rectangle(0, 0, 650, self::H);
        $img->drawImage($panel);

        // ── Red top accent strip ──────────────────────────────────────────────
        $strip = new ImagickDraw();
        $strip->setFillColor(new ImagickPixel(self::RED));
        $strip->rectangle(0, 0, 650, 14);
        $img->drawImage($strip);

        // ── Eyebrow ───────────────────────────────────────────────────────────
        $eyebrow = new ImagickDraw();
        $eyebrow->setFont('Liberation-Sans-Bold');
        $eyebrow->setFontSize(22);
        $eyebrow->setFillColor(new ImagickPixel(self::RED));
        $eyebrow->setTextKerning(6);
        $img->annotateImage($eyebrow, 58, 76, 0, 'FIFA WORLD CUP 2026');

        // ── "MUNDIAL" ─────────────────────────────────────────────────────────
        $t1 = new ImagickDraw();
        $t1->setFont('Liberation-Sans-Bold');
        $t1->setFontSize(144);
        $t1->setFillColor(new ImagickPixel(self::CREAM));
        $img->annotateImage($t1, 44, 238, 0, 'MUNDIAL');

        // ── "DE PARCHE" ───────────────────────────────────────────────────────
        $t2 = new ImagickDraw();
        $t2->setFont('Liberation-Sans-Bold');
        $t2->setFontSize(78);
        $t2->setFillColor(new ImagickPixel(self::YEL));
        $img->annotateImage($t2, 44, 328, 0, 'DE PARCHE');

        // ── Divider ───────────────────────────────────────────────────────────
        $line = new ImagickDraw();
        $line->setStrokeColor(new ImagickPixel(self::CREAM));
        $line->setStrokeOpacity(0.25);
        $line->setStrokeWidth(2);
        $line->line(44, 358, 610, 358);
        $img->drawImage($line);

        // ── Subtitle ──────────────────────────────────────────────────────────
        $sub = new ImagickDraw();
        $sub->setFont('Liberation-Sans-Bold');
        $sub->setFontSize(24);
        $sub->setFillColor(new ImagickPixel(self::CREAM));
        $sub->setFillOpacity(0.65);
        $sub->setTextKerning(3);
        $img->annotateImage($sub, 44, 398, 0, 'LA QUINIELA DEL PARCHE');

        // ── Teal CTA box ──────────────────────────────────────────────────────
        $pill = new ImagickDraw();
        $pill->setFillColor(new ImagickPixel(self::TEAL));
        $pill->setStrokeColor(new ImagickPixel(self::CREAM));
        $pill->setStrokeWidth(3);
        $pill->rectangle(44, 438, 390, 492);
        $img->drawImage($pill);

        $cta = new ImagickDraw();
        $cta->setFont('Liberation-Sans-Bold');
        $cta->setFontSize(22);
        $cta->setFillColor(new ImagickPixel(self::CREAM));
        $cta->setTextKerning(4);
        $img->annotateImage($cta, 62, 474, 0, 'ENTRA AL PARCHE →');

        // ── Trophy (right side) ───────────────────────────────────────────────
        $this->drawTrophy($img, 900, 290);

        // ── Bottom red band (right side only) ────────────────────────────────
        $band = new ImagickDraw();
        $band->setFillColor(new ImagickPixel(self::RED));
        $band->setStrokeColor(new ImagickPixel(self::INK));
        $band->setStrokeWidth(4);
        $band->rectangle(670, 516, self::W - 20, 582);
        $img->drawImage($band);

        $year = new ImagickDraw();
        $year->setFont('Liberation-Sans-Bold');
        $year->setFontSize(30);
        $year->setFillColor(new ImagickPixel(self::CREAM));
        $year->setTextKerning(4);
        $img->annotateImage($year, 695, 558, 0, 'USA · CANADA · MEXICO 2026');

        // ── Outer border ─────────────────────────────────────────────────────
        $border = new ImagickDraw();
        $border->setFillOpacity(0);
        $border->setStrokeColor(new ImagickPixel(self::INK));
        $border->setStrokeWidth(8);
        $border->rectangle(4, 4, self::W - 4, self::H - 4);
        $img->drawImage($border);

        $path = public_path('images/og.jpeg');
        $img->writeImage($path);
        $img->destroy();

        $this->info("OG image generated → {$path}");

        return self::SUCCESS;
    }

    private function drawTrophy(Imagick $img, int $cx, int $cy): void
    {
        $d = new ImagickDraw();
        $d->setFillColor(new ImagickPixel(self::YEL));
        $d->setStrokeColor(new ImagickPixel(self::INK));
        $d->setStrokeWidth(5);

        // Cup body
        $d->roundRectangle($cx - 85, $cy - 150, $cx + 85, $cy + 50, 12, 12);

        // Left handle
        $d->arc($cx - 130, $cy - 110, $cx - 60, $cy + 10, 90, 270);

        // Right handle
        $d->arc($cx + 60, $cy - 110, $cx + 130, $cy + 10, 270, 90);

        // Stem
        $d->rectangle($cx - 22, $cy + 50, $cx + 22, $cy + 100);

        // Base plate
        $d->roundRectangle($cx - 65, $cy + 100, $cx + 65, $cy + 130, 4, 4);

        $img->drawImage($d);

        // Star circle inside cup
        $star = new ImagickDraw();
        $star->setFillColor(new ImagickPixel(self::RED));
        $star->setStrokeColor(new ImagickPixel(self::INK));
        $star->setStrokeWidth(3);
        $star->circle($cx, $cy - 50, $cx + 28, $cy - 50);
        $img->drawImage($star);

        $starTxt = new ImagickDraw();
        $starTxt->setFont('Liberation-Sans-Bold');
        $starTxt->setFontSize(32);
        $starTxt->setFillColor(new ImagickPixel(self::CREAM));
        $img->annotateImage($starTxt, $cx - 11, $cy - 38, 0, '★');
    }
}
