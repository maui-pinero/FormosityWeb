<aside class="main-sidebar sidebar-dark-primary elevation-4">
<!-- Brand Logo -->
<a href="{{ route('admin.dashboard') }}" class="brand-link">
    <img src="{{ asset('admin-assets/img/admin-logo.png') }}" alt="Admin Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
    <span class="brand-text font-weight-light">Formosity</span>
</a>
<!-- Sidebar -->
<div class="sidebar">
    <!-- Sidebar user (optional) -->
    <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
            <!-- Add icons to the links using the .nav-icon class
                with font-awesome or any other icon font library -->
            <li class="nav-item">
                <a href="{{ route('admin.dashboard') }}" class="nav-link">
                    <i class="nav-icon fas fa-tachometer-alt"></i>
                    <p>Dashboard</p>
                </a>																
            </li>
            <li class="nav-item">
                <a href="{{ route('categories.index') }}" class="nav-link">
                    <i class="nav-icon fas fa-file-alt"></i>
                    <p>Category</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('sub-categories.index') }}" class="nav-link">
                    <i class="nav-icon fas fa-list-ul"></i>
                    <p>Sub Category</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('products.index') }}" class="nav-link">
                    <i class="nav-icon fas fa-tag"></i>
                    <p>Products</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('products.productRatings') }}" class="nav-link">
                    <i class="nav-icon fas fa-star"></i>
                    <p>Ratings</p>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="{{ route('shipping.create') }}" class="nav-link">
                    <!-- <i class="nav-icon fas fa-tag"></i> -->
                    <i class="fas fa-truck nav-icon"></i>
                    <p>Shipping</p>
                </a>
            </li>							
            <li class="nav-item">
                <a href="{{ route('orders.index') }}" class="nav-link">
                    <i class="nav-icon fas fa-shopping-bag"></i>
                    <p>Orders</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('coupons.index') }}" class="nav-link">
                    <i class="nav-icon  fa fa-percent" aria-hidden="true"></i>
                    <p>Discount</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('users.index') }}" class="nav-link">
                    <i class="nav-icon  fas fa-users"></i>
                    <p>Users</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('pages.index') }}" class="nav-link">
                    <i class="nav-icon  fa fa-file"></i>
                    <p>Pages</p>
                </a>
            </li>							
        </ul>
    </nav>
    <!-- /.sidebar-menu -->
</div>
<!-- /.sidebar -->
</aside>