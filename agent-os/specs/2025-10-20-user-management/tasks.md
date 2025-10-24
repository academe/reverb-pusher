# Task Breakdown: User Management in Filament Admin Panel

## Overview
Total Tasks: 28 sub-tasks across 5 task groups
Assigned implementers: database-engineer, api-engineer, ui-designer, testing-engineer

## Task List

### Phase 1: Database Foundation

#### Task Group 1: User Model and Database Schema
**Assigned implementer:** database-engineer
**Dependencies:** None

- [x] 1.0 Complete database layer for user management
  - [x] 1.1 Write 2-8 focused tests for User model active status functionality
    - Test canAccessPanel() returns false when user is inactive
    - Test canAccessPanel() returns true when user is active
    - Test active status cast to boolean
    - Test active defaults to true for new users
    - Limit to 4-6 highly focused tests maximum
  - [x] 1.2 Create migration for active column
    - File: `database/migrations/YYYY_MM_DD_HHMMSS_add_active_to_users_table.php`
    - Add boolean column `active`, not nullable, default true
    - Add index on `active` column for query performance
    - Implement reversible down() method
    - Follow pattern: small, focused changes per migration
  - [x] 1.3 Update User model with active field support
    - File: `app/Models/User.php`
    - Add `'active'` to $fillable array
    - Add `'active' => 'boolean'` to casts() method
    - Update canAccessPanel() to check: `return $this->active;`
    - Optional: Add helper method `isLastActiveUser()` for reusability
  - [x] 1.4 Run migration and verify database changes
    - Execute: `php artisan migrate`
    - Verify active column exists with correct defaults
    - Verify existing users have active=true
  - [x] 1.5 Ensure database layer tests pass
    - Run ONLY the 4-6 tests written in 1.1
    - Verify canAccessPanel() logic works correctly
    - Do NOT run entire test suite at this stage

**Acceptance Criteria:**
- The 4-6 tests written in 1.1 pass
- Migration successfully adds active column with default true
- User model properly casts active to boolean
- canAccessPanel() checks active status
- Existing users remain active after migration

**Estimated Effort:** Small

---

### Phase 2: Filament Resource Core Structure

#### Task Group 2: UserResource and Basic Pages
**Assigned implementer:** api-engineer
**Dependencies:** Task Group 1

- [x] 2.0 Complete UserResource implementation with core functionality
  - [x] 2.1 Write 2-8 focused tests for UserResource core operations
    - Test user creation with all required fields
    - Test user update functionality
    - Test user deletion
    - Test email uniqueness validation
    - Limit to 4-6 highly focused tests maximum
  - [x] 2.2 Create UserResource with form configuration
    - File: `app/Filament/Admin/Resources/UserResource.php`
    - Model: `App\Models\User`
    - Navigation icon: `heroicon-o-users`
    - Navigation label: "Users"
    - Model label: "User"
    - Follow pattern from: `ReverbAppResource.php`
  - [x] 2.3 Implement form() method with three sections
    - **Section 1: Account Details**
      - name: TextInput, required, maxLength(255)
      - email: TextInput, email validation, unique(ignoreRecord: true), copyable with message
    - **Section 2: Security**
      - password: TextInput, password, revealable, required on create only, minLength(8)
      - password_confirmation: TextInput, password, dehydrated(false), same validation
    - **Section 3: Status**
      - active: Toggle, default true, label "Active"
      - email_verified: Toggle for controlling email_verified_at timestamp
  - [x] 2.4 Implement table() method with columns and filters
    - Columns: name (searchable, sortable), email (searchable, sortable, copyable), active (IconColumn boolean), email_verified_at (IconColumn boolean), created_at (sortable)
    - Filters: TernaryFilter for active status
    - Actions: EditAction, DeleteAction (protection logic added later)
    - BulkActions: DeleteBulkAction (protection logic added later)
    - Default sort: created_at desc
  - [x] 2.5 Create ListUsers page
    - File: `app/Filament/Admin/Resources/UserResource/Pages/ListUsers.php`
    - Extends: `Filament\Resources\Pages\ListRecords`
    - Header actions: CreateAction
    - Follow pattern from: `ListReverbApps.php`
  - [x] 2.6 Create CreateUser page
    - File: `app/Filament/Admin/Resources/UserResource/Pages/CreateUser.php`
    - Extends: `Filament\Resources\Pages\CreateRecord`
    - Follow pattern from: `CreateReverbApp.php`
    - Note: Invitation email logic added in Task Group 3
  - [x] 2.7 Create EditUser page
    - File: `app/Filament/Admin/Resources/UserResource/Pages/EditUser.php`
    - Extends: `Filament\Resources\Pages\EditRecord`
    - Header actions: DeleteAction (protection added later)
    - Follow pattern from: `EditReverbApp.php`
  - [x] 2.8 Implement getPages() method
    - Define routes: index (ListUsers), create (CreateUser), edit (EditUser)
    - No view page needed for this resource
  - [x] 2.9 Ensure UserResource tests pass
    - Run ONLY the 4-6 tests written in 2.1
    - Verify CRUD operations work
    - Do NOT run entire test suite at this stage

