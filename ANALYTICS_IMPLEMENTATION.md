# Analytics System Implementation

## Overview
The analytics system has been completely refactored and integrated throughout the application. It tracks user activities, gig views, and provides comprehensive analytics dashboards for both clients and freelancers.

## What Was Fixed

### 1. **Core Analytics Class (`includes/analytics.php`)**
- ✅ Removed duplicate code (was mixing PDO and MySQLi)
- ✅ Standardized to use PDO throughout
- ✅ Fixed all database queries
- ✅ Added proper error handling
- ✅ Added new methods for better analytics

### 2. **Analytics Integration**
Analytics tracking has been integrated into the following areas:

#### **Login Tracking** (`index.php`)
- Tracks every successful login
- Records the user's preferred mode (hiring/working)

#### **Gig Creation** (`client/create_gig.php`)
- Tracks when clients create new gigs
- Records category and credit amount

#### **Gig Viewing** (`freelancer/marketplace.php`)
- Tracks when users view gigs in the marketplace
- Records viewer ID and IP address
- Prevents duplicate tracking with proper database constraints

#### **Gig Application** (`freelancer/marketplace.php`)
- Tracks when freelancers accept/apply to gigs

#### **Profile Viewing** (`public_profile.php`)
- Tracks when users view other users' profiles
- Only tracks if viewer is logged in and viewing someone else's profile

#### **Message Sending** (`community/messages.php`)
- Tracks when users send messages
- Links messages to gigs when relevant

## Database Tables

### Analytics_Activity
Tracks all user activities:
- `id`: Auto-increment primary key
- `BRACU_ID`: User who performed the activity
- `activity_type`: Type of activity (login, gig_view, gig_create, gig_apply, profile_view, message_send)
- `gig_id`: Related gig (if applicable)
- `target_user`: Target user (for profile views, messages)
- `activity_data`: JSON data with additional context
- `ip_address`: User's IP address
- `user_agent`: Browser user agent
- `created_at`: Timestamp

### Gig_Views
Tracks gig views:
- `id`: Auto-increment primary key
- `GID`: Gig being viewed
- `BRACU_ID`: Viewer (if logged in)
- `viewer_ip`: IP address
- `viewed_at`: Timestamp

### User_Earnings
Tracks user earnings:
- `id`: Auto-increment primary key
- `BRACU_ID`: User earning
- `gig_id`: Related gig
- `amount`: Earning amount
- `status`: pending, released, or refunded
- `earned_at`: Timestamp
- `released_at`: When payment was released

## Analytics Class Methods

### Core Methods

#### `logActivity($bracu_id, $activity_type, $gig_id, $target_user, $activity_data)`
Logs any user activity with optional context.

**Parameters:**
- `$bracu_id` (string): User ID
- `$activity_type` (string): Type of activity
- `$gig_id` (int|null): Related gig ID
- `$target_user` (string|null): Target user ID
- `$activity_data` (array|null): Additional data as JSON

**Returns:** `bool` - Success status

#### `logGigView($gig_id, $bracu_id)`
Logs a gig view.

**Parameters:**
- `$gig_id` (int): Gig ID
- `$bracu_id` (string|null): Viewer ID (null for anonymous)

**Returns:** `bool` - Success status

#### `getGigViewsCount($gig_id)`
Gets total view count for a gig.

**Returns:** `int` - View count

#### `getGigUniqueViewersCount($gig_id)`
Gets unique viewer count for a gig.

**Returns:** `int` - Unique viewer count

### Analytics Dashboard Methods

#### `getUserAnalytics($bracu_id)`
Gets comprehensive user analytics.

**Returns:** Array with:
- `total_logins`: Total login count
- `total_gig_views`: Views on user's gigs
- `gigs_created`: Gigs created by user
- `gigs_applied`: Gigs user applied to
- `total_earnings`: Total earnings
- `pending_earnings`: Pending earnings
- `completion_rate`: Percentage of completed gigs
- `last_activity`: Last activity timestamp
- `activity_breakdown`: Activity counts by type

#### `getGigAnalytics($gig_id)`
Gets analytics for a specific gig.

**Returns:** Array with:
- `views`: Total views
- `unique_viewers`: Unique viewer count
- `applications`: Application count
- `completion_status`: Gig status
- `earned_amount`: Total earned from gig

#### `getTrendingGigs($limit, $days)`
Gets trending gigs based on views.

**Parameters:**
- `$limit` (int): Number of gigs to return (default: 10)
- `$days` (int): Time period in days (default: 7)

