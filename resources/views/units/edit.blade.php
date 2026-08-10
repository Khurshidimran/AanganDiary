@extends('layouts.app')

@section('title', 'Edit Unit')

@section('content')
    <h1 class="h4 mb-3">Edit Unit</h1>

    <div class="card shadow-sm" style="max-width: 560px;">
        <div class="card-body">
            <form method="POST" action="{{ route('units.update', $unit) }}">
                @csrf
                @method('PUT')
                @include('units._form', ['unit' => $unit])
                <button type="submit" class="btn btn-primary">Update Unit</button>
                <a href="{{ route('units.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
