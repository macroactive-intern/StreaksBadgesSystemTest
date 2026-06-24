# UNDERSTANDING.md

# Phase 1 — Understand

## What is the task asking me to build?

This task is asking me to build a platform-wide **Streaks & Badges System** for MacroActive apps.

The goal is to improve end-user retention by giving users daily habit reinforcement, progress identity, social status, and achievement rewards inside the app. Instead of the app only being a content delivery tool for workouts, nutrition, or coaching programs, the feature should make users feel like they are building an identity over time.

The main feature areas are:

* A streak system that tracks consecutive days of completing defined actions.
* A badge system that rewards consistency, milestones, challenges, certifications, and community participation.
* Dashboard UI that shows current streaks, longest streaks, next milestone progress, and earned badges.
* Creator controls so creators can enable streak types, define qualifying actions, launch challenges, award badges, and attach rewards.
* Notification and reminder logic tied to streak risk, milestones, and streak breaks.
* Event tracking and daily evaluation jobs to calculate streaks and badge eligibility.
* Basic anti-cheat and privacy considerations.
* Leaderboards are mentioned, but they appear to be Phase 2 rather than the first build.

The business reason behind the task is to reduce churn, especially in the first month, increase engagement, improve average subscription length, and support higher LTV.

---

## What inputs does it take?

The system will need several types of inputs.

### User action inputs

The system needs to receive tracked events from user behavior, such as:

* Workout completed.
* Meal or nutrition log submitted.
* Habit completed.
* Community action completed, such as posting a comment.
* Challenge completed.
* Program or phase completed.
* Weight lifted or workout volume recorded, if milestone badges use workout data.

These events are used to decide whether a streak should continue and whether a badge should be awarded.

### Creator configuration inputs

Creators should be able to configure parts of the system, including:

* Which streak types are enabled.
* What action qualifies for a streak.
* Minimum completion thresholds, such as logging at least one meal.
* Which badges are available.
* Which rewards are attached to milestones.
* Whether to launch streak-based challenges.
* Whether users can appear on leaderboards in a future phase.
* How privacy options are handled for community/status display.

### System and scheduled inputs

The system will also need:

* A daily cron or scheduled job to evaluate streaks.
* User timezone or app timezone rules.
* Push notification trigger events.
* Streak freeze availability, such as 1 freeze every 30 days.
* Badge eligibility rules.
* Retention and analytics events for reporting.

---

## What does it return or display?

### End-user dashboard

The user should see a new streak widget showing:

* Current streak.
* Longest streak.
* Next streak milestone.
* Earned badges.
* Progress toward the next badge or milestone.
* A circular ring or progress visual.

### Profile and community display

Badges may appear:

* On the user profile page.
* Next to usernames in community chat.
* In leaderboard views in Phase 2.
* In challenge or program completion areas.

### Creator control panel

Creators should be able to see and manage:

* Enabled streak types.
* Badge rules.
* Top engaged members.
* Challenge participants.
* Users who recently broke or are at risk of breaking streaks.
* Manual badge awarding.
* Reward unlocks tied to streak milestones.

### API or backend responses

The backend will likely need to return:

* A user’s current streak state.
* A user’s longest streak.
* A list of earned badges.
* Progress toward the next milestone.
* Creator streak/badge configuration.
* Badge definitions.
* Challenge badge progress.
* Engagement analytics for creators.
* Leaderboard data in Phase 2.

---

## What seemed unclear, contradictory, or under-specified?

### 1. The exact build scope is not fully defined

The PRD describes a large platform-wide product feature, but it does not clearly say what must be built first.

It mentions:

* Streaks.
* Badges.
* Creator configuration.
* Push notifications.
* Leaderboards.
* Analytics.
* Anti-cheat.
* Data warehouse integration.
* Cross-app identity as a long-term opportunity.

I am treating leaderboards, cross-app identity, and advanced analytics as future work unless the brief later says they are required in Phase 1.

---

### 2. The supported streak types are listed, but qualification rules are vague

The PRD says Phase 1 streak types include:

* Workout Completion Streak.
* Nutrition Log Streak.
* Habit Completion Streak.
* Community Participation Streak.

However, it does not fully define what counts as completion.

Examples:

* Does a workout count if the user starts it but does not finish it?
* Does a nutrition log count if the user logs one meal, all meals, or hits macro targets?
* Does a habit count if it is marked complete manually?
* Does community participation require a comment, reaction, post, or reply?
* Can creators define these rules differently per app?

