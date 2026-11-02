<?php
require_once __DIR__ . "/config/database.php";

$pageTitle = "Campus Lost & Found - Metropolitan University";

$totalUsers = (int)$conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalLost = (int)$conn->query("SELECT COUNT(*) FROM items WHERE status = 'Lost'")->fetchColumn();
$totalFound = (int)$conn->query("SELECT COUNT(*) FROM items WHERE status = 'Found'")->fetchColumn();
$totalClaimed = (int)$conn->query("SELECT COUNT(*) FROM items WHERE status = 'Claimed'")->fetchColumn();
$totalReturned = (int)$conn->query("SELECT COUNT(*) FROM items WHERE status = 'Returned'")->fetchColumn();
$totalReports = (int)$conn->query("SELECT (SELECT COUNT(*) FROM lost_reports) + (SELECT COUNT(*) FROM found_reports)")->fetchColumn();

$recentFoundStmt = $conn->prepare("
    SELECT 
        items.item_id,
        items.item_name,
        items.description,
        items.color,
        items.status,
        categories.category_name,
        locations.location_name,
        found_reports.found_date,
        found_reports.report_date,
        users.name AS reporter_name,
        users.user_type AS reporter_role
    FROM found_reports
    INNER JOIN items ON found_reports.item_id = items.item_id
    INNER JOIN categories ON items.category_id = categories.category_id
    INNER JOIN locations ON found_reports.location_id = locations.location_id
    INNER JOIN users ON found_reports.user_id = users.user_id
    ORDER BY found_reports.report_date DESC, found_reports.found_report_id DESC
    LIMIT 6
");
$recentFoundStmt->execute();
$recentFoundItems = $recentFoundStmt->fetchAll();

$recentLostStmt = $conn->prepare("
    SELECT 
        items.item_id,
        items.item_name,
        items.description,
        items.color,
        items.status,
        categories.category_name,
        locations.location_name,
        lost_reports.lost_date,
        lost_reports.report_date,
        users.name AS reporter_name,
        users.user_type AS reporter_role
    FROM lost_reports
    INNER JOIN items ON lost_reports.item_id = items.item_id
    INNER JOIN categories ON items.category_id = categories.category_id
    INNER JOIN locations ON lost_reports.location_id = locations.location_id
    INNER JOIN users ON lost_reports.user_id = users.user_id
    WHERE items.status = 'Lost'
    ORDER BY lost_reports.report_date DESC, lost_reports.lost_report_id DESC
    LIMIT 6
");
$recentLostStmt->execute();
$recentLostItems = $recentLostStmt->fetchAll();

require_once __DIR__ . "/includes/header.php";
?>

<section class="hero-section">
    <div class="container hero-container">
        <div class="hero-content">
            <span class="hero-badge">Metropolitan University Central Portal</span>
            <h1>Lost Something on Campus? <br><span class="hero-gradient">We Help You Recover It.</span></h1>
            <p>
                A centralized, relational database system designed for Metropolitan University students, teachers, and staff to report, track, claim, and return lost belongings with administrator verification.
            </p>
            <div class="hero-buttons">
                <a href="<?php echo BASE_URL; ?>search.php" class="btn btn-primary btn-lg">
                    <span>&#128269;</span> Search All Items
                </a>
                <a href="<?php echo BASE_URL; ?>user/report-lost.php" class="btn btn-danger btn-lg">
                    <span>&#10060;</span> Report Lost Item
                </a>
                <a href="<?php echo BASE_URL; ?>user/report-found.php" class="btn btn-accent btn-lg">
                    <span>&#10004;</span> Report Found Item
                </a>
            </div>
        </div>
    </div>
</section>

<section class="stats-section">
    <div class="container">
        <div class="section-header text-center">
            <h2>Campus Lost &amp; Found Live Statistics</h2>
            <p>Real-time relational database metrics tracking items and claims across the campus</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card stat-users">
                <div class="stat-icon">&#128101;</div>
                <div class="stat-number"><?php echo $totalUsers; ?></div>
                <div class="stat-label">Registered Users</div>
                <div class="stat-sub">Students, Faculty &amp; Staff</div>
            </div>

            <div class="stat-card stat-lost">
                <div class="stat-icon">&#10060;</div>
                <div class="stat-number"><?php echo $totalLost; ?></div>
                <div class="stat-label">Active Lost Items</div>
                <div class="stat-sub">Awaiting identification</div>
            </div>

            <div class="stat-card stat-found">
                <div class="stat-icon">&#128269;</div>
                <div class="stat-number"><?php echo $totalFound; ?></div>
                <div class="stat-label">Available Found Items</div>
                <div class="stat-sub">Ready to be claimed</div>
            </div>

            <div class="stat-card stat-claimed">
                <div class="stat-icon">&#128221;</div>
                <div class="stat-number"><?php echo $totalClaimed; ?></div>
                <div class="stat-label">Items Claimed</div>
                <div class="stat-sub">Under owner verification</div>
            </div>

            <div class="stat-card stat-returned">
                <div class="stat-icon">&#127881;</div>
                <div class="stat-number"><?php echo $totalReturned; ?></div>
                <div class="stat-label">Successfully Returned</div>
                <div class="stat-sub">Reunited with owners</div>
            </div>
        </div>
    </div>
</section>

<section class="section-padding bg-white">
    <div class="container">
        <div class="section-header-flex">
            <div>
                <span class="section-tag section-tag-found">&#10004; Newly Discovered</span>
                <h2>Recently Found Belongings</h2>
                <p>Latest items reported found around Metropolitan University campus. Did you lose one of these?</p>
            </div>
            <a href="<?php echo BASE_URL; ?>search.php" class="btn btn-outline">View All Found Items &rarr;</a>
        </div>

        <?php if (!empty($recentFoundItems)): ?>
            <div class="items-grid">
                <?php foreach ($recentFoundItems as $item): ?>
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

                        <p class="item-card-desc">
                            <?php 
                            $desc = $item['description'] ? htmlspecialchars($item['description']) : "No detailed description provided.";
                            echo (strlen($desc) > 95) ? substr($desc, 0, 92) . '...' : $desc;
                            ?>
                        </p>

                        <div class="item-card-meta">
                            <div class="meta-row">
                                <span class="meta-icon">&#127912;</span>
                                <span>Color: <strong><?php echo htmlspecialchars($item['color'] ?? 'N/A'); ?></strong></span>
                            </div>
                            <div class="meta-row">
                                <span class="meta-icon">&#128205;</span>
                                <span>Location: <strong><?php echo htmlspecialchars($item['location_name']); ?></strong></span>
                            </div>
                            <div class="meta-row">
                                <span class="meta-icon">&#128197;</span>
                                <span>Found Date: <strong><?php echo htmlspecialchars(date('M d, Y', strtotime($item['found_date']))); ?></strong></span>
                            </div>
                        </div>

                        <div class="item-card-footer">
                            <a href="<?php echo BASE_URL; ?>item-details.php?id=<?php echo $item['item_id']; ?>" class="btn btn-sm btn-outline">
                                View Details
                            </a>
                            <?php if ($item['status'] === 'Found'): ?>
                                <a href="<?php echo BASE_URL; ?>user/submit-claim.php?item_id=<?php echo $item['item_id']; ?>" class="btn btn-sm btn-accent">
                                    Claim This Item
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <span class="empty-icon">&#128269;</span>
                <h3>No found items reported recently</h3>
                <p>If you've found an item on campus, please submit a found report to help the owner find it.</p>
                <a href="<?php echo BASE_URL; ?>user/report-found.php" class="btn btn-accent">Report a Found Item</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="section-padding bg-slate">
    <div class="container">
        <div class="section-header-flex">
            <div>
                <span class="section-tag section-tag-lost">&#10060; Missing on Campus</span>
                <h2>Recently Lost Items</h2>
                <p>Campus belongings currently reported as missing. Have you spotted any of them?</p>
            </div>
            <a href="<?php echo BASE_URL; ?>search.php" class="btn btn-outline">View All Lost Items &rarr;</a>
        </div>

        <?php if (!empty($recentLostItems)): ?>
            <div class="items-grid">
                <?php foreach ($recentLostItems as $item): ?>
                    <div class="item-card-modern">
                        <div class="item-card-top">
                            <span class="badge-cat"><?php echo htmlspecialchars($item['category_name']); ?></span>
                            <span class="badge-status badge-status-lost">LOST</span>
                        </div>

                        <h3 class="item-card-title">
                            <a href="<?php echo BASE_URL; ?>item-details.php?id=<?php echo $item['item_id']; ?>">
                                <?php echo htmlspecialchars($item['item_name']); ?>
                            </a>
                        </h3>

                        <p class="item-card-desc">
                            <?php 
                            $desc = $item['description'] ? htmlspecialchars($item['description']) : "No detailed description provided.";
                            echo (strlen($desc) > 95) ? substr($desc, 0, 92) . '...' : $desc;
                            ?>
                        </p>

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
                                <span>Lost Date: <strong><?php echo htmlspecialchars(date('M d, Y', strtotime($item['lost_date']))); ?></strong></span>
                            </div>
                        </div>

                        <div class="item-card-footer">
                            <a href="<?php echo BASE_URL; ?>item-details.php?id=<?php echo $item['item_id']; ?>" class="btn btn-sm btn-outline">
                                View Details &rarr;
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <span class="empty-icon">&#127881;</span>
                <h3>No active lost reports at this moment</h3>
                <p>All reported items have been handled or no items are currently reported lost.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="section-padding bg-white">
    <div class="container">
        <div class="section-header text-center">
            <h2>How the Campus Lost &amp; Found System Works</h2>
            <p>A reliable 4-step database lifecycle from reporting to verified hand-off</p>
        </div>

        <div class="steps-grid">
            <div class="step-card">
                <div class="step-number">1</div>
                <h3>Submit Report</h3>
                <p>Students, faculty, or staff report missing or discovered items, selecting category, color, location, and date.</p>
            </div>

            <div class="step-card">
                <div class="step-number">2</div>
                <h3>Live DB Search</h3>
                <p>Users filter and search reports by keywords, category, color, and specific campus location.</p>
            </div>

            <div class="step-card">
                <div class="step-number">3</div>
                <h3>Ownership Claim</h3>
                <p>The rightful owner submits a claim with identifying details (distinguishing marks, serial numbers, wallpapers).</p>
            </div>

            <div class="step-card">
                <div class="step-number">4</div>
                <h3>Admin Verification</h3>
                <p>An administrator reviews ownership proof, approves the claim, and updates item status to Returned.</p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
