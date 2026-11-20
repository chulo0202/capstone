<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Facades\Process;

class NationalIdOcrController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'valid_id' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120', 'dimensions:max_width=6000,max_height=6000'],
        ]);

        $photo = $request->file('valid_id')->getRealPath();
        $prepared = $this->preparePhoto($photo);
        $details = ['full_name' => null, 'birthdate' => null];
        $read = false;

        try {
            foreach ([[$prepared ?: $photo, '11'], [$photo, '6']] as [$path, $mode]) {
                $result = Process::timeout(8)->env(['OMP_THREAD_LIMIT' => '1'])->run([
                    'tesseract', $path, 'stdout', '-l', 'eng', '--psm', $mode,
                ]);

                if ($result->successful()) {
                    $read = true;
                    $details = array_replace($this->parseDetails($result->output()), array_filter($details));

                    if ($details['full_name'] && $details['birthdate']) {
                        break;
                    }
                }
            }
        } catch (ProcessTimedOutException) {
            if (! $read) {
                return response()->json(['message' => 'The ID took too long to read. Try a clearer image or enter the details manually.'], 422);
            }
        } finally {
            if ($prepared) {
                unlink($prepared);
            }
        }

        if (! $read) {
            return response()->json(['message' => 'The ID could not be read. Enter the details manually.'], 422);
        }

        return response()->json($details);
    }

    private function preparePhoto(string $path): ?string
    {
        $image = @imagecreatefromstring(file_get_contents($path));

        if (! $image) {
            return null;
        }

        $scale = min(3, 2200 / max(imagesx($image), imagesy($image)));
        $image = imagescale($image, max(1, (int) round(imagesx($image) * $scale)), max(1, (int) round(imagesy($image) * $scale)));

        if (! $image) {
            return null;
        }

        imagefilter($image, IMG_FILTER_GRAYSCALE);
        imagefilter($image, IMG_FILTER_CONTRAST, -25);

        $file = tempnam(sys_get_temp_dir(), 'fams-id-');

        if ($file === false) {
            return null;
        }

        if (! imagepng($image, $file)) {
            unlink($file);

            return null;
        }

        return $file;
    }

    private function parseDetails(string $text): array
    {
        $given = $this->readField($text, 'Given[ \t]*Names?|Mga[ \t]+Pangalan');
        $middle = $this->readField($text, 'Middle[ \t]*Name|Gitnang[ \t]+Apelyido');
        $family = $this->readField($text, 'Last[ \t]*Name|^Apelyido');

        $names = array_filter([$given, $middle, $family]);
        $fullName = $given && $family && collect($names)->every(fn ($name) => preg_match('/^[\p{L} .\x{2019}\x{0027}-]+$/u', $name))
            ? implode(' ', $names)
            : null;

        $birthdate = null;
        $date = preg_replace('/[ \t]+/', ' ', $this->readField($text, 'Date[ \t]*of[ \t]*Birth|Petsa[ \t]+ng[ \t]+Kapanganakan') ?? '');

        if ($date && preg_match('/^[a-z]{3,9} \d{1,2},? \d{4}$/i', $date)) {
            $parsed = date_parse(str_replace(',', '', $date));

            if (
                $parsed['error_count'] === 0 && $parsed['warning_count'] === 0
                && checkdate($parsed['month'], $parsed['day'], $parsed['year'])
            ) {
                $candidate = CarbonImmutable::createMidnightDate($parsed['year'], $parsed['month'], $parsed['day']);
                $birthdate = $candidate->isBefore(today()) ? $candidate->toDateString() : null;
            }
        }

        return [
            'full_name' => $fullName,
            'birthdate' => $birthdate,
        ];
    }

    private function readField(string $text, string $label): ?string
    {
        $pattern = '/^[^\r\n]*\b(?:'.$label.')[ \t]*[:\-]?[ \t]*(?:\R[ \t]*)*([^\r\n]+)$/mi';

        if (! preg_match($pattern, $text, $match)) {
            return null;
        }

        $value = trim($match[1]);

        return $value !== '' && ! preg_match('/[\/:]|\b(?:Last[ \t]*Name|Given[ \t]*Names?|Middle[ \t]*Name|Date[ \t]*of[ \t]*Birth|Address|Apelyido|Pangalan|Kapanganakan|Tirahan)\b/i', $value)
            ? $value
            : null;
    }
}
