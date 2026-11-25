<?php

namespace App\Services;

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Writer;

/**
 * Standards-compliant ISO/IEC 18004 QR Code Generator powered by BaconQrCode.
 * Generates genuine scannable QR Code SVGs and Data URIs readable by any smartphone camera.
 */
class QRCodeService
{
    /**
     * Generate an inline standards-compliant SVG for any given text/URL.
     */
    public function generateSvg(string $data, int $size = 200, int $margin = 4): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size, $margin),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);

        return $writer->writeString($data);
    }

    /**
     * Return base64 encoded SVG data URI suitable for <img> tags and DomPDF.
     */
    public function generateDataUri(string $data, int $size = 200): string
    {
        $svg = $this->generateSvg($data, $size);
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
