# Implementation Tasks: Workspace Management

**Feature**: Workspace Management
**Branch**: 001-workspace-management
**Date**: 2026-03-21
**Spec**: [spec.md](./spec.md)
**Plan**: [plan.md](./plan.md)

## Phase 1: Setup

**Goal**: Initialize project structure and create foundational code artifacts

**Independent Test**: N/A (setup phase)

### Tasks

- [ ] T001 [P] Create WorkspaceRole enum in app/Enums/WorkspaceRole.php
- [ ] T002 [P] Create Workspace model in app/Models/Workspace.php
- [ ] T003 [P] Create Member model in app/Models/Member.php
- [ ] T004 [P] Create Invitation model in app/Models/Invitation.php
- [ ] T005 [P] Extend User model with workspace relationships in app/Models/User.php
- [ ] T006 [P] Create BelongsToWorkspace trait in app/Traits/BelongsToWorkspace.php
- [ ] T007 [P] Create WorkspaceFactory in database/factories/WorkspaceFactory.php
- [ ] T008 [P] Create MemberFactory in database/factories/MemberFactory.php
- [ ] T009 [P] Create InvitationFactory in database/factories/InvitationFactory.php

---

## Phase 2: Foundational Infrastructure

**Goal**: Create database schema, authorization layer, and middleware

**Independent Test**: Can be tested by running migrations and verifying tables are created with correct constraints

### Tasks

- [ ] T010 Create workspaces table migration in database/migrations/YYYY_MM_DD_HHMMSS_create_workspaces_table.php
- [ ] T011 Create members table migration in database/migrations/YYYY_MM_DD_HHMMSS_create_members_table.php
- [ ] T012 Create invitations table migration in database/migrations/YYYY_MM_DD_HHMMSS_create_invitations_table.php
- [ ] T013 Add workspace_id to projects table migration in database/migrations/YYYY_MM_DD_HHMMSS_add_workspace_id_to_projects_table.php
- [ ] T014 Create WorkspacePolicy in app/Policies/WorkspacePolicy.php
- [ ] T015 Create ProjectPolicy in app/Policies/ProjectPolicy.php
- [ ] T016 Create SetCurrentWorkspace middleware in app/Http/Middleware/SetCurrentWorkspace.php
- [ ] T017 Register SetCurrentWorkspace middleware in bootstrap/app.php

---

## Phase 3: User Story 1 - Create and Switch Between Workspaces (P1)

**Goal**: Enable users to create workspaces, switch between them, and maintain session context

**Independent Test**: Can be tested by creating a workspace, switching to it, and verifying session context persists across requests

### Tests (TDD - Write First)

- [ ] T018 [US1] Write test: test_user_can_create_workspace in tests/Feature/WorkspaceTest.php
- [ ] T019 [US1] Write test: test_workspace_slug_is_generated_if_not_provided in tests/Feature/WorkspaceTest.php
- [ ] T020 [US1] Write test: test_workspace_creation_requires_name in tests/Feature/WorkspaceTest.php
- [ ] T021 [US1] Write test: test_workspace_slug_must_be_unique in tests/Feature/WorkspaceTest.php
- [ ] T022 [US1] Write test: test_workspace_name_has_max_length in tests/Feature/WorkspaceTest.php
- [ ] T023 [US1] Write test: test_user_is_switched_to_new_workspace_after_creation in tests/Feature/WorkspaceTest.php
- [ ] T024 [US1] Write test: test_guest_cannot_create_workspace in tests/Feature/WorkspaceTest.php
- [ ] T025 [US1] Write test: test_user_can_switch_between_workspaces in tests/Feature/WorkspaceTest.php
- [ ] T026 [US1] Write test: test_guest_cannot_access_workspace_creation_page in tests/Feature/WorkspaceTest.php

### Implementation

- [ ] T027 [P] [US1] Create CreateWorkspaceRequest in app/Http/Requests/CreateWorkspaceRequest.php
- [ ] T028 [P] [US1] Create WorkspaceController in app/Http/Controllers/WorkspaceController.php
- [ ] T029 [US1] Implement WorkspaceController@store method for workspace creation
- [ ] T030 [US1] Implement WorkspaceController@switch method for workspace switching
- [ ] T031 [US1] Implement WorkspaceController@create method to show creation form
- [ ] T032 [P] [US1] Add workspace routes in routes/web.php (create, store, switch)
- [ ] T033 [P] [US1] Create workspace-switcher Blade component in resources/views/components/workspace-switcher.blade.php
- [ ] T034 [P] [US1] Create create-workspace-modal Blade component in resources/views/components/create-workspace-modal.blade.php
- [ ] T035 [P] [US1] Create workspace creation page view in resources/views/dashboard/workspaces/create.blade.php
- [ ] T036 [US1] Register workspace switcher in dashboard layout in resources/views/components/layout/dashboard.blade.php

