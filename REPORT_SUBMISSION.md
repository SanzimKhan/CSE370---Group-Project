# Report Submission

Students:

- ID : 21301114, Name : Md Sanzim Rahman Khan
- ID : 22201166, Name : MD Ali Razin
- ID : 22201655, Name : MD INZAMAMUL HAQUE

---

**Frontend Development**

Brief summary: Frontend work includes UI pages for client/freelancer/community, styling, and integration of analytics/search UI. Screenshots can be added under each person's section if required.

Contribution of ID : 21301114, Name : Md Sanzim Rahman Khan
- Forum & community UI, message views and profile pages: [community/forum.php](community/forum.php), [community/forum_view.php](community/forum_view.php), [community/messages.php](community/messages.php), [community/messages_inbox.php](community/messages_inbox.php), [community/profile.php](community/profile.php), [community/rate_user.php](community/rate_user.php)
- Various client/freelancer pages and site-wide includes affecting UI: [client/analytics.php](client/analytics.php), [freelancer/analytics.php](freelancer/analytics.php), [includes/header.php](includes/header.php), [includes/footer.php](includes/footer.php), [includes/search.php](includes/search.php), [search.php](search.php)

Contribution of ID : 22201166, Name : MD Ali Razin
- Worked on UI integration for credit and profile views and messaging pages: [client/create_gig.php](client/create_gig.php), [client/my_gigs.php](client/my_gigs.php), [community/messages.php](community/messages.php), [community/profile.php](community/profile.php), [profile.php](profile.php), [public_profile.php](public_profile.php)
- Minor styling and layout updates: [assets/css/styles.css](assets/css/styles.css)

Contribution of ID : 22201655, Name : MD INZAMAMUL HAQUE
- Analytics UI components and integration on client/freelancer pages: [client/analytics.php](client/analytics.php), [freelancer/analytics.php](freelancer/analytics.php), and related docs/specs in `.kiro/specs/forum-feature-completion/`


---

**Backend Development**

Brief summary: Backend work covers authentication, database migrations and setup scripts, credit system, messaging persistence, analytics backend hooks, and helper modules.

Contribution of ID : 21301114, Name : Md Sanz Rahman Khan
- Forum and sample-data setup and related DB scripts: [database/setup_forum.php](database/setup_forum.php), [database/load_sample_data.php](database/load_sample_data.php), [database/sample_data.sql](database/sample_data.sql)
- General site setup and core includes used by backend: [includes/config.php](includes/config.php), [includes/db.php](includes/db.php), [includes/helpers.php](includes/helpers.php)

Contribution of ID : 22201166, Name : MD Ali Razin
- Credit system, migrations and verification scripts: [database/migrate_credits.php](database/migrate_credits.php), [database/migration_add_credit_management.sql](database/migration_add_credit_management.sql), [database/setup_credits.php](database/setup_credits.php), [includes/credits.php](includes/credits.php)
- Messaging backend and helper scripts for seeding messages: [database/insert_dummy_messages.php](database/insert_dummy_messages.php), [community/messages.php](community/messages.php)
- Auth and signup improvements: [includes/auth.php](includes/auth.php), [signup.php](signup.php)

Contribution of ID : 22201655, Name : MD INZAMAMUL HAQUE
- Analytics back-end integration, docs and tests: [includes/analytics.php](includes/analytics.php), analytics docs ([ANALYTICS_IMPLEMENTATION.md](ANALYTICS_IMPLEMENTATION.md), [ANALYTICS_QUICK_START.md](ANALYTICS_QUICK_START.md)), and related test: [tests/test_analytics.php](tests/test_analytics.php)
- Some maintenance on credits/tests and header integration: [tests/test_credit_system.php](tests/test_credit_system.php), [includes/header.php](includes/header.php)


---

**Source Code Repository**

The project source code is available at:
- https://github.com/SanzimKhan/CSE370---Group-Project (master branch)


---

**Conclusion**

This report lists individual contributions extracted from the project's git commits. Frontend tasks focused on community pages, client/freelancer pages, and styling; backend tasks covered authentication, credit system, messaging, database migrations, and analytics hooks.


---

**References**

- Project repository: https://github.com/SanzimKhan/CSE370---Group-Project
- Commit log (local repository)







