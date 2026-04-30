-- ============================================================================
-- BRACU Freelance Marketplace - Analytics, Indexing & Community Testing
-- ============================================================================
-- These queries test if the new features are running properly in the database
-- Run them in order to verify complete functionality
-- ============================================================================

-- ============================================================================
-- SECTION 1: VERIFY ALL TABLES WERE CREATED
-- ============================================================================

-- Check if all Analytics tables exist
SHOW TABLES LIKE 'Analytics_%';
SHOW TABLES LIKE 'Gig_Views';
SHOW TABLES LIKE 'User_Earnings';

-- Check if all Community tables exist
SHOW TABLES LIKE 'Ratings';
SHOW TABLES LIKE 'User_Badges';
SHOW TABLES LIKE 'Messages';
SHOW TABLES LIKE 'Forum_%';

-- Check if Search index table exists
SHOW TABLES LIKE 'Gig_Search_Index';

-- Show all new tables created (should be 11 tables)
SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = 'bracu_freelance_marketplace' 
AND TABLE_NAME IN (
    'Analytics_Activity',
    'Gig_Views',
    'User_Earnings',
    'Ratings',
    'User_Badges',
    'Messages',
    'Forum_Threads',
    'Forum_Replies',
    'Gig_Search_Index'
);

-- ============================================================================
-- SECTION 2: ANALYTICS TESTING
-- ============================================================================

-- 2.1 VIEW ANALYTICS_ACTIVITY TABLE STRUCTURE
DESCRIBE Analytics_Activity;

-- 2.2 INSERT TEST DATA - User Activity
-- Get a real user ID from your database first:
SELECT id FROM Users LIMIT 1;

-- INSERT sample activities (replace USER_ID with real ID)
INSERT INTO Analytics_Activity (bracu_id, activity_type, gig_id, target_user, activity_data, created_at) VALUES
(1, 'login', NULL, NULL, '{"ip":"192.168.1.1"}', NOW()),
(1, 'gig_view', 5, NULL, '{"source":"search"}', NOW()),
(1, 'gig_apply', 5, 2, '{"proposal":"Great work"}', NOW()),
(1, 'gig_create', NULL, NULL, '{"category":"web"}', NOW());

-- 2.3 CHECK LOGGED ACTIVITIES
SELECT * FROM Analytics_Activity ORDER BY created_at DESC LIMIT 10;

-- 2.4 COUNT ACTIVITIES BY TYPE
SELECT activity_type, COUNT(*) as count FROM Analytics_Activity GROUP BY activity_type;

-- ============================================================================
-- SECTION 3: GIG VIEWS TESTING
-- ============================================================================

-- 3.1 VIEW GIG_VIEWS TABLE STRUCTURE
DESCRIBE Gig_Views;

-- 3.2 INSERT TEST DATA - Gig Views
-- First get a real gig ID:
SELECT id FROM Gigs LIMIT 1;

-- INSERT sample views (replace GIG_ID and USER_ID)
INSERT INTO Gig_Views (gig_id, viewer_id, viewed_at) VALUES
(1, 1, NOW()),
(1, 2, NOW()),
(1, 3, NOW()),
(2, 1, DATE_SUB(NOW(), INTERVAL 1 DAY));

-- 3.3 CHECK TOTAL VIEWS PER GIG
SELECT gig_id, COUNT(*) as view_count FROM Gig_Views GROUP BY gig_id;

-- 3.4 CHECK UNIQUE VIEWERS PER GIG
SELECT gig_id, COUNT(DISTINCT viewer_id) as unique_viewers FROM Gig_Views GROUP BY gig_id;

-- 3.5 VERIFY VIEW TRACKING
SELECT * FROM Gig_Views ORDER BY viewed_at DESC LIMIT 10;

-- ============================================================================
-- SECTION 4: USER EARNINGS TESTING
-- ============================================================================

-- 4.1 VIEW USER_EARNINGS TABLE STRUCTURE
DESCRIBE User_Earnings;

