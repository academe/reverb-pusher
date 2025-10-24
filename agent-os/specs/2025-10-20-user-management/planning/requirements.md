# Spec Requirements: User Management

## Initial Description
User management. The first admin user is created using an artisan command. Further users should be manageable within the app.

## Requirements Discussion

### First Round Questions

**Q1:** For the artisan command to create the first admin user, I'm assuming it should validate the email format and ensure password meets basic security requirements (min 8 characters). Should the command be able to run multiple times to create additional users if needed, or should it be strictly for the first user only?

**Answer:** Already exists and can be run at any time. No changes needed.

**Q2:** I'm thinking the artisan command should prompt for name, email, and password interactively, and automatically mark the user as email verified. Is that correct, or would you prefer command-line arguments like `--email` and `--password`?

**Answer:** No changes needed to the artisan command.

**Q3:** For managing users within the app, should we create a UserResource in the Filament admin panel (similar to how ReverbAppResource works)? This would provide list, create, edit, and delete functionality with a consistent UI.

**Answer:** Yes, if it makes sense.

**Q4:** I assume the user form should include: name, email, password (for creation), and an email verification toggle. Should we also include an Active/Inactive status flag?

**Answer:** Yes to all fields (name, email, password, email verification toggle) AND add an Active/Inactive flag.

**Q5:** For password management in the admin panel, should admins be able to directly reset/change another user's password, or should they only be able to trigger a password reset email to the user?

**Answer:** Yes, admins can reset passwords directly AND have a "Send Reset Email" button.

**Q6:** I'm thinking we should prevent the currently logged-in admin from deleting or deactivating their own account through the admin panel. Is that correct?

**Answer:** Yes, the current user should not be able to delete or disable themselves.

**Q7:** For email verification and password reset functionality, should we leverage the existing Laravel Breeze infrastructure (which is already set up), or build custom email templates and workflows?

**Answer:** Yes, use Breeze to stick to tried and tested approaches.

**Q8:** When an admin creates a new user, should the system send an invitation email with a "set your password" link, or should the admin set the password and communicate it to the new user separately?

**Answer:** Send a "set your password" link. Admins can also set the password for communicating outside the system.

**Q9:** For user profile management, should we consolidate the existing ProfileController routes (/profile) into the Filament panel, or keep the separate profile management interface?

**Answer:** Consolidate into Filament panel.

### Existing Code to Reference

**Similar Features Identified:**
- Feature: Reverb App Management - Path: `app/Filament/Admin/Resources/ReverbAppResource.php`
- Components to potentially reuse: Form sections, Table configuration, Navigation patterns
- Backend patterns: Filament resource structure with List, Create, View, Edit pages
- UI patterns: Copyable fields, Toggle components, Sections for organizing forms

### Follow-up Questions

**Follow-up 1: Roles and Permissions**
The roadmap mentions "all users are admins" - should we implement this as:
- A) Single admin role where everyone has identical permissions (simpler, sufficient for now)
- B) Add a role field to future-proof for different permission levels later

**Answer:** A - Single admin role where everyone has identical permissions (simpler, sufficient for now)

**Follow-up 2: Active/Inactive Flag and Self-Protection**
Should the active/inactive flag interact with the self-protection rule? In other words:
- A) Users cannot toggle their own active status (similar to delete prevention)
- B) Users can toggle their own active status, but cannot delete themselves

**Answer:** A - Users cannot toggle their own active status (similar to delete prevention)

**Follow-up 3: Last User Protection**
Should the system prevent deletion or deactivation of the last remaining active user to ensure there's always at least one admin who can access the system?

**Answer:** Yes (good) - Prevent deletion or deactivation of the last remaining active user

**Follow-up 4: User Invitation Email Content**
What should the invitation email content be?
- A) Detailed explanation of the system purpose and account access
- B) Customizable invitation message per user
- C) Use a standard Laravel Breeze-style email template with minimal customization

