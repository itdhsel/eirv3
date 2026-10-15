@extends('layouts.eirv3')
@section('title', 'Register User')
@section('content')
<section class="admin-heading">
    <div><p class="ir-eyebrow">ADMIN MODULE</p><h1>Register User</h1><p>Create an individual account for an authorized staff member.</p></div>
    <a class="ir-btn ir-btn-light" href="{{ route('admin.users.index') }}">Back to users</a>
</section>
<form method="POST" action="{{ route('admin.users.store') }}" class="ir-panel admin-form">
    @csrf
    @if ($errors->any())
        <div class="ir-alert ir-alert-error" role="alert"><strong>Please correct these errors:</strong>
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    <div class="admin-fields">
        <label>Full name <span class="ir-required">*</span>
            <input type="text" name="name" value="{{ old('name') }}" maxlength="255" required autocomplete="name">
        </label>
        <label>Staff email <span class="ir-required">*</span>
            <input type="email" name="email" value="{{ old('email') }}" maxlength="255" required autocomplete="email">
        </label>
        <label>Role <span class="ir-required">*</span>
            <select name="role_id" required>
                <option value="">Select a role</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}" @selected((string)old('role_id') === (string)$role->id)>{{ $role->name }}</option>
                @endforeach
            </select>
        </label>
        <label>Temporary password <span class="ir-required">*</span>
            <input type="password" name="password" minlength="12" required autocomplete="new-password">
        </label>
        <label>Confirm password <span class="ir-required">*</span>
            <input type="password" name="password_confirmation" minlength="12" required autocomplete="new-password">
        </label>
    </div>
    <div class="ir-actions"><button type="submit" class="ir-btn ir-btn-orange">Create account</button></div>
</form>
@endsection
