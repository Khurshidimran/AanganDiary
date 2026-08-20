@extends('layouts.app')

@section('title', 'New Expense Category')

@section('content')
    <h1 class="h4 mb-3">New Expense Category</h1>

    <div class="card shadow-sm" style="max-width: 480px;">
        <div class="card-body">
            <form method="POST" action="{{ route('expense-categories.store') }}">
                @csrf
                @include('expense-categories._form', ['expenseCategory' => null])
                <button type="submit" class="btn btn-primary">Create Category</button>
                <a href="{{ route('expense-categories.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