-- 4.2 INSERT TEST DATA - Earnings
INSERT INTO User_Earnings (user_id, gig_id, amount, earned_at, status) VALUES
(1, 1, 500, NOW(), 'completed'),
(1, 2, 750, DATE_SUB(NOW(), INTERVAL 2 DAY), 'completed'),
(1, 3, 300, NOW(), 'pending'),
(2, 1, 400, NOW(), 'completed');

-- 4.3 CHECK TOTAL EARNINGS BY USER
SELECT user_id, SUM(amount) as total_earned, COUNT(*) as gigs_completed 
FROM User_Earnings WHERE status = 'completed' 
GROUP BY user_id;

-- 4.4 CHECK PENDING EARNINGS
SELECT user_id, SUM(amount) as pending_amount FROM User_Earnings 
WHERE status = 'pending' GROUP BY user_id;

-- 4.5 VERIFY EARNINGS TRACKING
SELECT * FROM User_Earnings ORDER BY earned_at DESC LIMIT 10;

-- ============================================================================
-- SECTION 5: RATINGS & REVIEWS TESTING
-- ============================================================================

-- 5.1 VIEW RATINGS TABLE STRUCTURE
DESCRIBE Ratings;

-- 5.2 INSERT TEST DATA - Ratings
-- Get real user IDs and gig IDs:
SELECT id FROM Users LIMIT 5;
SELECT id FROM Gigs LIMIT 3;

-- INSERT sample ratings (replace IDs with real values)
INSERT INTO Ratings (rater_id, ratee_id, gig_id, rating, review_text, is_client_rating, created_at) VALUES
(1, 2, 1, 5, 'Excellent work! Very professional and quick delivery.', 1, NOW()),
(2, 1, 1, 4, 'Good quality, minor revisions needed.', 0, NOW()),
(3, 2, 2, 5, 'Outstanding freelancer, highly recommended!', 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1, 3, 3, 3, 'Decent work but communication could be better.', 1, DATE_SUB(NOW(), INTERVAL 2 DAY));

-- 5.3 VIEW ALL RATINGS FOR A USER
SELECT * FROM Ratings WHERE ratee_id = 2 ORDER BY created_at DESC;

-- 5.4 CALCULATE AVERAGE RATING PER USER
SELECT ratee_id, 
       ROUND(AVG(rating), 2) as avg_rating, 
       COUNT(*) as total_ratings,
       SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_stars,
       SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_stars,
       SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_stars,
       SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_stars,
       SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_stars
FROM Ratings GROUP BY ratee_id;

-- 5.5 GET TOP RATED USERS
SELECT ratee_id, ROUND(AVG(rating), 2) as avg_rating, COUNT(*) as total_ratings
FROM Ratings GROUP BY ratee_id HAVING AVG(rating) >= 4.5 
ORDER BY avg_rating DESC;

-- 5.6 VERIFY RATING DATA
SELECT * FROM Ratings ORDER BY created_at DESC LIMIT 10;

-- ============================================================================
-- SECTION 6: BADGES TESTING
-- ============================================================================

-- 6.1 VIEW USER_BADGES TABLE STRUCTURE
DESCRIBE User_Badges;

-- 6.2 INSERT TEST DATA - Badges
INSERT INTO User_Badges (user_id, badge_type, earned_at, description) VALUES
(1, 'top_rated', NOW(), 'Average rating of 4.5+ with 5+ ratings'),
(1, 'responsive', NOW(), 'Responds to messages within 24 hours'),
(2, 'top_rated', DATE_SUB(NOW(), INTERVAL 5 DAY), 'Average rating of 4.5+ with 5+ ratings'),
(2, 'trusted', NOW(), 'Completed 5+ gigs successfully'),
(3, 'responsive', NOW(), 'Responds to messages within 24 hours');

-- 6.3 VIEW ALL BADGES FOR A USER
SELECT * FROM User_Badges WHERE user_id = 1;

-- 6.4 COUNT BADGES BY TYPE
SELECT badge_type, COUNT(*) as count FROM User_Badges GROUP BY badge_type;

