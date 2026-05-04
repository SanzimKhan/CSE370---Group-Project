# Virtual Economy & Transaction Processing System

## Overview

This document describes the Virtual Economy and Transaction Processing system added to the BRACU Freelance Marketplace. The system includes:

1. **Rewards/Points System** - Users earn points through activities and can redeem them for credits
2. **Transaction Ledger** - Complete audit trail of all financial transactions
3. **Batch Processing** - Process multiple transactions efficiently
4. **Dispute Resolution** - Handle refunds, chargebacks, and disputes

---

## Database Tables

### 1. Transaction_Ledger
Tracks all financial transactions with complete audit trail.

**Columns:**
- `id`: Primary key
- `transaction_id`: Unique transaction identifier (TXN-...)
- `from_user`: Sender BRACU_ID
- `to_user`: Recipient BRACU_ID
- `transaction_type`: gig_payment, points_redemption, refund, bonus, withdrawal
- `amount`: Credit amount
- `points_transferred`: Points amount (if applicable)
- `gig_id`: Related gig (optional)
- `status`: pending, completed, failed, reversed
- `description`: Transaction description
- `metadata`: JSON data for additional context
- `batch_id`: Associated batch ID (for batch processing)
- `created_at`, `updated_at`, `completed_at`: Timestamps

**Indexes:**
- from_user, to_user, status, transaction_type, batch_id, created_at

### 2. User_Points
Stores user points balances and tier information.

**Columns:**
- `BRACU_ID`: User identifier
- `total_points`: Current point balance
- `available_points`: Points available for redemption
- `points_redeemed`: Total points redeemed so far
- `lifetime_points`: Total points earned all time
- `points_tier`: bronze, silver, gold, platinum
- `last_points_earned_at`, `last_points_redeemed_at`: Timestamps

**Tiers:**
- Bronze: 0-499 points
- Silver: 500-999 points
- Gold: 1000-4999 points
- Platinum: 5000+ points

### 3. Points_Activity
Tracks all point activities (earned, redeemed, bonus, expired).

**Columns:**
- `BRACU_ID`: User identifier
- `activity_type`: earned, redeemed, bonus, expired
- `points_amount`: Amount of points
- `related_gig`: Associated gig ID
- `description`: Activity description
- `created_at`: Timestamp

### 4. Transaction_Batch
Manages batch transaction processing.

**Columns:**
- `batch_id`: Unique batch identifier
- `batch_type`: daily_settlements, points_conversion, refund_batch, bonus_distribution
- `total_transactions`: Count of transactions in batch
- `successful_transactions`: Count of successful ones
- `failed_transactions`: Count of failed ones
- `total_amount`: Total credit amount processed
- `status`: pending, processing, completed, failed
- `initiated_by`: Admin user who initiated
- `created_at`, `started_at`, `completed_at`: Timestamps
- `error_log`: JSON array of errors

### 5. Transaction_Disputes
Stores dispute and dispute resolution information.

**Columns:**
- `dispute_id`: Unique dispute identifier (DSP-...)
- `transaction_id`: Related transaction ID
- `complainant_id`: User filing the complaint
- `respondent_id`: User being complained about
- `gig_id`: Related gig (optional)
- `dispute_reason`: payment_error, work_not_completed, quality_issue, duplicate_charge, unauthorized, other
- `dispute_description`: Detailed description
- `status`: open, under_review, resolved, closed
- `resolution_type`: refund, partial_refund, accepted, rejected
- `refund_amount`: Amount to refund
- `admin_notes`: Admin's notes on resolution
- `resolved_by`: Admin user who resolved
- `created_at`, `updated_at`, `resolved_at`: Timestamps

### 6. Redemption_History
Tracks point redemption history.

**Columns:**
- `redemption_id`: Unique redemption identifier
- `BRACU_ID`: User who redeemed
- `points_redeemed`: Points amount
- `credit_received`: Credit amount
- `redemption_rate`: Conversion rate (default 0.1)
- `status`: pending, completed, cancelled
- `created_at`, `completed_at`: Timestamps

---

## PHP Class: VirtualEconomy

Located in `includes/virtual_economy.php`

### Constructor
```php
$economy = new VirtualEconomy($pdo);
```

### Points Methods

#### awardPoints()
Award points to a user for completing an activity.

```php
bool awardPoints(
    string $userId,
    int $points,
    string $activityType,
    ?int $gigId = null,
    string $description = ''
): bool
```

**Parameters:**
- `$userId`: BRACU_ID of user
- `$points`: Number of points to award
- `$activityType`: Type of activity (earned, bonus, etc.)
- `$gigId`: (Optional) Related gig ID
- `$description`: (Optional) Description of activity

**Example:**
```php
$economy->awardPoints('user123', 50, 'earned', 42, 'Gig completion bonus');
```

#### redeemPoints()
Convert user points to credits.

```php
?array redeemPoints(string $userId, int $points): ?array
```

