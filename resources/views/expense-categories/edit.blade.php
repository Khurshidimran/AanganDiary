@extends('layouts.app')

@section('title', 'Edit Expense Category')

@section('content')
    <h1 class="h4 mb-3">Edit Expense Category</h1>

    <div class="card shadow-sm" style="max-width: 480px;">
        <div class="card-body">
            <form method="POST" action="{{ route('expense-categories.update', $expenseCategory) }}">
                @csrf
                @method('PUT')
                @include('expense-categories._form', ['expenseCategory' => $expenseCategory])
                <button type="submit" class="btn btn-primary">Update Category</button>
                <a href="{{ route('expense-categories.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
