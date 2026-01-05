<nav class="navbar">
    <div class="navbar-inner">
        <!-- Logo / Brand -->
        <a href="{{ url('/quicknote') }}" class="navbar-brand">
            <div class="brand-icon">
                <i class="fas fa-microphone-lines"></i>
            </div>
            <span class="brand-text">QuickNote</span>
        </a>

        <!-- Menu Links -->
        <ul class="navbar-menu">
            <li>
                <!-- Menu Rekam -->
                <a href="{{ url('/') }}" class="nav-link {{ Request::is('quicknote') || Request::is('/') ? 'active' : '' }}">
                    <i class="fas fa-record-vinyl"></i> Rekam
                </a>
            </li>
            <li>
                <!-- Menu Riwayat -->
                <a href="{{ url('/history') }}" class="nav-link {{ Request::is('history') ? 'active' : '' }}">
                    <i class="fas fa-history"></i> Riwayat
                </a>
            </li>
        </ul>
    </div>
</nav>