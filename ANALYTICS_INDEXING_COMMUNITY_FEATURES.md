# Analytics, Indexing, & Community Features

This document describes the three major features added to the BRACU Freelance Marketplace.

## 🚀 Quick Start

### 1. Database Setup
Run the migration to create all required tables:
```bash
# Navigate to the database folder and run:
mysql -u root -p bracu_freelance_marketplace < database/migration_add_analytics_indexing_community.sql
```

### 2. Access the Features

**Analytics Dashboard:**
- Freelancers: `/freelancer/analytics.php`
- Clients: `/client/analytics.php`

**Community:**
- Forum: `/community/forum.php`
- Messaging: `/community/messages_inbox.php`
- User Profiles: `/community/profile.php?id=USER_ID`

**Search:**
- `/search.php` - Full-text gig search with advanced filters

---

## 📊 Analytics Feature

### What It Tracks
- **User Activity**: Logins, profile views, gig interactions
- **Gig Performance**: Views, applications, completion status
- **Earnings**: Total earnings, pending earnings, earned amounts
- **Completion Rate**: Percentage of completed jobs

### Features
1. **Freelancer Dashboard** - View:
   - Total gigs created and applied to
   - Total earnings and pending earnings
   - Completion rate percentage
   - Gig-specific metrics (views, applications, earnings)
   - Activity breakdown by type

2. **Client Dashboard** - View:
   - Total gigs posted
   - Completed gigs count
   - Spending by category
   - Total spent vs. average per gig
   - Gig status overview (listed, pending, completed)

### API Classes
**`Analytics` class** (`includes/analytics.php`):
```php
$analytics = new Analytics($conn);

// Log activity
$analytics->logActivity('USER_ID', 'login');
$analytics->logGigView($gig_id, $user_id);

// Get analytics
$user_analytics = $analytics->getUserAnalytics('USER_ID');
$gig_analytics = $analytics->getGigAnalytics($gig_id);
```

---

## 🔍 Indexing & Search Feature

### What It Indexes
- Gig titles and descriptions
- Categories
- Search keywords extracted from gig content

### Search Capabilities
1. **Full-Text Search** - Search across gig titles and descriptions
2. **Advanced Search** - Filter by:
   - Category
   - Price range (min/max credits)
   - Sort by: Recent, Price (high/low), Popular, Deadline
3. **User Search** - Find other users by name or bio

### Usage
```php
$search = new Search($conn);

// Simple search
$results = $search->searchGigs('web development');

// Advanced search with filters
$results = $search->advancedSearch(
    keyword: 'web',
    category: 'IT',
    sort_by: 'recent',
    min_credits: 100,
    max_credits: 5000
);

// Reindex all gigs (admin task)
$search->reindexAllGigs();
```

### API Classes
**`Search` class** (`includes/search.php`):
- `searchGigs($query, $category, $limit, $offset)`
- `advancedSearch($keyword, $category, $sort_by, $min, $max, $limit, $offset)`
- `searchUsers($query, $limit, $offset)`
- `indexGig($gig_id, $title, $description, $category)`
- `removeFromIndex($gig_id)`
- `reindexAllGigs()`

---

## 💬 Community Features

### 1. Ratings & Reviews System

**Features:**
- 1-5 star rating system
- Optional review text (up to 500 chars)
- Separate ratings for clients and freelancers
- Rating statistics (average, breakdown by stars)

**Pages:**
- `/community/rate_user.php?user=USER_ID&gig=GIG_ID` - Rate a user
- `/community/profile.php?id=USER_ID` - View user profile with ratings

**Usage:**
```php
$community = new Community($conn);

// Create a rating
$community->createRating(
    rater_id: 'RATER_ID',
    ratee_id: 'RATEE_ID',
    rating: 5,
    review_text: 'Great work!',
    gig_id: 123,
    is_client_rating: false
);

// Get ratings
$ratings = $community->getUserRatings('USER_ID');
$stats = $community->getUserRatingAverage('USER_ID');
```

### 2. Direct Messaging

**Features:**
- One-to-one messaging between users
- Message read tracking
- Unread message counter
- Message context (related gig)

**Pages:**
- `/community/messages_inbox.php` - View all conversations
- `/community/messages.php?user=USER_ID` - Chat with a user

**Usage:**
```php
// Send message
$community->sendMessage(
    sender_id: 'USER_A',
    recipient_id: 'USER_B',
    message_text: 'Hello!',
    gig_id: 123 // optional
);

// Get conversations
$conversations = $community->getUserConversations('USER_ID');

// Mark messages as read
$community->markMessagesAsRead('RECIPIENT_ID', 'SENDER_ID');

// Get unread count
$unread = $community->getUnreadMessageCount('USER_ID');
```

### 3. Community Forum

