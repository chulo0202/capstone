<dl class="row g-2 mb-0">
    <dt class="col-sm-4">Topic</dt><dd class="col-sm-8">{{ $training->topic }}</dd>
    <dt class="col-sm-4">Date and Time</dt><dd class="col-sm-8">{{ $training->scheduled_date->format('M j, Y') }} · {{ substr($training->start_time, 0, 5) }}–{{ substr($training->end_time, 0, 5) }} (Philippine time)</dd>
    <dt class="col-sm-4">Venue</dt><dd class="col-sm-8">{{ $training->venue }}</dd>
    <dt class="col-sm-4">Trainer</dt><dd class="col-sm-8">{{ $training->trainer }}</dd>
    <dt class="col-sm-4">Description and Objectives</dt><dd class="col-sm-8 text-break">{{ $training->objectives }}</dd>
    @if ($training->instructions)
        <dt class="col-sm-4">Instructions</dt><dd class="col-sm-8 text-break">{{ $training->instructions }}</dd>
    @endif
</dl>
