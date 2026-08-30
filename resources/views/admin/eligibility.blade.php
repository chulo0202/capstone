@extends('layouts.admin')

@section('page-title', 'Eligibility Rules')

@section('main')
<div class="row g-3">

  {{-- SET ELIGIBILITY RULES --}}
  <div class="col-lg-5">
    <div class="card border-0 shadow-sm">

      <div class="card-header bg-white">
        <h6 class="mb-0">Set Eligibility Rules</h6>
      </div>

      <div class="card-body">

        <form method="POST" action="{{ route('admin.eligibility.store') }}">
          @csrf

          {{-- PROGRAM --}}
          <div class="mb-3">
            <label class="form-label fw-semibold">Program</label>

            <select
              name="program_id"
              class="form-select"
              required
            >
              <option value="">Select program</option>

              @foreach($programs as $program)
                <option value="{{ $program->id }}">
                  {{ $program->name }}
                </option>
              @endforeach

            </select>
          </div>


          {{-- CROP TYPES --}}
          <div class="mb-3">

    <label class="form-label fw-semibold">
        Crop Type
    </label>

    <select name="crop_types[]" class="form-select" required>
        <option value="">Select crop type</option>
        <option value="Rice">Rice</option>
        <option value="Corn">Corn</option>
        <option value="Vegetables">Vegetables</option>
        <option value="Fruits">Fruits</option>
        <option value="Coconut">Coconut</option>
        <option value="Other">Other</option>
    </select>

</div>


          {{-- LAND SIZE --}}
          <div class="row">

            <div class="col-6 mb-3">

              <label class="form-label fw-semibold">
                Min Land (ha)
              </label>

              <input
                type="number"
                step="0.01"
                min="0"
                name="min_land_size"
                class="form-control"
                placeholder="e.g. 1.00"
              >

            </div>

            <div class="col-6 mb-3">

              <label class="form-label fw-semibold">
                Max Land (ha)
              </label>

              <input
                type="number"
                step="0.01"
                min="0"
                name="max_land_size"
                class="form-control"
                placeholder="e.g. 5.00"
              >

            </div>

          </div>


          {{-- RSBSA --}}
          <div class="form-check mb-2">

            <input
              type="checkbox"
              name="requires_rsbsa"
              value="1"
              class="form-check-input"
              id="rsbsa"
            >

            <label
              class="form-check-label"
              for="rsbsa"
            >
              Requires RSBSA
            </label>

          </div>


          {{-- ASSOCIATION --}}
          <div class="form-check mb-2">

            <input
              type="checkbox"
              name="requires_association"
              value="1"
              class="form-check-input"
              id="assoc"
            >

            <label
              class="form-check-label"
              for="assoc"
            >
              Requires Association Membership
            </label>

          </div>


          {{-- 4PS --}}
          <div class="form-check mb-3">

            <input
              type="checkbox"
              name="requires_4ps"
              value="1"
              class="form-check-input"
              id="fourps"
            >

            <label
              class="form-check-label"
              for="fourps"
            >
              Requires 4Ps Membership
            </label>

          </div>


          {{-- SAVE --}}
          <button
            type="submit"
            class="btn btn-fams w-100"
          >
            Save Rules
          </button>

        </form>

      </div>
    </div>
  </div>


  {{-- CURRENT RULES --}}
  <div class="col-lg-7">

    <div class="card border-0 shadow-sm">

      <div class="card-header bg-white">
        <h6 class="mb-0">Current Rules</h6>
      </div>

      <div class="list-group list-group-flush">

        @forelse($programs->whereNotNull('eligibilityRule') as $program)

          <div class="list-group-item">

            <div class="d-flex justify-content-between align-items-start">

              <div>
                <strong>
                  {{ $program->name }}
                </strong>
              </div>

              <form
                action="{{ route('admin.eligibility.destroy', $program->eligibilityRule) }}"
                method="POST"
                onsubmit="return confirm('Delete rules?')"
              >

                @csrf
                @method('DELETE')

                <button
                  type="submit"
                  class="btn btn-sm btn-outline-danger"
                >
                  Delete
                </button>

              </form>

            </div>


            <small class="text-muted d-block mt-2">

              <strong>Crops:</strong>

              @if($program->eligibilityRule->crop_types)
                {{ implode(', ', $program->eligibilityRule->crop_types) }}
              @else
                Any
              @endif

              |

              <strong>Land:</strong>

              {{ $program->eligibilityRule->min_land_size ?? '0' }}
              -
              {{ $program->eligibilityRule->max_land_size ?? '∞' }}
              ha

              |

              <strong>RSBSA:</strong>

              {{ $program->eligibilityRule->requires_rsbsa ? 'Yes' : 'No' }}

              |

              <strong>Association:</strong>

              {{ $program->eligibilityRule->requires_association ? 'Yes' : 'No' }}

            </small>

          </div>

        @empty

          <div class="list-group-item text-muted">
            No eligibility rules configured
          </div>

        @endforelse

      </div>

    </div>

  </div>

</div>
@endsection