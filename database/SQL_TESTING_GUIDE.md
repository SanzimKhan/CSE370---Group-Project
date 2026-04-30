# SQL Testing Guide - Quick Reference

## 📋 Quick Command Reference

### 1️⃣ **VERIFY EVERYTHING IS INSTALLED**
```sql
-- Run this first to check if all tables exist
SHOW TABLES LIKE 'Analytics_%';
SHOW TABLES LIKE 'Gig_Views';
SHOW TABLES LIKE 'Ratings';
SHOW TABLES LIKE 'Messages';
SHOW TABLES LIKE 'Forum_%';
SHOW TABLES LIKE 'Gig_Search_Index';
```

### 2️⃣ **QUICK STATUS CHECK** (Most Important!)
```sql
-- Run this one query to see everything at a glance
SELECT 
    'Analytics_Activity' as feature,
    IF(COUNT(*) > 0, '✅ Active', '❌ Empty') as status,
    COUNT(*) as records
FROM Analytics_Activity
UNION ALL
SELECT 'Gig_Views', IF(COUNT(*) > 0, '✅ Active', '❌ Empty'), COUNT(*) FROM Gig_Views
UNION ALL
SELECT 'User_Earnings', IF(COUNT(*) > 0, '✅ Active', '❌ Empty'), COUNT(*) FROM User_Earnings
UNION ALL
SELECT 'Ratings', IF(COUNT(*) > 0, '✅ Active', '❌ Empty'), COUNT(*) FROM Ratings
UNION ALL
SELECT 'User_Badges', IF(COUNT(*) > 0, '✅ Active', '❌ Empty'), COUNT(*) FROM User_Badges
UNION ALL
SELECT 'Messages', IF(COUNT(*) > 0, '✅ Active', '❌ Empty'), COUNT(*) FROM Messages
UNION ALL
SELECT 'Forum_Threads', IF(COUNT(*) > 0, '✅ Active', '❌ Empty'), COUNT(*) FROM Forum_Threads
UNION ALL
SELECT 'Forum_Replies', IF(COUNT(*) > 0, '✅ Active', '❌ Empty'), COUNT(*) FROM Forum_Replies
UNION ALL
SELECT 'Gig_Search_Index', IF(COUNT(*) > 0, '✅ Active', '❌ Empty'), COUNT(*) FROM Gig_Search_Index;
```

---

## 🧪 Testing by Feature

### **ANALYTICS TESTING**

**Add test data:**
```sql
INSERT INTO Analytics_Activity (bracu_id, activity_type, gig_id, activity_data, created_at) 
VALUES (1, 'login', NULL, '{"ip":"192.168.1.1"}', NOW());
```

**Check activities:**
```sql
SELECT * FROM Analytics_Activity ORDER BY created_at DESC LIMIT 10;
SELECT activity_type, COUNT(*) as count FROM Analytics_Activity GROUP BY activity_type;
```

**Remove test data:**
```sql
DELETE FROM Analytics_Activity WHERE bracu_id = 1;
```

---

### **GIG VIEWS TESTING**

**Add test data:**
```sql
INSERT INTO Gig_Views (gig_id, viewer_id, viewed_at) VALUES (1, 1, NOW());
```

**Check view tracking:**
```sql
SELECT gig_id, COUNT(*) as view_count FROM Gig_Views GROUP BY gig_id;
SELECT gig_id, COUNT(DISTINCT viewer_id) as unique_viewers FROM Gig_Views GROUP BY gig_id;
```

**Remove test data:**
```sql
DELETE FROM Gig_Views WHERE gig_id = 1;
```

---

### **EARNINGS TRACKING TESTING**

**Add test data:**
```sql
INSERT INTO User_Earnings (user_id, gig_id, amount, earned_at, status) 
VALUES (1, 1, 500, NOW(), 'completed');
```

**Check earnings:**
```sql
SELECT user_id, SUM(amount) as total_earned FROM User_Earnings 
WHERE status = 'completed' GROUP BY user_id;
```

**Remove test data:**
```sql
DELETE FROM User_Earnings WHERE user_id = 1;
```

---

### **RATINGS & REVIEWS TESTING**

**Add test data:**
```sql
INSERT INTO Ratings (rater_id, ratee_id, gig_id, rating, review_text, is_client_rating) 
VALUES (1, 2, 1, 5, 'Excellent work!', 1);
```

**Check ratings:**
```sql
SELECT ratee_id, ROUND(AVG(rating), 2) as avg_rating, COUNT(*) as total_ratings 
FROM Ratings GROUP BY ratee_id;
```

**Remove test data:**
```sql
DELETE FROM Ratings WHERE rater_id = 1;
```

---

### **BADGES TESTING**

**Add test data:**
```sql
INSERT INTO User_Badges (user_id, badge_type, earned_at, description) 
VALUES (1, 'top_rated', NOW(), 'Average rating of 4.5+');
```

**Check badges:**
```sql
SELECT user_id, badge_type, earned_at FROM User_Badges ORDER BY earned_at DESC;
SELECT badge_type, COUNT(*) as count FROM User_Badges GROUP BY badge_type;
```

**Remove test data:**
```sql
DELETE FROM User_Badges WHERE user_id = 1;
```

---

### **MESSAGING TESTING**

**Add test data:**
```sql
INSERT INTO Messages (sender_id, recipient_id, message, is_read, created_at) 
VALUES (1, 2, 'Hi there!', 0, NOW());
```