---

## Phase 4: User Story 2 - Invite and Manage Workspace Members (P1)

**Goal**: Enable workspace owners and admins to invite members, manage roles, and handle invitations

**Independent Test**: Can be tested by inviting a member, accepting the invitation, and verifying role-based permissions

### Tests (TDD - Write First)

- [ ] T037 [US2] Write test: test_owner_can_invite_member in tests/Feature/InviteTest.php
- [ ] T038 [US2] Write test: test_admin_can_invite_member in tests/Feature/InviteTest.php
- [ ] T039 [US2] Write test: test_member_cannot_invite_member in tests/Feature/InviteTest.php
- [ ] T040 [US2] Write test: test_invitation_is_created_and_email_sent in tests/Feature/InviteTest.php
- [ ] T041 [US2] Write test: test_duplicate_invitation_prevented_for_same_email in tests/Feature/InviteTest.php
- [ ] T042 [US2] Write test: test_invitation_prevented_for_existing_member in tests/Feature/InviteTest.php
- [ ] T043 [US2] Write test: test_invitee_can_accept_invitation in tests/Feature/InviteTest.php
- [ ] T044 [US2] Write test: test_invitation_acceptance_creates_membership in tests/Feature/InviteTest.php
- [ ] T045 [US2] Write test: test_owner_can_view_members_list in tests/Feature/InviteTest.php
- [ ] T046 [US2] Write test: test_members_list_shows_pending_invitations in tests/Feature/InviteTest.php
- [ ] T047 [US2] Write test: test_owner_can_change_member_role in tests/Feature/InviteTest.php
- [ ] T048 [US2] Write test: test_owner_can_remove_member in tests/Feature/InviteTest.php
- [ ] T049 [US2] Write test: test_viewer_cannot_manage_members in tests/Feature/InviteTest.php

### Implementation

- [ ] T050 [P] [US2] Create InviteMemberRequest in app/Http/Requests/InviteMemberRequest.php
- [ ] T051 [P] [US2] Create WorkspaceInviteNotification in app/Notifications/WorkspaceInviteNotification.php
- [ ] T052 [P] [US2] Create MemberController in app/Http/Controllers/MemberController.php
- [ ] T053 [US2] Implement MemberController@invite method to create invitations
- [ ] T054 [US2] Implement MemberController@accept method to handle invitation acceptance
- [ ] T055 [US2] Implement MemberController@index method to list members and invitations
- [ ] T056 [US2] Implement MemberController@updateRole method to change member role
- [ ] T057 [US2] Implement MemberController@remove method to remove members
- [ ] T058 [P] [US2] Add member routes in routes/web.php (invite, accept, index, updateRole, remove)
- [ ] T059 [P] [US2] Create invite-modal Blade component in resources/views/components/invite-modal.blade.php
- [ ] T060 [P] [US2] Create members list page view in resources/views/dashboard/members/index.blade.php

---

## Phase 5: User Story 3 - Workspace Settings and Deletion (P2)

**Goal**: Enable workspace owners to update workspace details and delete workspaces

**Independent Test**: Can be tested by updating workspace details, verifying changes persist, and deleting a workspace

### Tests (TDD - Write First)

- [ ] T061 [US3] Write test: test_owner_can_update_workspace_name in tests/Feature/WorkspaceTest.php
- [ ] T062 [US3] Write test: test_owner_can_update_workspace_description in tests/Feature/WorkspaceTest.php
- [ ] T063 [US3] Write test: test_workspace_slug_validation_on_update in tests/Feature/WorkspaceTest.php
- [ ] T064 [US3] Write test: test_member_cannot_update_workspace in tests/Feature/WorkspaceTest.php
- [ ] T065 [US3] Write test: test_owner_can_delete_workspace in tests/Feature/WorkspaceTest.php
- [ ] T066 [US3] Write test: test_member_cannot_delete_workspace in tests/Feature/WorkspaceTest.php
- [ ] T067 [US3] Write test: test_workspace_deletion_removes_associated_data in tests/Feature/WorkspaceTest.php
- [ ] T068 [US3] Write test: test_workspace_deletion_with_projects_succeeds in tests/Feature/WorkspaceTest.php

### Implementation