**Answer:** C - Use a standard Laravel Breeze-style email template with minimal customization

**Follow-up 5: Profile Management Routes**
When consolidating profile management into Filament, should we:
- A) Keep the /profile route as a redirect to Filament
- B) Completely remove the Breeze profile routes and only access profiles through Filament

**Answer:** B - Completely remove the Breeze profile routes and only access profiles through Filament

### Visual Assets

**Mandatory Check Performed:** Yes
**Files Found:** No visual files found in `agent-os/specs/2025-10-20-user-management/planning/visuals/`

No visual assets provided.

## Final Requirements Summary

### Core Features

#### UserResource in Filament Admin Panel
- Create new Filament resource following ReverbAppResource pattern
- Implement List, Create, Edit pages
- User table display with filtering and search capabilities
- Consistent navigation and UI patterns with existing Filament resources

#### User Fields
- **name**: Text input, required
- **email**: Email input, required, unique validation
- **password**: Password input (required on create, optional on edit)
- **email_verified_at**: Toggle for email verification status
- **active**: Boolean toggle for active/inactive status

#### Password Management
- **Direct Password Reset**: Admins can directly set/change another user's password in the edit form
- **Send Reset Email Button**: Action button to trigger Laravel Breeze password reset email
- **New User Invitation**: On user creation, send "set your password" link via email
- **Optional Initial Password**: Admins can optionally set an initial password for out-of-band communication

#### User Invitation Workflow
- When admin creates new user, system sends invitation email
- Email contains "set your password" link using Laravel Breeze infrastructure
- Email template follows standard Laravel Breeze styling with minimal customization
- Admin can optionally set initial password if needed for external communication

#### Self-Protection Rules
- Currently logged-in user cannot delete themselves
- Currently logged-in user cannot deactivate themselves (toggle active status to false)
- UI should disable/hide these actions when viewing own account

#### Last User Protection
- System must prevent deletion of the last remaining active user
- System must prevent deactivation of the last remaining active user
- Ensures there's always at least one admin with access to the system
- Display appropriate error/warning message when attempting these actions

#### Profile Management Consolidation
- Remove existing Breeze profile routes (/profile, /profile/edit, /profile/destroy)
- All profile management happens through Filament UserResource
- Users edit their own profile by accessing their user record in Filament
- Remove ProfileController and related views

### Business Rules

#### Roles and Permissions
- **Single Admin Role**: All users are admins with identical permissions
- **No Role Field**: Do not implement role/permission system at this time
- **Panel Access**: All active users have full panel access
- **Future-Proofing Not Required**: Keep implementation simple, can add roles later if needed

#### Authentication and Email
- **Laravel Breeze**: Use existing Breeze infrastructure for all email functionality
- **Email Verification**: Use Breeze email verification system
- **Password Resets**: Use Breeze password reset workflows and email templates
- **Standard Templates**: Minimal customization to Breeze email templates

#### User Status and Access
- **Active Flag Required**: User must be active to access the panel
- **Email Verification Optional**: Can be toggled by admins, not required for access
- **Artisan Command Unchanged**: Existing command for creating users remains as-is

### Technical Constraints

#### Technology Stack
- **Laravel**: Version 12
- **PHP**: Version 8.3
- **Filament**: Version 3.3
- **Authentication**: Laravel Breeze (already installed)
- **Database**: Existing users table

#### Integration Points
- **Existing User Model**: Located at `app/Models/User.php`
- **FilamentUser Interface**: Already implemented on User model
- **canAccessPanel() Method**: Update to check active status
- **Breeze Routes**: Remove profile routes from `routes/auth.php`
- **ProfileController**: Remove from `app/Http/Controllers/Auth/`

#### Database Changes
- **Migration Required**: Add `active` boolean column to users table if not present
- **Default Value**: Active should default to true for existing users
- **Index Considerations**: May want index on active column for performance

