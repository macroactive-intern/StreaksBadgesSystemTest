# APPROACH.md

# Phase 4 — Approach

## Overview

I will build a Phase 1 Streaks & Badges System for MacroActive apps.

The system will track user activity events, calculate daily streaks, award badges, show progress to end users, and give creators basic control over which streak and badge types are enabled.

Phase 1 will focus on:

* Streak tracking.
* Badge awarding.
* Creator configuration.
* Dashboard streak widget data.
* Badge display data.
* Scheduled daily streak evaluation.
* Basic notification trigger records.
* Basic anti-cheat validation.
* Data structure that can support leaderboards later.

I will not build full leaderboards, cross-app identity, or advanced data warehouse analytics in Phase 1 because the PRD labels leaderboards as Phase 2 and treats cross-app identity as a long-term opportunity.

---

## Main decisions

### Phase 1 scope

Leaderboards are not part of the first build. They will be supported later by storing streak and badge data in a way that can be queried for leaderboard aggregation.

### Streak scope

Streaks will be tracked per:

* User.
* Creator app or creator account.
* Streak type.

This means a user can have separate streaks in different creator apps.

### Badge permanence

Badges will remain earned once awarded, even if the user later breaks the streak. Badges can only be revoked manually by an admin or creator if there was a mistake, cheating, or moderation reason.

### Timezone behavior

Streaks will use the end user’s local calendar day. The system will still store UTC timestamps for auditability.

Each activity event will store:

* UTC event timestamp.
* User timezone at the time of the event.
* Local event date.

### Event-based qualification

The streak and badge system will not directly calculate progress from many unrelated feature tables. Instead, user actions will create standard activity events such as:

* `workout_completed`
* `nutrition_logged`
* `habit_completed`
* `community_comment_posted`
* `program_completed`
* `challenge_completed`

The streak and badge services will evaluate these events.

### Creator configuration

Phase 1 will use default templates with limited configuration. Creators can enable or disable streak types, set simple thresholds, enable badge templates, attach simple rewards, and manually award badges.

A complex custom rule builder is future work.

---

## Data model

The exact table names may change depending on existing project conventions, but these are the core tables I plan to add.

---

## `activity_events`

This table stores user actions that may qualify for streak progress or badge progress.

### Columns

* `id` — primary key.
* `user_id` — foreign key to users.
* `creator_app_id` — foreign key to the creator app or creator account.
* `event_type` — string or enum, for example `workout_completed`.
* `source_type` — nullable string, for example `workout`, `nutrition_log`, `habit`, `community_comment`.
* `source_id` — nullable ID of the source record.
* `event_timestamp_utc` — datetime stored in UTC.
* `user_timezone` — string, for example `Pacific/Auckland`.
* `local_event_date` — date calculated from the user timezone.
* `metadata` — JSON for extra data such as workout ID, habit ID, lifted volume, or challenge ID.
* `created_at`
* `updated_at`

### Constraints and indexes

* Index on `user_id`.
* Index on `creator_app_id`.
* Index on `event_type`.
* Index on `local_event_date`.
* Composite index on `user_id`, `creator_app_id`, `event_type`, and `local_event_date`.

I will allow multiple events on the same day to be stored, but only one qualifying event per streak type should count toward streak progress for that day.

---

## `streak_configs`

This table stores creator-level streak settings.

### Columns

* `id` — primary key.
* `creator_app_id` — foreign key.
* `streak_type` — enum/string: `workout`, `nutrition`, `habit`, `community`.
* `enabled` — boolean.
* `qualifying_event_type` — string, for example `workout_completed`.
* `minimum_threshold` — nullable integer.
* `threshold_unit` — nullable string, for example `meal_count`, `comment_count`, `completion_count`.
* `reward_config` — nullable JSON.
* `created_at`
* `updated_at`

### Constraints and indexes

* Unique constraint on `creator_app_id` and `streak_type`.
* Index on `enabled`.

---

## `user_streaks`

This table stores the current streak state for each user.

### Columns

* `id` — primary key.
* `user_id` — foreign key.
* `creator_app_id` — foreign key.
* `streak_type` — enum/string.
* `current_count` — integer, default `0`.
* `longest_count` — integer, default `0`.
* `last_completed_date` — nullable date.
* `last_evaluated_date` — nullable date.
* `status` — enum/string: `active`, `at_risk`, `broken`.
* `created_at`
* `updated_at`

### Constraints and indexes

