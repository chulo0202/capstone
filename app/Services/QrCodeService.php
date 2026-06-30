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
        $path = "qr-codes/farmer-{$farmer->id}.png";

        $qrContent = json_encode([
            'farmer_id' => $farmer->id,
            'token' => $token,
            'name' => $farmer->full_name,
        ]);

        $png = QrCode::format('png')
            ->size(300)
            ->margin(2)
            ->generate($qrContent);

        Storage::disk('public')->put($path, $png);

        $farmer->update([
            'qr_code_token' => $token,
            'qr_code_path' => $path,
        ]);
    }
}
