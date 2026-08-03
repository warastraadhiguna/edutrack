<x-filament-panels::page.simple>
    <div
        class="wan-login-grid"
        style="--wan-login-image: url('{{ asset('wan-edutrack.png') }}')"
    >
        <section class="wan-login-visual" aria-label="WAN Edutrack">
            <div class="wan-login-visual-glow wan-login-visual-glow-one"></div>
            <div class="wan-login-visual-glow wan-login-visual-glow-two"></div>

            <div class="wan-login-visual-badge">
                <span class="wan-login-visual-badge-dot"></span>
                Learning progress, clearly tracked
            </div>

            <div class="wan-login-visual-copy">
                <p class="wan-login-kicker">WELCOME TO</p>
                <h2>WAN Edutrack</h2>
                <p>
                    Kelola aktivitas pembelajaran, pantau perkembangan, dan raih hasil terbaik
                    dalam satu ruang kerja yang terintegrasi.
                </p>
            </div>
        </section>

        <section class="wan-login-form-panel">
            <div class="wan-login-mobile-brand">
                <img src="{{ asset('wan-edutrack.png') }}" alt="WAN Edutrack">
            </div>

            <div class="wan-login-form-heading">
                <span class="wan-login-form-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M7 10V8a5 5 0 0 1 10 0v2M6.5 10h11A1.5 1.5 0 0 1 19 11.5v7A1.5 1.5 0 0 1 17.5 20h-11A1.5 1.5 0 0 1 5 18.5v-7A1.5 1.5 0 0 1 6.5 10Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                        <path d="M12 14v2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                    </svg>
                </span>
                <div>
                    <p class="wan-login-kicker">SECURE ACCESS</p>
                    <h1>Selamat datang kembali</h1>
                </div>
            </div>

            <p class="wan-login-form-description">
                Masukkan akun Anda untuk melanjutkan ke dashboard WAN Edutrack.
            </p>

            <div class="wan-login-form-content">
                {{ $this->content }}
            </div>

            <p class="wan-login-footer">
                &copy; {{ now()->year }} WAN Edutrack. Learning made measurable.
            </p>
        </section>
    </div>
</x-filament-panels::page.simple>