The brief gives one example: “log at least 1 meal,” but not enough rules for every streak type.

---

### 3. Timezone behavior is not specified

Streaks depend heavily on calendar days, but the PRD does not say which timezone should be used.

Unclear cases:

* Should streak days use the end user’s local timezone?
* Should they use the creator/app timezone?
* What happens if a user travels across timezones?
* When exactly does a streak break?
* When should reminder notifications fire?

This is important because a user may complete an action near midnight.

---

### 4. Streak freeze rules need more detail

The PRD says there should be “1 per 30 days,” but it does not fully explain how freezes work.

Questions:

* Is the freeze applied automatically or manually?
* Does the user need to earn the freeze?
* Can unused freezes stack?
* Does the freeze protect yesterday only, or any missed day?
* Does using a freeze preserve the visible streak number?
* Does a freeze count as a completed day for milestone badges?

---

### 5. Badge awarding rules are not fully defined

Badge categories are listed, but exact logic is missing.

Examples:

* Should badges be awarded automatically when rules are met?
* Can creators override or revoke badges?
* Are badges permanent after being earned?
* Can users earn the same badge more than once?
* Are badges global across all creator apps or specific to one creator app?
* Are certification badges tied to program completion data?
* Are milestone badges based on lifetime totals or current subscription period?

---

### 6. Creator configurability could become very complex

Creators can enable/disable streak types, define qualifying actions, set thresholds, attach rewards, launch challenges, and award manual badges.

Unclear:

* How much control should creators have in Phase 1?
* Do creators create their own custom badges?
* Are badge icons uploaded by creators or chosen from templates?
* Are there platform default badges?
* Can creators edit rules after users already earned badges?
* What happens to existing progress if a creator disables a streak type?

---

### 7. Leaderboards are mentioned but appear to be Phase 2

The PRD includes leaderboards under “Phase 2 Integration,” so I assume leaderboards should not be required for the first implementation.

However, because badges and streaks may later feed leaderboards, the data model should not block future leaderboard support.

---

### 8. Privacy options are only briefly mentioned

The PRD says leaderboards need nickname/alias support and opt-in visibility, but it is not clear how privacy works for badges and community display.

Questions:

* Are badges public by default?
* Can users hide badges?
* Can users hide streaks?
* Can users appear in community chat without showing achievement status?
* Are leaderboards opt-in only?

---

### 9. Anti-cheat logic is mentioned but not defined

The technical requirements mention anti-cheat logic, but not what cheating looks like.

Possible concerns:

* Users manually marking fake habits complete.
* Users editing logged workouts after the fact.
* Users spamming community comments for badges.
* Users logging unrealistic workout volume.
* Users changing timezone/device date to preserve streaks.

The implementation needs at least basic protection, but the expected level is unclear.

---

### 10. Push notification rules are not detailed

The PRD mentions:

* Loss aversion messaging.
* Countdown reminders.
* Notifications tied to streak state.

Unclear:

* When should notifications be sent?
* How many reminders are allowed per day?
* Can creators customize notification copy?
* Can users opt out?
* How do we avoid notification fatigue?
* What should happen if push permissions are disabled?

---

### 11. Rewards and unlocks are not fully specified

The PRD gives an example:

“If user hits 60-day streak → Unlock bonus workout program.”

But it does not define the reward system.

Questions:

* Are rewards only informational badges?
* Can rewards unlock content?
* Can rewards unlock discounts?
* Are rewards configured per creator?
* What happens if a user loses the streak after unlocking a reward?
* Is the reward permanent?

---

### 12. Metrics are listed, but measurement implementation is unclear

The success metrics include:

* 30-Day Retention Rate.
* 90-Day Retention Rate.
* Average Subscription Length.
* Churn Reduction.
* LTV Increase.
* NDR Increase.
* DAU.
* Habit Completion Rate.
* Badge Earn Rate.

The PRD does not define where these metrics are calculated or how they should be connected to the feature.

This may require analytics events and data warehouse integration, but that could be outside the first build.

---

### 13. Cross-app identity is long-term, not immediate

The PRD mentions future platform opportunities like:

* Cross-app identity layer.
* MacroActive universal status level.
* Inter-creator seasonal competitions.
* Platform-wide challenges.

