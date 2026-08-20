@extends('layouts.app')

@section('title', 'New Expense')

@section('content')
    <h1 class="h4 mb-3">New Expense</h1>

    <div class="card shadow-sm" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('expenses.store') }}">
                @csrf
                @include('expenses._form', ['expense' => null, 'categories' => $categories, 'warehouses' => $warehouses])
                <button type="submit" class="btn btn-primary">Record Expense</button>
                <a href="{{ route('expenses.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
