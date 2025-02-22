<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="sidebar-sticky pt-3 leftside-navigation">
        
        <ul class="nav flex-column">

            <li class="nav-item">
                <a class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}" href="{{ route( 'dashboard' ) }}">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('query-builder') ? 'active' : '' }}" href="{{ route( 'query-builder.index' ) }}">
                    <i class="fa-solid fa-square-plus"></i> Add Queries
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->is('query-report') ? 'active' : '' }}" href="{{ route( 'query-report.index' ) }}">
                    <i class="fa-solid fa-chart-pie"></i> Reports
                </a>
            </li>

        </ul>

    </div>
</nav>
