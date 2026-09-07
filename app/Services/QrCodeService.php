<?php

namespace App\Services;

use App\Models\Farmer;
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

        $qrContent = json_encode([
            'token' => $token,
        ]);

        $svg = QrCode::format('svg')
            ->size(300)
            ->margin(2)
            ->generate($qrContent);

        Storage::disk('public')->put($path, $svg);

        $farmer->update([
            'qr_code_token' => $token,
            'qr_code_path' => $path,
        ]);
    }
}