- [ ] T069 [P] [US3] Create UpdateSettingsRequest in app/Http/Requests/UpdateSettingsRequest.php
- [ ] T070 [US3] Create SettingsController in app/Http/Controllers/SettingsController.php
- [ ] T071 [US3] Implement SettingsController@show method to display settings
- [ ] T072 [US3] Implement SettingsController@update method to update workspace
- [ ] T073 [US3] Implement SettingsController@destroy method to delete workspace
- [ ] T074 [P] [US3] Add settings routes in routes/web.php (show, update, destroy)
- [ ] T075 [P] [US3] Create settings page view in resources/views/dashboard/settings/index.blade.php
- [ ] T076 [P] [US3] Create settings geral partial view in resources/views/dashboard/settings/partials/geral.blade.php
- [ ] T077 [US3] Create settings membros partial view in resources/views/dashboard/settings/partials/membros.blade.php

---

## Phase 6: User Story 4 - Leave Workspace (P2)

**Goal**: Enable non-owner members to leave workspaces they no longer need

**Independent Test**: Can be tested by a member leaving a workspace and verifying their access is revoked

### Tests (TDD - Write First)

- [ ] T078 [US4] Write test: test_non_owner_member_can_leave_workspace in tests/Feature/InviteTest.php
- [ ] T079 [US4] Write test: test_owner_cannot_leave_workspace in tests/Feature/InviteTest.php
- [ ] T080 [US4] Write test: test_leaving_workspace_removes_membership in tests/Feature/InviteTest.php
- [ ] T081 [US4] Write test: test_leaving_workspace_revokes_access in tests/Feature/InviteTest.php
- [ ] T082 [US4] Write test: test_member_with_projects_can_leave in tests/Feature/InviteTest.php

### Implementation

- [ ] T083 [P] [US4] Add leave method to MemberController in app/Http/Controllers/MemberController.php
- [ ] T084 [US4] Implement MemberController@leave method to remove membership
- [ ] T085 [P] [US4] Add leave route in routes/web.php
- [ ] T086 [US4] Add leave button to settings membros partial in resources/views/dashboard/settings/partials/membros.blade.php

---

## Phase 7: User Story 5 - Transfer Workspace Ownership (P3)

**Goal**: Enable workspace owners to transfer ownership to other members

**Independent Test**: Can be tested by an owner transferring ownership and verifying permissions change appropriately

### Tests (TDD - Write First)

- [ ] T087 [US5] Write test: test_owner_can_transfer_ownership in tests/Feature/WorkspaceTest.php
- [ ] T088 [US5] Write test: test_ownership_transfer_demotes_old_owner in tests/Feature/WorkspaceTest.php
- [ ] T089 [US5] Write test: test_ownership_transfer_promotes_new_owner in tests/Feature/WorkspaceTest.php
- [ ] T090 [US5] Write test: test_cannot_transfer_to_non_member in tests/Feature/WorkspaceTest.php
- [ ] T091 [US5] Write test: test_admin_cannot_transfer_ownership in tests/Feature/WorkspaceTest.php
- [ ] T092 [US5] Write test: test_member_cannot_transfer_ownership in tests/Feature/WorkspaceTest.php

### Implementation

- [ ] T093 [P] [US5] Create TransferOwnershipRequest in app/Http/Requests/TransferOwnershipRequest.php
- [ ] T094 [US5] Add transferOwnership method to WorkspaceController in app/Http/Controllers/WorkspaceController.php
- [ ] T095 [US5] Implement WorkspaceController@transferOwnership method
- [ ] T096 [P] [US5] Add transfer ownership route in routes/web.php
- [ ] T097 [US5] Add transfer ownership option to settings membros partial in resources/views/dashboard/settings/partials/membros.blade.php

---

## Phase 8: User Story 6 - Account Deletion with Workspace Considerations (P3)

**Goal**: Prevent users from deleting accounts if they own workspaces; handle data reassignment

**Independent Test**: Can be tested by a non-owner deleting their account (success) and an owner attempting to delete (blocked)

### Tests (TDD - Write First)

- [ ] T098 [US6] Write test: test_non_owner_can_delete_account in tests/Feature/DeleteAccountTest.php
- [ ] T099 [US6] Write test: test_owner_cannot_delete_account in tests/Feature/DeleteAccountTest.php
- [ ] T100 [US6] Write test: test_deleting_account_removes_memberships in tests/Feature/DeleteAccountTest.php
- [ ] T101 [US6] Write test: test_user_with_multiple_workspaces_can_delete_if_not_owner in tests/Feature/DeleteAccountTest.php
- [ ] T102 [US6] Write test: test_deleting_account_requires_ownership_transfer in tests/Feature/DeleteAccountTest.php
- [ ] T103 [US6] Write test: test_owner_projects_reassigned_on_account_deletion in tests/Feature/DeleteAccountTest.php