**Acceptance Criteria:**
- The 4-6 tests written in 2.1 pass
- UserResource visible in Filament navigation
- List page displays users with all columns
- Create page allows user creation with validation
- Edit page allows user updates
- Forms follow ReverbAppResource patterns
- Search and filter functionality works

**Estimated Effort:** Medium

---

### Phase 3: User Invitation and Password Management

#### Task Group 3: Email Workflows
**Assigned implementer:** api-engineer
**Dependencies:** Task Group 2

- [x] 3.0 Complete user invitation and password reset functionality
  - [x] 3.1 Write 2-8 focused tests for email workflows
    - Test invitation email sent when creating user without password
    - Test no email sent when creating user with password
    - Test password reset email sent from action button
    - Test password reset link is valid
    - Limit to 4-6 highly focused tests maximum
  - [x] 3.2 Implement invitation email on user creation
    - File: `app/Filament/Admin/Resources/UserResource/Pages/CreateUser.php`
    - Add afterCreate() hook
    - Check if password field is empty
    - Generate password reset token: `Password::createToken($this->record)`
    - Send notification: `$this->record->sendPasswordResetNotification($token)`
    - Use Laravel Breeze infrastructure (no custom templates)
  - [x] 3.3 Add password reset email action to EditUser page
    - File: `app/Filament/Admin/Resources/UserResource/Pages/EditUser.php`
    - Implement getHeaderActions() method
    - Add Action::make('sendPasswordReset')
    - Label: "Send Password Reset Email"
    - Icon: `heroicon-o-envelope`
    - Action: Generate token and send notification
    - Success notification: "Password reset email sent"
  - [x] 3.4 Handle email_verified toggle in form
    - Update form to properly handle email_verified toggle
    - Convert toggle state to email_verified_at timestamp (now() or null)
    - Use afterStateUpdated for reactive behavior
    - Ensure timestamp saved correctly on create/update
  - [x] 3.5 Ensure email workflow tests pass
    - Run ONLY the 4-6 tests written in 3.1
    - Verify invitation emails send correctly
    - Verify password reset emails send from action
    - Do NOT run entire test suite at this stage

**Acceptance Criteria:**
- The 4-6 tests written in 3.1 pass
- Creating user without password sends invitation email
- Creating user with password does not send email
- "Send Password Reset Email" action works in EditUser
- Email verified toggle correctly sets/unsets timestamp
- All emails use Laravel Breeze templates

**Estimated Effort:** Medium

---

### Phase 4: Protection Logic and Policies

#### Task Group 4: Self-Protection and Last User Protection
**Assigned implementer:** api-engineer
**Dependencies:** Task Group 3

- [x] 4.0 Complete protection logic for users
  - [x] 4.1 Write 2-8 focused tests for protection logic
    - Test self-deletion prevention
    - Test self-deactivation prevention
    - Test last active user deletion prevention
    - Test last active user deactivation prevention
    - Test bulk delete protection
    - Limit to 5-8 highly focused tests maximum
  - [x] 4.2 Create UserPolicy for authorization
    - File: `app/Policies/UserPolicy.php`
    - Implement delete() method: prevent if $user->id === $model->id
    - Implement update() method: prevent self-deactivation
    - Add last user protection logic in both methods
    - Check: `User::where('active', true)->count() > 1` before allowing delete/deactivate of active user
  - [x] 4.3 Register UserPolicy in service provider
    - File: `app/Providers/AppServiceProvider.php` or `app/Providers/AuthServiceProvider.php`
    - Register: `Gate::policy(User::class, UserPolicy::class);`
  - [x] 4.4 Add self-protection to DeleteAction in table
    - File: `app/Filament/Admin/Resources/UserResource.php`
    - Update DeleteAction with before() hook
    - Check if $record->id === auth()->id()
    - If true, send danger notification and cancel action
    - Message: "Cannot delete your own account"
  - [x] 4.5 Add last user protection to DeleteAction
    - Check active user count before deletion
    - If $record->active and count <= 1, prevent deletion
    - Send danger notification and cancel action
    - Message: "Cannot delete the last active user"
  - [x] 4.6 Add self-deactivation protection to form
    - File: `app/Filament/Admin/Resources/UserResource.php`
    - Disable active toggle when editing own record
    - Use: `->disabled(fn ($record) => $record?->id === auth()->id())`
  - [x] 4.7 Add last user deactivation protection
    - File: `app/Filament/Admin/Resources/UserResource/Pages/EditUser.php`
    - Add beforeSave() hook
    - Check if toggling active to false for last active user
    - If true, halt with error notification
    - Message: "Cannot deactivate the last active user"
  - [x] 4.8 Implement bulk delete protection
    - Update DeleteBulkAction in UserResource
    - Add before() hook to check each record
    - Skip self and last active user from bulk delete
    - Show notification listing which users were skipped
  - [x] 4.9 Ensure protection logic tests pass
    - Run ONLY the 5-8 tests written in 4.1
    - Verify all protection rules work correctly
    - Do NOT run entire test suite at this stage

