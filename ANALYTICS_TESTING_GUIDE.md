# Analytics Testing Quick Guide

## Prerequisites
1. Ensure MySQL is running
2. Ensure analytics tables exist (run migration if needed)
3. Have at least one test user account

## Quick Test Checklist

### ✅ 1. Database Tables Check
```sql
-- Check if tables exist
SHOW TABLES LIKE 'Analytics_Activity';
SHOW TABLES LIKE 'Gig_Views';
SHOW TABLES LIKE 'User_Earnings';

-- Check table structure
DESCRIBE Analytics_Activity;
DESCRIBE Gig_Views;
DESCRIBE User_Earnings;
```

### ✅ 2. Login Tracking Test
**Steps:**
1. Open browser and navigate to the application
2. Log in with any user account
3. Run this query:
```sql
SELECT * FROM Analytics_Activity 
WHERE activity_type = 'login' 
ORDER BY created_at DESC 
LIMIT 5;
```
**Expected:** New row with your BRACU_ID and 'login' activity_type

### ✅ 3. Gig Creation Tracking Test
**Steps:**
1. Log in as a client (hiring mode)
2. Navigate to "Create Gig"
3. Fill out the form and submit
4. Run this query:
```sql
SELECT aa.*, g.LIST_OF_GIGS 
FROM Analytics_Activity aa
LEFT JOIN Gigs g ON aa.gig_id = g.GID
WHERE aa.activity_type = 'gig_create' 
ORDER BY aa.created_at DESC 
LIMIT 5;
```
**Expected:** New row with 'gig_create' activity_type and gig_id populated

### ✅ 4. Gig View Tracking Test
**Steps:**
1. Log in as a freelancer (working mode)
2. Navigate to "Marketplace"
3. View the list of gigs
4. Run this query:
```sql
SELECT gv.*, g.LIST_OF_GIGS, u.full_name as viewer_name
FROM Gig_Views gv
JOIN Gigs g ON gv.GID = g.GID
LEFT JOIN User u ON gv.BRACU_ID = u.BRACU_ID
ORDER BY gv.viewed_at DESC 
LIMIT 10;
```
**Expected:** New rows for each gig displayed in the marketplace

### ✅ 5. Gig Application Tracking Test
**Steps:**
1. As a freelancer, accept a gig from the marketplace
2. Run this query:
```sql
SELECT aa.*, g.LIST_OF_GIGS 
FROM Analytics_Activity aa
LEFT JOIN Gigs g ON aa.gig_id = g.GID
WHERE aa.activity_type = 'gig_apply' 
ORDER BY aa.created_at DESC 
LIMIT 5;
```
**Expected:** New row with 'gig_apply' activity_type

### ✅ 6. Profile View Tracking Test
**Steps:**
1. Log in as any user
2. Navigate to another user's public profile (e.g., `/public_profile.php?id=20101001`)
3. Run this query:
```sql
SELECT aa.*, 
       u1.full_name as viewer_name,
       u2.full_name as profile_owner_name
FROM Analytics_Activity aa
JOIN User u1 ON aa.BRACU_ID = u1.BRACU_ID
JOIN User u2 ON aa.target_user = u2.BRACU_ID
WHERE aa.activity_type = 'profile_view' 
ORDER BY aa.created_at DESC 
LIMIT 5;
```
**Expected:** New row with 'profile_view' activity_type and target_user populated

### ✅ 7. Message Tracking Test
**Steps:**
1. Log in and send a message to another user
2. Run this query:
```sql
SELECT aa.*, 
       u1.full_name as sender_name,
       u2.full_name as recipient_name
FROM Analytics_Activity aa
JOIN User u1 ON aa.BRACU_ID = u1.BRACU_ID
LEFT JOIN User u2 ON aa.target_user = u2.BRACU_ID
WHERE aa.activity_type = 'message_send' 
ORDER BY aa.created_at DESC 
LIMIT 5;
```
**Expected:** New row with 'message_send' activity_type

### ✅ 8. Client Analytics Dashboard Test
**Steps:**
1. Log in as a client (hiring mode)
2. Navigate to `/client/analytics.php`
3. Verify the following sections display:
   - ✓ Total Logins
   - ✓ Profile Views
   - ✓ Gigs Posted
   - ✓ Completed Gigs
   - ✓ Total Spent
   - ✓ Last Activity
   - ✓ Spending Overview
   - ✓ Spending by Category
   - ✓ Gig Status Overview

**Expected:** All metrics display without errors

### ✅ 9. Freelancer Analytics Dashboard Test
**Steps:**
1. Log in as a freelancer (working mode)
2. Navigate to `/freelancer/analytics.php`
3. Verify the following sections display:
   - ✓ Total Logins
   - ✓ Gig Views
   - ✓ Gigs Created
   - ✓ Gigs Applied
   - ✓ Total Earnings
   - ✓ Pending Earnings
   - ✓ Completion Rate
   - ✓ Last Activity
   - ✓ Gig Performance
   - ✓ Activity Breakdown

**Expected:** All metrics display without errors

### ✅ 10. Analytics Class Methods Test
**Run the automated test:**
```bash
php tests/test_analytics.php
```

