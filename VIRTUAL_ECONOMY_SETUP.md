# Virtual Economy & Transaction Processing - Implementation Guide

## Quick Setup (5 minutes)

### Step 1: Database Migration
```bash
# In phpMyAdmin or MySQL CLI, run:
SOURCE database/migration_add_virtual_economy.sql;
```

This creates:
- ✅ Transaction_Ledger table
- ✅ User_Points table
- ✅ Points_Activity table
- ✅ Transaction_Batch table
- ✅ Transaction_Disputes table
- ✅ Redemption_History table

### Step 2: Verify Files Created
Check that these files exist:
```
✅ includes/virtual_economy.php
✅ client/transaction_ledger.php
✅ freelancer/rewards_system.php
✅ client/disputes.php
✅ admin/disputes_admin.php
✅ VIRTUAL_ECONOMY.md (this documentation)
```

### Step 3: Update Navigation Header
Edit `includes/header.php` and add navigation links:

```php
<!-- Add to main navigation -->
<a href="<?php echo ($role === 'freelancer') ? '../' : ''; ?>freelancer/rewards_system.php" class="nav-link">
    ⭐ Rewards
</a>
<a href="<?php echo ($role === 'client') ? '../' : ''; ?>client/transaction_ledger.php" class="nav-link">
    💰 Transactions
</a>
<a href="<?php echo ($role === 'client') ? '../' : ''; ?>client/disputes.php" class="nav-link">
    ⚖️ Disputes
</a>
```

### Step 4: Test the Installation
Visit these pages in your browser:
1. `/freelancer/rewards_system.php` - Should show 0 points
2. `/client/transaction_ledger.php` - Should show empty history
3. `/client/disputes.php` - Should show no disputes

---

## Testing Checklist

### Phase 1: Database & Classes (10 min)

- [ ] Run migration SQL successfully
- [ ] Verify all 6 new tables created in database
- [ ] Check User_Points table has entries for existing users
- [ ] Verify includes/virtual_economy.php loads without errors

```bash
# Test PHP syntax
php -l includes/virtual_economy.php
```

### Phase 2: Points System (15 min)

#### Test Point Awarding
```php
// In a test script or debug page
$economy = new VirtualEconomy($pdo);
$result = $economy->awardPoints('test_user_id', 50, 'earned', null, 'Test points');
echo $result ? 'Points awarded' : 'Failed';
```

- [ ] Award points to a test user
- [ ] Check User_Points table updated
- [ ] Check Points_Activity table logged
- [ ] Verify user can see points in rewards_system.php

#### Test Point Redemption
- [ ] User visits `/freelancer/rewards_system.php`
- [ ] User has at least 10 points
- [ ] User enters points to redeem
- [ ] User clicks "Redeem Points"
- [ ] Check: User's credit_balance increased
- [ ] Check: Points decreased
- [ ] Check: Redemption_History table updated
- [ ] Check: Points_Activity shows 'redeemed' entry

### Phase 3: Transaction Ledger (15 min)

#### Record Transaction
```php
$txnId = $economy->recordTransaction(
    'client_bracu_id',
    'freelancer_bracu_id',
    'gig_payment',
    100.00,
    42,
    'Test transaction'
);
echo "Transaction ID: " . $txnId;
```

- [ ] Transaction recorded with unique ID
- [ ] Check Transaction_Ledger table
- [ ] Status should be 'pending'

#### View Transaction Ledger
- [ ] User visits `/client/transaction_ledger.php`
- [ ] See transaction in the table
- [ ] Verify direction indicator (→ or ←)
- [ ] Verify amount with correct sign
- [ ] Verify status badge
- [ ] Click "View" button for details

### Phase 4: Batch Processing (20 min)

#### Create and Process Batch
```php
$batchId = $economy->createBatch('daily_settlements', 'admin_user_id');
$result = $economy->processBatch($batchId);
echo json_encode($result, JSON_PRETTY_PRINT);
```

