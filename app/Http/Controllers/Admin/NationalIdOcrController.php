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
        $prepared = [];
        $details = ['full_name' => null, 'birthdate' => null];
        $read = false;
        $timedOut = false;

        try {
            $prepared = $this->preparePhoto($photo);
            $passes = [
                [$prepared['full'] ?? $photo, '11', false],
                [$prepared['text'] ?? $photo, '6', isset($prepared['text'])],
                [$photo, '6', false],
            ];

            foreach (array_unique($passes, SORT_REGULAR) as [$path, $mode, $focused]) {
                try {
                    $result = Process::timeout(5)->env(['OMP_THREAD_LIMIT' => '1'])->run([
                        'tesseract', $path, 'stdout', '-l', 'eng', '--psm', $mode,
                    ]);
                } catch (ProcessTimedOutException) {
                    $timedOut = true;

                    continue;
                }

                if ($result->successful()) {
                    $read = true;
                    $details = array_replace($this->parseDetails($result->output(), $focused), array_filter($details));

                    if ($details['full_name'] && $details['birthdate']) {
                        break;
                    }
                }
            }
        } finally {
            foreach ($prepared as $file) {
                unlink($file);
            }
        }

        if (! $read && $timedOut) {
            return response()->json(['message' => 'The ID took too long to read. Try a clearer image or enter the details manually.'], 422);
        }

        if (! $read) {
            return response()->json(['message' => 'The ID could not be read. Enter the details manually.'], 422);
        }

        return response()->json($details);
    }

    private function preparePhoto(string $path): array
    {
        $image = @imagecreatefromstring(file_get_contents($path));

        if (! $image) {
            return [];
        }

        $scale = min(3, 2200 / max(imagesx($image), imagesy($image)));
        $image = imagescale($image, max(1, (int) round(imagesx($image) * $scale)), max(1, (int) round(imagesy($image) * $scale)));

        if (! $image) {
            return [];
        }

        imagefilter($image, IMG_FILTER_GRAYSCALE);
        imagefilter($image, IMG_FILTER_CONTRAST, -25);

        $width = imagesx($image);
        $height = imagesy($image);
        // The name and birthdate sit to the right of the portrait on the physical PhilID.
        $text = imagecrop($image, [
            'x' => (int) floor($width * 0.43),
            'y' => (int) floor($height * 0.28),
            'width' => max(1, (int) floor($width * 0.52)),
            'height' => max(1, (int) floor($height * 0.55)),
        ]);

        return array_filter([
            'full' => $this->saveTemporaryImage($image),
            'text' => $text ? $this->saveTemporaryImage($text) : null,
        ]);
    }

    private function saveTemporaryImage(\GdImage $image): ?string
    {
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

    private function parseDetails(string $text, bool $focused): array
    {
        $given = $this->readField($text, 'Given[ \t]*Names?|Mga[ \t]+Pangalan');
        $middle = $this->readField($text, 'Middle[ \t]*Name|Gitnang[ \t]+Apelyido');
        $family = $this->readField($text, 'Last[ \t]*Name|^Apelyido');

        $names = array_filter([$given, $middle, $family]);
        $fullName = $given && $family && collect($names)->every(fn ($name) => preg_match('/^[\p{L} .\x{2019}\x{0027}-]+$/u', $name))
            ? implode(' ', $names)
            : null;

        $birthdate = null;
        $datePattern = '/\b(?:Jan(?:uary)?|Feb(?:ruary)?|Mar(?:ch)?|Apr(?:il)?|May|Jun(?:e)?|Jul(?:y)?|Aug(?:ust)?|Sep(?:tember)?|Oct(?:ober)?|Nov(?:ember)?|Dec(?:ember)?)[ \t]+\d{1,2},?[ \t]+\d{4}\b/i';
        $dateField = $this->readField($text, 'Date[ \t]*of[ \t]*Birth|Petsa[ \t]+ng[ \t]+Kapanganakan');
        preg_match_all($datePattern, $dateField ?? '', $matches);
        $dates = array_unique($matches[0]);

        // Recover a clear date when the small label disappeared in the cropped pass.
        if ($focused && $dateField === null) {
            preg_match_all($datePattern, $text, $matches);
            $dates = array_unique($matches[0]);
        }

        if (count($dates) === 1) {
            $date = reset($dates);
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