**Returns:**
```php
[
    'redemption_id' => 'RED-...',
    'points' => 50,
    'credit' => 5.00,
    'new_balance' => 150
]
```

**Example:**
```php
$result = $economy->redeemPoints('user123', 50); // 50 points = ৳5.00
```

#### getUserPoints()
Get user's point summary.

```php
?array getUserPoints(string $userId): ?array
```

**Returns:**
```php
[
    'BRACU_ID' => 'user123',
    'total_points' => 200,
    'available_points' => 200,
    'points_redeemed' => 50,
    'lifetime_points' => 250,
    'points_tier' => 'silver',
    ...
]
```

#### getPointsActivity()
Get user's point activity history.

```php
array getPointsActivity(string $userId, int $limit = 20, int $offset = 0): array
```

### Transaction Ledger Methods

#### recordTransaction()
Record a transaction in the ledger.

```php
?string recordTransaction(
    string $fromUser,
    string $toUser,
    string $transactionType,
    float $amount,
    ?int $gigId = null,
    string $description = '',
    ?int $pointsTransferred = null
): ?string
```

**Returns:** Transaction ID or null on error

**Example:**
```php
$txnId = $economy->recordTransaction(
    'client123',
    'freelancer456',
    'gig_payment',
    100.00,
    42,
    'Payment for web development'
);
```

#### getTransactionLedger()
Get transaction history for a user.

```php
array getTransactionLedger(string $userId, int $limit = 50, int $offset = 0): array
```

#### verifyTransaction()
Verify a transaction is valid for settlement.

```php
bool verifyTransaction(string $transactionId): bool
```

### Batch Processing Methods

#### createBatch()
Create a new transaction batch.

```php
?string createBatch(string $batchType, string $initiatedBy): ?string
```

**Parameters:**
- `$batchType`: daily_settlements, points_conversion, refund_batch, bonus_distribution
- `$initiatedBy`: Admin user ID

**Returns:** Batch ID or null on error

**Example:**
```php
$batchId = $economy->createBatch('daily_settlements', 'admin123');
```

#### processBatch()
Process all transactions in a batch.

```php
array processBatch(string $batchId): array
```

**Returns:**
```php
[
    'success' => true,
    'batch_id' => 'BATCH-...',
    'successful' => 45,
    'failed' => 2,
    'total_amount' => 5000.00,
    'errors' => [...]
]
```

#### getBatch()
Get batch details.

```php
?array getBatch(string $batchId): ?array
```

#### getBatchHistory()
Get batch processing history.

```php
array getBatchHistory(int $limit = 20, int $offset = 0): array
```

### Dispute Methods

#### createDispute()
Create a new dispute.

```php
?string createDispute(
    string $transactionId,
    string $complainantId,
    string $respondentId,
    string $reason,
    string $description,
    ?int $gigId = null
): ?string
```

**Reasons:** payment_error, work_not_completed, quality_issue, duplicate_charge, unauthorized, other

**Returns:** Dispute ID or null

**Example:**
```php
$disputeId = $economy->createDispute(
    'TXN-123456',
    'user123',
    'user456',
    'work_not_completed',
    'Freelancer did not complete the work as agreed'
);
```

#### getUserDisputes()
Get disputes for a user (as complainant or respondent).

```php
array getUserDisputes(string $userId, int $limit = 20, int $offset = 0): array
```

#### resolveDispute()
Resolve a dispute with refund or rejection.

```php
bool resolveDispute(
    string $disputeId,
    string $resolutionType,
    string $adminId,
    ?float $refundAmount = null,
    string $notes = ''
): bool
```

**Resolution Types:** refund, partial_refund, accepted, rejected

**Example:**
```php
$economy->resolveDispute(
    'DSP-789',
    'refund',
    'admin123',
    100.00,
    'Full refund approved - work not completed'
);
```

#### getOpenDisputes()
Get all open disputes (admin view).

```php
array getOpenDisputes(int $limit = 50, int $offset = 0): array
```

---

## Frontend Pages

### 1. client/transaction_ledger.php
View transaction history with filtering and pagination.

**Features:**
- View all transactions (sent/received)
- Filter by type and status
- Pagination support
- Transaction detail view

**Access:** Clients and freelancers

### 2. freelancer/rewards_system.php
View and redeem points.

**Features:**
- View point balance and tier
- Redeem points for credits
- View points activity history
- Tier information

**Access:** All users (primarily freelancers)

### 3. client/disputes.php
File and view disputes.

**Features:**
- Create new dispute
- View all disputes (filed or against user)
- Real-time dispute status
- Dispute guidelines

**Access:** All users

### 4. admin/disputes_admin.php
Admin dispute management panel.

**Features:**
- View all open disputes
- Resolution tools (refund, reject, accept)
- Admin notes and decision tracking
- Summary statistics

**Access:** Admins only

---

## Integration Points

### With Gig Completion
When a gig is marked as done:

```php
// Record transaction
$txnId = $economy->recordTransaction(
    $clientId,
    $freelancerId,
    'gig_payment',
    $gigAmount,
    $gigId,
    'Gig completion payment'
);

// Award points to freelancer
$economy->awardPoints(
    $freelancerId,
    10, // 10 points per gig
    'earned',
    $gigId,
    'Completed gig'
);
```

### With Gig Application
When freelancer applies for a gig:

```php
// Award points for activity
$economy->awardPoints(
    $freelancerId,
    2,
    'earned',
    $gigId,
    'Applied to gig'
);
```

### Batch Processing
Run daily batches for settlements:

```php
$batchId = $economy->createBatch('daily_settlements', 'system_admin');
$result = $economy->processBatch($batchId);

if (!$result['success']) {
    // Log errors and retry
    error_log("Batch processing failed: " . json_encode($result['errors']));
}
```

---

## Setup Instructions

### 1. Database Migration
Run the migration SQL file:

```sql
SOURCE database/migration_add_virtual_economy.sql;
```

### 2. Include the Class
Add to your PHP files:

```php
require_once 'includes/virtual_economy.php';
$economy = new VirtualEconomy($pdo);
```

### 3. Update Navigation
Add links to header for users:

```html
<a href="freelancer/rewards_system.php">Rewards</a>
<a href="client/transaction_ledger.php">Transactions</a>
<a href="client/disputes.php">Disputes</a>
```

### 4. Admin Navigation
Add link for admins:

```html
<a href="admin/disputes_admin.php">Manage Disputes</a>
```

---

## Usage Examples

### Awarding Points for Gig Completion
```php
$gigAmount = 100.00;
$gigId = 42;

// Record payment transaction
$txnId = $economy->recordTransaction(
    'client001',
    'freelancer001',
    'gig_payment',
    $gigAmount,
    $gigId
);

// Mark transaction as completed
// (in real implementation, settle it)

// Award points to freelancer
$pointsEarned = 25; // ৳100 = 25 points
$economy->awardPoints(
    'freelancer001',
    $pointsEarned,
    'earned',
    $gigId,
    'Completed gig worth ৳100'
);

// Award points to client for using platform
$economy->awardPoints(
    'client001',
    5,
    'earned',
    $gigId,
    'Posted and completed gig'
);
```

### Processing Daily Settlements
```php
// Create batch
$batchId = $economy->createBatch('daily_settlements', 'admin_user');

// Add pending transactions to batch
// (typically done via WHERE batch_id = null and status = 'pending')

// Process all transactions in batch
$result = $economy->processBatch($batchId);

if ($result['success']) {
    $message = "Successfully processed " . $result['successful'] . " transactions";
} else {
    $message = "Batch processing failed with " . count($result['errors']) . " errors";
}
```

### Handling Disputes
```php
// User files dispute
$disputeId = $economy->createDispute(
    'TXN-2024050100001',
    'user001',  // complainant
    'user002',  // respondent
    'work_not_completed',
    'The freelancer never submitted the work after I paid',
    42  // gig ID
);

// Admin resolves dispute
$economy->resolveDispute(
    $disputeId,
    'refund',  // resolution type
    'admin001',  // admin user
    100.00,  // refund amount
    'Full refund issued - no work submitted'
);
```

### Redeeming Points
```php
// User wants to redeem 100 points
$result = $economy->redeemPoints('user123', 100);

if ($result) {
    // 100 points = ৳10 credit
    // User's credit_balance increased by ৳10
    echo "Redeemed successfully! Added ৳" . $result['credit'] . " to your balance";
}
```

---

## Security Considerations

1. **Prepared Statements**: All database queries use prepared statements to prevent SQL injection
2. **Authentication**: All pages require user authentication
3. **Admin Verification**: Dispute resolution restricted to admins
4. **Transaction Verification**: Transactions are verified before settlement
5. **Audit Trail**: All transactions logged with timestamps and user IDs
6. **Balance Validation**: Point redemption checks available balance before processing

---

## Troubleshooting

### Points not updating
- Check User_Points table exists
- Verify PDO connection is valid
- Check transaction within try-catch block

### Batch processing fails
- Verify all transactions in batch have valid data
- Check user credit_balance is sufficient
- Review error_log in Transaction_Batch record

### Disputes not resolving
- Verify dispute status is 'open' or 'under_review'
- Check respondent user exists
- Ensure refund_amount is valid for refunds

---

## Performance Optimization

1. **Indexes**: Created on frequently queried columns
2. **Pagination**: Limited queries with LIMIT/OFFSET
3. **Batch Processing**: Processes multiple transactions efficiently
4. **Lazy Loading**: Point calculations only when needed

---

## Future Enhancements

1. Automated bonus point distribution
2. Point expiration policies
3. Dispute appeals process
4. Advanced analytics on transaction patterns
5. Recurring payment support
6. Point marketplace with redemption options