**Acceptance Criteria:**
- The 5-8 tests written in 4.1 pass
- UserPolicy created and registered
- Self-deletion blocked at UI and backend
- Self-deactivation blocked at UI and backend
- Last active user deletion blocked
- Last active user deactivation blocked
- Bulk actions respect protection rules
- Clear error messages shown for blocked actions

**Estimated Effort:** Medium

---

### Phase 5: Profile Consolidation and Cleanup

#### Task Group 5: Remove Breeze Profile Components
**Assigned implementer:** api-engineer
**Dependencies:** Task Group 4

- [x] 5.0 Complete profile management consolidation
  - [x] 5.1 Write 2-4 focused tests for profile consolidation
    - Test profile routes no longer exist
    - Test users can edit their own profile via Filament
    - Limit to 2-4 highly focused tests maximum
  - [x] 5.2 Remove ProfileController
    - File to delete: `app/Http/Controllers/ProfileController.php`
    - Verify no other code references this controller
  - [x] 5.3 Remove ProfileUpdateRequest if exists
    - File to check and delete if exists: `app/Http/Requests/ProfileUpdateRequest.php`
  - [x] 5.4 Remove profile views directory
    - Directory to delete: `resources/views/profile/`
    - Includes: edit.blade.php and partials subdirectory
  - [x] 5.5 Remove profile routes from web.php
    - File: `routes/web.php`
    - Remove any profile-related routes (GET/PATCH/DELETE /profile)
    - Keep password reset routes intact (Breeze infrastructure)
  - [x] 5.6 Verify Filament profile menu still works
    - Test that Filament's built-in user menu remains functional
    - Ensure users can access their own record via UserResource
  - [x] 5.7 Ensure profile consolidation tests pass
    - Run ONLY the 2-4 tests written in 5.1
    - Verify profile routes removed
    - Verify Filament profile access works
    - Do NOT run entire test suite at this stage

**Acceptance Criteria:**
- The 2-4 tests written in 5.1 pass
- ProfileController deleted
- ProfileUpdateRequest deleted (if existed)
- Profile views directory deleted
- Profile routes removed from routes/web.php
- Password reset routes remain intact
- Users can edit their profile via Filament UserResource
- No broken references to deleted components

**Estimated Effort:** Small

---

### Phase 6: Testing and Verification

#### Task Group 6: Test Coverage Review and Integration Testing
**Assigned implementer:** testing-engineer
**Dependencies:** Task Groups 1-5

- [x] 6.0 Review and complete test coverage for user management feature
  - [x] 6.1 Review existing tests from previous task groups
    - Review 4-6 tests from database-engineer (Task 1.1) - File: tests/Feature/UserActiveStatusTest.php
    - Review 4-6 tests from api-engineer (Task 2.1) - File: tests/Feature/UserResourceTest.php
    - Review 4-6 tests from api-engineer (Task 3.1) - File: tests/Feature/UserEmailWorkflowTest.php
    - Review 5-8 tests from api-engineer (Task 4.1) - File: tests/Feature/UserProtectionTest.php
    - Review 2-4 tests from api-engineer (Task 5.1) - File: tests/Feature/ProfileConsolidationTest.php
    - Total existing tests: approximately 19-30 tests
  - [x] 6.2 Analyze test coverage gaps for user management feature only
    - Identify critical end-to-end workflows not covered
    - Focus ONLY on user management feature requirements
    - Prioritize integration workflows over additional unit tests
    - Do NOT assess entire application test coverage
  - [x] 6.3 Write up to 10 additional strategic tests maximum
    - End-to-end: Create user -> Send invitation -> Verify email received -> Set password flow
    - End-to-end: Admin edits another user -> Changes password -> User can login with new password
    - End-to-end: Toggle user active -> User loses panel access -> Reactivate -> Access restored
    - Integration: Bulk delete with mixed protected/unprotected users
    - Integration: Password reset email contains valid token and link
    - Security: Inactive user cannot access panel (integration with middleware)
    - Security: Email uniqueness enforced across create and update
    - Edge case: Password confirmation mismatch shows proper error
    - Edge case: Multiple active users, deactivate one, last user still protected
    - Maximum 10 new tests to fill critical gaps
    - Focus on integration points and end-to-end workflows
  - [x] 6.4 Run feature-specific tests only
    - Run ONLY tests related to user management feature
    - Expected total: approximately 29-40 tests maximum
    - Do NOT run entire application test suite
    - Verify all critical workflows pass
  - [x] 6.5 Document any known limitations or future testing needs
    - Note any edge cases intentionally skipped
    - Document areas that may need performance testing later
    - List any manual testing scenarios for QA