**Expected Output:**
```
=== Analytics System Test ===

✓ Database connection successful

Checking analytics tables...
✓ Table Analytics_Activity exists
  - Records: X
✓ Table Gig_Views exists
  - Records: X
✓ Table User_Earnings exists
  - Records: X

Testing Analytics class...
✓ Analytics class instantiated

Testing with user: XXXXXXXX

Testing logActivity()...
✓ Activity logged successfully

Testing getUserAnalytics()...
✓ User analytics retrieved
  - Total logins: X
  - Total gig views: X
  - Gigs created: X
  - Gigs applied: X
  - Total earnings: ৳X
  - Pending earnings: ৳X
  - Completion rate: X%
  - Last activity: YYYY-MM-DD HH:MM:SS

... (more test results)

=== All Tests Completed ===
```

## Common Issues and Solutions

### Issue: "Table doesn't exist"
**Solution:**
```bash
# Run the analytics migration
php database/run_migration.php migration_add_analytics_indexing_community.sql
```

### Issue: "Analytics dashboard shows 0 for everything"
**Solution:**
1. Perform some activities (login, create gig, view gigs)
2. Check if data is being inserted:
```sql
SELECT COUNT(*) FROM Analytics_Activity;
SELECT COUNT(*) FROM Gig_Views;
```
3. If counts are 0, check PHP error logs

### Issue: "Permission denied" when accessing analytics
**Solution:**
1. Ensure you're logged in
2. Check your user's `preferred_mode`:
```sql
SELECT BRACU_ID, preferred_mode FROM User WHERE BRACU_ID = 'YOUR_ID';
```
3. Client analytics requires `preferred_mode = 'hiring'`
4. Freelancer analytics requires `preferred_mode = 'working'`

### Issue: "PHP errors on analytics pages"
**Solution:**
1. Check PHP error logs
2. Verify `includes/analytics.php` has no syntax errors
3. Ensure database connection is working
4. Check if all required tables exist

## Verification Queries

### Overall Analytics Summary
```sql
SELECT 
    'Total Activities' as metric,
    COUNT(*) as value
FROM Analytics_Activity
UNION ALL
SELECT 
    'Total Gig Views',
    COUNT(*)
FROM Gig_Views
UNION ALL
SELECT 
    'Total Earnings Records',
    COUNT(*)
FROM User_Earnings;
```

### Activity Breakdown
```sql
SELECT 
    activity_type,
    COUNT(*) as count,
    COUNT(DISTINCT BRACU_ID) as unique_users,
    MIN(created_at) as first_activity,
    MAX(created_at) as last_activity
FROM Analytics_Activity
GROUP BY activity_type
ORDER BY count DESC;
```

### Most Active Users
```sql
SELECT 
    u.BRACU_ID,
    u.full_name,
    COUNT(aa.id) as total_activities,
    MAX(aa.created_at) as last_activity
FROM User u
LEFT JOIN Analytics_Activity aa ON u.BRACU_ID = aa.BRACU_ID
GROUP BY u.BRACU_ID
ORDER BY total_activities DESC
LIMIT 10;
```

### Most Viewed Gigs
```sql
SELECT 
    g.GID,
    g.LIST_OF_GIGS,
    g.CATAGORY,
    COUNT(gv.id) as total_views,
    COUNT(DISTINCT gv.BRACU_ID) as unique_viewers
FROM Gigs g
LEFT JOIN Gig_Views gv ON g.GID = gv.GID
GROUP BY g.GID
ORDER BY total_views DESC
LIMIT 10;
```

## Performance Testing

### Check Query Performance
```sql
-- Enable profiling
SET profiling = 1;

-- Run analytics query
SELECT * FROM Analytics_Activity WHERE BRACU_ID = 'YOUR_ID';

-- Check performance
SHOW PROFILES;
```

### Check Index Usage
```sql
EXPLAIN SELECT * FROM Analytics_Activity WHERE BRACU_ID = 'YOUR_ID';
EXPLAIN SELECT * FROM Gig_Views WHERE GID = 1;
```

## Test Data Generation

If you need test data, you can insert sample activities:

```sql
-- Insert sample login activities
INSERT INTO Analytics_Activity (BRACU_ID, activity_type, ip_address, user_agent)
SELECT BRACU_ID, 'login', '127.0.0.1', 'Test Browser'
FROM User
LIMIT 5;

-- Insert sample gig views
INSERT INTO Gig_Views (GID, BRACU_ID, viewer_ip)
SELECT g.GID, u.BRACU_ID, '127.0.0.1'
FROM Gigs g
CROSS JOIN User u
LIMIT 20;
```

## Success Criteria

✅ **All tests pass if:**
1. All analytics tables exist and are accessible
2. Activities are logged when actions are performed
3. Analytics dashboards display without errors
4. Metrics show accurate counts
5. No PHP errors in error logs
6. Database queries execute efficiently
7. Test script completes successfully

## Next Steps After Testing

1. ✅ Verify all edge cases work correctly
2. ✅ Test with multiple users simultaneously
3. ✅ Monitor performance under load
4. ✅ Set up regular backups of analytics data
5. ✅ Consider adding data visualization (charts)
6. ✅ Document any custom analytics requirements
7. ✅ Train users on how to use analytics dashboards

## Support

If you encounter issues:
1. Check PHP error logs: `/var/log/php/error.log` or similar
2. Check MySQL error logs
3. Review `ANALYTICS_IMPLEMENTATION.md` for detailed documentation
4. Verify database schema matches migration files
5. Ensure all required PHP extensions are installed (PDO, pdo_mysql)
