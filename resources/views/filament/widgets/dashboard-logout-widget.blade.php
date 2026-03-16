<x-filament-widgets::widget>
    <x-filament::section class="dashboard-logout-widget-card">
        <div class="dashboard-logout-widget">
            <div class="dashboard-logout-widget-main">
                <div class="dashboard-logout-widget-icon-wrap">
                    <svg
                        class="dashboard-logout-widget-icon"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6A2.25 2.25 0 0 0 5.25 5.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"
                        />
                    </svg>
                </div>

                <div>
                <h3 class="dashboard-logout-widget-heading">Logout</h3>
                <p class="dashboard-logout-widget-description">
                    Keluar dari dashboard untuk mengakhiri sesi Anda.
                </p>
                </div>
            </div>

            <form action="{{ filament()->getLogoutUrl() }}" method="post">
                @csrf

                <x-filament::button
                    color="danger"
                    :icon="\Filament\Support\Icons\Heroicon::ArrowLeftOnRectangle"
                    tag="button"
                    type="submit"
                >
                    Log out
                </x-filament::button>
            </form>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