-- 6.5 GET USERS WITH MOST BADGES
SELECT user_id, COUNT(*) as badge_count FROM User_Badges 
GROUP BY user_id ORDER BY badge_count DESC;

-- 6.6 VERIFY BADGE DATA
SELECT * FROM User_Badges ORDER BY earned_at DESC;

-- ============================================================================
-- SECTION 7: MESSAGES TESTING
-- ============================================================================

-- 7.1 VIEW MESSAGES TABLE STRUCTURE
DESCRIBE Messages;

-- 7.2 INSERT TEST DATA - Messages
INSERT INTO Messages (sender_id, recipient_id, message, gig_id, is_read, created_at) VALUES
(1, 2, 'Hi, are you interested in this gig?', 1, 0, NOW()),
(2, 1, 'Yes, I am! When can we discuss details?', 1, 1, NOW()),
(1, 2, 'Tomorrow at 3 PM would work for me.', 1, 1, DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(3, 1, 'Do you have experience with Laravel?', NULL, 0, DATE_SUB(NOW(), INTERVAL 2 HOUR));

-- 7.3 GET CONVERSATION BETWEEN TWO USERS
SELECT * FROM Messages 
WHERE (sender_id = 1 AND recipient_id = 2) OR (sender_id = 2 AND recipient_id = 1)
ORDER BY created_at ASC;

-- 7.4 GET UNREAD MESSAGES FOR A USER
SELECT * FROM Messages WHERE recipient_id = 1 AND is_read = 0;

-- 7.5 COUNT UNREAD MESSAGES PER USER
SELECT recipient_id, COUNT(*) as unread_count FROM Messages 
WHERE is_read = 0 GROUP BY recipient_id;

-- 7.6 GET LATEST MESSAGE FROM EACH CONVERSATION
SELECT 
    CASE WHEN sender_id = 1 THEN recipient_id ELSE sender_id END as other_user,
    MAX(created_at) as last_message_time,
    message as last_message
FROM Messages
WHERE sender_id = 1 OR recipient_id = 1
GROUP BY other_user
ORDER BY last_message_time DESC;

-- 7.7 VERIFY MESSAGE DATA
SELECT * FROM Messages ORDER BY created_at DESC LIMIT 15;

-- ============================================================================
-- SECTION 8: FORUM THREADS TESTING
-- ============================================================================

-- 8.1 VIEW FORUM_THREADS TABLE STRUCTURE
DESCRIBE Forum_Threads;

-- 8.2 INSERT TEST DATA - Forum Threads
INSERT INTO Forum_Threads (creator_id, title, content, category, views, is_pinned, is_locked, created_at) VALUES
(1, 'Best practices for client communication', 'I have been working as a freelancer for 2 years...', 'Tips', 0, 0, 0, NOW()),
(2, 'How to set competitive pricing?', 'New freelancer here, struggling with pricing strategy...', 'Help', 0, 0, 0, NOW()),
(1, 'My first big project!', 'Just completed a huge website redesign project...', 'Showcase', 0, 0, 0, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, 'PHP performance optimization', 'Anyone know good resources for PHP optimization?', 'General', 0, 0, 0, DATE_SUB(NOW(), INTERVAL 3 DAY));

-- 8.3 GET ALL THREADS BY CATEGORY
SELECT * FROM Forum_Threads WHERE category = 'Tips' ORDER BY created_at DESC;

-- 8.4 GET MOST VIEWED THREADS
SELECT id, title, views, category FROM Forum_Threads 
ORDER BY views DESC LIMIT 10;

-- 8.5 COUNT THREADS BY CATEGORY
SELECT category, COUNT(*) as thread_count FROM Forum_Threads 
GROUP BY category;

-- 8.6 GET LATEST THREADS
SELECT * FROM Forum_Threads ORDER BY created_at DESC LIMIT 10;

-- 8.7 VERIFY THREAD DATA
SELECT * FROM Forum_Threads ORDER BY created_at DESC;

-- ============================================================================
-- SECTION 9: FORUM REPLIES TESTING
-- ============================================================================

-- 9.1 VIEW FORUM_REPLIES TABLE STRUCTURE
DESCRIBE Forum_Replies;

-- 9.2 INSERT TEST DATA - Forum Replies
-- First get a real thread ID:
SELECT id FROM Forum_Threads LIMIT 1;

-- INSERT sample replies (replace THREAD_ID)
INSERT INTO Forum_Replies (thread_id, author_id, content, likes, created_at) VALUES
(1, 2, 'Great tips! I usually start with researching similar gigs...', 0, NOW()),
(1, 3, 'The most important thing is clear communication from day one.', 0, NOW()),
(2, 1, 'I usually charge based on hourly rate, around $25-50/hour depending on complexity.', 1, NOW()),
(2, 4, 'Consider your experience level and market rates in your region.', 0, DATE_SUB(NOW(), INTERVAL 1 DAY));

-- 9.3 GET ALL REPLIES FOR A THREAD
SELECT * FROM Forum_Replies WHERE thread_id = 1 ORDER BY created_at ASC;

-- 9.4 COUNT REPLIES PER THREAD
SELECT thread_id, COUNT(*) as reply_count FROM Forum_Replies GROUP BY thread_id;

-- 9.5 GET MOST LIKED REPLIES
SELECT * FROM Forum_Replies ORDER BY likes DESC LIMIT 10;

-- 9.6 VERIFY REPLY DATA
SELECT * FROM Forum_Replies ORDER BY created_at DESC LIMIT 15;

-- ============================================================================
-- SECTION 10: SEARCH INDEX TESTING
-- ============================================================================

-- 10.1 VIEW GIG_SEARCH_INDEX TABLE STRUCTURE
DESCRIBE Gig_Search_Index;

-- 10.2 INSERT TEST DATA - Search Index
INSERT INTO Gig_Search_Index (gig_id, search_keywords, category, indexed_at) VALUES
(1, 'web design website redesign ui ux frontend', 'web', NOW()),
(2, 'logo design branding graphics creative', 'design', NOW()),
(3, 'php laravel backend api development', 'programming', NOW()),
(4, 'content writing blog posts seo copywriting', 'writing', NOW()),
(5, 'social media marketing instagram facebook', 'marketing', NOW());

-- 10.3 FULLTEXT SEARCH EXAMPLES
-- Search for "web design"
SELECT gig_id, search_keywords, category FROM Gig_Search_Index
WHERE MATCH(search_keywords) AGAINST('web design' IN BOOLEAN MODE);

-- Search for "programming" or "backend"
SELECT gig_id, search_keywords, category FROM Gig_Search_Index
WHERE MATCH(search_keywords) AGAINST('programming backend' IN BOOLEAN MODE);

-- Search for exact phrase "logo design"
SELECT gig_id, search_keywords, category FROM Gig_Search_Index
WHERE MATCH(search_keywords) AGAINST('"logo design"' IN BOOLEAN MODE);

-- 10.4 VERIFY SEARCH INDEX DATA
SELECT * FROM Gig_Search_Index ORDER BY indexed_at DESC;

-- ============================================================================
-- SECTION 11: VERIFY ALL INDEXES WERE CREATED
-- ============================================================================

-- Check indexes on Analytics_Activity
SHOW INDEX FROM Analytics_Activity;

-- Check indexes on Gig_Views
SHOW INDEX FROM Gig_Views;

-- Check indexes on Ratings
SHOW INDEX FROM Ratings;

-- Check indexes on Messages
SHOW INDEX FROM Messages;

-- Check indexes on Forum_Threads
SHOW INDEX FROM Forum_Threads;

-- Check FULLTEXT index on Gig_Search_Index
SHOW INDEX FROM Gig_Search_Index;

-- ============================================================================
-- SECTION 12: DATA REMOVAL TESTING
-- ============================================================================

-- 12.1 DELETE TEST DATA - Start fresh
-- WARNING: Run these one at a time to avoid cascading deletes!

-- Delete all activities
DELETE FROM Analytics_Activity;

-- Delete all gig views
DELETE FROM Gig_Views;

-- Delete all earnings
DELETE FROM User_Earnings;

-- Delete all ratings
DELETE FROM Ratings;

-- Delete all badges
DELETE FROM User_Badges;

-- Delete all messages
DELETE FROM Messages;

-- Delete all forum replies
DELETE FROM Forum_Replies;

-- Delete all forum threads
DELETE FROM Forum_Threads;

-- Delete all search indexes
DELETE FROM Gig_Search_Index;

-- ============================================================================
-- SECTION 13: COMPREHENSIVE TESTING QUERIES
-- ============================================================================

-- 13.1 GET COMPLETE USER ANALYTICS DASHBOARD
SELECT 
    'Analytics_Activity' as table_name,
    COUNT(*) as total_records,
    COUNT(DISTINCT bracu_id) as unique_users
FROM Analytics_Activity
UNION ALL
SELECT 'Gig_Views', COUNT(*), COUNT(DISTINCT viewer_id) FROM Gig_Views
UNION ALL
SELECT 'User_Earnings', COUNT(*), COUNT(DISTINCT user_id) FROM User_Earnings
UNION ALL
SELECT 'Ratings', COUNT(*), COUNT(DISTINCT ratee_id) FROM Ratings
UNION ALL
SELECT 'User_Badges', COUNT(*), COUNT(DISTINCT user_id) FROM User_Badges
UNION ALL
SELECT 'Messages', COUNT(*), COUNT(DISTINCT sender_id) FROM Messages
UNION ALL
SELECT 'Forum_Threads', COUNT(*), COUNT(DISTINCT creator_id) FROM Forum_Threads
UNION ALL
SELECT 'Forum_Replies', COUNT(*), COUNT(DISTINCT author_id) FROM Forum_Replies
UNION ALL
SELECT 'Gig_Search_Index', COUNT(*), COUNT(DISTINCT category) FROM Gig_Search_Index;

-- 13.2 CHECK DATABASE SIZE
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

-- 13.3 FULL STATUS CHECK (Run this to verify everything is working)
SELECT 
    'Analytics_Activity' as feature,
    IF(COUNT(*) > 0, 'Active', 'Empty') as status,
    COUNT(*) as records
FROM Analytics_Activity
UNION ALL
SELECT 'Gig_Views', IF(COUNT(*) > 0, 'Active', 'Empty'), COUNT(*) FROM Gig_Views
UNION ALL
SELECT 'User_Earnings', IF(COUNT(*) > 0, 'Active', 'Empty'), COUNT(*) FROM User_Earnings
UNION ALL
SELECT 'Ratings', IF(COUNT(*) > 0, 'Active', 'Empty'), COUNT(*) FROM Ratings
UNION ALL
SELECT 'User_Badges', IF(COUNT(*) > 0, 'Active', 'Empty'), COUNT(*) FROM User_Badges
UNION ALL
SELECT 'Messages', IF(COUNT(*) > 0, 'Active', 'Empty'), COUNT(*) FROM Messages
UNION ALL
SELECT 'Forum_Threads', IF(COUNT(*) > 0, 'Active', 'Empty'), COUNT(*) FROM Forum_Threads
UNION ALL
SELECT 'Forum_Replies', IF(COUNT(*) > 0, 'Active', 'Empty'), COUNT(*) FROM Forum_Replies
UNION ALL
SELECT 'Gig_Search_Index', IF(COUNT(*) > 0, 'Active', 'Empty'), COUNT(*) FROM Gig_Search_Index;

-- ============================================================================
-- END OF TEST QUERIES
-- ============================================================================
-- Usage Instructions:
-- 1. Run SECTION 1 queries first to verify all tables exist
-- 2. Run SECTION 2-10 queries in order to test each feature
-- 3. Section 12 contains DELETE queries to clean up test data
-- 4. Section 13 contains comprehensive diagnostic queries
-- 5. Use Section 13.3 to get a full status report
-- ============================================================================
