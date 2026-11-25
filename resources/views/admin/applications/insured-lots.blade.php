@foreach ($lots as $index => $lot)
    <section class="border rounded-3 p-3 mb-3">
        <h3 class="h6">Lot {{ $index + 1 }}</h3>
        <dl class="mb-0">
            <dt>Area to be Insured</dt>
            <dd>{{ $lot['area_to_insure'] }} hectares</dd>
            @foreach (['north', 'south', 'east', 'west'] as $direction)
                <dt>{{ ucfirst($direction) }}</dt>
                <dd>{{ $lot['adjacent_'.$direction] }}</dd>
            @endforeach
        </dl>
    </section>
@endforeach
