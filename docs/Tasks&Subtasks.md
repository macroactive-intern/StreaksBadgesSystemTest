Streaks & Badges System — Tasks and Subtasks
1. Project setup and scope confirmation
1.1 Confirm Phase 1 scope
Treat streaks and badges as Phase 1.
Treat leaderboards as Phase 2.
Treat cross-app identity as future work.
Treat advanced analytics/data warehouse work as future work unless required.
1.2 Define supported Phase 1 streak types
Workout completion streak.
Nutrition log streak.
Habit completion streak.
Community participation streak.
1.3 Define default milestones
3 days.
7 days.
14 days.
30 days.
60 days.
90 days.
180 days.
365 days.
2. Data model / database design
2.1 Create activity events table

Tracks all user actions that can qualify for streaks or badges.

Subtasks:

Add id.
Add user_id.
Add creator_app_id or creator_id.
Add event_type.
Add event_timestamp_utc.
Add user_timezone.
Add local_event_date.
Add optional metadata JSON.
Add source_type, such as workout, nutrition, habit, community.
Add source_id if linked to another record.
Add timestamps.
2.2 Create streak types / streak config table

Stores enabled streak rules per creator app.

Subtasks:

Add id.
Add creator_app_id.
Add streak_type.
Add enabled.
Add qualifying_event_type.
Add minimum_threshold.
Add optional reward_config.
Add timestamps.
2.3 Create user streaks table

Stores current streak state per user, creator app, and streak type.

Subtasks:

Add id.
Add user_id.
Add creator_app_id.
Add streak_type.
Add current_count.
Add longest_count.
Add last_completed_date.
Add last_evaluated_date.
Add status, such as active, at_risk, broken.
Add timestamps.
Add unique constraint on user_id, creator_app_id, and streak_type.
2.4 Create badge definitions table

Stores badge templates and creator-enabled badges.

Subtasks:

Add id.
Add creator_app_id, nullable if platform default.
Add name.
Add description.
Add badge_category.
Add icon.
Add rule_type, such as streak, milestone, challenge, certification, community.
Add rule_config JSON.
Add enabled.
Add timestamps.
2.5 Create user badges table

Stores badges earned by users.

Subtasks:

Add id.
Add user_id.
Add creator_app_id.
Add badge_definition_id.
Add earned_at.
Add awarded_by, nullable for automatic awards.
Add revoked_at, nullable.
Add revoke_reason, nullable.
Add timestamps.
Add unique constraint so a user cannot earn the same badge twice unless repeatable badges are later allowed.
2.6 Create streak freezes table

Tracks freeze availability and usage.

Subtasks:

Add id.
Add user_id.
Add creator_app_id.
Add streak_type.
Add earned_at.
Add used_at.
Add applied_to_date.
Add timestamps.
2.7 Create notification triggers table

Stores notification-worthy moments.

Subtasks:

Add id.
Add user_id.
Add creator_app_id.
Add trigger_type.
Add payload JSON.
Add scheduled_for.
Add sent_at, nullable.
Add timestamps.
3. Event tracking layer
3.1 Build event recording service

Subtasks:

Create a service for recording user activity events.
Accept event type, user, creator app, timestamp, timezone, and metadata.
Convert UTC timestamp into local event date.
Prevent future-dated events from counting.
Prevent duplicate streak credit for the same user, streak type, and date.
3.2 Add supported event types

Subtasks:

workout_completed.
nutrition_logged.
habit_completed.
community_comment_posted.
program_completed.
challenge_completed.
3.3 Add metadata support

Subtasks:

Store workout ID.
Store meal/log ID.
Store habit ID.
Store comment/post ID.
Store challenge ID.
Store volume lifted if needed for milestone badges.
4. Streak calculation logic
4.1 Build streak evaluation service

Subtasks:

Load enabled streak configs for creator app.
Find qualifying user events.
Check whether the user completed the action for the local date.
Update current streak count.
Update longest streak count.
Update streak status.
Detect at-risk streaks.
Detect broken streaks.
4.2 Handle consecutive day logic

Subtasks:

If completed today and yesterday was completed, increment streak.
If completed today but previous day was missed, restart streak.
If no completion today but day is not over, mark at risk.
If required day was missed, mark broken.
Use local calendar dates, not only UTC dates.
4.3 Handle streak milestones

Subtasks:

Check if streak reached 3 days.
Check if streak reached 7 days.
Check if streak reached 14 days.
Check if streak reached 30 days.
Check if streak reached 60 days.
Check if streak reached 90 days.
Check if streak reached 180 days.
Check if streak reached 365 days.
Trigger badge checks when milestones are reached.
4.4 Handle streak freezes