### Implementation

- [ ] T104 [P] [US6] Add ownership check to ProfileController@destroy in app/Http/Controllers/ProfileController.php
- [ ] T105 [US6] Implement account deletion prevention logic with workspace ownership check
- [ ] T106 [US6] Implement project reassignment logic when account is deleted
- [ ] T107 [P] [US6] Update error message in profile partial in resources/views/dashboard/profile/partials/perfil.blade.php

---

## Phase 9: Project Scoping (Cross-Cutting)

**Goal**: Automatically scope all project-related data to current workspace context

**Independent Test**: Can be tested by creating projects in different workspaces and verifying data isolation

### Tests (TDD - Write First)

- [ ] T108 [US1] Write test: test_projects_are_scoped_to_current_workspace in tests/Feature/ProjectTest.php
- [ ] T109 [US1] Write test: test_project_auto_assigns_workspace_id in tests/Feature/ProjectTest.php
- [ ] T110 [US1] Write test: test_cannot_access_projects_from_other_workspace in tests/Feature/ProjectTest.php

### Implementation

- [ ] T111 [P] Apply BelongsToWorkspace trait to Project model in app/Models/Project.php
- [ ] T112 [P] Update Project factory to set workspace_id in database/factories/ProjectFactory.php
- [ ] T113 [P] Update BriefingController@store to include workspace_id in app/Http/Controllers/BriefingController.php

---

## Phase 10: Browser Tests (E2E Testing)

**Goal**: Test critical user journeys involving JavaScript interactions (modals, Alpine.js, flash notifications)

**Independent Test**: Each browser test can be run independently and verifies complete user journey

### Tests

- [ ] T114 [P] [US1] Write browser test: test_user_can_open_and_close_create_workspace_modal in tests/Browser/WorkspaceFlowTest.php
- [ ] T115 [P] [US1] Write browser test: test_user_can_switch_workspace_via_ui in tests/Browser/WorkspaceFlowTest.php
- [ ] T116 [P] [US2] Write browser test: test_user_can_invite_member_via_ui in tests/Browser/WorkspaceFlowTest.php
- [ ] T117 [P] [US3] Write browser test: test_user_can_update_workspace_settings_via_ui in tests/Browser/WorkspaceFlowTest.php

### Implementation

- [ ] T118 [P] Add dusk attributes to interactive elements in Blade components
- [ ] T119 [P] Configure PHPFlasher notifications for Noty adapter in config/flasher.php (if not configured)

---

## Phase 11: Polish & Cross-Cutting Concerns

**Goal**: Finalize feature with error handling, edge cases, and code quality improvements

### Tasks

- [ ] T120 [P] Add rate limiting to invitation routes in routes/web.php
- [ ] T121 [P] Add CSRF token to all forms in Blade components
- [ ] T122 [P] Implement invitation token expiration check in MemberController@accept
- [ ] T123 [P] Handle invitation link expiration with appropriate error message
- [ ] T124 [P] Handle concurrent workspace modifications with error messages
- [ ] T125 [P] Implement slug collision handling with unique suffix in WorkspaceController@store
- [ ] T126 [P] Add eager loading to workspace queries in WorkspaceController and MemberController
- [ ] T127 [P] Ensure all flash messages use correct keys (success, error, warning, info)
- [ ] T128 [P] Run vendor/bin/pint to format code
- [ ] T129 [P] Run ./vendor/bin/phpstan analyse for static analysis
- [ ] T130 [P] Run php artisan test --compact to verify all tests pass
- [ ] T131 [P] Run php artisan dusk to verify browser tests pass (if applicable)
- [ ] T132 [P] Update README or documentation if workspace feature affects public API

---

## Task Summary

| Metric | Count |
|--------|-------|
| **Total Tasks** | 132 |
| **Setup Tasks** | 9 |
| **Foundational Tasks** | 8 |
| **US1 (Create/Switch Workspaces)** | 19 (9 tests, 10 implementation) |
| **US2 (Invite/Manage Members)** | 24 (13 tests, 11 implementation) |
| **US3 (Settings/Deletion)** | 17 (8 tests, 9 implementation) |
| **US4 (Leave Workspace)** | 9 (5 tests, 4 implementation) |
| **US5 (Transfer Ownership)** | 11 (6 tests, 5 implementation) |
| **US6 (Account Deletion)** | 10 (6 tests, 4 implementation) |
| **Project Scoping** | 6 (3 tests, 3 implementation) |
| **Browser Tests** | 6 (4 tests, 2 implementation) |
| **Polish** | 13 |