* Unique constraint on `user_id`, `creator_app_id`, and `streak_type`.
* Index on `status`.
* Index on `last_completed_date`.

---

## `badge_definitions`

This table stores available badge templates.

### Columns

* `id` — primary key.
* `creator_app_id` — nullable foreign key. Null means platform default badge.
* `name` — string, for example `7-Day Consistency`.
* `description` — text.
* `badge_category` — enum/string: `consistency`, `milestone`, `challenge`, `certification`, `community`.
* `icon` — nullable string/path.
* `rule_type` — enum/string: `streak_count`, `event_count`, `challenge_completion`, `program_completion`, `manual`.
* `rule_config` — JSON for rule data.
* `enabled` — boolean.
* `created_at`
* `updated_at`

### Example `rule_config`

```json
{
  "streak_type": "workout",
  "required_count": 7
}
```

```json
{
  "event_type": "workout_completed",
  "required_count": 100
}
```

### Constraints and indexes

* Index on `creator_app_id`.
* Index on `badge_category`.
* Index on `enabled`.

---

## `user_badges`

This table stores badges earned by users.

### Columns

* `id` — primary key.
* `user_id` — foreign key.
* `creator_app_id` — foreign key.
* `badge_definition_id` — foreign key.
* `earned_at` — datetime.
* `awarded_by` — nullable foreign key to users, for manual awards.
* `revoked_at` — nullable datetime.
* `revoke_reason` — nullable text.
* `created_at`
* `updated_at`

### Constraints and indexes

* Unique constraint on `user_id`, `creator_app_id`, and `badge_definition_id`.
* Index on `earned_at`.
* Index on `revoked_at`.

The unique constraint prevents the same badge from being awarded twice in Phase 1.

---

## `streak_freezes`

This table stores streak freeze usage.

### Columns

* `id` — primary key.
* `user_id` — foreign key.
* `creator_app_id` — foreign key.
* `streak_type` — enum/string.
* `earned_at` — datetime.
* `used_at` — nullable datetime.
* `applied_to_date` — nullable date.
* `created_at`
* `updated_at`

### Constraints and indexes

* Index on `user_id`, `creator_app_id`, and `streak_type`.
* Index on `used_at`.

For Phase 1, I will assume users can have 1 freeze per 30 days and unused freezes do not stack.

---

## `notification_triggers`

This table stores notification-worthy moments created by the streak and badge system.

### Columns

* `id` — primary key.
* `user_id` — foreign key.
* `creator_app_id` — foreign key.
* `trigger_type` — enum/string: `streak_at_risk`, `streak_broken`, `streak_milestone_reached`, `badge_earned`.
* `payload` — JSON.
* `scheduled_for` — nullable datetime.
* `sent_at` — nullable datetime.
* `created_at`
* `updated_at`

### Constraints and indexes

* Index on `user_id`.
* Index on `creator_app_id`.
* Index on `trigger_type`.
* Index on `sent_at`.

The streak system will create trigger records, but actual push delivery can be handled by the existing notification system.

---

## Services

## `ActivityEventService`

Responsible for recording activity events.

### Responsibilities

* Accept user action details.
* Store the event timestamp in UTC.
* Store the user timezone.
* Calculate the local event date.
* Store metadata.
* Reject future-dated events.
* Avoid giving duplicate streak credit for the same streak type and local date.

### Example usage

When a user completes a workout, the workout feature calls:

```php
ActivityEventService::record(
    user: $user,
    creatorApp: $creatorApp,
    eventType: 'workout_completed',
    sourceType: 'workout',
    sourceId: $workout->id,
    metadata: []
);
```

---

## `StreakEvaluationService`

Responsible for calculating and updating streaks.

### Responsibilities

* Load enabled streak configs for a creator app.
* Find qualifying events by user, streak type, and local date.
* Start a new streak when a user completes a qualifying action.
* Increment streaks for consecutive days.
* Reset streaks after missed days.
* Update longest streak.
* Mark streaks as `active`, `at_risk`, or `broken`.
* Apply streak freezes if available.
* Trigger badge evaluation when milestones are reached.
* Create notification trigger records.

### Consecutive day logic

If the user has a qualifying event today:

* If `last_completed_date` was yesterday, increment the streak.
* If `last_completed_date` was today, do not increment again.
* If `last_completed_date` was before yesterday, restart the streak at 1.

If the user does not have a qualifying event today:

* If the local day is not over, mark the streak as `at_risk`.
* If the user missed the required day, mark the streak as `broken` unless a freeze is applied.

---

