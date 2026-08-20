@extends('layouts.app')

@section('title', 'Edit Channel')

@section('content')
    <h1 class="h4 mb-3">Edit Channel</h1>

    <div class="card shadow-sm" style="max-width: 480px;">
        <div class="card-body">
            <form method="POST" action="{{ route('channels.update', $channel) }}">
                @csrf
                @method('PUT')
                @include('channels._form', ['channel' => $channel])
                <button type="submit" class="btn btn-primary">Update Channel</button>
                <a href="{{ route('channels.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