Expected output:
```json
{
    "success": true,
    "batch_id": "BATCH-...",
    "successful": X,
    "failed": 0,
    "total_amount": Y
}
```

- [ ] Batch created successfully
- [ ] Check Transaction_Batch table
- [ ] Batch status should be 'completed' after processing
- [ ] Check successful_transactions count
- [ ] Verify transactions marked as 'completed'
- [ ] Verify user credit_balance updated

### Phase 5: Disputes System (25 min)

#### File Dispute
- [ ] User visits `/client/disputes.php`
- [ ] Fill in dispute form:
  - [ ] Transaction ID (from ledger)
  - [ ] Against User ID
  - [ ] Dispute Reason
  - [ ] Description
- [ ] Click "Submit Dispute"
- [ ] Confirm success message
- [ ] Check Transaction_Disputes table

#### View Dispute as User
- [ ] Dispute appears in user's disputes list
- [ ] Status shows as 'open'
- [ ] All details visible correctly

#### Resolve Dispute as Admin
- [ ] Admin visits `/admin/disputes_admin.php`
- [ ] See the open dispute in queue
- [ ] Click "Review" button
- [ ] Modal opens with dispute details
- [ ] Select resolution type:
  - [ ] "Issue Full Refund"
  - [ ] Enter refund amount
- [ ] Add admin notes
- [ ] Click "Resolve Dispute"
- [ ] Check dispute status changed to 'resolved'
- [ ] If refund: check respondent credit_balance decreased
- [ ] If refund: check complainant credit_balance increased

### Phase 6: Integration with Gig Completion (20 min)

#### Integration Point 1: Mark Gig as Done
Add to your gig completion code:

```php
// When client marks gig as done in client/my_gigs.php
require_once '../includes/virtual_economy.php';
$economy = new VirtualEconomy($pdo);

// Record transaction
$txnId = $economy->recordTransaction(
    $clientId,
    $freelancerId,
    'gig_payment',
    $gigAmount,
    $gigId
);

// Award points to freelancer
$pointsEarned = floor($gigAmount / 4); // ৳100 = 25 points
$economy->awardPoints(
    $freelancerId,
    $pointsEarned,
    'earned',
    $gigId,
    'Gig completion'
);

// Award points to client
$economy->awardPoints(
    $clientId,
    5,
    'earned',
    $gigId,
    'Posted gig'
);
```

- [ ] Update client/my_gigs.php with integration code
- [ ] Test: Mark a gig as done
- [ ] Check: Transaction created in ledger
- [ ] Check: Points awarded to freelancer
- [ ] Check: Points awarded to client

#### Integration Point 2: Daily Batch Processing
Create a scheduled task or admin page for daily settlements:

```php
// admin/run_daily_settlements.php
$batchId = $economy->createBatch('daily_settlements', $_SESSION['bracu_id']);
$result = $economy->processBatch($batchId);
```

- [ ] Create admin page for batch processing
- [ ] Test running batch manually
- [ ] Verify all pending transactions settled
- [ ] Check email logs (if implemented)

---

## Testing Data Setup

### Sample Test User Points
```sql
INSERT INTO User_Points (BRACU_ID, total_points, available_points, lifetime_points, points_tier)
VALUES
('test_user_1', 100, 100, 100, 'bronze'),
('test_user_2', 500, 500, 500, 'silver'),
('test_user_3', 2000, 2000, 2000, 'gold');
```

### Sample Transactions
```sql
INSERT INTO Transaction_Ledger 
(transaction_id, from_user, to_user, transaction_type, amount, gig_id, status, description, created_at)
VALUES
('TXN-test-001', 'test_user_1', 'test_user_2', 'gig_payment', 100.00, 1, 'pending', 'Test transaction', NOW()),
('TXN-test-002', 'test_user_2', 'test_user_1', 'bonus', 50.00, NULL, 'completed', 'Test bonus', NOW());
```

