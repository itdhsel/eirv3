@extends('layouts.eirv3')
@section('title', 'User Management')
@section('content')
<section class="admin-heading">
    <div>
        <p class="ir-eyebrow">ADMIN MODULE</p>
        <h1>User Management</h1>
        <p>Register staff and assign their eIRv3 roles.</p>
    </div>
    <a class="ir-btn ir-btn-orange" href="{{ route('admin.users.create') }}">+ Register User</a>
</section>

@if (session('status'))
    <div class="ir-alert ir-alert-success" role="status">{{ session('status') }}</div>
@endif

<section class="ir-panel">
    <div class="ir-table-wrap">
        <table class="ir-table">
            <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Action</th></tr></thead>
            <tbody>
            @forelse ($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td><span class="admin-role-pill">{{ $roleNames[$user->role_id] ?? 'Not assigned' }}</span></td>
                    <td><a href="{{ route('admin.users.edit', $user) }}">Edit user</a></td>
                </tr>
            @empty
                <tr><td colspan="4">No users registered yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <nav class="admin-pagination" aria-label="User pages">
        <span>Page {{ $users->currentPage() }} of {{ $users->lastPage() }}</span>
        @if ($users->onFirstPage())
            <span class="admin-page-disabled">Previous</span>
        @else
            <a href="{{ $users->previousPageUrl() }}">Previous</a>
        @endif
        @if ($users->hasMorePages())
            <a href="{{ $users->nextPageUrl() }}">Next</a>
        @else
            <span class="admin-page-disabled">Next</span>
        @endif
    </nav>
</section>
@endsection
