# Analytics System - Quick Start Guide

## 🚀 Get Started in 5 Minutes

### Step 1: Verify Database Tables (30 seconds)
Run this SQL query to check if analytics tables exist:

```sql
SHOW TABLES LIKE 'Analytics_Activity';
SHOW TABLES LIKE 'Gig_Views';
SHOW TABLES LIKE 'User_Earnings';
```

**If tables don't exist**, run the migration:
```bash
php database/run_migration.php migration_add_analytics_indexing_community.sql
```

### Step 2: Run Automated Test (1 minute)
```bash
php tests/test_analytics.php
```

**Expected:** All tests pass with ✓ marks

### Step 3: Test Login Tracking (1 minute)
1. Open your browser
2. Log in to the application
3. Run this query:
```sql
SELECT * FROM Analytics_Activity 
WHERE activity_type = 'login' 
ORDER BY created_at DESC 
LIMIT 1;
```

**Expected:** You should see your login recorded

### Step 4: View Analytics Dashboard (2 minutes)

**For Clients (Hiring Mode):**
1. Log in as a client
2. Navigate to: `/client/analytics.php`
3. You should see:
   - Total logins
   - Gigs posted
   - Total spent
   - Spending breakdown

**For Freelancers (Working Mode):**
1. Log in as a freelancer
2. Navigate to: `/freelancer/analytics.php`
3. You should see:
   - Total logins
   - Gigs applied
   - Total earnings
   - Completion rate

### Step 5: Test Gig View Tracking (1 minute)
1. As a freelancer, visit the marketplace
2. Run this query:
```sql
SELECT COUNT(*) as view_count FROM Gig_Views 
WHERE viewed_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE);
```

**Expected:** You should see views recorded for the gigs you just viewed

## ✅ Success Indicators

You'll know everything is working if:
- ✓ Test script completes without errors
- ✓ Login activity is recorded in database
- ✓ Analytics dashboards load without errors
- ✓ Metrics show non-zero values after activity
- ✓ No PHP errors in error logs

## 🔧 Quick Troubleshooting

### Problem: "Table doesn't exist"
**Solution:**
```bash
php database/run_migration.php migration_add_analytics_indexing_community.sql
```

### Problem: "Analytics dashboard shows all zeros"
**Solution:**
1. Perform some activities (login, create gig, view marketplace)
2. Refresh the analytics page
3. Check if data is being inserted:
```sql
SELECT COUNT(*) FROM Analytics_Activity;
```

### Problem: "Permission denied"
**Solution:**
- For client analytics: Ensure `preferred_mode = 'hiring'`
- For freelancer analytics: Ensure `preferred_mode = 'working'`

Check your mode:
```sql
SELECT BRACU_ID, preferred_mode FROM User WHERE BRACU_ID = 'YOUR_ID';
```

### Problem: "PHP errors"
**Solution:**
1. Check PHP error logs
2. Verify `includes/analytics.php` exists
3. Ensure database connection is working

## 📊 What Gets Tracked

### Automatically Tracked Events:
1. **Login** - Every successful login
2. **Gig Creation** - When clients create gigs
3. **Gig Views** - When users view gigs in marketplace
4. **Gig Applications** - When freelancers accept gigs
5. **Profile Views** - When users view other profiles
6. **Messages** - When users send messages

### Where to See Analytics:
- **Client Dashboard**: `/client/analytics.php`
- **Freelancer Dashboard**: `/freelancer/analytics.php`

## 🎯 Quick Verification Queries

### Check Recent Activity
```sql
SELECT activity_type, COUNT(*) as count 
FROM Analytics_Activity 
WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
GROUP BY activity_type;
```

### Check Most Viewed Gigs
```sql
SELECT g.GID, g.LIST_OF_GIGS, COUNT(gv.id) as views
FROM Gigs g
LEFT JOIN Gig_Views gv ON g.GID = gv.GID
GROUP BY g.GID
ORDER BY views DESC
LIMIT 5;
```

### Check Your Analytics
```sql
SELECT 
    (SELECT COUNT(*) FROM Analytics_Activity WHERE BRACU_ID = 'YOUR_ID') as my_activities,
    (SELECT COUNT(*) FROM Gigs WHERE BRACU_ID = 'YOUR_ID') as my_gigs,
    (SELECT COUNT(*) FROM Working_on WHERE BRACU_ID = 'YOUR_ID') as my_applications;
```

## 📚 Need More Help?

- **Detailed Documentation**: See `ANALYTICS_IMPLEMENTATION.md`
- **Testing Guide**: See `ANALYTICS_TESTING_GUIDE.md`
- **Changes Summary**: See `ANALYTICS_CHANGES_SUMMARY.md`

## 🎉 You're All Set!

The analytics system is now tracking user activities and providing insights. The dashboards will populate as users interact with the platform.

### Next Steps:
1. ✅ Monitor analytics dashboards regularly
2. ✅ Check for any PHP errors in logs
3. ✅ Verify data accuracy periodically
4. ✅ Consider adding data visualization (charts)
5. ✅ Archive old analytics data as needed

---

**Questions?** Check the detailed documentation files or review the test script output for diagnostics.