**Returns:** Array of trending gigs

#### `getUserActivityHistory($bracu_id, $limit)`
Gets user's activity history.

**Parameters:**
- `$bracu_id` (string): User ID
- `$limit` (int): Number of activities (default: 50)

**Returns:** Array of activities

## Analytics Dashboards

### Client Analytics (`client/analytics.php`)
Shows:
- Total logins
- Profile views (views on their gigs)
- Gigs posted
- Completed gigs
- Total spent
- Last activity
- Spending overview (active gigs, average per gig, min-max range)
- Spending by category
- Gig status breakdown (completed, pending, listed)

**Access:** Only accessible to users with `preferred_mode = 'hiring'`

### Freelancer Analytics (`freelancer/analytics.php`)
Shows:
- Total logins
- Gig views
- Gigs created
- Gigs applied
- Total earnings
- Pending earnings
- Completion rate
- Last activity
- Gig performance (views, applications, earned amount per gig)
- Activity breakdown

**Access:** Only accessible to users with `preferred_mode = 'working'`

## Testing

### Automated Test Script
Run the test script to verify analytics functionality:

```bash
php tests/test_analytics.php
```

This will:
1. Check database connection
2. Verify analytics tables exist
3. Test all Analytics class methods
4. Log sample activities
5. Retrieve and display analytics data

### Manual Testing Steps

#### 1. Test Login Tracking
1. Log out if logged in
2. Log in with any user account
3. Check `Analytics_Activity` table for new login entry:
   ```sql
   SELECT * FROM Analytics_Activity WHERE activity_type = 'login' ORDER BY created_at DESC LIMIT 5;
   ```

#### 2. Test Gig Creation Tracking
1. Log in as a client (hiring mode)
2. Create a new gig
3. Check for gig_create activity:
   ```sql
   SELECT * FROM Analytics_Activity WHERE activity_type = 'gig_create' ORDER BY created_at DESC LIMIT 5;
   ```

#### 3. Test Gig View Tracking
1. Log in as a freelancer (working mode)
2. Visit the marketplace
3. Check `Gig_Views` table:
   ```sql
   SELECT gv.*, g.LIST_OF_GIGS FROM Gig_Views gv 
   JOIN Gigs g ON gv.GID = g.GID 
   ORDER BY viewed_at DESC LIMIT 10;
   ```

#### 4. Test Gig Application Tracking
1. As a freelancer, accept a gig
2. Check for gig_apply activity:
   ```sql
   SELECT * FROM Analytics_Activity WHERE activity_type = 'gig_apply' ORDER BY created_at DESC LIMIT 5;
   ```

#### 5. Test Profile View Tracking
1. Log in as any user
2. Visit another user's public profile
3. Check for profile_view activity:
   ```sql
   SELECT * FROM Analytics_Activity WHERE activity_type = 'profile_view' ORDER BY created_at DESC LIMIT 5;
   ```

#### 6. Test Message Tracking
1. Log in and send a message to another user
2. Check for message_send activity:
   ```sql
   SELECT * FROM Analytics_Activity WHERE activity_type = 'message_send' ORDER BY created_at DESC LIMIT 5;
   ```

#### 7. Test Analytics Dashboards
1. **Client Dashboard:**
   - Log in as a client
   - Visit `/client/analytics.php`
   - Verify all metrics display correctly
   - Check spending breakdown by category

2. **Freelancer Dashboard:**
   - Log in as a freelancer
   - Visit `/freelancer/analytics.php`
   - Verify all metrics display correctly
   - Check gig performance section

### SQL Queries for Verification

#### Check Total Activities by Type
```sql
SELECT activity_type, COUNT(*) as count 
FROM Analytics_Activity 
GROUP BY activity_type 
ORDER BY count DESC;
```

#### Check Most Viewed Gigs
```sql
SELECT g.GID, g.LIST_OF_GIGS, COUNT(gv.id) as views
FROM Gigs g
LEFT JOIN Gig_Views gv ON g.GID = gv.GID
GROUP BY g.GID
ORDER BY views DESC
LIMIT 10;
```

