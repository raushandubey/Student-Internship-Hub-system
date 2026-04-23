{{-- Reusable filters component for application list --}}
{{-- Usage: @include('recruiter.applications.filters', ['internships' => $internships, 'filters' => $filters]) --}}

<form method="GET" action="{{ route('recruiter.applications.index') }}">
    <div class="filters-bar">
        <div class="filter-group">
            <label>Status</label>
            <select name="status" class="filter-control">
                <option value="">All Statuses</option>
                @foreach(\App\Enums\ApplicationStatus::cases() as $s)
                    <option value="{{ $s->value }}" {{ ($filters['status'] ?? '') === $s->value ? 'selected' : '' }}>
                        {{ $s->label() }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="filter-group">
            <label>Internship</label>
            <select name="internship_id" class="filter-control">
                <option value="">All Internships</option>
                @foreach($internships as $i)
                    <option value="{{ $i->id }}" {{ ($filters['internship_id'] ?? '') == $i->id ? 'selected' : '' }}>
                        {{ $i->title }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="filter-group">
            <label>From Date</label>
            <input type="date" name="date_from" class="filter-control" value="{{ $filters['date_from'] ?? '' }}">
        </div>

        <div class="filter-group">
            <label>To Date</label>
            <input type="date" name="date_to" class="filter-control" value="{{ $filters['date_to'] ?? '' }}">
        </div>

        <button type="submit" class="btn-filter">
            <i class="fas fa-filter me-1"></i>Filter
        </button>
        <a href="{{ route('recruiter.applications.index') }}" class="btn-clear">Clear</a>
    </div>
</form>
