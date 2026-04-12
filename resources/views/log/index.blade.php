@extends('layouts.dashboard')

@section('content')
<div class="container mx-auto py-4">
    <h2 class="text-2xl font-semibold mb-6">Log Aktivitas Pengguna</h2>

    {{-- Filter --}}
    <form method="GET" action="{{ route('log.index') }}" class="flex flex-wrap items-end gap-4 bg-white p-4 rounded shadow-sm mb-6">

            <label for="user_id" class="block text-sm font-medium">User</label>
            <select name="user_id" id="user_id" class="border rounded px-2 py-1">
                <option value="">Semua</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                        {{ $user->name }}
                    </option>
                @endforeach
            </select>



            <label for="role" class="block text-sm font-medium">Role</label>
            <select name="role" id="role" class="border rounded px-2 py-1">
                <option value="">Semua</option>
                @foreach ($roles as $role)
                    <option value="{{ $role }}" {{ request('role') == $role ? 'selected' : '' }}>{{ ucfirst($role) }}</option>
                @endforeach
            </select>



            <label for="start_date" class="block text-sm font-medium">Tanggal </label>
            <input type="date" name="start_date" id="start_date" class="border rounded px-2 py-1" value="{{ request('start_date') }}">
            <input type="date" name="end_date" id="end_date" class="border rounded px-2 py-1" value="{{ request('end_date') }}">

            <button type="submit" class="btn btn-sm btn-danger">Filter</button>
            <a href="{{ route('log.index') }}" class="btn btn-sm btn-warning">Reset</a>
    </form>

    {{-- Tabel --}}
    <div class="overflow-x-auto bg-white rounded shadow">
        <table class="table-fixed w-full border-collapse text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-2 py-1 w-32">Waktu</th>
                    <th class="border px-2 py-1 w-40">User</th>
                    <th class="border px-2 py-1 w-28">Role</th>
                    <th class="border px-2 py-1 w-28">Aksi</th>
                    <th class="border px-2 py-1 w-48">Keterangan</th>
                    <th class="border px-2 py-1 w-64">URL</th>
                    <th class="border px-2 py-1 w-32">IP</th>
                    <th class="border px-2 py-1 w-20">Method</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                <tr class="hover:bg-gray-50">
                    <td class="border px-2 py-1 align-top">{{ $log->created_at ? $log->created_at->timezone('Asia/Jakarta')->format('d-m-Y H:i') . ' WIB' : '-' }}</td>
                    <td class="border px-2 py-1 align-top">{{ $log->user->name ?? 'N/A' }}</td>
                    <td class="border px-2 py-1 align-top">{{ $log->role ?? '-' }}</td>
                    <td class="border px-2 py-1 align-top">{{ $log->aksi ?? '-' }}</td>
                    <td class="border px-2 py-1 align-top">{{ $log->keterangan ?? '-' }}</td>
                    <td class="border px-2 py-1 align-top break-words text-xs">{{ $log->url ?? '-' }}</td>
                    <td class="border px-2 py-1 align-top">{{ $log->ip_address ?? '-' }}</td>
                    <td class="border px-2 py-1 align-top">{{ $log->method ?? '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center p-4 text-gray-500">Tidak ada data log ditemukan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>


</div>
@endsection
