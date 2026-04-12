@php
    $user = auth()->user();
    $ssoIds = [];
    try {
        $ssoIds = $user ? $user->getSsoAllowedOpdIds('kematangan') : [];
    } catch (\Throwable $e) {
        $ssoIds = [];
    }

    $isGlobal = empty($ssoIds);
    $opdNames = [];
    if (!$isGlobal) {
        $opdNames = \Illuminate\Support\Facades\DB::table('users')
            ->whereIn('id', $ssoIds)
            ->pluck('opd_name')
            ->filter()
            ->values()
            ->all();
    }
@endphp

<div class="d-flex align-items-center gap-3">
  @if ($isGlobal)
    <span class="badge bg-success">GLOBAL</span>
  @else
    <span class="badge bg-warning text-dark">Terbatas: {{ implode(', ', array_slice($opdNames, 0, 3)) }}{{ count($opdNames) > 3 ? ', ...' : '' }}</span>
  @endif
</div>
