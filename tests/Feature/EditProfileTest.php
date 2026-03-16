<?php

namespace Tests\Feature;

use App\Filament\Resources\Profiles\Pages\EditProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class EditProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_only_the_name_when_password_fields_are_empty(): void
    {
        $user = User::factory()->create([
            'role_id' => 3,
            'password' => Hash::make('old-password'),
        ]);

        $oldPasswordHash = $user->password;

        $this->actingAs($user);

        Livewire::test(EditProfile::class, ['record' => $user->id])
            ->fillForm([
                'name' => 'Nama Baru',
                'password' => '',
                'password_confirmation' => '',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();

        $this->assertSame('Nama Baru', $user->name);
        $this->assertSame($oldPasswordHash, $user->password);
        $this->assertTrue(Hash::check('old-password', $user->password));
    }

    public function test_it_updates_the_password_when_password_and_confirmation_match(): void
    {
        $user = User::factory()->create([
            'role_id' => 3,
            'password' => Hash::make('old-password'),
        ]);

        $this->actingAs($user);

        Livewire::test(EditProfile::class, ['record' => $user->id])
            ->fillForm([
                'name' => 'Nama Baru',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();

        $this->assertSame('Nama Baru', $user->name);
        $this->assertTrue(Hash::check('new-password', $user->password));
    }

    public function test_it_rejects_password_update_when_confirmation_does_not_match(): void
    {
        $user = User::factory()->create([
            'role_id' => 3,
            'name' => 'Nama Lama',
            'password' => Hash::make('old-password'),
        ]);

        $oldName = $user->name;
        $oldPasswordHash = $user->password;

        $this->actingAs($user);

        Livewire::test(EditProfile::class, ['record' => $user->id])
            ->fillForm([
                'name' => 'Nama Baru',
                'password' => 'new-password',
                'password_confirmation' => 'different-password',
            ])
            ->call('save')
            ->assertHasFormErrors(['password']);

        $user->refresh();

        $this->assertSame($oldName, $user->name);
        $this->assertSame($oldPasswordHash, $user->password);
        $this->assertTrue(Hash::check('old-password', $user->password));
    }
}