Subtasks:

Check whether user has an available freeze.
Apply freeze to a missed date if allowed.
Preserve current streak when freeze is used.
Record freeze usage.
Prevent more than 1 freeze per 30 days.
Decide whether unused freezes stack or not. For Phase 1, assume they do not stack.
5. Badge awarding logic
5.1 Build badge evaluation service

Subtasks:

Load enabled badge definitions.
Match user activity and streak state against badge rules.
Award badges automatically when rules are met.
Prevent duplicate badge awards.
Store earned_at.
5.2 Add consistency badges

Subtasks:

7-Day Consistency.
30-Day Machine.
90-Day Elite.
5.3 Add milestone badges

Subtasks:

100 Workouts Completed.
Total weight lifted badge.
Nutrition logging milestone badge.
Habit completion milestone badge.
5.4 Add challenge badges

Subtasks:

5-Day Challenge Finisher.
Transformation Champion.
Challenge completion badge.
5.5 Add certification badges

Subtasks:

Program Completion Certificate.
Phase Completion Badge.
5.6 Add community status badges

Subtasks:

Top Contributor.
50 Comments Posted.
Accountability Leader.
5.7 Add manual badge awarding

Subtasks:

Allow creator/admin to award a badge manually.
Store who awarded the badge.
Prevent duplicate manual awards.
Allow manual revoke for mistakes or cheating.
6. Creator configuration
6.1 Create creator streak settings

Subtasks:

Enable/disable streak types.
Choose qualifying event type.
Set minimum threshold.
Attach simple rewards.
View current streak settings.
6.2 Create creator badge settings

Subtasks:

Enable/disable badge templates.
View badge definitions.
Attach badge rewards.
Manually award badges.
Revoke badges if needed.
6.3 Create creator engagement view

Subtasks:

Show top engaged members.
Show users with active streaks.
Show users at risk of breaking streaks.
Show recently broken streaks.
Show recent badge earns.
7. End-user dashboard UI
7.1 Build “Your Streak” widget

Subtasks:

Display current streak.
Display longest streak.
Display next milestone.
Display progress toward next milestone.
Show circular progress ring.
Show streak status: active, at risk, broken.
7.2 Build badge display section

Subtasks:

Show earned badges.
Show badge names.
Show badge descriptions.
Show earned date.
Show locked/unearned badges if required.
7.3 Build profile badge display

Subtasks:

Show badges on user profile.
Show selected or featured badge.
Respect user privacy settings if added.
7.4 Build community badge display

Subtasks:

Show badge next to username in chat/community areas.
Show only public/visible badges.
Avoid overcrowding the UI.
8. API endpoints
End-user endpoints
8.1 Get user streak summary

Example:

GET /api/streaks

Returns:

Current streaks.
Longest streaks.
Next milestones.
Streak status.
Available freeze info.
8.2 Get user badges

Example:

GET /api/badges

Returns:

Earned badges.
Available badges.
Locked badges if needed.
8.3 Record qualifying event

Example:

POST /api/events

Used internally or by app features to record completion events.

8.4 Use streak freeze

Example:

POST /api/streaks/{streak}/freeze

Uses an available streak freeze.

Creator/admin endpoints
8.5 Get creator streak config
GET /api/creator/streak-config
8.6 Update creator streak config
PATCH /api/creator/streak-config
8.7 Get creator badge config
GET /api/creator/badge-config
8.8 Update creator badge config
PATCH /api/creator/badge-config
8.9 Manually award badge
POST /api/creator/users/{user}/badges
8.10 Revoke badge
DELETE /api/creator/users/{user}/badges/{badge}
8.11 Get engagement summary
GET /api/creator/engagement

Returns:

Top engaged members.
At-risk users.
Recently broken streaks.
Recent badge awards.
9. Scheduled jobs
9.1 Daily streak evaluation job

Subtasks:

Run once per day per timezone window.
Evaluate active streaks.
Mark streaks as active, at risk, or broken.
Apply freezes if automatic.
Trigger badge evaluation.
Create notification trigger records.
9.2 Badge evaluation job

Subtasks:

Check milestone progress.
Award earned badges.
Prevent duplicate badge awards.
Create badge-earned notification triggers.
9.3 Notification trigger job

Subtasks:

Find unsent notification triggers.
Pass them to existing notification system.
Mark trigger as sent.
Respect notification preferences if available.
10. Notification triggers
10.1 Streak at-risk trigger

Subtasks:

