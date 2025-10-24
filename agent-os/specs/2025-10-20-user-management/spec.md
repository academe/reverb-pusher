# Specification: User Management in Filament Admin Panel

## Goal
Enable admin users to manage all system users through the Filament admin panel, replacing the existing Breeze profile management with a centralized, consistent admin interface for creating, editing, activating/deactivating, and managing users.

## User Stories
- As an admin, I want to view all users in a searchable, sortable table so that I can quickly find and manage specific users
- As an admin, I want to create new users and send them invitation emails so that they can set their own passwords
- As an admin, I want to toggle user active status so that I can temporarily disable access without deleting accounts
- As an admin, I want to manually reset user passwords or send password reset emails so that I can help users regain access
- As an admin, I want to be prevented from deleting or deactivating myself so that I don't accidentally lock myself out
- As a system, I want to ensure at least one active admin always exists so that the system remains accessible

## Core Requirements

### Functional Requirements
- Create, read, update, and delete users through Filament admin interface
- Send invitation emails with "set password" links when creating new users
- Toggle user active/inactive status to control panel access
- Directly reset user passwords or trigger password reset emails
- Search and filter users by name, email, and status
- View and edit email verification status
- Prevent self-deletion and self-deactivation
- Prevent deletion/deactivation of the last active user
- Remove existing Breeze profile management routes and views

### Non-Functional Requirements
- Follow existing Filament resource patterns (reference ReverbAppResource)
- Use Laravel Breeze infrastructure for all email functionality
- Maintain consistency with existing admin panel UI/UX
- Ensure proper validation at both form and database levels
- Provide clear error messages for protected operations
- Keep codebase simple and maintainable (no role system yet)

## Visual Design
No mockups provided. Follow Filament 3.3 default styling and layout patterns as demonstrated in ReverbAppResource.

Key UI elements:
- Users table with columns: name, email, active status, email verified status, created date
- Form sections organized logically: Account Details, Security, Status
- Copyable email field (similar to app_id in ReverbAppResource)
- Toggle components for boolean fields (active, email verified)
- Action buttons: Edit, Delete (with protection logic)
- Header actions: Send Password Reset Email
- Password field with reveal/hide functionality

## Reusable Components

### Existing Code to Leverage
**Components from ReverbAppResource:**
- `Forms\Components\Section` for organizing form fields
- `Forms\Components\TextInput` with copyable() method for email
- `Forms\Components\Toggle` for boolean fields (active, email verified)
- `Tables\Columns\TextColumn` with searchable() and sortable()
- `Tables\Columns\IconColumn` with boolean() for status display
- `Tables\Actions\DeleteAction` with custom logic for protection
- `Tables\Filters\TernaryFilter` for active status filtering
- Navigation icon pattern using heroicon

**Laravel Breeze Infrastructure:**
- Password reset token system (`password_reset_tokens` table)
- Email verification system (VerifyEmailController, notification emails)
- Password reset email templates and controllers
- Email notification infrastructure (Notifiable trait on User model)

**User Model Features:**
- Already implements FilamentUser interface
- Already has Notifiable trait for emails
- Already has email_verified_at timestamp field
- Already has password hashing via casts

### New Components Required
**UserResource and Pages:**
- `app/Filament/Admin/Resources/UserResource.php` - Main resource definition
- `app/Filament/Admin/Resources/UserResource/Pages/ListUsers.php` - List page
- `app/Filament/Admin/Resources/UserResource/Pages/CreateUser.php` - Create page
- `app/Filament/Admin/Resources/UserResource/Pages/EditUser.php` - Edit page

