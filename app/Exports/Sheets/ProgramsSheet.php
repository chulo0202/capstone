<?php

namespace App\Exports\Sheets;

use App\Models\Program;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;

class ProgramsSheet implements FromCollection
{
    public function collection(): Collection
    {
        $rows = collect([
            [
                'ID',
                'Program Name',
                'Description',
                'Eligibility Criteria',
                'Status',
                'Start Date',
                'End Date',
            ],
        ]);

        Program::query()
            ->orderBy('name')
            ->get()
            ->each(function (Program $program) use ($rows) {
                $rows->push([
                    $program->id,
                    $program->name,
                    $program->description,
                    $program->eligibility_criteria,
                    $program->is_active ? 'Active' : 'Inactive',
                    $program->start_at?->format('Y-m-d H:i:s'),
                    $program->end_at?->format('Y-m-d H:i:s'),
                ]);
            });

        return $rows;
    }
}