<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use App\Services\QuickUserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

class QuickUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_new_users_and_skips_existing_emails(): void
    {
        $creator = User::query()->findOrFail(1);
        $existingUser = User::factory()->create([
            'role_id' => 2,
            'name' => 'Existing Name',
            'email' => '562024003@student.uksw.edu',
        ]);

        $result = app(QuickUserService::class)->create(
            "562024004@student.uksw.edu Andy Setya Novanto\n"
            ."562024003@student.uksw.edu Replacement Name\n"
            .'562024010@student.uksw.edu Agus Hari Mukti',
            $creator->id,
        );

        $this->assertSame(['created' => 2, 'skipped' => 1], $result);
        $this->assertSame('Existing Name', $existingUser->fresh()->name);
        $this->assertSame(2, $existingUser->fresh()->role_id);

        $newUser = User::query()->where('email', '562024004@student.uksw.edu')->firstOrFail();

        $this->assertSame('Andy Setya Novanto', $newUser->name);
        $this->assertSame('user', $newUser->role->name);
        $this->assertSame($creator->id, $newUser->user_id);
        $this->assertTrue(Hash::check('1', $newUser->password));
    }

    public function test_invalid_line_cancels_the_whole_batch(): void
    {
        $creator = User::query()->findOrFail(1);

        try {
            app(QuickUserService::class)->create(
                "valid@student.uksw.edu Valid User\ninvalid-line-without-name",
                $creator->id,
            );

            $this->fail('Invalid input should prevent user creation.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('line(s): 2', $exception->getMessage());
        }

        $this->assertDatabaseMissing('users', ['email' => 'valid@student.uksw.edu']);
    }

    public function test_superadmin_can_submit_the_quick_user_modal(): void
    {
        $creator = User::query()->findOrFail(1);
        $this->actingAs($creator);

        Livewire::test(ListUsers::class)
            ->assertActionExists('quickUserEntry')
            ->callAction('quickUserEntry', [
                'users' => '562024099@student.uksw.edu Modal User',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => '562024099@student.uksw.edu',
            'name' => 'Modal User',
            'role_id' => 3,
            'user_id' => $creator->id,
        ]);
    }
}