**Features:**
- Create discussion threads
- Reply to threads
- Categorized discussions (General, Tips, Help, Showcase)
- Pin and lock threads (admin)
- View count and reply count

**Pages:**
- `/community/forum.php` - Forum main page
- `/community/forum_view.php?id=THREAD_ID` - View thread with replies

**Usage:**
```php
// Create thread
$thread_id = $community->createForumThread(
    creator_id: 'USER_ID',
    title: 'Tips for freelancers',
    description: 'Share your tips...',
    category: 'Tips'
);

// Get threads
$threads = $community->getForumThreads(
    category: 'Tips',
    limit: 20,
    offset: 0
);

// Get thread with replies
$data = $community->getForumThreadWithReplies($thread_id);

// Add reply
$community->addForumReply($thread_id, 'USER_ID', 'Great tips!');
```

### 4. User Badges & Achievements

**Available Badges:**
- `verified` - Verified user
- `top_rated` - Average rating ≥ 4.5 stars (5+ ratings)
- `responsive` - Fast average response time
- `trusted` - Completed 5+ jobs
- `new_member` - New to platform

**Usage:**
```php
// Award badge manually
$community->awardBadge('USER_ID', 'top_rated', 'Top Rated', 'Rating 4.5+ stars');

// Auto-check and award badges
$community->checkAndAwardBadges('USER_ID');

// Get user badges
$badges = $community->getUserBadges('USER_ID');
```

### 5. User Profiles

**Profile Page** (`/community/profile.php?id=USER_ID`):
- User avatar and bio
- Earned badges
- Rating statistics
- Reviews/ratings from others
- Message button
- Job completion count

---

## 📊 Database Schema

### Analytics Tables
- `Analytics_Activity` - Track all user activities
- `Gig_Views` - Track gig views with IP tracking
- `User_Earnings` - Track earnings per gig

### Community Tables
- `Ratings` - Store user ratings and reviews
- `User_Badges` - Store user badges and achievements
- `Messages` - Direct messages between users
- `Forum_Threads` - Forum discussion threads
- `Forum_Replies` - Replies to forum threads

### Indexing Tables
- `Gig_Search_Index` - Full-text search index for gigs

---

## 🔧 Integration Examples

### Adding Analytics Tracking to Pages

```php
require_once 'includes/analytics.php';

$analytics = new Analytics($conn);

// Log page view
$analytics->logActivity($_SESSION['user_id'], 'gig_view', $gig_id);

// Log gig application
$analytics->logActivity($_SESSION['user_id'], 'gig_apply', $gig_id);
```

### Initializing Search Index for New Gigs

```php
require_once 'includes/search.php';

$search = new Search($conn);

// When creating a new gig
$search->indexGig($new_gig_id, $title, $description, $category);

// When updating a gig
$search->indexGig($gig_id, $new_title, $new_description, $category);

// When deleting/unlisting a gig
$search->removeFromIndex($gig_id);
```

### Adding Rating Link to Completed Work

```php
// In work completion template
<a href="/community/rate_user.php?user=<?php echo $freelancer_id; ?>&gig=<?php echo $gig_id; ?>">
    Rate Freelancer
</a>
```

---

## 📈 Performance Considerations

### Indexes
All tables have proper indexes for:
- User lookups (`BRACU_ID`)
- Activity filtering (`activity_type`, `created_at`)
- Message filtering (`is_read`, `sender_id`, `recipient_id`)
- Forum filtering (`category`, `is_pinned`)

### Full-Text Search
- Uses MySQL FULLTEXT indexes
- Supports boolean search mode
- Automatically extracts keywords from descriptions

### Recommendations
1. Regularly run `ANALYZE TABLE` on high-traffic tables
2. Archive old activity records after 6 months
3. Periodically reindex search (run `search->reindexAllGigs()`)

---

## 🚫 Security Considerations

All features include:
- ✅ User authentication checks (`requireLogin()`)
- ✅ SQL injection prevention (prepared statements)
- ✅ XSS prevention (htmlspecialchars/escape)
- ✅ Rate limiting (for messaging if needed)

---

## 🎯 Future Enhancements

1. **Advanced Analytics:**
   - Charts and graphs for earnings trends
   - Category-wise performance breakdown
   - Response time analytics

2. **Enhanced Search:**
   - Saved searches
   - Search filters UI improvements
   - Advanced boolean operators

3. **Community:**
   - Thread subscription/notifications
   - Reputation point system
   - Community moderation tools
   - Notifications for new messages/forum replies

4. **Social Features:**
   - Follow other users
   - Friend system
   - Activity feeds

---

## 📞 Support

For issues or questions about these features:
1. Check the database migration for table structure
2. Review API usage in backend modules
3. Test pages individually for functionality

