<?php

namespace Tests\Feature;

use Filament\Facades\Filament;
use Tests\TestCase;

class BrandingTest extends TestCase
{
    public function test_admin_panel_uses_wan_edutrack_branding(): void
    {
        $panel = Filament::getPanel('admin');

        $this->assertSame('WAN Edutrack', $panel->getBrandName());
        $this->assertStringEndsWith('/wan-edutrack.png', $panel->getFavicon());
        $this->assertStringContainsString('wan-edutrack.png', $panel->getBrandLogo()->render());
    }

    public function test_login_page_displays_the_custom_branding(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('fi-width-5xl', escape: false)
            ->assertSee('WAN Edutrack')
            ->assertSee('wan-edutrack.png', escape: false)
            ->assertSee('Selamat datang kembali');
    }
}
