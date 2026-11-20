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

        try {
            $result = Process::timeout(15)->env(['OMP_THREAD_LIMIT' => '1'])->run([
                'tesseract', $request->file('valid_id')->getRealPath(), 'stdout', '-l', 'eng', '--psm', '6',
            ]);
        } catch (ProcessTimedOutException) {
            return response()->json(['message' => 'The ID took too long to read. Try a clearer image or enter the details manually.'], 422);
        }

        if ($result->failed()) {
            return response()->json(['message' => 'The ID could not be read. Enter the details manually.'], 422);
        }

        $text = $result->output();
        $given = $this->readField($text, 'Given Names?');
        $middle = $this->readField($text, 'Middle Name');
        $family = $this->readField($text, 'Last Name');

        $names = array_filter([$given, $middle, $family]);
        $fullName = $given && $family && collect($names)->every(fn ($name) => preg_match('/^[\p{L} .\x{2019}\x{0027}-]+$/u', $name))
            ? implode(' ', $names)
            : null;

        $birthdate = null;
        $date = preg_replace('/[ \t]+/', ' ', $this->readField($text, 'Date of Birth') ?? '');

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

        return response()->json([
            'full_name' => $fullName,
            'birthdate' => $birthdate,
        ]);
    }

    private function readField(string $text, string $label): ?string
    {
        $pattern = '/^[^\r\n]*\b(?:'.$label.')[ \t]*[:\-]?[ \t]*(?:\R[ \t]*)*([^\r\n]+)$/mi';

        if (! preg_match($pattern, $text, $match)) {
            return null;
        }

        $value = trim($match[1]);

        return $value !== '' && ! preg_match('/[\/:]|\b(?:Last Name|Given Names?|Middle Name|Date of Birth|Address)\b/i', $value)
            ? $value
            : null;
    }
}