Detect when user has not completed today’s action.
Create reminder trigger before local day ends.
Avoid sending too many reminders.
10.2 Streak broken trigger

Subtasks:

Detect missed day.
Create streak broken trigger.
Include reactivation messaging.
10.3 Milestone reached trigger

Subtasks:

Detect milestone achievement.
Create milestone notification trigger.
Include current streak and badge info.
10.4 Badge earned trigger

Subtasks:

Detect newly awarded badge.
Create badge-earned notification trigger.
Include badge name and description.
11. Anti-cheat and validation
11.1 Prevent duplicate credit

Subtasks:

Do not count multiple events for the same user, streak type, and local date.
Allow multiple events to be stored, but only one should count for streak progress.
11.2 Prevent future-dated events

Subtasks:

Reject future local dates.
Use server time as source of truth.
Store device time only as metadata if needed.
11.3 Validate event source

Subtasks:

Ignore deleted workouts/logs/comments.
Ignore invalid or reversed actions.
Prevent manual backfill unless admin-approved.
11.4 Rate-limit community badges

Subtasks:

Limit badge progress from spam comments.
Only count meaningful community actions if possible.
Prevent repeated identical comments from quickly farming badges.
12. Privacy and visibility
12.1 Add badge visibility rules

Subtasks:

Decide whether badges are public by default.
Allow users to hide badges if required.
Allow users to choose featured badge if required.
12.2 Prepare leaderboard privacy for Phase 2

Subtasks:

Add optional nickname/alias field if needed.
Add opt-in visibility flag.
Do not expose leaderboard data until Phase 2.
13. Analytics and success metrics
13.1 Track engagement events

Subtasks:

Track streak started.
Track streak continued.
Track streak broken.
Track badge earned.
Track freeze used.
Track notification trigger created.
13.2 Track retention-related metrics

Subtasks:

Daily active users.
Habit completion rate.
Community participation rate.
Percentage of users with active streaks.
Badge earn rate.
13.3 Prepare pilot reporting

Subtasks:

Support 10 pilot creators.
Compare pilot vs control cohorts.
Measure Day-7 retention.
Measure Day-30 retention.
Measure first-month churn delta.
14. Testing tasks
14.1 Event tracking tests

Subtasks:

Can record valid activity event.
Cannot count future-dated event.
Stores user timezone.
Stores local event date.
Prevents duplicate streak credit.
14.2 Streak calculation tests

Subtasks:

Starts streak after first qualifying event.
Increments streak after consecutive days.
Resets streak after missed day.
Updates longest streak.
Marks streak as at risk.
Marks streak as broken.
Handles user timezone correctly.
14.3 Streak freeze tests

Subtasks:

User can use available freeze.
Freeze preserves streak.
Freeze records applied date.
User cannot use more than allowed.
Freeze cannot be used for invalid/future date.
14.4 Badge awarding tests

Subtasks:

Awards 7-day badge.
Awards 30-day badge.
Awards milestone badge.
Awards program completion badge.
Does not award same badge twice.
Keeps badge after streak breaks.
Allows manual award.
Allows manual revoke.
14.5 Creator configuration tests

Subtasks:

Creator can enable streak type.
Creator can disable streak type.
Creator can update threshold.
Creator can enable badge template.
Creator can manually award badge.
Non-creator cannot manage config.
14.6 Dashboard/API tests

Subtasks:

User can fetch streak summary.
User can fetch earned badges.
Dashboard response includes next milestone.
Dashboard response includes progress value.
Private/hidden badges are not exposed if privacy is enabled.
14.7 Notification trigger tests

Subtasks:

Creates at-risk trigger.
Creates streak-broken trigger.
Creates milestone trigger.
Creates badge-earned trigger.
Does not create duplicate triggers for same event.
15. Phase 2 / future tasks

These should be noted but not built in Phase 1.

15.1 Leaderboards

Subtasks:

Weekly workout leaderboard.
Monthly streak leaderboard.
Volume lifted leaderboard.
Challenge leaderboard.
Nickname/alias support.
Opt-in visibility.
15.2 Cross-app identity

Subtasks:

Universal MacroActive status level.
Cross-app streak identity.
Inter-creator competitions.
Platform-wide challenges.
15.3 Advanced analytics

Subtasks:

Data warehouse integration.
LTV correlation.
NDR correlation.
Cohort retention dashboards.
CAC efficiency reporting.
15.4 Advanced anti-cheat

Subtasks:

Detect suspicious timezone switching.
Detect unrealistic workout volume.
Detect comment spam patterns.
Detect suspicious backfilled activity.
Add moderation review queue.