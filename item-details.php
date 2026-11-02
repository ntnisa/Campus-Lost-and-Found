<?php
require_once __DIR__ . "/config/database.php";

$itemId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($itemId <= 0) {
    set_flash('error', "Invalid item requested.");
    header("Location: " . BASE_URL . "search.php");
    exit();
}

$itemStmt = $conn->prepare("
    SELECT 
        items.item_id,
        items.item_name,
        items.description,
        items.color,
        items.status,
        items.created_at,
        categories.category_id,
        categories.category_name,
        categories.description AS category_desc
    FROM items
    INNER JOIN categories ON items.category_id = categories.category_id
    WHERE items.item_id = ?
");
$itemStmt->execute([$itemId]);
$item = $itemStmt->fetch();

if (!$item) {
    set_flash('error', "Item not found in database.");
    header("Location: " . BASE_URL . "search.php");
    exit();
}

$lostStmt = $conn->prepare("
    SELECT 
        lost_reports.*,
        locations.location_name,
        locations.description AS location_desc,
        users.name AS reporter_name,
        users.email AS reporter_email,
        users.phone AS reporter_phone,
        users.user_type AS reporter_role
    FROM lost_reports
    INNER JOIN locations ON lost_reports.location_id = locations.location_id
    INNER JOIN users ON lost_reports.user_id = users.user_id
    WHERE lost_reports.item_id = ?
");
$lostStmt->execute([$itemId]);
$lostReport = $lostStmt->fetch();

$foundStmt = $conn->prepare("
    SELECT 
        found_reports.*,
        locations.location_name,
        locations.description AS location_desc,
        users.name AS reporter_name,
        users.email AS reporter_email,
        users.phone AS reporter_phone,
        users.user_type AS reporter_role
    FROM found_reports
    INNER JOIN locations ON found_reports.location_id = locations.location_id
    INNER JOIN users ON found_reports.user_id = users.user_id
    WHERE found_reports.item_id = ?
");
$foundStmt->execute([$itemId]);
$foundReport = $foundStmt->fetch();

$reportType = $foundReport ? 'Found' : ($lostReport ? 'Lost' : 'Unknown');
$reportData = $foundReport ?: $lostReport;

$existingUserClaim = null;
if (is_logged_in()) {
    $claimStmt = $conn->prepare("SELECT * FROM claims WHERE item_id = ? AND user_id = ?");
    $claimStmt->execute([$itemId, $_SESSION['user_id']]);
    $existingUserClaim = $claimStmt->fetch();
}

$allClaims = [];
if (is_admin()) {
    $adminClaimsStmt = $conn->prepare("
        SELECT 
            claims.*,
            users.name AS claimant_name,
            users.email AS claimant_email,
            users.phone AS claimant_phone,
            users.user_type AS claimant_role,
            verifier.name AS verifier_name
        FROM claims
        INNER JOIN users ON claims.user_id = users.user_id
        LEFT JOIN users AS verifier ON claims.verified_by = verifier.user_id
        WHERE claims.item_id = ?
        ORDER BY claims.claim_date DESC
    ");
    $adminClaimsStmt->execute([$itemId]);
    $allClaims = $adminClaimsStmt->fetchAll();
}

$pageTitle = htmlspecialchars($item['item_name']) . " - Details";
require_once __DIR__ . "/includes/header.php";
?>

<div class="page-header bg-slate">
    <div class="container breadcrumb-container">
        <ul class="breadcrumb">
            <li><a href="<?php echo BASE_URL; ?>index.php">Home</a></li>
            <li><a href="<?php echo BASE_URL; ?>search.php">Search Items</a></li>
            <li class="active"><?php echo htmlspecialchars($item['item_name']); ?></li>
        </ul>
    </div>
</div>

<div class="container py-4">
    <div class="details-layout">
        
        <div class="details-main">
            <div class="details-card">
                <div class="details-header">
                    <div>
                        <span class="badge-cat"><?php echo htmlspecialchars($item['category_name']); ?></span>
                        <h1 class="details-title"><?php echo htmlspecialchars($item['item_name']); ?></h1>
                    </div>
                    <div>
                        <span class="badge-status badge-status-<?php echo strtolower($item['status']); ?> badge-lg">
                            <?php echo htmlspecialchars($item['status']); ?>
                        </span>
                    </div>
                </div>

                <div class="details-meta-grid">
                    <div class="meta-box">
                        <span class="meta-label">Category</span>
                        <span class="meta-value"><?php echo htmlspecialchars($item['category_name']); ?></span>
                    </div>
                    <div class="meta-box">
                        <span class="meta-label">Primary Color</span>
                        <span class="meta-value"><?php echo htmlspecialchars($item['color'] ?? 'Not Specified'); ?></span>
                    </div>
                    <div class="meta-box">
                        <span class="meta-label"><?php echo ($reportType === 'Found') ? 'Found Date' : 'Lost Date'; ?></span>
                        <span class="meta-value">
                            <?php echo !empty($reportData['found_date'] ?? $reportData['lost_date']) ? date('F d, Y', strtotime($reportData['found_date'] ?? $reportData['lost_date'])) : 'Unknown'; ?>
                        </span>
                    </div>
                    <div class="meta-box">
                        <span class="meta-label">Campus Location</span>
                        <span class="meta-value"><?php echo htmlspecialchars($reportData['location_name'] ?? 'N/A'); ?></span>
                    </div>
                </div>

                <div class="details-section">
                    <h3>Item Description</h3>
                    <p class="description-text">
                        <?php echo nl2br(htmlspecialchars($item['description'] ?: 'No detailed description was provided during report submission.')); ?>
                    </p>
                </div>

                <?php if (!empty($reportData['additional_details'])): ?>
                    <div class="details-section">
                        <h3>Additional Circumstances / Context</h3>
                        <p class="description-text">
                            <?php echo nl2br(htmlspecialchars($reportData['additional_details'])); ?>
                        </p>
                    </div>
                <?php endif; ?>

                <div class="details-section location-highlight">
                    <h3><span>&#128205;</span> Campus Location Details</h3>
                    <p><strong><?php echo htmlspecialchars($reportData['location_name'] ?? ''); ?></strong></p>
                    <p class="text-muted"><?php echo htmlspecialchars($reportData['location_desc'] ?? 'General campus area.'); ?></p>
                </div>
            </div>

            
            <?php if (is_admin()): ?>
                <div class="admin-action-box mt-4">
                    <div class="admin-action-header">
                        <h3><span>&#9881;</span> Administrator Controls</h3>
                        <span class="badge badge-info">Admin Only</span>
                    </div>
                    
                    <form method="POST" action="<?php echo BASE_URL; ?>admin/dashboard.php?action=update_item_status" class="status-change-inline">
                        <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                        <label for="status"><strong>Update Item Lifecycle Status:</strong></label>
                        <select name="status" id="status" class="form-select-sm">
                            <option value="Lost" <?php echo ($item['status'] === 'Lost') ? 'selected' : ''; ?>>Lost</option>
                            <option value="Found" <?php echo ($item['status'] === 'Found') ? 'selected' : ''; ?>>Found</option>
                            <option value="Claimed" <?php echo ($item['status'] === 'Claimed') ? 'selected' : ''; ?>>Claimed</option>
                            <option value="Returned" <?php echo ($item['status'] === 'Returned') ? 'selected' : ''; ?>>Returned</option>
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary">Update Status</button>
                    </form>

                    <?php if (!empty($allClaims)): ?>
                        <h4 class="mt-4">Submitted Claims for this Item (<?php echo count($allClaims); ?>)</h4>
                        <div class="table-responsive mt-2">
                            <table class="table-modern">
                                <thead>
                                    <tr>
                                        <th>Claimant</th>
                                        <th>Date</th>
                                        <th>Proof / Details</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($allClaims as $claim): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($claim['claimant_name']); ?></strong><br>
                                                <small><?php echo htmlspecialchars($claim['claimant_phone'] ?? $claim['claimant_email']); ?></small>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($claim['claim_date'])); ?></td>
                                            <td><small><?php echo htmlspecialchars($claim['identification_details']); ?></small></td>
                                            <td>
                                                <span class="badge-status badge-status-<?php echo strtolower($claim['status']); ?>">
                                                    <?php echo htmlspecialchars($claim['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($claim['status'] === 'Pending'): ?>
                                                    <a href="<?php echo BASE_URL; ?>admin/dashboard.php?action=verify_claim&claim_id=<?php echo $claim['claim_id']; ?>&decision=approve" 
                                                       class="btn btn-xs btn-accent" 
                                                       data-confirm="Approve this claim? This will verify ownership and set item status to Claimed.">
                                                       Approve
                                                    </a>
                                                    <a href="<?php echo BASE_URL; ?>admin/dashboard.php?action=verify_claim&claim_id=<?php echo $claim['claim_id']; ?>&decision=reject" 
                                                       class="btn btn-xs btn-danger"
                                                       data-confirm="Reject this claim?">
                                                       Reject
                                                    </a>
                                                <?php else: ?>
                                                    <small class="text-muted">Verified by <?php echo htmlspecialchars($claim['verifier_name'] ?? 'Admin'); ?></small>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        
        <div class="details-sidebar">
            
            <div class="sidebar-card">
                <h3><span>&#128100;</span> Reporter Information</h3>
                <div class="reporter-summary">
                    <p class="reporter-name"><?php echo htmlspecialchars($reportData['reporter_name'] ?? 'Campus User'); ?></p>
                    <span class="role-badge role-<?php echo strtolower($reportData['reporter_role'] ?? 'student'); ?>">
                        <?php echo htmlspecialchars($reportData['reporter_role'] ?? 'Student'); ?>
                    </span>
                    <p class="report-timestamp">
                        Reported on: <?php echo date('M d, Y h:i A', strtotime($reportData['report_date'] ?? 'now')); ?>
                    </p>
                </div>

                <?php if (is_logged_in()): ?>
                    <div class="contact-details mt-3">
                        <p><strong>Contact Coordinator:</strong></p>
                        <p>&#9993; <?php echo htmlspecialchars($reportData['reporter_email'] ?? 'Available via Admin'); ?></p>
                        <?php if (!empty($reportData['reporter_phone'])): ?>
                            <p>&#128222; <?php echo htmlspecialchars($reportData['reporter_phone']); ?></p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="login-prompt-box">
                        <small>Please <a href="<?php echo BASE_URL; ?>auth/login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">login</a> to view reporter contact info or submit an ownership claim.</small>
                    </div>
                <?php endif; ?>
            </div>

            
            <?php if ($reportType === 'Found'): ?>
                <div class="sidebar-card highlight-claim-card">
                    <h3><span>&#128221;</span> Is this your item?</h3>

                    <?php if ($existingUserClaim): ?>
                        <div class="claim-status-box">
                            <p><strong>Your Claim Status:</strong></p>
                            <span class="badge-status badge-status-<?php echo strtolower($existingUserClaim['status']); ?> badge-lg">
                                <?php echo htmlspecialchars($existingUserClaim['status']); ?>
                            </span>
                            <p class="mt-2 text-muted">
                                Submitted on <?php echo date('M d, Y', strtotime($existingUserClaim['claim_date'])); ?>.
                                <?php if ($existingUserClaim['status'] === 'Pending'): ?>
                                    An administrator will review your identification proof.
                                <?php elseif ($existingUserClaim['status'] === 'Approved'): ?>
                                    Congratulations! Your claim was approved. Contact the reporter or campus security to receive your item.
                                <?php else: ?>
                                    Your claim could not be verified with the details provided.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php elseif ($item['status'] === 'Found'): ?>
                        <p>If you lost this item on campus, you can submit an ownership claim with identifying proof.</p>
                        <?php if (is_logged_in()): ?>
                            <a href="<?php echo BASE_URL; ?>user/submit-claim.php?item_id=<?php echo $item['item_id']; ?>" class="btn btn-accent btn-block">
                                Submit Ownership Claim
                            </a>
                        <?php else: ?>
                            <a href="<?php echo BASE_URL; ?>auth/login.php?redirect=<?php echo urlencode(BASE_URL . 'user/submit-claim.php?item_id=' . $item['item_id']); ?>" class="btn btn-primary btn-block">
                                Login to Submit Claim
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="text-muted">This item is currently marked as <strong><?php echo htmlspecialchars($item['status']); ?></strong> and is no longer accepting new claims.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="sidebar-card">
                <h4>Need Assistance?</h4>
                <p class="text-muted"><small>For immediate help or physical collection, visit the Metropolitan University Campus Lost &amp; Found Desk at Academic Building, Ground Floor.</small></p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>

