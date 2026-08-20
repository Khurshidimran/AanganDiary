@extends('layouts.app')

@section('title', 'New Channel')

@section('content')
    <h1 class="h4 mb-3">New Channel</h1>

    <div class="card shadow-sm" style="max-width: 480px;">
        <div class="card-body">
            <form method="POST" action="{{ route('channels.store') }}">
                @csrf
                @include('channels._form', ['channel' => null])
                <button type="submit" class="btn btn-primary">Create Channel</button>
                <a href="{{ route('channels.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
