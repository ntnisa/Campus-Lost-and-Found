<?php
require_once __DIR__ . "/../config/database.php";

require_login();

$userId = (int)$_SESSION["user_id"];

$lostQuery = $conn->prepare("
    SELECT
        items.item_id,
        items.item_name,
        items.color,
        items.status,
        categories.category_name,
        locations.location_name,
        lost_reports.lost_date,
        lost_reports.report_date
    FROM lost_reports
    INNER JOIN items ON lost_reports.item_id = items.item_id
    INNER JOIN categories ON items.category_id = categories.category_id
    INNER JOIN locations ON lost_reports.location_id = locations.location_id
    WHERE lost_reports.user_id = ?
    ORDER BY lost_reports.report_date DESC
");
$lostQuery->execute([$userId]);
$lostItems = $lostQuery->fetchAll();

$foundQuery = $conn->prepare("
    SELECT
        items.item_id,
        items.item_name,
        items.color,
        items.status,
        categories.category_name,
        locations.location_name,
        found_reports.found_date,
        found_reports.report_date
    FROM found_reports
    INNER JOIN items ON found_reports.item_id = items.item_id
    INNER JOIN categories ON items.category_id = categories.category_id
    INNER JOIN locations ON found_reports.location_id = locations.location_id
    WHERE found_reports.user_id = ?
    ORDER BY found_reports.report_date DESC
");
$foundQuery->execute([$userId]);
$foundItems = $foundQuery->fetchAll();

$claimsQuery = $conn->prepare("
    SELECT
        claims.claim_id,
        claims.claim_date,
        claims.identification_details,
        claims.status AS claim_status,
        items.item_id,
        items.item_name,
        items.color,
        items.status AS item_status,
        categories.category_name,
        verifier.name AS verifier_name
    FROM claims
    INNER JOIN items ON claims.item_id = items.item_id
    INNER JOIN categories ON items.category_id = categories.category_id
    LEFT JOIN users AS verifier ON claims.verified_by = verifier.user_id
    WHERE claims.user_id = ?
    ORDER BY claims.claim_date DESC
");
$claimsQuery->execute([$userId]);
$myClaims = $claimsQuery->fetchAll();

$pageTitle = "My Activity & Reports - Campus Lost & Found";
require_once __DIR__ . "/../includes/header.php";
?>

<div class="page-header bg-slate">
    <div class="container">
        <h1>My Campus Reports &amp; Activity</h1>
        <p>Track all the lost items you've posted, found items you've turned in, and ownership claims you've submitted.</p>
    </div>
</div>

<div class="container py-4">
    
    <div class="activity-summary-grid">
        <div class="activity-stat-card">
            <span class="activity-stat-number"><?php echo count($lostItems); ?></span>
            <span class="activity-stat-title">My Lost Reports</span>
        </div>
        <div class="activity-stat-card">
            <span class="activity-stat-number"><?php echo count($foundItems); ?></span>
            <span class="activity-stat-title">My Found Reports</span>
        </div>
        <div class="activity-stat-card">
            <span class="activity-stat-number"><?php echo count($myClaims); ?></span>
            <span class="activity-stat-title">Claims Submitted</span>
        </div>
    </div>

    
    <div class="action-banner-row">
        <a href="<?php echo BASE_URL; ?>user/report-lost.php" class="btn btn-danger">
            <span>&#10060;</span> Report Another Lost Item
        </a>
        <a href="<?php echo BASE_URL; ?>user/report-found.php" class="btn btn-accent">
            <span>&#10004;</span> Report Another Found Item
        </a>
        <a href="<?php echo BASE_URL; ?>user/submit-claim.php" class="btn btn-outline">
            <span>&#128221;</span> Submit New Claim
        </a>
    </div>

    
    <div class="section-title-wrap mt-5">
        <h2 class="section-subheading">
            <span class="badge-status badge-status-lost">&#10060;</span> 
            My Lost Item Reports (<?php echo count($lostItems); ?>)
        </h2>
    </div>

    <?php if (!empty($lostItems)): ?>
        <div class="items-grid">
            <?php foreach ($lostItems as $item): ?>
                <div class="item-card-modern">
                    <div class="item-card-top">
                        <span class="badge-cat"><?php echo htmlspecialchars($item['category_name']); ?></span>
                        <span class="badge-status badge-status-<?php echo strtolower($item['status']); ?>">
                            <?php echo htmlspecialchars($item['status']); ?>
                        </span>
                    </div>

                    <h3 class="item-card-title">
                        <a href="<?php echo BASE_URL; ?>item-details.php?id=<?php echo $item['item_id']; ?>">
                            <?php echo htmlspecialchars($item['item_name']); ?>
                        </a>
                    </h3>

                    <div class="item-card-meta">
                        <div class="meta-row">
                            <span class="meta-icon">&#127912;</span>
                            <span>Color: <strong><?php echo htmlspecialchars($item['color'] ?? 'N/A'); ?></strong></span>
                        </div>
                        <div class="meta-row">
                            <span class="meta-icon">&#128205;</span>
                            <span>Last Seen: <strong><?php echo htmlspecialchars($item['location_name']); ?></strong></span>
                        </div>
                        <div class="meta-row">
                            <span class="meta-icon">&#128197;</span>
                            <span>Lost Date: <strong><?php echo date('M d, Y', strtotime($item['lost_date'])); ?></strong></span>
                        </div>
                        <div class="meta-row">
                            <span class="meta-icon">&#128340;</span>
                            <span>Reported: <small><?php echo date('M d, Y h:i A', strtotime($item['report_date'])); ?></small></span>
                        </div>
                    </div>

                    <div class="item-card-footer">
                        <a href="<?php echo BASE_URL; ?>item-details.php?id=<?php echo $item['item_id']; ?>" class="btn btn-sm btn-outline btn-block">
                            View Report Details
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state-card">
            <p>You have not submitted any lost item reports yet.</p>
        </div>
    <?php endif; ?>

    
    <div class="section-title-wrap mt-5">
        <h2 class="section-subheading">
            <span class="badge-status badge-status-found">&#10004;</span> 
            My Found Item Reports (<?php echo count($foundItems); ?>)
        </h2>
    </div>

    <?php if (!empty($foundItems)): ?>
        <div class="items-grid">
            <?php foreach ($foundItems as $item): ?>
                <div class="item-card-modern">
                    <div class="item-card-top">
                        <span class="badge-cat"><?php echo htmlspecialchars($item['category_name']); ?></span>
                        <span class="badge-status badge-status-<?php echo strtolower($item['status']); ?>">
                            <?php echo htmlspecialchars($item['status']); ?>
                        </span>
                    </div>

                    <h3 class="item-card-title">
                        <a href="<?php echo BASE_URL; ?>item-details.php?id=<?php echo $item['item_id']; ?>">
                            <?php echo htmlspecialchars($item['item_name']); ?>
                        </a>
                    </h3>

                    <div class="item-card-meta">
                        <div class="meta-row">
                            <span class="meta-icon">&#127912;</span>
                            <span>Color: <strong><?php echo htmlspecialchars($item['color'] ?? 'N/A'); ?></strong></span>
                        </div>
                        <div class="meta-row">
                            <span class="meta-icon">&#128205;</span>
                            <span>Found At: <strong><?php echo htmlspecialchars($item['location_name']); ?></strong></span>
                        </div>
                        <div class="meta-row">
                            <span class="meta-icon">&#128197;</span>
                            <span>Found Date: <strong><?php echo date('M d, Y', strtotime($item['found_date'])); ?></strong></span>
                        </div>
                        <div class="meta-row">
                            <span class="meta-icon">&#128340;</span>
                            <span>Reported: <small><?php echo date('M d, Y h:i A', strtotime($item['report_date'])); ?></small></span>
                        </div>
                    </div>

                    <div class="item-card-footer">
                        <a href="<?php echo BASE_URL; ?>item-details.php?id=<?php echo $item['item_id']; ?>" class="btn btn-sm btn-outline btn-block">
                            View Report Details
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state-card">
            <p>You have not reported any found items yet.</p>
        </div>
    <?php endif; ?>

    
    <div class="section-title-wrap mt-5" id="claims">
        <h2 class="section-subheading">
            <span class="badge-status badge-status-claimed">&#128221;</span> 
            My Ownership Claims (<?php echo count($myClaims); ?>)
        </h2>
    </div>

    <?php if (!empty($myClaims)): ?>
        <div class="table-responsive">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Claimed Item</th>
                        <th>Category</th>
                        <th>Claim Date</th>
                        <th>Identification Proof Provided</th>
                        <th>Verification Status</th>
                        <th>Item Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($myClaims as $claim): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($claim['item_name']); ?></strong><br>
                                <small class="text-muted">Color: <?php echo htmlspecialchars($claim['color']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($claim['category_name']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($claim['claim_date'])); ?></td>
                            <td>
                                <div class="proof-excerpt">
                                    <?php echo htmlspecialchars($claim['identification_details']); ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge-status badge-status-<?php echo strtolower($claim['claim_status']); ?>">
                                    <?php echo htmlspecialchars($claim['claim_status']); ?>
                                </span>
                                <?php if (!empty($claim['verifier_name'])): ?>
                                    <br><small class="text-muted">Verified by <?php echo htmlspecialchars($claim['verifier_name']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo BASE_URL; ?>item-details.php?id=<?php echo $claim['item_id']; ?>" class="btn btn-xs btn-outline">
                                    View Item &rarr;
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state-card">
            <p>You have not submitted any claims for found items yet.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