#### Code Patterns to Follow
- **ReverbAppResource Structure**: Use as template for UserResource
- **Filament Components**: TextInput, Toggle, Section, Actions
- **Filament Table**: Columns, Filters, BulkActions
- **Model Observers**: Consider for handling invitation emails on user creation
- **Policy Classes**: Implement authorization logic for self-protection and last user protection

### Scope Boundaries

**In Scope:**
- UserResource in Filament admin panel (List, Create, Edit pages)
- User CRUD operations with validation
- Active/inactive status field and functionality
- Email verification toggle (admin-controlled)
- Direct password reset capability for admins
- "Send Reset Email" action button
- User invitation email with "set password" link
- Self-deletion prevention
- Self-deactivation prevention
- Last user protection (deletion and deactivation)
- Profile management consolidation into Filament
- Removal of Breeze profile routes and controllers
- Database migration for active column
- Update to canAccessPanel() to check active status

**Out of Scope:**
- Role-based access control system
- Granular permissions system
- Multi-factor authentication
- User activity logging/audit trails
- User groups or teams functionality
- API token management
- Custom email templates (beyond minimal Breeze customization)
- Changes to existing artisan command
- User profile photo/avatar management
- Advanced user filtering or reporting
- User import/export functionality

### Technical Implementation Notes

#### Filament Resource Structure
```
app/Filament/Admin/Resources/
├── UserResource.php
├── UserResource/
    ├── Pages/
        ├── ListUsers.php
        ├── CreateUser.php
        ├── EditUser.php
```

#### Key Methods and Logic
- **canAccessPanel()**: Add active status check to existing method in User model
- **Self-Protection**: Implement in UserPolicy or form logic to disable actions
- **Last User Protection**: Query active users count before delete/deactivate
- **Invitation Email**: Trigger on user creation (Model Observer or Form action)
- **Password Management**: Conditional password field (required on create, optional on edit)

#### Routes to Remove
- `routes/auth.php`: Remove profile-related routes
  - GET /profile
  - PATCH /profile
  - DELETE /profile

#### Controllers to Remove
- `app/Http/Controllers/Auth/ProfileController.php`

#### Views to Remove/Archive
- `resources/views/profile/` directory and contents

#### Validation Rules
- Email: required, email format, unique (except on edit for same user)
- Name: required, string, max length
- Password: required on create, min 8 characters, confirmed
- Active: boolean
- Email Verified: boolean (stored as timestamp in email_verified_at)

#### Business Logic Constraints
- Cannot delete self: `$user->id !== auth()->id()`
- Cannot deactivate self: `$user->id !== auth()->id()` when toggling active to false
- Cannot delete last active user: `User::where('active', true)->count() > 1`
- Cannot deactivate last active user: `User::where('active', true)->where('id', '!=', $userId)->exists()`

### Reusability Opportunities

#### Existing Code to Reference
- **ReverbAppResource.php**: Overall resource structure, navigation, page organization
- **ReverbAppResource Form**: Section components, TextInput patterns, Toggle usage
- **ReverbAppResource Table**: Column configuration, Actions, BulkActions patterns
- **User Model**: Already implements FilamentUser, has necessary relationships
- **Laravel Breeze**: Email templates, password reset logic, verification workflows

#### Components to Reuse
- Filament TextInput with copyable() method
- Filament Toggle for boolean fields
- Filament Section for form organization
- Filament Actions for table row actions
- Filament Notifications for success/error messages

### Success Criteria

The feature will be complete when:
1. UserResource exists in Filament admin panel with List, Create, Edit pages
2. Admins can create users with all required fields
3. New users receive invitation email with "set password" link
4. Admins can directly reset passwords and send password reset emails
5. Users cannot delete or deactivate themselves
6. System prevents deletion/deactivation of last active user
7. Active status correctly controls panel access via canAccessPanel()
8. Breeze profile routes are removed
9. All profile management occurs within Filament
10. Database migration adds active column with appropriate defaults
11. All functionality follows ReverbAppResource patterns for consistency
