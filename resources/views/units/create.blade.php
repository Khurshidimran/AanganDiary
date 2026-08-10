@extends('layouts.app')

@section('title', 'New Unit')

@section('content')
    <h1 class="h4 mb-3">New Unit</h1>

    <div class="card shadow-sm" style="max-width: 560px;">
        <div class="card-body">
            <form method="POST" action="{{ route('units.store') }}">
                @csrf
                @include('units._form', ['unit' => null])
                <button type="submit" class="btn btn-primary">Create Unit</button>
                <a href="{{ route('units.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