## `BadgeEvaluationService`

Responsible for awarding badges.

### Responsibilities

* Load enabled badge definitions.
* Check whether a user meets each badge rule.
* Award badges automatically.
* Prevent duplicate badge awards.
* Create `badge_earned` notification triggers.
* Support manually awarded badges.

### Badge examples

Consistency badges:

* 7-Day Consistency.
* 30-Day Machine.
* 90-Day Elite.

Milestone badges:

* 100 Workouts Completed.
* Total Weight Lifted badge.
* Nutrition logging milestone.
* Habit completion milestone.

Challenge badges:

* 5-Day Challenge Finisher.
* Transformation Champion.

Certification badges:

* Program Completion Certificate.
* Phase Completion Badge.

Community badges:

* Top Contributor.
* 50 Comments Posted.
* Accountability Leader.

---

## `NotificationTriggerService`

Responsible for creating notification trigger records.

### Responsibilities

* Create `streak_at_risk` triggers.
* Create `streak_broken` triggers.
* Create `streak_milestone_reached` triggers.
* Create `badge_earned` triggers.
* Avoid duplicate triggers for the same user, event, and day.
* Leave actual push/email/in-app delivery to the existing notification system.

---

## Endpoints / routes

The exact route names can change depending on existing project conventions, but these are the routes I plan to support.

---

## End-user routes

### `GET /api/streaks`

Returns the authenticated user’s streak summary.

### Response includes

* Current streaks.
* Longest streaks.
* Streak status.
* Last completed date.
* Next milestone.
* Progress toward next milestone.
* Available freeze information.

---

### `GET /api/badges`

Returns the authenticated user’s badges.

### Response includes

* Earned badges.
* Earned date.
* Badge category.
* Badge description.
* Badge icon.
* Locked or available badges if needed.

---

### `POST /api/events`

Records a qualifying activity event.

This may be an internal route or service call from existing features.

### Request example

```json
{
  "event_type": "workout_completed",
  "source_type": "workout",
  "source_id": 123,
  "timezone": "Pacific/Auckland",
  "metadata": {}
}
```

### Response includes

* Created event.
* Updated streak summary if applicable.
* Newly earned badges if applicable.

---

### `POST /api/streaks/{streak}/freeze`

Uses a streak freeze for a missed day.

### Response includes

* Updated streak.
* Freeze usage record.
* Remaining freeze availability.

---

## Creator/admin routes

### `GET /api/creator/streak-config`

Returns the creator’s streak settings.

---

### `PATCH /api/creator/streak-config`

Updates creator streak settings.

### Can update

* Enabled/disabled state.
* Qualifying event type.
* Minimum threshold.
* Reward config.

---

### `GET /api/creator/badge-config`

Returns badge templates and enabled badge settings.

---

### `PATCH /api/creator/badge-config`

Updates which badges are enabled for the creator app.

---

### `POST /api/creator/users/{user}/badges`

Manually awards a badge to a user.

### Request example

```json
{
  "badge_definition_id": 5
}
```

---

### `DELETE /api/creator/users/{user}/badges/{badge}`

Revokes a badge from a user.

### Request example

```json
{
  "reason": "Awarded by mistake"
}
```

---

### `GET /api/creator/engagement`

Returns creator engagement information.

### Response includes

* Users with active streaks.
* Users at risk of breaking streaks.
* Recently broken streaks.
* Recently earned badges.
* Top engaged users.

---

## Scheduled jobs

## Daily streak evaluation job

This job will run on a schedule and evaluate streak states.

### Responsibilities

* Find active users and enabled streak configs.
* Evaluate streak completion by local date.
* Update current streak and longest streak.
* Mark streaks as active, at risk, or broken.
* Apply freeze logic if available.
* Create notification triggers.
* Run badge evaluation.

### Command example

```bash
php artisan streaks:evaluate
```

---

## Badge evaluation job

This job can run after streak evaluation or be called directly from the streak service.

### Responsibilities

* Check milestone badges.
* Check event-count badges.
* Check challenge badges.
* Check program completion badges.
* Award badges.
* Create badge notification triggers.

### Command example

```bash
php artisan badges:evaluate
```

---

## Notification trigger job

This job passes trigger records to the existing notification system.

### Responsibilities

* Find unsent notification triggers.
* Send or enqueue notifications.
* Mark triggers as sent.
* Respect user notification preferences if available.

### Command example

```bash
php artisan notification-triggers:process
```

---

## Component structure