I assume these are not part of the immediate build. The current system should be designed so it could support them later, but not implement them now.

---

## Assumptions I am making

### Assumption 1: Phase 1 focuses on streaks and badges, not full leaderboards

Phase 1 should focus on streaks, badges, creator configuration, dashboard display, event tracking, scheduled evaluation, and basic notification triggers.

Leaderboards are labelled as Phase 2, so I will not treat them as required for the first build.

Phase 1 should include:

* Streak tracking.
* Badge awarding.
* Creator configuration.
* Dashboard streak widget.
* Badge display.
* Scheduled streak evaluation.
* Basic notification triggers.
* Basic event tracking.
* A data model that can support leaderboards later.

---

### Assumption 2: Streaks are tracked per user, per creator app, and per streak type

A user may subscribe to different creator apps, so streaks should not automatically be global across all MacroActive apps unless the long-term cross-app identity layer is built later.

For now, I assume a user’s workout streak in one creator app is separate from their streak in another creator app.

A streak record should likely be tied to:

* User.
* Creator app or creator account.
* Streak type.

Example:

* A user has a 12-day workout streak in Creator A’s app.
* The same user has a 3-day nutrition streak in Creator B’s app.

---

### Assumption 3: Badges are earned permanently unless manually revoked

Once a user earns a badge, it should stay on their profile even if their current streak later breaks.

The streak can reset, but the earned badge stays.

Example:

* Current streak: 0 days.
* Longest streak: 34 days.
* Earned badges: 7-Day Consistency, 30-Day Machine.

The only exception would be manual admin or creator revocation for cheating, mistakes, or moderation reasons.

---

### Assumption 4: Streaks use the end user’s local calendar day

Streaks should be based on the end user’s local day, not only UTC, because users think in terms of “today” and “yesterday.”

For example, if a user completes a workout at 11:30pm in New Zealand, it should count for that New Zealand calendar day.

The system should store enough data for both accurate streak logic and auditability, such as:

* Event timestamp in UTC.
* User timezone at the time of the event.
* Local event date.

---

### Assumption 5: Streak qualification is event-based

Streak and badge qualification should be based on standard tracked events.

Examples:

* `workout_completed`
* `nutrition_logged`
* `habit_completed`
* `community_comment_posted`
* `program_completed`

This is better than making the streak system read directly from many unrelated feature tables, because it gives the streak and badge system one consistent source of activity data.

---

### Assumption 6: Creators get default templates first

Because full creator customization could become complex, Phase 1 should provide default streak and badge templates with limited creator configuration.

Creators should be able to:

* Enable or disable streak types.
* Choose from default badge templates.
* Set simple thresholds.
* Attach simple rewards.
* Manually award badges.

A complex custom rule builder should be future work.

For example, Phase 1 should avoid advanced rules like:

“Give this badge only if a user completes 3 workouts, logs 5 meals, posts 2 comments, and lifts 10,000kg within 14 days.”

---

### Assumption 7: Notification implementation starts as trigger events

The backend should create internal notification trigger events such as:

* `streak_at_risk`
* `streak_broken`
* `streak_milestone_reached`
* `badge_earned`

The actual push notification delivery may depend on MacroActive’s existing notification infrastructure.

This keeps the streak system responsible for identifying notification-worthy moments, while the notification system handles delivery through push, email, or in-app messages.

---

### Assumption 8: Anti-cheat starts with basic validation

For Phase 1, anti-cheat should prevent obvious problems but not try to solve every possible abuse case.

Basic anti-cheat should include:

* Do not count duplicate completion events for the same user, streak type, and local date.
* Do not count future-dated events.
* Use server timestamps as the source of truth where possible.
* Ignore deleted, invalid, or reversed activity.
* Rate-limit community actions for badge progress.
* Prevent suspicious backfilling unless allowed by an admin.

More advanced cheat detection can be added later.

---

## Summary

In my own words, this task is asking me to design and build a gamification layer for MacroActive apps that rewards users for consistent actions over time.

The feature should make users feel progress, identity, and status through streaks, badges, milestones, and eventually leaderboards. The main goal is not just “fun gamification,” but better retention, lower churn, and increased LTV.

The biggest unclear areas are exact Phase 1 scope, streak qualification rules, timezone behavior, badge permanence, streak freeze behavior, creator customization depth, anti-cheat expectations, privacy rules, reward unlocks, and notification rules.