**Database Migration:**
- Migration to add `active` boolean column to users table (doesn't exist yet)

**Notification/Email:**
- Custom invitation notification class or use Breeze's password reset as invitation

**Policy (Optional but Recommended):**
- UserPolicy for authorization logic (self-protection, last user protection)

## Technical Approach

### Database
**Migration Required:**
- Add `active` boolean column to users table
- Default value: true (existing users remain active)
- Not nullable
- Add index on active column for query performance

**No changes to existing schema:**
- users table already has: id, name, email, email_verified_at, password, remember_token, timestamps
- password_reset_tokens table already exists for password resets

### API/Backend

**User Model Updates:**
- Add `active` to $fillable array
- Add `active` cast to boolean in casts() method
- Update canAccessPanel() method to check: `return $this->active;`
- Consider adding helper methods: isLastActiveUser()

**UserResource Structure:**
```
form() method:
  - Section: Account Details
    - name (TextInput, required, max 255)
    - email (TextInput, email validation, unique, copyable)
  - Section: Security
    - password (TextInput, password, revealable, required on create, optional on edit, minLength 8, confirmed)
    - password_confirmation (TextInput, password, dehydrated false)
  - Section: Status
    - active (Toggle, default true, disabled if current user)
    - email_verified_at (Toggle, converts to timestamp)

table() method:
  - Columns: name, email, active (IconColumn), email_verified_at (IconColumn), created_at
  - Filters: TernaryFilter for active status
  - Actions: EditAction, DeleteAction (with beforeAction hook for protection)
  - Bulk Actions: DeleteBulkAction (with protection logic)
  - Default sort: created_at desc

getPages() method:
  - index: ListUsers
  - create: CreateUser
  - edit: EditUser
```

**Protection Logic Implementation:**

*Self-Protection (prevent editing own status/deletion):*
```php
// In EditUser page or UserResource
- Disable active toggle when: $record->id === auth()->id()
- Add before hook to DeleteAction:
  - Check if $record->id === auth()->id()
  - If true, halt() with error notification

// Alternative: Use UserPolicy
public function delete(User $user, User $model): bool
{
    return $user->id !== $model->id;
}
```

*Last User Protection:*
```php
// Before deletion or deactivation
$activeUserCount = User::where('active', true)->count();

// For deletion
if ($record->active && $activeUserCount <= 1) {
    halt() with error: "Cannot delete the last active user"
}

// For deactivation
if ($record->active && $activeUserCount <= 1 && !$newValue) {
    halt() with error: "Cannot deactivate the last active user"
}
```

**Invitation Email System:**
```php
// In CreateUser page afterCreate() hook:
- Generate password reset token
- Send password reset notification (Breeze's PasswordResetNotification)
- This reuses Breeze infrastructure as "invitation"

// Pseudo-code:
protected function afterCreate(): void
{
    if (empty($this->data['password'])) {
        // Send invitation email with set password link
        $token = Password::createToken($this->record);
        $this->record->sendPasswordResetNotification($token);
    }
}
```

**Password Reset Email Action:**
```php
// In EditUser page getHeaderActions():
Action::make('sendPasswordReset')
    ->label('Send Password Reset Email')
    ->icon('heroicon-o-envelope')
    ->action(function ($record) {
        $token = Password::createToken($record);
        $record->sendPasswordResetNotification($token);
        Notification::make()
            ->success()
            ->title('Password reset email sent')
            ->send();
    })
```

### Frontend
**Navigation:**
- Add to Filament admin panel navigation
- Icon: heroicon-o-users
- Label: "Users"
- Order: Position after WebSocket Apps or as appropriate

**Form Behavior:**
- Password field required on create, optional on edit
- When password is filled on edit, require confirmation
- Active toggle disabled when viewing own user record
- Email verified toggle controls email_verified_at (set to now() or null)
- Show password with revealable() option for admin convenience

**Table Interactions:**
- Searchable on name and email
- Sortable on name, email, created_at
- Copyable email with "copied" notification
- Filter by active status (all/active/inactive)
- Delete action shows confirmation modal
- Bulk delete shows confirmation with protection checks

### Testing
**Key Test Scenarios:**
- Create user with all fields
- Create user without password (should send invitation)
- Create user with password (should not send invitation)
- Edit user details without changing password
- Edit user and change password
- Toggle user active status
- Toggle email verification status
- Send password reset email action
- Attempt to delete own user (should fail)
- Attempt to deactivate own user (should fail)
- Attempt to delete last active user (should fail)
- Attempt to deactivate last active user (should fail)
- Verify active=false users cannot access panel
- Search and filter functionality
- Email uniqueness validation

## Implementation Details

### Files to Create

**1. UserResource (Main Resource)**
- Path: `app/Filament/Admin/Resources/UserResource.php`
- Pattern: Copy structure from ReverbAppResource.php
- Model reference: `App\Models\User`
- Navigation icon: `heroicon-o-users`
- Navigation label: "Users"
- Model label: "User"

**2. ListUsers Page**
- Path: `app/Filament/Admin/Resources/UserResource/Pages/ListUsers.php`
- Extends: `Filament\Resources\Pages\ListRecords`
- Header actions: CreateAction
- Pattern: Copy from ListReverbApps.php

**3. CreateUser Page**
- Path: `app/Filament/Admin/Resources/UserResource/Pages/CreateUser.php`
- Extends: `Filament\Resources\Pages\CreateRecord`
- Custom logic: afterCreate() hook to send invitation email if no password provided
- Pattern: Copy from CreateReverbApp.php and extend

**4. EditUser Page**
- Path: `app/Filament/Admin/Resources/UserResource/Pages/EditUser.php`
- Extends: `Filament\Resources\Pages\EditRecord`
- Header actions: SendPasswordResetAction, DeleteAction (with protection)
- Custom logic: beforeSave() hook to check self-deactivation and last user protection
- Pattern: Copy from EditReverbApp.php and extend

**5. Database Migration**
- Path: `database/migrations/YYYY_MM_DD_HHMMSS_add_active_to_users_table.php`
- Up: Add boolean column `active`, default true, not nullable, with index
- Down: Drop column `active`
- Use standard Laravel migration structure

**6. UserPolicy (Recommended)**
- Path: `app/Policies/UserPolicy.php`
- Methods: viewAny, view, create, update, delete, restore, forceDelete
- delete() method: Implement self-protection and last user protection logic
- update() method: Implement self-deactivation protection logic

### Files to Modify

**1. User Model**
- Path: `app/Models/User.php`
- Add to $fillable: `'active'`
- Add to casts(): `'active' => 'boolean'`
- Update canAccessPanel(): Change from `return true;` to `return $this->active;`
- Optional: Add helper method `isLastActiveUser()` for reusability

**2. AppServiceProvider or AuthServiceProvider**
- Path: `app/Providers/AppServiceProvider.php` or `app/Providers/AuthServiceProvider.php`
- Register UserPolicy: `Gate::policy(User::class, UserPolicy::class);`
- Or add to $policies array in AuthServiceProvider

**3. routes/auth.php**
- Path: `routes/auth.php`
- Remove profile-related routes (currently not present, but document for safety)
- Ensure password reset routes remain intact (already present)

### Files to Delete

**1. ProfileController**
- Path: `app/Http/Controllers/ProfileController.php`
- Reason: Profile management consolidated into Filament UserResource

**2. ProfileUpdateRequest** (if exists)
- Path: `app/Http/Requests/ProfileUpdateRequest.php`
- Reason: No longer needed without ProfileController

**3. Profile Views**
- Path: `resources/views/profile/edit.blade.php`
- Path: `resources/views/profile/partials/*` (entire directory)
- Reason: No longer using separate profile management interface

**4. Profile Routes** (verify and remove if present)
- In `routes/web.php` or `routes/auth.php`
- Routes like: `/profile`, `/profile/edit`, etc.

### Code Patterns to Follow

**From ReverbAppResource:**
```php
// Form sections with clear labels
Forms\Components\Section::make('Section Title')
    ->schema([...])
    ->columns(1)

// Copyable text inputs
Forms\Components\TextInput::make('email')
    ->email()
    ->copyable()
    ->copyMessage('Email copied to clipboard')

// Toggle with custom label
Forms\Components\Toggle::make('active')
    ->label('Active')
    ->default(true)

// Password with reveal
Forms\Components\TextInput::make('password')
    ->password()
    ->revealable()
    ->required(fn ($livewire) => $livewire instanceof CreateUser)

// Table columns with search/sort
Tables\Columns\TextColumn::make('name')
    ->searchable()
    ->sortable()

// Icon column for booleans
Tables\Columns\IconColumn::make('active')
    ->boolean()

// Ternary filter for boolean status
Tables\Filters\TernaryFilter::make('active')
    ->label('Active Status')
```

**Protection Logic Pattern:**
```php
// In DeleteAction
Tables\Actions\DeleteAction::make()
    ->before(function (User $record, Tables\Actions\DeleteAction $action) {
        // Self-protection
        if ($record->id === auth()->id()) {
            Notification::make()
                ->danger()
                ->title('Cannot delete your own account')
                ->send();
            $action->cancel();
        }

        // Last user protection
        $activeCount = User::where('active', true)->count();
        if ($record->active && $activeCount <= 1) {
            Notification::make()
                ->danger()
                ->title('Cannot delete the last active user')
                ->send();
            $action->cancel();
        }
    })
```

**Email Verification Toggle Pattern:**
```php
Forms\Components\Toggle::make('email_verified')
    ->label('Email Verified')
    ->default(false)
    ->afterStateUpdated(function ($state, Forms\Set $set) {
        $set('email_verified_at', $state ? now() : null);
    })
    ->dehydrated(false) // Don't save this field directly

Forms\Components\Hidden::make('email_verified_at')
    ->dehydrateStateUsing(fn ($state) => $state ? now() : null)
```

## Integration Points

### Filament Admin Panel
- UserResource automatically registers in Filament navigation
- Uses existing admin panel authentication and middleware
- Follows Filament 3.3 component patterns and styling
- Integrates with Filament's notification system for feedback

### Laravel Breeze
- Reuses password reset token generation: `Password::createToken($user)`
- Reuses password reset notification: `$user->sendPasswordResetNotification($token)`
- Maintains password reset routes: `/reset-password/{token}`
- Leverages Notifiable trait already on User model
- Uses existing password reset email templates with minimal customization

### Active Status and Panel Access
- `canAccessPanel()` method called by Filament middleware on every request
- Returns boolean based on `active` column
- Inactive users immediately lose panel access (redirected to login)
- Active status checked before rendering protected actions (delete, deactivate)

### Navigation and Menu
- UserResource appears in Filament sidebar navigation
- Positioned logically (suggest after WebSocket Apps)
- Icon: heroicon-o-users
- Badge: Could show total user count (optional enhancement)

## Edge Cases and Validation

### Self-Deletion Prevention
- Check: `$record->id === auth()->id()` before deletion
- UI: Disable delete action when viewing own record
- Backend: Halt action with error notification if attempted
- Policy: Return false from delete() method for self

### Self-Deactivation Prevention
- Check: `$record->id === auth()->id()` before toggling active to false
- UI: Disable active toggle when editing own record
- Backend: Validate before save, reject with error notification
- Policy: Return false from update() when deactivating self

### Last Active User Protection
- Query: `User::where('active', true)->count()` before deletion/deactivation
- Deletion: If count <= 1 and user is active, prevent deletion
- Deactivation: If count <= 1 and user is active, prevent toggle to false
- Message: Clear error explaining system must have at least one active admin

### Email Uniqueness Validation
- Form validation: `->unique(ignoreRecord: true)` in edit mode
- Database constraint: Email column already has unique constraint
- Error handling: Show clear message if duplicate email attempted
- Case sensitivity: Laravel handles case-insensitive email matching

### Password Requirements
- Minimum length: 8 characters (Laravel default)
- Confirmation: Required when password is provided
- Create vs Edit: Required on create, optional on edit
- Hashing: Automatic via User model casts
- Reset link: Valid for limited time (Laravel config)

### Email Verification Toggle
- Toggle affects: email_verified_at timestamp (set or null)
- Not required: For panel access (only active status required)
- Admin control: Admins can manually verify without user action
- Breeze compatibility: Setting timestamp bypasses verification routes

### Invitation Email Behavior
- Sent when: New user created without password
- Not sent when: Admin provides initial password
- Uses: Laravel's password reset system as invitation
- Token expiry: Standard password reset expiry applies
- Resend: Admin can use "Send Password Reset Email" action

### Bulk Actions Protection
- Bulk delete: Check each record for protection before proceeding
- Mixed selection: Allow deletion of unprotected, skip protected with notification
- Self in selection: Skip self, continue with others
- Last user in selection: Skip if no other active users, show error

## Testing Considerations

### Security Testing
- Verify inactive users cannot access panel at all
- Verify self-deletion is blocked at UI and backend levels
- Verify self-deactivation is blocked at UI and backend levels
- Verify last user protection works for both delete and deactivate
- Verify email uniqueness is enforced
- Verify password hashing occurs before storage

### Functional Testing
- Create user with password -> No email sent
- Create user without password -> Invitation email sent with reset link
- Edit user details -> Changes saved correctly
- Edit user password -> New password works, confirmation required
- Toggle active status -> Panel access changes immediately
- Toggle email verified -> Timestamp updates correctly
- Send password reset -> Email sent, link works
- Delete user -> User removed from database
- Search users -> Correct results returned
- Filter by active status -> Correct filtering applied

### Edge Case Testing
- Attempt self-delete -> Error shown, action blocked
- Attempt self-deactivate -> Error shown, action blocked
- Attempt to delete last active user -> Error shown, action blocked
- Attempt to deactivate last active user -> Error shown, action blocked
- Create user with duplicate email -> Validation error shown
- Create user with short password -> Validation error shown
- Create user without password confirmation -> Validation error shown
- Bulk delete including self -> Self skipped, others deleted
- Multiple tabs: Edit user in both, save in both -> Optimistic locking handled

### Email Testing
- Invitation email contains valid reset link
- Reset link expires after configured time
- Reset link is single-use (invalidated after use)
- Email sent to correct recipient
- Email template renders correctly
- Clicking link leads to password reset form

### UI/UX Testing
- Active toggle disabled on own user page
- Delete action hidden/disabled on own user page
- Delete action disabled on last active user page
- Copyable email shows "copied" notification
- Form validation errors display clearly
- Success notifications show after actions
- Table sorting works on all sortable columns
- Table search finds users by name and email
- Filters update table results correctly

## Success Criteria

The feature will be considered complete and successful when:

1. **UserResource exists** in Filament admin panel with List, Create, Edit pages following ReverbAppResource patterns
2. **User creation works** with all fields (name, email, password, active, email verified) and proper validation
3. **Invitation emails send** automatically when creating users without passwords, using Breeze infrastructure
4. **Password management works** with direct reset capability and "Send Reset Email" action button
5. **Self-protection prevents** logged-in admins from deleting or deactivating themselves with clear error messages
6. **Last user protection prevents** deletion or deactivation of the last active user in the system
7. **Active status controls access** via updated canAccessPanel() method - inactive users cannot access panel
8. **Breeze profile routes removed** from routes/auth.php and web.php
9. **ProfileController deleted** along with all profile views and related files
10. **Database migration applied** adding active boolean column with default true and proper indexing
11. **UI consistency maintained** with ReverbAppResource styling, navigation, and component usage
12. **All tests pass** covering security, functionality, edge cases, and email behavior
13. **No regression** in existing authentication, login, logout, or password reset functionality

## Post-Implementation Notes

### Future Enhancements (Out of Scope)
- Role-based access control with granular permissions
- User activity logging and audit trails
- User profile photos/avatars
- Two-factor authentication
- API token management for users
- User groups or team functionality
- Advanced user reporting and analytics
- Import/export user data functionality
- Custom email templates beyond Breeze defaults
- User session management (force logout, view active sessions)

### Maintenance Considerations
- Monitor invitation email delivery success rates
- Review password reset token expiry configuration
- Consider adding user last login timestamp in future
- May want to add soft deletes for user recovery in future
- Consider adding user deactivation reason notes in future
- May benefit from user creation audit log in future

### Documentation Needs
- Update user documentation to explain new admin user management
- Document how to create first admin user via artisan command
- Explain invitation email workflow for new admins
- Document self-protection and last user protection behaviors
- Note that all users are admins (no role differentiation yet)