If the frontend is included in this task, I would structure it like this.

## End-user components

### `StreakWidget`

Displays:

* Current streak.
* Longest streak.
* Streak status.
* Next milestone.
* Circular progress ring.
* Freeze availability.

### `BadgeGrid`

Displays:

* Earned badges.
* Badge icons.
* Badge names.
* Earned dates.
* Locked badges if needed.

### `ProfileBadgeDisplay`

Displays:

* Featured badge.
* Earned badges on user profile.
* Public badge visibility only.

### `CommunityUserBadge`

Displays:

* Small badge next to username in community areas.
* Respects privacy settings.

---

## Creator/admin components

### `StreakConfigPanel`

Allows creators to:

* Enable/disable streak types.
* Set simple thresholds.
* View qualifying action rules.

### `BadgeConfigPanel`

Allows creators to:

* Enable/disable badge templates.
* View badge rules.
* Attach simple rewards.

### `ManualBadgeAwardForm`

Allows creators/admins to:

* Select a user.
* Select a badge.
* Award the badge.
* Revoke a badge if needed.

### `EngagementDashboard`

Displays:

* Top engaged members.
* Active streak users.
* At-risk users.
* Recently broken streaks.
* Recent badge awards.

---

## Libraries / packages

The exact stack is not specified in the PRD, so I am assuming this will be added to the existing MacroActive app stack.

If using Laravel:

* Laravel migrations for schema changes.
* Laravel Eloquent models for activity events, streaks, badges, and configs.
* Laravel Form Requests for endpoint validation.
* Laravel Policies or Gates for creator/admin permissions.
* Laravel Scheduler for daily streak evaluation jobs.
* Laravel Queues for notification trigger processing.
* Laravel JSON Resources for consistent API responses.
* PHPUnit/Pest for feature and service tests.

If using a React or Next.js frontend:

* React components for dashboard widgets and creator control panels.
* Existing API client or fetch layer.
* Existing UI component library if the app has one.
* A circular progress component or SVG-based progress ring for the streak widget.

I will avoid adding unnecessary third-party packages unless the existing project already uses them.

---

## Validation rules

## Activity event validation

* `event_type` is required.
* `event_type` must be one of the supported event types.
* `timezone` is required.
* `timezone` must be a valid timezone.
* `source_type` is optional.
* `source_id` is optional.
* `metadata` must be valid JSON if provided.
* Future-dated events should not count.

## Creator config validation

* `streak_type` must be one of the supported streak types.
* `enabled` must be boolean.
* `minimum_threshold` must be numeric if provided.
* `qualifying_event_type` must match a supported event type.
* Creator can only update their own app config.

## Badge validation

* Badge definition must exist.
* Badge must be enabled.
* User cannot receive the same badge twice.
* Creator can only award badges within their own app.
* Revoked badges should not appear as active earned badges.

---

## Edge cases

## Duplicate events

A user may complete multiple workouts or post multiple comments in one day.

The system can store multiple events, but only one qualifying event should count toward a daily streak for that streak type.

---

## User completes action near midnight

The system must use the user’s local date, not just UTC.

Example:

A user in New Zealand completes a workout at 11:30pm local time. This should count for that New Zealand calendar day.

---

## User changes timezone

The event should store the timezone used at the time the event happened.

For Phase 1, existing events should not be recalculated if the user later changes timezone.

---

## Missed day

If a user misses a required day, the streak should be marked broken unless a streak freeze is available and applied.

The user’s previously earned badges should remain.

---

## Streak freeze limits

A user should not be able to use more than 1 freeze in a 30-day period.

For Phase 1, unused freezes do not stack.

---

## Badge already earned

If a badge has already been earned and has not been revoked, the system should not award it again.

---

## Badge revoked

If a badge is revoked, it should no longer appear as an active earned badge.

The record should remain in the database for audit/history.

---

## Creator disables a streak type

If a creator disables a streak type, the system should stop evaluating that streak type going forward.

Existing streak records can remain stored for history, but they should not be shown as active unless re-enabled.

---

## Creator changes badge rules

Changing badge rules after users have already earned badges could cause confusion.

For Phase 1, previously earned badges should remain earned. Rule changes should affect future awards only.

---

## Deleted or invalid source activity

If a workout, nutrition log, comment, or habit completion is deleted or invalidated, it should not create new progress.

If it already created progress, advanced recalculation may be future work unless required.

---

## Community spam

Community badges should not be farmable by spam.

For Phase 1:

* Rate-limit community badge progress.
* Count only a limited number of comments per day.
* Do not count deleted comments.

---

## Notification fatigue

The system should avoid creating duplicate notification triggers for the same user, streak type, and day.

For Phase 1, triggers should be limited to important moments:

* Streak at risk.
* Streak broken.
* Milestone reached.
* Badge earned.

---

## Privacy

Badges may appear on profile and community surfaces.

For Phase 1, I will assume badges are visible by default inside the creator app unless privacy settings already exist.

Leaderboard privacy, alias support, and opt-in visibility will be prepared for Phase 2 but not fully implemented in Phase 1.

---

## Security and authorization

* Users can only view their own streaks and badges.
* Creators can only manage configs for their own creator app.
* Creators can only manually award or revoke badges for users in their own creator app.
* Admins may have global access if the existing app supports admin roles.
* All write routes should require authentication.
* Creator/admin routes should use policy checks.

---

## Testing approach

I will write tests before implementation where possible.

## Event tracking tests

* User can record a valid activity event.
* Event stores UTC timestamp.
* Event stores user timezone.
* Event stores local event date.
* Future-dated events do not count.
* Duplicate same-day events do not create duplicate streak credit.

## Streak tests

* First qualifying event starts a streak.
* Consecutive daily events increment a streak.
* Missing a day breaks a streak.
* Longest streak updates correctly.
* Same-day repeated events do not increment the streak twice.
* Streak uses user local date.
* Streak status can become active, at risk, or broken.

## Streak freeze tests

* User can use an available freeze.
* Freeze preserves the streak.
* Freeze records the applied missed date.
* User cannot use more than 1 freeze per 30 days.
* Freeze cannot be applied to future dates.

## Badge tests

* 7-day badge is awarded at the correct streak count.
* 30-day badge is awarded at the correct streak count.
* Milestone badge is awarded from event totals.
* Program completion badge can be awarded.
* Badge is not awarded twice.
* Badge remains earned after streak breaks.
* Creator can manually award a badge.
* Creator can revoke a badge.

## Creator config tests

* Creator can enable a streak type.
* Creator can disable a streak type.
* Creator can update a threshold.
* Creator can enable a badge template.
* Creator cannot manage another creator’s config.
* Non-creator cannot access creator config routes.

## API response tests

* User can fetch streak summary.
* User can fetch badge list.
* Streak response includes current streak.
* Streak response includes longest streak.
* Streak response includes next milestone.
* Badge response includes earned badges.
* Revoked badges are excluded from active badge list.

## Notification trigger tests

* At-risk streak creates a notification trigger.
* Broken streak creates a notification trigger.
* Milestone reached creates a notification trigger.
* Badge earned creates a notification trigger.
* Duplicate notification triggers are not created for the same event.

---

## Rollout approach

Phase 1 should be safe to pilot with a small group of creators.

### Step 1

Create database tables and models.

### Step 2

Build event tracking service.

### Step 3

Build streak evaluation service.

### Step 4

Build badge evaluation service.

### Step 5

Add end-user API responses for dashboard and badges.

### Step 6

Add creator configuration endpoints.

### Step 7

Add scheduled jobs.

### Step 8

Add notification trigger records.

### Step 9

Add frontend dashboard and creator control panel components if frontend is included.

### Step 10

Test with pilot creator data before wider rollout.

---

## Future work not included in Phase 1

The following features are mentioned in the PRD but should not be built in the first phase:

* Weekly workout leaderboard.
* Monthly streak leaderboard.
* Volume lifted leaderboard.
* Challenge leaderboard.
* Nickname/alias leaderboard support.
* Opt-in leaderboard visibility.
* Cross-app identity layer.
* MacroActive universal status level.
* Inter-creator seasonal competitions.
* Platform-wide challenges.
* Advanced data warehouse retention correlation.
* Advanced anti-cheat and fraud detection.
* Full creator custom rule builder.

---

## Summary

I will build the Streaks & Badges System as an event-based gamification layer.

User actions will create activity events. Streak evaluation will use those events to update current streaks, longest streaks, milestone progress, and streak status. Badge evaluation will award permanent badges when users meet consistency, milestone, challenge, certification, or community rules.

Creators will get simple Phase 1 controls to enable streaks, configure basic thresholds, enable badges, and manually award badges. Users will see streak progress and earned badges in the dashboard, profile, and community surfaces.

The implementation will focus on a clean Phase 1 foundation while leaving leaderboards, cross-app identity, and advanced analytics for future phases.
 