@extends('layouts.eirv3')
@section('title', 'User Management')
@section('content')

<div class="dash-workspace-header">
    <div class="dash-workspace-title">
        <p class="dash-eyebrow" style="color: var(--orange); margin-bottom: 8px; display: block;">ADMIN MODULE</p>
        <h1>User Management</h1>
        <p>Register staff and assign their eIRv3 roles.</p>
    </div>
    <div style="display: flex; gap: 12px;">
        <!-- Assuming your route is admin.users.create based on standard Laravel conventions -->
        <a href="{{ route('admin.users.create') }}" class="ir-btn ir-btn-orange" style="display: flex; align-items: center; gap: 8px;">
            <span aria-hidden="true">＋</span> Register User
        </a>
    </div>
</div>

@if(session('success'))
    <div class="ir-alert ir-alert-success" style="margin-bottom: 24px;">{{ session('success') }}</div>
@endif

<div class="dash-table-section">
    <div style="overflow-x: auto;">
        <table class="dash-ui-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Role</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>
                            <span style="font-weight: 700; color: var(--navy);">{{ $user->name }}</span>
                        </td>
                        <td>
                            <span style="color: var(--muted);">{{ $user->jabatan ?? '-' }}</span>
                        </td>
                        <td>
                            @php
                                // Dynamically assign badge colors based on role
                                $roleClass = 'dash-badge-gray'; // Default
                                $roleName = $user->role->name ?? 'Not assigned';
                                
                                if (stripos($roleName, 'Super') !== false) {
                                    $roleClass = 'dash-badge-approved'; // Greenish
                                } elseif (stripos($roleName, 'Quality') !== false || stripos($roleName, 'Reviewer') !== false) {
                                    $roleClass = 'dash-badge-submitted'; // Yellowish
                                } elseif (stripos($roleName, 'Head') !== false) {
                                    $roleClass = 'dash-badge-returned'; // Reddish/Orange
                                }
                            @endphp
                            <span class="dash-badge {{ $roleClass }}">{{ $roleName }}</span>
                        </td>
                        <td style="text-align: right;">
                            <a href="{{ route('admin.users.edit', $user) }}" class="dash-action-btn-outline">
                                <span aria-hidden="true">✎</span> Edit user
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 40px; color: var(--muted);">
                            No users found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if(method_exists($users, 'links') && $users->hasPages())
        <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border);">
            {{ $users->links() }}
        </div>
    @endif
</div>
@endsection