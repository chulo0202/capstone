<?php

namespace App\Services;

use App\Models\Farmer;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeService
{
    public function generateForFarmer(Farmer $farmer): void
    {
        $token = $farmer->qr_code_token ?? Str::uuid()->toString();

        // SVG instead of PNG para hindi kailangan ng Imagick
        $path = "qr-codes/farmer-{$farmer->id}.svg";

        /*
        |--------------------------------------------------------------------------
        | Secure QR Payload
        |--------------------------------------------------------------------------
        |
        | Token lang ang nasa QR code.
        | Walang farmer_id at walang full name para hindi ma-expose
        | ang personal information ng farmer.
        |
        */

        $svg = QrCode::format('svg')
            ->size(300)
            ->margin(2)
            ->generate($this->payload($token));

        Storage::disk('public')->put($path, $svg);

        $farmer->update([
            'qr_code_token' => $token,
            'qr_code_path' => $path,
        ]);
    }

    public function outputDownloadPng(string $token): void
    {
        $matrix = Encoder::encode($this->payload($token), ErrorCorrectionLevel::L())->getMatrix();
        $width = $matrix->getWidth();
        $canvasSize = 720;
        // Even-sized modules leave an equal white margin on both sides.
        $moduleSize = 2 * intdiv($canvasSize, 2 * ($width + 8));
        $offset = intdiv($canvasSize - $width * $moduleSize, 2);

        $image = imagecreatetruecolor($canvasSize, $canvasSize);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagefill($image, 0, 0, $white);

        for ($y = 0; $y < $width; $y++) {
            for ($x = 0; $x < $width; $x++) {
                if ($matrix->get($x, $y) === 1) {
                    $left = $offset + $x * $moduleSize;
                    $top = $offset + $y * $moduleSize;
                    imagefilledrectangle($image, $left, $top, $left + $moduleSize - 1, $top + $moduleSize - 1, $black);
                }
            }
        }

        imagepng($image);
    }

    private function payload(string $token): string
    {
        return json_encode(['token' => $token], JSON_THROW_ON_ERROR);
    }
}