**Check messages:**
```sql
SELECT * FROM Messages WHERE recipient_id = 1 AND is_read = 0;
SELECT recipient_id, COUNT(*) as unread_count FROM Messages WHERE is_read = 0 GROUP BY recipient_id;
```

**Remove test data:**
```sql
DELETE FROM Messages WHERE sender_id = 1;
```

---

### **FORUM THREADS TESTING**

**Add test data:**
```sql
INSERT INTO Forum_Threads (creator_id, title, content, category, views) 
VALUES (1, 'Test Thread', 'Test content', 'General', 0);
```

**Check threads:**
```sql
SELECT * FROM Forum_Threads ORDER BY created_at DESC;
SELECT category, COUNT(*) as thread_count FROM Forum_Threads GROUP BY category;
```

**Remove test data:**
```sql
DELETE FROM Forum_Threads WHERE creator_id = 1;
```

---

### **FORUM REPLIES TESTING**

**Add test data:**
```sql
INSERT INTO Forum_Replies (thread_id, author_id, content, likes) 
VALUES (1, 2, 'Great point!', 0);
```

**Check replies:**
```sql
SELECT * FROM Forum_Replies WHERE thread_id = 1 ORDER BY created_at ASC;
SELECT thread_id, COUNT(*) as reply_count FROM Forum_Replies GROUP BY thread_id;
```

**Remove test data:**
```sql
DELETE FROM Forum_Replies WHERE author_id = 1;
```

---

### **SEARCH INDEX TESTING**

**Add test data:**
```sql
INSERT INTO Gig_Search_Index (gig_id, search_keywords, category, indexed_at) 
VALUES (1, 'web design frontend ui', 'web', NOW());
```

**Test FULLTEXT search:**
```sql
-- Search for keywords
SELECT gig_id, search_keywords FROM Gig_Search_Index 
WHERE MATCH(search_keywords) AGAINST('web design' IN BOOLEAN MODE);

-- Search with phrase
SELECT gig_id, search_keywords FROM Gig_Search_Index 
WHERE MATCH(search_keywords) AGAINST('"web design"' IN BOOLEAN MODE);
```

**Remove test data:**
```sql
DELETE FROM Gig_Search_Index WHERE gig_id = 1;
```

---

## 🧹 CLEANUP - Remove ALL Test Data

```sql
-- Run these one by one to remove all test records
DELETE FROM Analytics_Activity;
DELETE FROM Gig_Views;
DELETE FROM User_Earnings;
DELETE FROM Ratings;
DELETE FROM User_Badges;
DELETE FROM Messages;
DELETE FROM Forum_Replies;
DELETE FROM Forum_Threads;
DELETE FROM Gig_Search_Index;
```

---

## 📊 Overall Database Status

**Check total records in all new tables:**
```sql
SELECT 
    'Analytics_Activity' as table_name,
    COUNT(*) as records
FROM Analytics_Activity
UNION ALL
SELECT 'Gig_Views', COUNT(*) FROM Gig_Views
UNION ALL
SELECT 'User_Earnings', COUNT(*) FROM User_Earnings
UNION ALL
SELECT 'Ratings', COUNT(*) FROM Ratings
UNION ALL
SELECT 'User_Badges', COUNT(*) FROM User_Badges
UNION ALL
SELECT 'Messages', COUNT(*) FROM Messages
UNION ALL
SELECT 'Forum_Threads', COUNT(*) FROM Forum_Threads
UNION ALL
SELECT 'Forum_Replies', COUNT(*) FROM Forum_Replies
UNION ALL
SELECT 'Gig_Search_Index', COUNT(*) FROM Gig_Search_Index;
```

---

## 🔍 Diagnostic Queries

**Check if all indexes were created:**
```sql
SHOW INDEX FROM Analytics_Activity;
SHOW INDEX FROM Gig_Views;
SHOW INDEX FROM Ratings;
SHOW INDEX FROM Messages;
SHOW INDEX FROM Forum_Threads;
SHOW INDEX FROM Gig_Search_Index;
```

**Check table sizes:**
```sql
SELECT 
    table_name,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) as size_mb
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = 'bracu_freelance_marketplace'
AND TABLE_NAME IN (
    'Analytics_Activity', 'Gig_Views', 'User_Earnings',
    'Ratings', 'User_Badges', 'Messages',
    'Forum_Threads', 'Forum_Replies', 'Gig_Search_Index'
)
ORDER BY (data_length + index_length) DESC;
```

---

## 📋 Testing Checklist

```
✅ Run quick status check query above
✅ Add test data for each feature
✅ Verify data was inserted correctly
✅ Run search queries to retrieve data
✅ Run update queries
✅ Run delete queries
✅ Verify indexes were created
✅ Check database size
✅ Cleanup test data when done
```

---

## 💡 Tips

1. **Use LIMIT clause** to avoid viewing too many records:
   ```sql
   SELECT * FROM Analytics_Activity LIMIT 10;
   ```

2. **Use WHERE clause** to filter specific test data:
   ```sql
   SELECT * FROM Ratings WHERE rater_id = 1;
   ```

3. **Use COUNT to see summary**:
   ```sql
   SELECT COUNT(*) as total_records FROM Analytics_Activity;
   ```

4. **Use GROUP BY for statistics**:
   ```sql
   SELECT category, COUNT(*) FROM Forum_Threads GROUP BY category;
   ```

5. **Always verify after INSERT**:
   ```sql
   INSERT INTO ... VALUES (...);
   SELECT * FROM table_name WHERE specific_condition;
   ```

---

**File Location:** `database/TEST_QUERIES.sql` - Complete test queries with all sections
