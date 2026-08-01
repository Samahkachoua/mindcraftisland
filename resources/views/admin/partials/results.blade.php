@if($registrations->total() === 0)
<div class="card empty-state">
    <div class="empty-icon">&#128203;</div>
    @if($search !== '')
    <p style="font-weight: 700; font-size: 1.1rem;">No results for "{{ $search }}".</p>
    <p style="margin-top: 0.4rem; color: #8a9ab0;"><a href="{{ route('admin.registrations', ['type' => $type, 'sort' => $sort, 'direction' => $direction]) }}" class="ajax-nav">Clear search</a> to see all registrations.</p>
    @else
    <p style="font-weight: 700; font-size: 1.1rem;">No registrations yet.</p>
    <p style="margin-top: 0.4rem; color: #8a9ab0;">Share the <a href="{{ route('register') }}">Registration Form</a> to get started.</p>
    @endif
</div>
@else

@php
$cols = [
'full_name' => 'Full Name',
'registration_type' => 'Registration Type',
'date_of_birth' => 'Age',
'created_at' => 'Submitted At',
];
$fixed = ['Phone Number', 'Emergency Contact', "Mother's Name", 'Medical Conditions', 'Field of Interests', 'Photo Consent'];

if (!function_exists('sortUrl')) {
function sortUrl(string $col, string $currentSort, string $currentDir): string {
$newDir = ($col === $currentSort && $currentDir === 'asc') ? 'desc' : 'asc';
return request()->fullUrlWithQuery(['sort' => $col, 'direction' => $newDir, 'page' => 1]);
}
}

if (!function_exists('sortIcon')) {
function sortIcon(string $col, string $currentSort, string $currentDir): string {
if ($col !== $currentSort) return '<span class="sort-icon">&#8597;</span>';
return $currentDir === 'asc'
? '<span class="sort-icon sort-active">&#8593;</span>'
: '<span class="sort-icon sort-active">&#8595;</span>';
}
}
@endphp

<div class="table-wrapper">
    <table>
        <thead>
            <tr>
                @foreach($cols as $key => $label)
                <th>
                    <a href="{{ sortUrl($key, $sort, $direction) }}" class="th-sort ajax-nav">
                        {{ $label }}{!! sortIcon($key, $sort, $direction) !!}
                    </a>
                </th>
                @endforeach
                @foreach($fixed as $label)
                <th>{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($registrations as $reg)
            <tr>
                <td data-label="Full Name" style="font-weight: 700;">{{ $reg['full_name'] ?? '—' }}</td>
                <td data-label="Registration Type">{{ ($reg['registration_type'] ?? 'child') === 'lady' ? 'Lady' : 'Child' }}</td>
                <td data-label="Age">
                    @if(isset($reg['date_of_birth']))
                    {{ \Carbon\Carbon::parse($reg['date_of_birth'])->age }} years
                    @else —
                    @endif
                </td>
                <td data-label="Submitted At" style="color: #8a9ab0; font-size: 0.83rem;">
                    @if(isset($reg['created_at']))
                    {{ \Carbon\Carbon::parse($reg['created_at'])->format('d M Y, H:i') }}
                    @else —
                    @endif
                </td>
                <td data-label="Phone Number">{{ $reg['phone_number'] ?? '—' }}</td>
                <td data-label="Emergency Contact">{{ $reg['emergency_contact_number'] ?? '—' }}</td>
                <td data-label="Mother's Name">{{ $reg['mother_name'] ?? '—' }}</td>
                <td data-label="Medical Conditions">{{ $reg['medical_conditions'] ?? '—' }}</td>
                <td data-label="Field of Interests">{{ $reg['field_of_interests'] ?? '—' }}</td>
                <td data-label="Photo Consent">{{ !empty($reg['photo_video_consent']) ? '✓' : '✗' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Pagination --}}
@if($registrations->lastPage() > 1)
<div class="pagination-bar">
    <div class="page-info">
        Showing {{ $registrations->firstItem() }}–{{ $registrations->lastItem() }} of {{ $registrations->total() }} registrations
        @if($search !== '') <span class="badge-filtered">(filtered)</span> @endif
    </div>
    <div class="page-buttons">
        {{-- Prev --}}
        @if($registrations->onFirstPage())
        <span class="page-btn disabled">&#8592;</span>
        @else
        <a href="{{ $registrations->previousPageUrl() }}" class="page-btn ajax-nav">&#8592;</a>
        @endif

        {{-- Page numbers --}}
        @php
        $current = $registrations->currentPage();
        $last = $registrations->lastPage();
        $window = 2;
        $pages = collect();
        for ($i = max(1, $current - $window); $i <= min($last, $current + $window); $i++) {
            $pages->push($i);
            }
            $showLeadingEllipsis = $pages->first() > 2;
            $showTrailingEllipsis = $pages->last() < $last - 1;
                @endphp

                @if($pages->first() > 1)
                <a href="{{ $registrations->url(1) }}" class="page-btn ajax-nav">1</a>
                @endif
                @if($showLeadingEllipsis)
                <span class="page-btn disabled">&hellip;</span>
                @endif

                @foreach($pages as $p)
                @if($p === $current)
                <span class="page-btn active">{{ $p }}</span>
                @else
                <a href="{{ $registrations->url($p) }}" class="page-btn ajax-nav">{{ $p }}</a>
                @endif
                @endforeach

                @if($showTrailingEllipsis)
                <span class="page-btn disabled">&hellip;</span>
                @endif
                @if($pages->last() < $last)
                    <a href="{{ $registrations->url($last) }}" class="page-btn ajax-nav">{{ $last }}</a>
                    @endif

                    {{-- Next --}}
                    @if($registrations->hasMorePages())
                    <a href="{{ $registrations->nextPageUrl() }}" class="page-btn ajax-nav">&#8594;</a>
                    @else
                    <span class="page-btn disabled">&#8594;</span>
                    @endif
    </div>
</div>
@else
<div class="page-info" style="margin-top: 1rem;">
    Showing all {{ $registrations->total() }} registration{{ $registrations->total() !== 1 ? 's' : '' }}
    @if($search !== '') <span class="badge-filtered">(filtered)</span> @endif
</div>
@endif

@endif