---

## Parallel Execution Opportunities

Tasks marked with `[P]` can be executed in parallel by different developers or within the same story phase.

**Phase 1 Parallelization** (T001-T009):
- All enums, models, and factories can be created independently

**Phase 3 Parallelization** (US1):
- T018-T026 (tests) can be written in parallel
- T027-T036 (implementation) can be started after tests begin

**Phase 4 Parallelization** (US2):
- T037-T049 (tests) can be written in parallel
- T050-T060 (implementation) can be started after tests begin

---

## Implementation Strategy

### MVP Scope (First Delivery)

**Recommended First Release**: Complete US1 (Create and Switch Between Workspaces) + US2 (Invite and Manage Workspace Members)

**Rationale**:
- These are both P1 features (highest priority)
- Together they deliver the core value: data organization and team collaboration
- Each is independently testable
- Users can start using the feature immediately

**Remaining Stories for Later Releases**:
- US3, US4 (P2): Settings management and leaving workspaces
- US5, US6 (P3): Ownership transfer and account deletion (admin functions)

### Incremental Delivery Approach

**Sprint 1**: US1 + US2 (MVP - core workspace functionality)
**Sprint 2**: US3 + US4 (workspace maintenance and member self-service)
**Sprint 3**: US5 + US6 (administrative functions)

### TDD Discipline

**Follow RED → GREEN → REFACTOR Cycle**:

1. **RED Phase**: Write failing test FIRST for each acceptance scenario
2. **GREEN Phase**: Write minimal implementation to make test pass
3. **REFACTOR Phase**: Improve code quality without changing behavior

**Test Execution Order**:
- Write all tests for a user story first (T018-T026 for US1)
- Run tests to verify they fail (RED)
- Implement features to make tests pass (GREEN)
- Run tests again to verify they pass
- Refactor and re-run tests

---

## Dependencies

### Story Completion Order

```mermaid
graph TD
    A[Setup: Enums, Models, Factories] --> B[Foundational: Migrations, Policies, Middleware]
    B --> C[US1: Create/Switch Workspaces]
    B --> D[US2: Invite/Manage Members]
    C --> E[US3: Settings/Deletion]
    D --> F[US4: Leave Workspace]
    E --> G[US5: Transfer Ownership]
    F --> H[US6: Account Deletion]
    C --> I[Project Scoping]
    D --> I
    E --> I
    G --> I
    C --> J[Browser Tests]
    D --> J
    E --> J
    F --> J
    J --> K[Polish: Error handling, code quality]
```

### Critical Dependencies

- **US1 depends on**: Setup + Foundational
- **US2 depends on**: Setup + Foundational + US1 (workspaces must exist before members)
- **US3 depends on**: Setup + Foundational + US1
- **US4 depends on**: Setup + Foundational + US1 + US2
- **US5 depends on**: Setup + Foundational + US1 + US2
- **US6 depends on**: Setup + Foundational + US1 + US2
- **Project Scoping depends on**: Setup + Foundational + US1
- **Browser Tests depend on**: US1, US2, US3 implementation

---

## Validation Checklist

Each task follows the required format:

- [ ] All tasks start with `- [ ]` (markdown checkbox)
- [ ] All tasks have sequential Task IDs (T001-T132)
- [ ] Parallel tasks marked with `[P]` (different files, no dependencies)
- [ ] User story phases marked with `[US1]`, `[US2]`, etc.
- [ ] Implementation tasks include exact file paths
- [ ] Test tasks written before implementation tasks (TDD discipline)
- [ ] Each phase has independent test criteria
- [ ] Total task count matches summary (132)

✅ **ALL REQUIREMENTS SATISFIED**

---

## Next Steps

1. Start with **Phase 1** (Setup): Execute T001-T009 in any order
2. Proceed to **Phase 2** (Foundational): Execute T010-T017 sequentially
3. Start **Phase 3** (US1): Write tests T018-T026, then implement T027-T036
4. Continue through remaining phases following TDD discipline
5. After each phase completion, run `php artisan test --compact` to verify tests pass
6. Before merging branch, run `vendor/bin/pint` and `./vendor/bin/phpstan analyse`
7. Verify all quality gates pass before creating pull request

---

**Ready to implement**: ✅ YES - All tasks are specific, executable, and follow Laravel 12 conventions