---

## Verification Checklist

After completing all phases, verify:

- [ ] **Database**
  - [ ] All 6 tables created
  - [ ] All indexes created
  - [ ] User_Points initialized for all users
  
- [ ] **PHP Class**
  - [ ] Virtual Economy class loads
  - [ ] All methods work without errors
  - [ ] Database transactions work correctly
  
- [ ] **Frontend Pages**
  - [ ] All 5 pages load without 404
  - [ ] Authentication required on all pages
  - [ ] Styling looks correct
  - [ ] Forms submit properly
  
- [ ] **User Features**
  - [ ] Points awarded and tracked
  - [ ] Points redeemable for credit
  - [ ] Transactions recorded and visible
  - [ ] Disputes can be filed and resolved
  
- [ ] **Admin Features**
  - [ ] Dispute management panel works
  - [ ] Batch processing works
  - [ ] Admin-only pages protected
  
- [ ] **Data Integrity**
  - [ ] Credit balances correct after transactions
  - [ ] Points accurately calculated
  - [ ] Audit trail complete

---

## Troubleshooting Common Issues

### Issue: "Class not found" error
**Solution:** Ensure `includes/virtual_economy.php` exists and is included before use
```php
require_once 'includes/virtual_economy.php';
```

### Issue: Points not updating
**Solution:** Check that `User_Points` table has entry for user:
```sql
SELECT * FROM User_Points WHERE BRACU_ID = 'your_user_id';
```

If missing, insert it:
```sql
INSERT INTO User_Points (BRACU_ID, total_points, available_points, points_tier)
VALUES ('your_user_id', 0, 0, 'bronze');
```

### Issue: Transactions not settling
**Solution:** Check `Transaction_Ledger` status and `User` credit_balance:
```sql
SELECT * FROM Transaction_Ledger WHERE transaction_id = 'TXN-xxx';
SELECT credit_balance FROM User WHERE BRACU_ID = 'your_id';
```

### Issue: Dispute resolution not working
**Solution:** Verify:
1. User is admin: `is_admin = 1`
2. Dispute status is 'open' or 'under_review'
3. Try-catch block error message

### Issue: Pages return 404
**Solution:** Check file paths and folder structure:
```
✅ includes/virtual_economy.php (relative path)
✅ client/transaction_ledger.php (same folder as other client pages)
✅ freelancer/rewards_system.php (same folder as other freelancer pages)
✅ admin/disputes_admin.php (same folder as other admin pages)
```

---

## Performance Notes

- Transaction_Ledger should be indexed for quick lookups
- User_Points queries are fast (indexed by BRACU_ID)
- Batch processing handles large volumes efficiently
- Pagination implemented on all list views

### Sample Performance Queries
```sql
-- Get user's transactions (fast)
SELECT * FROM Transaction_Ledger 
WHERE (from_user = 'user_id' OR to_user = 'user_id')
ORDER BY created_at DESC LIMIT 50;

-- Get open disputes (fast, indexed)
SELECT * FROM Transaction_Disputes 
WHERE status IN ('open', 'under_review');

-- Get user points (instant, indexed)
SELECT * FROM User_Points WHERE BRACU_ID = 'user_id';
```

---

## Deployment Checklist

Before going live:

- [ ] Run all SQL migrations
- [ ] Test all features in development
- [ ] Review security (authentication, admin checks)
- [ ] Test with real user data
- [ ] Set up automated daily batch processing
- [ ] Configure email notifications (optional)
- [ ] Create admin documentation
- [ ] Train admins on dispute resolution
- [ ] Monitor first few days of transactions
- [ ] Backup database before deployment

---

## Support

For issues or questions:
1. Check VIRTUAL_ECONOMY.md for API reference
2. Review this implementation guide
3. Check database tables for data consistency
4. Review error logs in includes folder

---

**Created:** May 2026
**Status:** Ready for Testing
**Last Updated:** May 4, 2026
