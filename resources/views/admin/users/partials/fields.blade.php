{{-- Shared account fields. Expects $roles, and optionally $user when editing. --}}
@php $user = $user ?? null; @endphp

<div>
    <label for="name" class="label">Name</label>
    <input id="name" type="text" name="name" value="{{ old('name', $user->name ?? '') }}" class="input" required
        autofocus autocomplete="off" />
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>

<div>
    <label for="email" class="label">Email</label>
    <input id="email" type="email" name="email" value="{{ old('email', $user->email ?? '') }}" class="input" required
        autocomplete="off" />
    <x-input-error :messages="$errors->get('email')" class="mt-2" />
</div>

<div>
    <label for="role" class="label">Role</label>
    @if ($user && $user->is(Auth::user()))
        {{-- Shown read-only; the hidden input inside still sends the unchanged role. --}}
        <x-select id="role" name="role" :options="$roles" :selected="$user->role" disabled />
        <p class="mt-2 text-xs text-slate-500">You cannot change your own role.</p>
    @else
        <x-select id="role" name="role" :options="$roles" :selected="old('role', $user->role ?? 'qa_engineer')" />
        <p class="mt-2 text-xs text-slate-500">
            QA engineers run analyses and maintain the registry. Developers can only view. Admins can also manage users.
        </p>
    @endif
    <x-input-error :messages="$errors->get('role')" class="mt-2" />
</div>
