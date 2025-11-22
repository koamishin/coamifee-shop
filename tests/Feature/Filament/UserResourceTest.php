<?php

declare(strict_types=1);

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

beforeEach(function () {
    $superAdminRole = Role::create(['name' => 'super_admin']);

    // Create necessary permissions for User resource
    $permissions = [
        'ViewAny:User',
        'View:User',
        'Create:User',
        'Update:User',
        'Delete:User',
    ];

    foreach ($permissions as $permission) {
        Permission::create(['name' => $permission]);
    }

    $superAdminRole->givePermissionTo($permissions);

    $this->superAdmin = User::factory()->create();
    $this->superAdmin->assignRole($superAdminRole);

    actingAs($this->superAdmin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

describe('User List Page', function () {
    it('can render list users page', function () {
        Livewire::test(ListUsers::class)
            ->assertSuccessful();
    });

    it('can list users', function () {
        $users = User::factory()->count(5)->create();

        Livewire::test(ListUsers::class)
            ->assertCanSeeTableRecords($users);
    });

    it('can search users by name', function () {
        $users = User::factory()->count(5)->create();
        $searchUser = $users->first();

        Livewire::test(ListUsers::class)
            ->searchTable($searchUser->name)
            ->assertCanSeeTableRecords([$searchUser])
            ->assertCanNotSeeTableRecords($users->skip(1));
    });

    it('can search users by email', function () {
        $users = User::factory()->count(5)->create();
        $searchUser = $users->last();

        Livewire::test(ListUsers::class)
            ->searchTable($searchUser->email)
            ->assertCanSeeTableRecords([$searchUser])
            ->assertCanNotSeeTableRecords($users->take($users->count() - 1));
    });

    it('can filter users by role', function () {
        $role = Role::create(['name' => 'manager']);

        $userWithRole = User::factory()->create();
        $userWithRole->assignRole($role);

        $userWithoutRole = User::factory()->create();

        Livewire::test(ListUsers::class)
            ->filterTable('roles', $role->id)
            ->assertCanSeeTableRecords([$userWithRole])
            ->assertCanNotSeeTableRecords([$userWithoutRole]);
    });

    it('can filter users by email verification status', function () {
        $verifiedUser = User::factory()->create(['email_verified_at' => now()]);
        $unverifiedUser = User::factory()->create(['email_verified_at' => null]);

        Livewire::test(ListUsers::class)
            ->filterTable('email_verified_at', true)
            ->assertCanSeeTableRecords([$verifiedUser])
            ->assertCanNotSeeTableRecords([$unverifiedUser]);
    });

    it('can filter users by 2FA status', function () {
        $userWith2FA = User::factory()->create(['two_factor_confirmed_at' => now()]);
        $userWithout2FA = User::factory()->create(['two_factor_confirmed_at' => null]);

        Livewire::test(ListUsers::class)
            ->filterTable('two_factor_confirmed_at', true)
            ->assertCanSeeTableRecords([$userWith2FA])
            ->assertCanNotSeeTableRecords([$userWithout2FA]);
    });

    it('can sort users by name', function () {
        User::factory()->create(['name' => 'Zebra User']);
        User::factory()->create(['name' => 'Alpha User']);

        Livewire::test(ListUsers::class)
            ->sortTable('name')
            ->assertCanSeeTableRecords(User::query()->orderBy('name')->get(), inOrder: true);
    });
});

describe('User Create Page', function () {
    it('can render create user page', function () {
        Livewire::test(CreateUser::class)
            ->assertSuccessful();
    });

    it('can create user with basic information', function () {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        Livewire::test(CreateUser::class)
            ->fillForm($userData)
            ->call('create')
            ->assertHasNoErrors();

        assertDatabaseHas(User::class, [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $user = User::where('email', 'test@example.com')->first();
        expect(Hash::check('password123', $user->password))->toBeTrue();
    });

    it('can create user with roles', function () {
        $role = Role::create(['name' => 'editor']);

        $userData = [
            'name' => 'Editor User',
            'email' => 'editor@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'roles' => [$role->id],
        ];

        Livewire::test(CreateUser::class)
            ->fillForm($userData)
            ->call('create')
            ->assertHasNoErrors();

        $user = User::where('email', 'editor@example.com')->first();
        expect($user->hasRole('editor'))->toBeTrue();
    });

    it('can create user with direct permissions', function () {
        $permission = Permission::create(['name' => 'edit posts']);

        $userData = [
            'name' => 'Permission User',
            'email' => 'permission@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'permissions' => [$permission->id],
        ];

        Livewire::test(CreateUser::class)
            ->fillForm($userData)
            ->call('create')
            ->assertHasNoErrors();

        $user = User::where('email', 'permission@example.com')->first();
        expect($user->hasPermissionTo('edit posts'))->toBeTrue();
    });

    it('can create user with verified email', function () {
        $userData = [
            'name' => 'Verified User',
            'email' => 'verified@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'email_verified' => true,
        ];

        Livewire::test(CreateUser::class)
            ->fillForm($userData)
            ->call('create')
            ->assertHasNoErrors();

        $user = User::where('email', 'verified@example.com')->first();
        expect($user->email_verified_at)->not->toBeNull();
    });

    it('validates required fields', function () {
        Livewire::test(CreateUser::class)
            ->assertOk()
            ->fillForm([
                'name' => '',
                'email' => '',
                'password' => '',
            ])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required', 'email' => 'required', 'password' => 'required']);
    });

    it('validates email format', function () {
        Livewire::test(CreateUser::class)
            ->assertOk()
            ->fillForm([
                'name' => 'Test User',
                'email' => 'invalid-email',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->call('create')
            ->assertHasFormErrors(['email' => 'email']);
    });

    it('validates unique email', function () {
        $existingUser = User::factory()->create(['email' => 'existing@example.com']);

        Livewire::test(CreateUser::class)
            ->assertOk()
            ->fillForm([
                'name' => 'New User',
                'email' => 'existing@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->call('create')
            ->assertHasFormErrors(['email' => 'unique']);
    });

    it('validates password confirmation', function () {
        Livewire::test(CreateUser::class)
            ->assertOk()
            ->fillForm([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'different-password',
            ])
            ->call('create')
            ->assertHasFormErrors(['password_confirmation' => 'same']);
    });
});

describe('User Edit Page', function () {
    it('can render edit user page', function () {
        $user = User::factory()->create();

        Livewire::test(EditUser::class, ['record' => $user->id])
            ->assertSuccessful();
    });

    it('can retrieve user data', function () {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);

        Livewire::test(EditUser::class, ['record' => $user->id])
            ->assertFormSet([
                'name' => 'Original Name',
                'email' => 'original@example.com',
            ]);
    });

    it('can update user basic information', function () {
        $user = User::factory()->create();

        Livewire::test(EditUser::class, ['record' => $user->id])
            ->fillForm([
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
            ])
            ->call('save')
            ->assertHasNoErrors();

        assertDatabaseHas(User::class, [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);
    });

    it('can update user password', function () {
        $user = User::factory()->create();

        Livewire::test(EditUser::class, ['record' => $user->id])
            ->fillForm([
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ])
            ->call('save')
            ->assertHasNoErrors();

        $user->refresh();
        expect(Hash::check('newpassword123', $user->password))->toBeTrue();
    });

    it('can update user roles', function () {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'moderator']);

        Livewire::test(EditUser::class, ['record' => $user->id])
            ->fillForm(['roles' => [$role->id]])
            ->call('save')
            ->assertHasNoErrors();

        $user->refresh();
        expect($user->hasRole('moderator'))->toBeTrue();
    });

    it('can update user permissions', function () {
        $user = User::factory()->create();
        $permission = Permission::create(['name' => 'delete posts']);

        Livewire::test(EditUser::class, ['record' => $user->id])
            ->fillForm(['permissions' => [$permission->id]])
            ->call('save')
            ->assertHasNoErrors();

        $user->refresh();
        expect($user->hasPermissionTo('delete posts'))->toBeTrue();
    });

    it('can verify user email', function () {
        $user = User::factory()->create(['email_verified_at' => null]);

        Livewire::test(EditUser::class, ['record' => $user->id])
            ->fillForm(['email_verified' => true])
            ->call('save')
            ->assertHasNoErrors();

        $user->refresh();
        expect($user->email_verified_at)->not->toBeNull();
    });

    it('can unverify user email', function () {
        $user = User::factory()->create(['email_verified_at' => now()]);

        Livewire::test(EditUser::class, ['record' => $user->id])
            ->fillForm(['email_verified' => false])
            ->call('save')
            ->assertHasNoErrors();

        $user->refresh();
        expect($user->email_verified_at)->toBeNull();
    });

    it('does not change password when field is empty', function () {
        $originalPassword = 'originalpassword';
        $user = User::factory()->create(['password' => Hash::make($originalPassword)]);

        Livewire::test(EditUser::class, ['record' => $user->id])
            ->fillForm([
                'name' => 'Updated Name',
                'password' => '',
                'password_confirmation' => '',
            ])
            ->call('save')
            ->assertHasNoErrors();

        $user->refresh();
        expect(Hash::check($originalPassword, $user->password))->toBeTrue();
    });
});

describe('User View Page', function () {
    it('can render view user page', function () {
        $user = User::factory()->create();

        Livewire::test(ViewUser::class, ['record' => $user->id])
            ->assertSuccessful();
    });

    it('can display user information', function () {
        $user = User::factory()->create([
            'name' => 'View Test User',
            'email' => 'viewtest@example.com',
            'email_verified_at' => now(),
        ]);

        Livewire::test(ViewUser::class, ['record' => $user->id])
            ->assertSee('View Test User')
            ->assertSee('viewtest@example.com');
    });

    it('can display user roles', function () {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'viewer']);
        $user->assignRole($role);

        Livewire::test(ViewUser::class, ['record' => $user->id])
            ->assertSee('viewer');
    });

    it('can display user permissions', function () {
        $user = User::factory()->create();
        $permission = Permission::create(['name' => 'view reports']);
        $role = Role::create(['name' => 'report_viewer']);
        $role->givePermissionTo($permission);
        $user->assignRole($role);

        Livewire::test(ViewUser::class, ['record' => $user->id])
            ->assertSee('report_viewer');
    });
});

describe('User Table Actions', function () {
    it('can verify email via table action', function () {
        $user = User::factory()->create(['email_verified_at' => null]);

        Livewire::test(ListUsers::class)
            ->callAction(TestAction::make('verify_email')->table($user));

        expect($user->refresh()->email_verified_at)->not->toBeNull();
    });

    it('can unverify email via table action', function () {
        $user = User::factory()->create(['email_verified_at' => now()]);

        Livewire::test(ListUsers::class)
            ->callAction(TestAction::make('unverify_email')->table($user));

        expect($user->refresh()->email_verified_at)->toBeNull();
    });

    it('can reset password via table action', function () {
        $user = User::factory()->create();

        Livewire::test(ListUsers::class)
            ->callAction(TestAction::make('reset_password')->table($user), [
                'new_password' => 'resetpassword123',
                'new_password_confirmation' => 'resetpassword123',
            ]);

        $user->refresh();
        expect(Hash::check('resetpassword123', $user->password))->toBeTrue();
    });

    it('can disable 2FA via table action', function () {
        $user = User::factory()->create([
            'two_factor_secret' => 'secret',
            'two_factor_recovery_codes' => 'codes',
            'two_factor_confirmed_at' => now(),
        ]);

        Livewire::test(ListUsers::class)
            ->callAction(TestAction::make('disable_2fa')->table($user));

        $user->refresh();
        // Disable 2FA action might set to empty string instead of null
        // Check if it's null in the database before casting
        $rawUser = Illuminate\Support\Facades\DB::table('users')->where('id', $user->id)->first();
        expect($rawUser->two_factor_confirmed_at)->toBeNull();
        expect($user->two_factor_secret)->toBeNull();
        expect($user->two_factor_recovery_codes)->toBeNull();
    });

    it('can delete user via table action', function () {
        $user = User::factory()->create();

        Livewire::test(ListUsers::class)
            ->callAction(TestAction::make('delete')->table($user));

        expect(User::find($user->id))->toBeNull();
    });
});

describe('User Bulk Actions', function () {
    it('can bulk verify emails', function () {
        $users = User::factory()->count(3)->create(['email_verified_at' => null]);

        Livewire::test(ListUsers::class)
            ->selectTableRecords($users->pluck('id')->toArray())
            ->callAction(TestAction::make('verify_email')->table()->bulk());

        $users->each(fn ($user) => expect($user->refresh()->email_verified_at)->not->toBeNull());
    });

    it('can bulk assign roles', function () {
        $users = User::factory()->count(3)->create();
        $role = Role::create(['name' => 'contributor']);

        Livewire::test(ListUsers::class)
            ->selectTableRecords($users->pluck('id')->toArray())
            ->callAction(TestAction::make('assign_role')->table()->bulk(), [
                'role' => 'contributor',
            ]);

        $users->each(fn ($user) => expect($user->refresh()->hasRole('contributor'))->toBeTrue());
    });

    it('can bulk delete users', function () {
        $users = User::factory()->count(3)->create();

        Livewire::test(ListUsers::class)
            ->selectTableRecords($users->pluck('id')->toArray())
            ->callAction(TestAction::make(DeleteAction::class)->table()->bulk());

        $users->each(fn ($user) => expect(User::find($user->id))->toBeNull());
    });
});