**Acceptance Criteria:**
- All user management feature tests pass (approximately 29-40 tests total)
- Critical end-to-end workflows covered:
  - User creation with invitation email
  - Password reset flows (direct and email)
  - Active status controlling panel access
  - Self-protection and last user protection
  - Profile management via Filament
- No more than 10 additional tests added by testing-engineer
- Testing focused exclusively on user management feature
- Test results documented
- Known limitations documented for future reference

**Estimated Effort:** Medium

---

## Execution Order

Recommended implementation sequence:

1. **Phase 1: Database Foundation** (Task Group 1)
   - Establishes active column and model updates
   - Required for all subsequent work

2. **Phase 2: Filament Resource Core** (Task Group 2)
   - Creates UserResource structure and basic CRUD
   - Foundation for email workflows and protection

3. **Phase 3: Email Workflows** (Task Group 3)
   - Adds invitation and password reset functionality
   - Builds on core CRUD from Phase 2

4. **Phase 4: Protection Logic** (Task Group 4)
   - Implements security rules and policies
   - Requires complete CRUD and email workflows

5. **Phase 5: Profile Consolidation** (Task Group 5)
   - Removes old Breeze profile components
   - Only safe after Filament user management complete

6. **Phase 6: Testing and Verification** (Task Group 6)
   - Comprehensive testing of complete feature
   - Validates all previous phases work together

## Implementation Notes

### Key Files to Create
- `database/migrations/YYYY_MM_DD_HHMMSS_add_active_to_users_table.php`
- `app/Filament/Admin/Resources/UserResource.php`
- `app/Filament/Admin/Resources/UserResource/Pages/ListUsers.php`
- `app/Filament/Admin/Resources/UserResource/Pages/CreateUser.php`
- `app/Filament/Admin/Resources/UserResource/Pages/EditUser.php`
- `app/Policies/UserPolicy.php`

### Key Files to Modify
- `app/Models/User.php` (add active to fillable/casts, update canAccessPanel)
- `app/Providers/AppServiceProvider.php` or `AuthServiceProvider.php` (register policy)
- `routes/web.php` (remove profile routes)

### Key Files to Delete
- `app/Http/Controllers/ProfileController.php`
- `app/Http/Requests/ProfileUpdateRequest.php` (if exists)
- `resources/views/profile/` (entire directory)

### Code Patterns Reference
- **Form Sections**: Follow `ReverbAppResource.php` Section pattern
- **Copyable Fields**: Use TextInput with copyable() and copyMessage()
- **Password Fields**: Use revealable() for admin convenience
- **Toggle Components**: Use for boolean fields with clear labels
- **Protection Logic**: Implement in before() hooks with Notification feedback
- **Email Sending**: Use Laravel Breeze's Password facade and notifications

### Testing Strategy
- Each implementer writes 2-8 focused tests during development
- Tests verify only critical behaviors, not exhaustive coverage
- Testing-engineer adds maximum 10 strategic integration tests
- Total expected tests: approximately 29-40 for entire feature
- Focus on end-to-end workflows and integration points

### Standards Compliance
- Follow database migration best practices (reversible, small changes)
- Follow model best practices (timestamps, validation, relationships)
- Follow test writing guidelines (minimal tests, core flows only)
- Use Laravel Breeze infrastructure (no custom email templates)
- Maintain consistency with existing Filament resources

### Success Criteria Summary
All tasks complete when:
1. UserResource functional with List, Create, Edit pages
2. User creation with invitation emails works
3. Password management (direct reset and email) works
4. Self-protection prevents admin from deleting/deactivating self
5. Last user protection prevents system lockout
6. Active status controls panel access via canAccessPanel()
7. Breeze profile components removed
8. Approximately 29-40 feature tests pass
9. No regressions in existing authentication flows
10. UI/UX consistent with ReverbAppResource patterns