#### Check User Activity Summary
```sql
SELECT 
    u.BRACU_ID,
    u.full_name,
    COUNT(DISTINCT aa.id) as total_activities,
    COUNT(DISTINCT CASE WHEN aa.activity_type = 'login' THEN aa.id END) as logins,
    COUNT(DISTINCT CASE WHEN aa.activity_type = 'gig_create' THEN aa.id END) as gigs_created,
    COUNT(DISTINCT CASE WHEN aa.activity_type = 'gig_apply' THEN aa.id END) as gigs_applied
FROM User u
LEFT JOIN Analytics_Activity aa ON u.BRACU_ID = aa.BRACU_ID
GROUP BY u.BRACU_ID
ORDER BY total_activities DESC
LIMIT 10;
```

## Edge Cases Handled

### 1. **Null Values**
- All methods handle null values gracefully
- Optional parameters default to null
- Database queries use `COALESCE` for null safety

### 2. **Anonymous Gig Views**
- Gig views can be tracked without a logged-in user
- Uses IP address for anonymous tracking
- `BRACU_ID` can be null in `Gig_Views` table

### 3. **Division by Zero**
- Completion rate calculation checks for zero gigs
- Average calculations use `COALESCE` with default 0

### 4. **Missing Data**
- All fetch operations check for false/null results
- Default values provided for missing data
- Empty arrays returned instead of null

### 5. **Database Errors**
- All database operations wrapped in try-catch
- Errors logged to PHP error log
- Methods return safe defaults on error

### 6. **Duplicate Views**
- Multiple views of same gig by same user are tracked separately
- This provides accurate view count metrics
- Unique viewer count available separately

### 7. **Self-Profile Views**
- Profile view tracking skips when user views their own profile
- Prevents inflated profile view counts

### 8. **Invalid Activity Types**
- Activity types validated by database ENUM constraint
- Invalid types will fail gracefully with error logging

## Performance Considerations

### Indexes
The following indexes should exist for optimal performance:
- `Analytics_Activity`: Index on `BRACU_ID`, `activity_type`, `created_at`
- `Gig_Views`: Index on `GID`, `BRACU_ID`, `viewed_at`
- `User_Earnings`: Index on `BRACU_ID`, `status`

### Query Optimization
- All queries use prepared statements
- Aggregate queries use appropriate GROUP BY
- LIMIT clauses prevent excessive data retrieval
- JOIN operations use indexed foreign keys

## Future Enhancements

### Potential Additions
1. **Real-time Analytics**: WebSocket-based live updates
2. **Export Functionality**: CSV/PDF export of analytics data
3. **Advanced Filtering**: Date range filters, category filters
4. **Visualization**: Charts and graphs using Chart.js
5. **Comparative Analytics**: Compare performance across time periods
6. **Notification Triggers**: Alert users on milestone achievements
7. **Admin Analytics**: Platform-wide analytics dashboard
8. **A/B Testing**: Track different feature variations

## Troubleshooting

### Analytics Not Showing
1. Check if analytics tables exist:
   ```sql
   SHOW TABLES LIKE 'Analytics_%';
   SHOW TABLES LIKE 'Gig_Views';
   SHOW TABLES LIKE 'User_Earnings';
   ```

2. Run migration if tables missing:
   ```bash
   php database/run_migration.php migration_add_analytics_indexing_community.sql
   ```

3. Check PHP error logs for database errors

### Incorrect Counts
1. Verify data in tables directly
2. Check for timezone issues
3. Ensure foreign key constraints are working
4. Verify user sessions are working correctly

### Performance Issues
1. Check if indexes exist
2. Analyze slow queries with EXPLAIN
3. Consider adding caching layer
4. Limit historical data retrieval

## Security Considerations

### Data Privacy
- IP addresses are stored but not displayed publicly
- User agents stored for fraud detection
- Activity data is user-specific and access-controlled

### Access Control
- Analytics dashboards require authentication
- Users can only view their own analytics
- Mode-based access control (hiring/working)

### SQL Injection Prevention
- All queries use prepared statements
- Input validation on all parameters
- Type casting for numeric values

### XSS Prevention
- All output uses `h()` helper for HTML escaping
- JSON data properly encoded
- No raw user input displayed

## Maintenance

### Regular Tasks
1. **Archive Old Data**: Consider archiving analytics data older than 1 year
2. **Index Maintenance**: Rebuild indexes periodically
3. **Log Rotation**: Rotate PHP error logs
4. **Performance Monitoring**: Monitor query performance

### Backup Recommendations
- Include analytics tables in regular backups
- Consider separate backup schedule for analytics (less critical)
- Test restore procedures

## Support

For issues or questions:
1. Check this documentation
2. Review PHP error logs
3. Check database error logs
4. Run test script for diagnostics
5. Verify database schema matches migration files
