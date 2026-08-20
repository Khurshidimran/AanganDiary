@extends('layouts.app')

@section('title', 'Edit Account')

@section('content')
    <h1 class="h4 mb-3">Edit Account</h1>

    <div class="card shadow-sm" style="max-width: 640px;">
        <div class="card-body">
            <form method="POST" action="{{ route('accounts.update', $account) }}">
                @csrf
                @method('PUT')
                @include('accounts._form', ['account' => $account, 'parents' => $parents])
                <button type="submit" class="btn btn-primary">Update Account</button>
                <a href="{{ route('accounts.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
