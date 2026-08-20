@extends('layouts.app')

@section('title', 'Edit Expense')

@section('content')
    <h1 class="h4 mb-3">Edit Expense</h1>

    <div class="card shadow-sm" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('expenses.update', $expense) }}">
                @csrf
                @method('PUT')
                @include('expenses._form', ['expense' => $expense, 'categories' => $categories, 'warehouses' => $warehouses])
                <button type="submit" class="btn btn-primary">Update Expense</button>
                <a href="{{ route('expenses.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
