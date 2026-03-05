@extends('admin.layouts.app')

@section('title', 'Détails du log')

@section('content')
<div class="mb-6">
    <h2 class="text-xl font-semibold text-gray-900">Détail du log</h2>
    <p class="text-sm text-gray-500">Informations sur l'action administrateur.</p>
</div>

<div class="bg-white rounded shadow p-6 space-y-4">
    <div class="flex items-center justify-between">
        <span class="text-sm text-gray-500">ID</span>
        <span class="font-semibold text-gray-900">#{{ $adminLog->id }}</span>
    </div>
    <div class="flex items-center justify-between">
        <span class="text-sm text-gray-500">Admin</span>
        <span class="font-semibold text-gray-900">{{ optional($adminLog->admin)->name ?? '—' }}</span>
    </div>
    <div class="flex items-center justify-between">
        <span class="text-sm text-gray-500">Action</span>
        <span class="font-semibold text-gray-900">{{ $adminLog->action }}</span>
    </div>
    <div class="flex items-center justify-between">
        <span class="text-sm text-gray-500">Date</span>
        <span class="font-semibold text-gray-900">{{ $adminLog->created_at->format('d/m/Y H:i') }}</span>
    </div>
</div>

<div class="mt-6">
    <a href="{{ route('admin.logs.index') }}" class="text-yellow-600 hover:text-yellow-700 font-semibold">
        ← Retour aux logs
    </a>
</div>
@endsection
