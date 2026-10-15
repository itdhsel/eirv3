@extends('layouts.eirv3')
@section('title', 'Edit User')
@section('content')
<section class="admin-heading">
    <div><p class="ir-eyebrow">ADMIN MODULE</p><h1>Edit User</h1><p>Update profile details and role assignment.</p></div>
    <a class="ir-btn ir-btn-light" href="{{ route('admin.users.index') }}">Back to users</a>
</section>
<form method="POST" action="{{ route('admin.users.update', $user) }}" class="ir-panel admin-form">
    @csrf
    @method('PUT')
    @if ($errors->any())
        <div class="ir-alert ir-alert-error" role="alert"><strong>Please correct these errors:</strong>
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    <div class="admin-fields">
        <label>Full name <span class="ir-required">*</span>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" maxlength="255" required>
        </label>
        <label>Staff email <span class="ir-required">*</span>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" maxlength="255" required>
        </label>
        <label>Role <span class="ir-required">*</span>
            <select name="role_id" required>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}" @selected((string)old('role_id', $user->role_id) === (string)$role->id)>{{ $role->name }}</option>
                @endforeach
            </select>
        </label>
    </div>
    <p class="admin-help">For safety, you cannot change your own role or remove the final Super Admin.</p>
    <div class="ir-actions"><button type="submit" class="ir-btn ir-btn-orange">Save changes</button></div>
</form>
@endsection
