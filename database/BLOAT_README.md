# Database Bloat Setup Script

This package contains a comprehensive database bloat script for the BRACU Student Freelance Marketplace. It generates thousands of dummy records across all tables for testing and development purposes.

## 🚀 Quick Start

### Method 1: Browser Interface (Recommended)

1. Navigate to: `http://localhost/proj/database/bloat_setup.html`
2. Click "Start Database Bloat"
3. Wait for the process to complete (1-2 minutes)
4. Use the test credentials to log in

### Method 2: Command Line

```bash
cd /opt/lampp/htdocs/proj
php database/bloat_database.php
```

### Method 3: Direct PHP Include

```php
require_once '/opt/lampp/htdocs/proj/database/bloat_database.php';
```

## 🔐 Test User Credentials

**All dummy users use password:** `password123`

The password hash is: `$2y$10$Uxu/O0g.SgvivIc1nhuH..PIB6uU.GUyNY4PwNBjwHRyfCITKvvTu` (verified)

### User Categories

#### 1. Admin User
- **BRACU ID:** `20101000`
- **Email:** `20101000@g.bracu.ac.bd`
- **Password:** `password123`
- **Credit Balance:** 10,000
- **Role:** Admin (is_admin = 1), Can hire and freelance

#### 2. Freelancer Users (50 users)
- **BRACU IDs:** `20101001` - `20101050`
- **Email Format:** `201010XX@g.bracu.ac.bd` (replace XX with your ID)
- **Password:** `password123` (all)
- **Credit Balance:** Random 100-5000
- **Role:** Freelancer (freelancer = 1, client = 0)
- **Preferred Mode:** Working

#### 3. Client Users (50 users)
- **BRACU IDs:** `20101051` - `20101100`
- **Email Format:** `201010XX@g.bracu.ac.bd` (replace XX with your ID)
- **Password:** `password123` (all)
- **Credit Balance:** Random 500-10,000
- **Role:** Client (client = 1, freelancer = 0)
- **Preferred Mode:** Hiring

#### 4. Mixed Role Users (50 users)
- **BRACU IDs:** `20101101` - `20101150`
- **Email Format:** `201010XX@g.bracu.ac.bd` (replace XX with your ID)
- **Password:** `password123` (all)
- **Credit Balance:** Random 1,000-8,000
- **Role:** Both Client and Freelancer (client = 1, freelancer = 1)
- **Preferred Mode:** Random (hiring or working)

## 📊 Data Generated

The script creates comprehensive test data:

| Table | Records | Description |
|-------|---------|-------------|
| User | 151 | 1 admin + 50 freelancers + 50 clients + 50 mixed |
| Gigs | 300 | Various categories, statuses, and deadlines |
| Working_on | ~90 | Freelancer assignments to gigs (30% of gigs) |
| Analytics_Activity | 1,000 | User login, view, create, apply activities |
| Gig_Views | 2,000 | Gig view tracking data |
| User_Earnings | ~90 | Earnings from completed gigs |
| Ratings | 300 | User ratings and reviews |
| User_Badges | ~375 | Achievement badges (avg 2-3 per user) |
| Messages | 500 | User-to-user messages |
| Forum_Threads | 100 | Discussion threads |
| Forum_Replies | 500 | Replies to threads |
| Gig_Search_Index | 300 | Search keywords for gigs |
| Transaction_Ledger | 400 | Financial transaction audit trail |
| User_Points | 151 | Points system data |
| Points_Activity | 300 | Points earning/redemption history |
| Transaction_Batch | 50 | Batch transaction processing |
| Transaction_Disputes | ~25 | Transaction disputes and refunds |

**Total: ~4,250+ records**

## 🎯 Use Cases

### Testing User Roles
- Test different permission levels with admin, client, and freelancer accounts
- Test mixed-role users who can both hire and work

### Testing Business Logic
- Test gig marketplace operations
- Test payment and transaction systems
- Test points and rewards system
- Test dispute resolution
- Test analytics and reporting

### Performance Testing
- Test database queries with realistic data volume
- Test search and filtering functionality
- Test reporting and aggregation queries

### UI/UX Testing
- Test layouts with populated data
- Test pagination and list views
- Test dashboard statistics
- Test notification systems

## 🔧 Customization

### Modifying Data Volume

Edit `bloat_database.php` and change these values:

```php
// Line ~200: Change number of freelancers
for ($i = 1; $i <= 50; $i++) {  // Change 50 to desired number

// Line ~230: Change number of clients
for ($i = 51; $i <= 100; $i++) {  // Change ranges as needed

// Line ~280: Change number of gigs
for ($i = 0; $i < 300; $i++) {  // Change 300 to desired number

// Line ~380: Change number of activities
for ($i = 0; $i < 1000; $i++) {  // Change 1000 to desired number
```

### Modifying Gig Categories

Edit the `$gig_descriptions` array in `createGigs()` function:

```php
$gig_descriptions = [
    'Your custom gig description here',
    'Another gig type',
    // Add more...
];
```

### Changing Password

To use a different password for test users:

1. Go to: `http://localhost/proj/generate_hash.php`
2. Enter your desired password
3. Copy the generated hash
4. Replace `$password_hash` in `bloat_database.php` with the new hash

Or generate a hash manually with PHP:
```php
echo password_hash('your_password', PASSWORD_BCRYPT);
```

## ⚠️ Important Notes

1. **Backup First:** Always backup your database before running this script
2. **Development Only:** Only use on development/testing environments
3. **Clear Existing Data:** The script will skip duplicate entries, but won't clear existing data by default
4. **Database Size:** The script may take 1-2 minutes to complete
5. **Timeout:** Set PHP `max_execution_time` to at least 300 seconds

## 🚨 Clearing Old Data

If you want to start fresh, uncomment the `clearAllData()` call in `bloat_database.php`:

```php
// Step 1: Clear existing data
clearAllData();  // <-- Uncomment this line
```

Or run the clear through SQL:

```sql
USE bracu_freelance_marketplace;

TRUNCATE TABLE Transaction_Disputes;
TRUNCATE TABLE Transaction_Batch;
TRUNCATE TABLE Points_Activity;
TRUNCATE TABLE User_Points;
TRUNCATE TABLE Transaction_Ledger;
TRUNCATE TABLE Gig_Search_Index;
TRUNCATE TABLE Forum_Replies;
TRUNCATE TABLE Forum_Threads;
TRUNCATE TABLE Messages;
TRUNCATE TABLE User_Badges;
TRUNCATE TABLE Ratings;
TRUNCATE TABLE User_Earnings;
TRUNCATE TABLE Gig_Views;
TRUNCATE TABLE Analytics_Activity;
TRUNCATE TABLE Working_on;
TRUNCATE TABLE Gigs;
TRUNCATE TABLE User;
```

## 🔍 Verification

After running the script, verify the data:

```sql
-- Check user count
SELECT COUNT(*) as total_users FROM User;

-- Check gigs count
SELECT COUNT(*) as total_gigs FROM Gigs;

-- Check messages count
SELECT COUNT(*) as total_messages FROM Messages;

-- View admin user
SELECT * FROM User WHERE BRACU_ID = '20101000';

-- View sample freelancer
SELECT * FROM User WHERE BRACU_ID = '20101001';
```

## 📝 Sample Login Sequence

1. Open `http://localhost/proj/index.php`
2. Use credentials:
   - **BRACU ID:** `20101000`
   - **Password:** `password123`
3. Click "Login"
4. You should be logged in as Admin

## 🐛 Troubleshooting

### Script Stops or Times Out
- Increase `max_execution_time` in `php.ini`
- Run with CLI instead of browser

### Duplicate Key Errors
- Normal behavior - script skips duplicates
- Check the output log for "duplicate error" messages

### Database Connection Error
- Check `includes/config.php` database credentials
- Verify database exists and is running
- Check MySQL user permissions

### Out of Memory Error
- Increase `memory_limit` in `php.ini` to at least 256M
- Run in smaller batches instead

## 📧 Generated Email Patterns

All emails follow this pattern:
```
{BRACU_ID}@g.bracu.ac.bd
```

Examples:
- `20101000@g.bracu.ac.bd` (Admin)
- `20101001@g.bracu.ac.bd` (First freelancer)
- `20101050@g.bracu.ac.bd` (Last freelancer)
- `20101051@g.bracu.ac.bd` (First client)
- etc.

## 📞 Support

For issues or improvements:
1. Check the error messages in the output
2. Verify database configuration
3. Check PHP error logs
4. Ensure all database tables are created (run migrations first)

## 📄 Files Included

- `bloat_database.php` - Main PHP script for generating dummy data
- `bloat_setup.html` - Browser-based UI for running the bloat script
- `BLOAT_README.md` - This documentation file

---

**Last Updated:** May 5, 2026
**Version:** 1.0
**Database:** bracu_freelance_marketplace
