<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('images/logo.png') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/admin-theme.css') }}">
    @stack('styles')
</head>
<body>
    <div class="d-flex">
        <nav id="sidebar" class="d-flex flex-column p-3">
            <a href="{{ route('dashboard') }}" class="brand text-decoration-none fs-5 fw-bold mb-4">
                <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}">
                {{ config('app.name') }}
            </a>
            <ul class="nav nav-pills flex-column gap-1">
                <li class="nav-item">
                    <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                </li>

                @canany(['categories.view', 'brands.view', 'units.view', 'products.view'])
                    <li class="nav-section-title">Catalog</li>
                    @can('categories.view')
                        <li class="nav-item">
                            <a href="{{ route('categories.index') }}" class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                                <i class="bi bi-diagram-3 me-2"></i> Categories
                            </a>
                        </li>
                    @endcan
                    @can('brands.view')
                        <li class="nav-item">
                            <a href="{{ route('brands.index') }}" class="nav-link {{ request()->routeIs('brands.*') ? 'active' : '' }}">
                                <i class="bi bi-tags me-2"></i> Brands
                            </a>
                        </li>
                    @endcan
                    @can('units.view')
                        <li class="nav-item">
                            <a href="{{ route('units.index') }}" class="nav-link {{ request()->routeIs('units.*') ? 'active' : '' }}">
                                <i class="bi bi-rulers me-2"></i> Units of Measure
                            </a>
                        </li>
                    @endcan
                    @can('products.view')
                        <li class="nav-item">
                            <a href="{{ route('products.index') }}" class="nav-link {{ request()->routeIs('products.index') || request()->routeIs('products.create') || request()->routeIs('products.edit') ? 'active' : '' }}">
                                <i class="bi bi-box-seam me-2"></i> Products
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('products.report') }}" class="nav-link {{ request()->routeIs('products.report') ? 'active' : '' }}">
                                <i class="bi bi-file-earmark-bar-graph me-2"></i> Product Report
                            </a>
                        </li>
                    @endcan
                @endcanany

                @can('orders.view')
                    <li class="nav-section-title">Orders</li>
                    <li class="nav-item">
                        <a href="{{ route('orders.index') }}" class="nav-link {{ request()->routeIs('orders.*') ? 'active' : '' }}">
                            <i class="bi bi-bag-check me-2"></i> Orders
                        </a>
                    </li>
                @endcan

                @canany(['dispatch.view', 'riders.view'])
                    <li class="nav-section-title">Dispatch</li>
                    @can('dispatch.view')
                        <li class="nav-item">
                            <a href="{{ route('dispatch.index') }}" class="nav-link {{ request()->routeIs('dispatch.*') ? 'active' : '' }}">
                                <i class="bi bi-signpost-split me-2"></i> Dispatch Board
                            </a>
                        </li>
                    @endcan
                    @can('riders.view')
                        <li class="nav-item">
                            <a href="{{ route('riders.index') }}" class="nav-link {{ request()->routeIs('riders.*') ? 'active' : '' }}">
                                <i class="bi bi-bicycle me-2"></i> Riders
                            </a>
                        </li>
                    @endcan
                @endcanany

                @canany(['vendors.view', 'purchase_orders.view', 'purchase_receipts.view'])
                    <li class="nav-section-title">Purchasing</li>
                    @can('vendors.view')
                        <li class="nav-item">
                            <a href="{{ route('vendors.index') }}" class="nav-link {{ request()->routeIs('vendors.*') ? 'active' : '' }}">
                                <i class="bi bi-truck me-2"></i> Vendors
                            </a>
                        </li>
                    @endcan
                    @can('purchase_orders.view')
                        <li class="nav-item">
                            <a href="{{ route('purchase-orders.index') }}" class="nav-link {{ request()->routeIs('purchase-orders.*') ? 'active' : '' }}">
                                <i class="bi bi-file-earmark-text me-2"></i> Purchase Orders
                            </a>
                        </li>
                    @endcan
                    @can('purchase_receipts.view')
                        <li class="nav-item">
                            <a href="{{ route('purchase-receipts.index') }}" class="nav-link {{ request()->routeIs('purchase-receipts.*') ? 'active' : '' }}">
                                <i class="bi bi-box-arrow-in-down me-2"></i> Goods Receipts
                            </a>
                        </li>
                    @endcan
                @endcanany

                @canany(['warehouses.view', 'stock.view', 'stock_transfers.view', 'stock_adjustments.view'])
                    <li class="nav-section-title">Inventory</li>
                    @can('warehouses.view')
                        <li class="nav-item">
                            <a href="{{ route('warehouses.index') }}" class="nav-link {{ request()->routeIs('warehouses.*') ? 'active' : '' }}">
                                <i class="bi bi-building me-2"></i> Warehouses
                            </a>
                        </li>
                    @endcan
                    @can('stock.view')
                        <li class="nav-item">
                            <a href="{{ route('stock-balances.index') }}" class="nav-link {{ request()->routeIs('stock-balances.*') ? 'active' : '' }}">
                                <i class="bi bi-clipboard-data me-2"></i> Stock Balances
                            </a>
                        </li>
                    @endcan
                    @can('stock_transfers.view')
                        <li class="nav-item">
                            <a href="{{ route('stock-transfers.index') }}" class="nav-link {{ request()->routeIs('stock-transfers.*') ? 'active' : '' }}">
                                <i class="bi bi-arrow-left-right me-2"></i> Stock Transfers
                            </a>
                        </li>
                    @endcan
                    @can('stock_adjustments.view')
                        <li class="nav-item">
                            <a href="{{ route('stock-adjustments.index') }}" class="nav-link {{ request()->routeIs('stock-adjustments.*') ? 'active' : '' }}">
                                <i class="bi bi-sliders me-2"></i> Stock Adjustments
                            </a>
                        </li>
                    @endcan
                @endcanany

                @can('shopify.view')
                    <li class="nav-section-title">Integrations</li>
                    <li class="nav-item">
                        <a href="{{ route('shopify.index') }}" class="nav-link {{ request()->routeIs('shopify.*') ? 'active' : '' }}">
                            <i class="bi bi-shop me-2"></i> Shopify
                        </a>
                    </li>
                @endcan

                @canany(['users.view', 'roles.view', 'settings.view'])
                    <li class="nav-section-title">Administration</li>
                    @can('users.view')
                        <li class="nav-item">
                            <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                                <i class="bi bi-people me-2"></i> Users
                            </a>
                        </li>
                    @endcan
                    @can('roles.view')
                        <li class="nav-item">
                            <a href="{{ route('roles.index') }}" class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}">
                                <i class="bi bi-shield-lock me-2"></i> Roles
                            </a>
                        </li>
                    @endcan
                    @can('settings.view')
                        <li class="nav-item">
                            <a href="{{ route('settings.edit') }}" class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                                <i class="bi bi-gear me-2"></i> Settings
                            </a>
                        </li>
                    @endcan
                @endcanany
            </ul>
        </nav>

        <main class="d-flex flex-column">
            <nav class="navbar topbar px-3">
                <span class="navbar-text fw-semibold text-dark">@yield('title', 'Dashboard')</span>

                <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i> {{ auth()->user()->name }}
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ route('password.edit') }}">Change Password</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item">Logout</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </nav>

            <div class="p-4">
                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
