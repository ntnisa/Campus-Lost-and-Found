<?php
require_once __DIR__ . "/../config/database.php";

require_admin();

$adminId = (int)$_SESSION["user_id"];
$action = $_GET['action'] ?? '';

if ($action === 'verify_claim' && isset($_GET['claim_id'], $_GET['decision'])) {
    $claimId = (int)$_GET['claim_id'];
    $decision = strtolower($_GET['decision']);

    if (in_array($decision, ['approve', 'reject'], true)) {
        
        $fetchStmt = $conn->prepare("SELECT item_id, status FROM claims WHERE claim_id = ?");
        $fetchStmt->execute([$claimId]);
        $targetClaim = $fetchStmt->fetch();

        if ($targetClaim) {
            $itemId = $targetClaim['item_id'];

            if ($decision === 'approve') {
                $conn->beginTransaction();
                try {
                    
                    $stmt1 = $conn->prepare("UPDATE claims SET status = 'Approved', verified_by = ? WHERE claim_id = ?");
                    $stmt1->execute([$adminId, $claimId]);

                    
                    $stmt2 = $conn->prepare("UPDATE items SET status = 'Claimed' WHERE item_id = ?");
                    $stmt2->execute([$itemId]);

                    
                    $stmt3 = $conn->prepare("UPDATE claims SET status = 'Rejected', verified_by = ? WHERE item_id = ? AND claim_id != ? AND status = 'Pending'");
                    $stmt3->execute([$adminId, $itemId, $claimId]);

                    $conn->commit();
                    set_flash('success', "Claim #$claimId has been APPROVED. Item status updated to 'Claimed'.");
                } catch (Exception $e) {
                    $conn->rollBack();
                    set_flash('error', "Database error during approval: " . $e->getMessage());
                }
            } else {
                
                $stmt = $conn->prepare("UPDATE claims SET status = 'Rejected', verified_by = ? WHERE claim_id = ?");
                $stmt->execute([$adminId, $claimId]);
                set_flash('success', "Claim #$claimId has been REJECTED.");
            }
        }
    }
    header("Location: dashboard.php#claims");
    exit();
}

if ($action === 'update_item_status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $itemId = (int)($_POST['item_id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';

    if ($itemId > 0 && in_array($newStatus, ['Lost', 'Found', 'Claimed', 'Returned'], true)) {
        $stmt = $conn->prepare("UPDATE items SET status = ? WHERE item_id = ?");
        $stmt->execute([$newStatus, $itemId]);
        set_flash('success', "Item #$itemId status successfully updated to '$newStatus'.");
    }
    header("Location: " . ($_POST['redirect_to'] ?? 'dashboard.php#items'));
    exit();
}

if ($action === 'add_category' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['category_name'] ?? '');
    $desc = trim($_POST['description'] ?? '');

    if (!empty($name)) {
        try {
            $stmt = $conn->prepare("INSERT INTO categories (category_name, description) VALUES (?, ?)");
            $stmt->execute([$name, $desc]);
            set_flash('success', "Category '$name' created successfully.");
        } catch (PDOException $e) {
            set_flash('error', "Failed to add category: It may already exist.");
        }
    }
    header("Location: dashboard.php#categories");
    exit();
}

if ($action === 'delete_category' && isset($_GET['id'])) {
    $catId = (int)$_GET['id'];
    
    $check = $conn->prepare("SELECT COUNT(*) FROM items WHERE category_id = ?");
    $check->execute([$catId]);
    if ($check->fetchColumn() > 0) {
        set_flash('error', "Cannot delete category: Existing items are assigned to it.");
    } else {
        $stmt = $conn->prepare("DELETE FROM categories WHERE category_id = ?");
        $stmt->execute([$catId]);
        set_flash('success', "Category deleted successfully.");
    }
    header("Location: dashboard.php#categories");
    exit();
}

if ($action === 'add_location' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['location_name'] ?? '');
    $desc = trim($_POST['description'] ?? '');

    if (!empty($name)) {
        try {
            $stmt = $conn->prepare("INSERT INTO locations (location_name, description) VALUES (?, ?)");
            $stmt->execute([$name, $desc]);
            set_flash('success', "Location '$name' created successfully.");
        } catch (PDOException $e) {
            set_flash('error', "Failed to add location: It may already exist.");
        }
    }
    header("Location: dashboard.php#locations");
    exit();
}

if ($action === 'delete_location' && isset($_GET['id'])) {
    $locId = (int)$_GET['id'];
    $check1 = $conn->prepare("SELECT COUNT(*) FROM lost_reports WHERE location_id = ?");
    $check1->execute([$locId]);
    $check2 = $conn->prepare("SELECT COUNT(*) FROM found_reports WHERE location_id = ?");
    $check2->execute([$locId]);

    if ($check1->fetchColumn() > 0 || $check2->fetchColumn() > 0) {
        set_flash('error', "Cannot delete location: Reports exist referencing this campus area.");
    } else {
        $stmt = $conn->prepare("DELETE FROM locations WHERE location_id = ?");
        $stmt->execute([$locId]);
        set_flash('success', "Location deleted successfully.");
    }
    header("Location: dashboard.php#locations");
    exit();
}

if ($action === 'delete_item' && isset($_GET['id'])) {
    $delId = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM items WHERE item_id = ?");
    $stmt->execute([$delId]);
    set_flash('success', "Item #$delId and associated reports removed.");
    header("Location: dashboard.php#items");
    exit();
}

$totalUsers = (int)$conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalLost = (int)$conn->query("SELECT COUNT(*) FROM items WHERE status = 'Lost'")->fetchColumn();
$totalFound = (int)$conn->query("SELECT COUNT(*) FROM items WHERE status = 'Found'")->fetchColumn();
$totalClaimed = (int)$conn->query("SELECT COUNT(*) FROM items WHERE status = 'Claimed'")->fetchColumn();
$totalReturned = (int)$conn->query("SELECT COUNT(*) FROM items WHERE status = 'Returned'")->fetchColumn();
$pendingClaimsCount = (int)$conn->query("SELECT COUNT(*) FROM claims WHERE status = 'Pending'")->fetchColumn();

$claimsStmt = $conn->query("
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
        users.name AS claimant_name,
        users.email AS claimant_email,
        users.phone AS claimant_phone,
        users.user_type AS claimant_role,
        verifier.name AS verifier_name
    FROM claims
    INNER JOIN items ON claims.item_id = items.item_id
    INNER JOIN categories ON items.category_id = categories.category_id
    INNER JOIN users ON claims.user_id = users.user_id
    LEFT JOIN users AS verifier ON claims.verified_by = verifier.user_id
    ORDER BY (claims.status = 'Pending') DESC, claims.claim_date DESC
");
$allClaims = $claimsStmt->fetchAll();

$itemsStmt = $conn->query("
    SELECT 
        items.item_id,
        items.item_name,
        items.description,
        items.color,
        items.status,
        items.created_at,
        categories.category_name,
        COALESCE(lost_loc.location_name, found_loc.location_name, 'Unassigned') AS location_name,
        CASE 
            WHEN lost_reports.lost_report_id IS NOT NULL THEN 'Lost'
            WHEN found_reports.found_report_id IS NOT NULL THEN 'Found'
            ELSE 'Unknown'
        END AS report_type,
        COALESCE(lost_user.name, found_user.name, 'Unknown') AS reporter_name
    FROM items
    INNER JOIN categories ON items.category_id = categories.category_id
    LEFT JOIN lost_reports ON items.item_id = lost_reports.item_id
    LEFT JOIN locations AS lost_loc ON lost_reports.location_id = lost_loc.location_id
    LEFT JOIN users AS lost_user ON lost_reports.user_id = lost_user.user_id
    LEFT JOIN found_reports ON items.item_id = found_reports.item_id
    LEFT JOIN locations AS found_loc ON found_reports.location_id = found_loc.location_id
    LEFT JOIN users AS found_user ON found_reports.user_id = found_user.user_id
    ORDER BY items.item_id DESC
");
$allItems = $itemsStmt->fetchAll();

$catStmt = $conn->query("
    SELECT 
        categories.*,
        COUNT(items.item_id) AS total_items
    FROM categories
    LEFT JOIN items ON categories.category_id = items.category_id
    GROUP BY categories.category_id
    ORDER BY categories.category_name ASC
");
$allCategories = $catStmt->fetchAll();

$locStmt = $conn->query("
    SELECT 
        locations.*,
        (SELECT COUNT(*) FROM lost_reports WHERE lost_reports.location_id = locations.location_id) +
        (SELECT COUNT(*) FROM found_reports WHERE found_reports.location_id = locations.location_id) AS total_reports
    FROM locations
    ORDER BY locations.location_name ASC
");
$allLocations = $locStmt->fetchAll();

$userStmt = $conn->query("
    SELECT 
        users.*,
        (SELECT COUNT(*) FROM lost_reports WHERE lost_reports.user_id = users.user_id) AS lost_count,
        (SELECT COUNT(*) FROM found_reports WHERE found_reports.user_id = users.user_id) AS found_count,
        (SELECT COUNT(*) FROM claims WHERE claims.user_id = users.user_id) AS claim_count
    FROM users
    ORDER BY users.user_id ASC
");
$allUsers = $userStmt->fetchAll();

$selectedQuery = $_GET['demo_query'] ?? '1';
$demoQueryTitle = "";
$demoQuerySql = "";
$demoQueryDesc = "";
$demoResults = [];

switch ($selectedQuery) {
    case '1':
        $demoQueryTitle = "1. Search For An Item By Name";
        $demoQueryDesc = "Find all lost or found reports related to a particular item name using SQL LIKE pattern matching across reports, items, categories, and users.";
        $demoQuerySql = "SELECT items.item_name, items.color, items.status, categories.category_name, users.name AS reported_by FROM items INNER JOIN categories ON items.category_id = categories.category_id LEFT JOIN lost_reports ON items.item_id = lost_reports.item_id LEFT JOIN found_reports ON items.item_id = found_reports.item_id LEFT JOIN users ON users.user_id = COALESCE(lost_reports.user_id, found_reports.user_id) WHERE items.item_name LIKE '%Phone%' OR items.item_name LIKE '%Watch%' LIMIT 10;";
        $demoResults = $conn->query($demoQuerySql)->fetchAll();
        break;

    case '2':
        $demoQueryTitle = "2. Filter Items By Category And Color";
        $demoQueryDesc = "Demonstrates multi-table INNER JOIN and compound WHERE clause filtering items belonging to a selected category ('Electronics') and having a specified color ('Black').";
        $demoQuerySql = "SELECT items.item_id, items.item_name, items.color, items.status, categories.category_name FROM items INNER JOIN categories ON items.category_id = categories.category_id WHERE categories.category_name = 'Electronics' AND items.color = 'Black';";
        $demoResults = $conn->query($demoQuerySql)->fetchAll();
        break;

    case '3':
        $demoQueryTitle = "3. Find Recently Reported Found Items";
        $demoQueryDesc = "Retrieve newly reported found items ordered chronologically descending by report_date to facilitate quick identification (FR-06).";
        $demoQuerySql = "SELECT found_reports.found_report_id, items.item_name, items.color, categories.category_name, locations.location_name, found_reports.found_date, found_reports.report_date FROM found_reports INNER JOIN items ON found_reports.item_id = items.item_id INNER JOIN categories ON items.category_id = categories.category_id INNER JOIN locations ON found_reports.location_id = locations.location_id ORDER BY found_reports.report_date DESC LIMIT 5;";
        $demoResults = $conn->query($demoQuerySql)->fetchAll();
        break;

    case '4':
        $demoQueryTitle = "4. Find Claims Awaiting Administrator Verification";
        $demoQueryDesc = "Retrieve all claim records whose status is 'Pending' with claimant contact details and identification evidence for administrative review (FR-08).";
        $demoQuerySql = "SELECT claims.claim_id, items.item_name, users.name AS claimant_name, users.email AS claimant_email, users.phone, claims.claim_date, claims.identification_details, claims.status FROM claims INNER JOIN items ON claims.item_id = items.item_id INNER JOIN users ON claims.user_id = users.user_id WHERE claims.status = 'Pending' ORDER BY claims.claim_date ASC;";
        $demoResults = $conn->query($demoQuerySql)->fetchAll();
        break;

    case '5':
        $demoQueryTitle = "5. Find All Items Reported At A Particular Campus Location";
        $demoQueryDesc = "Display all lost or found belongings associated with a chosen campus location (e.g. 'Central Library' or 'Computer Lab').";
        $demoQuerySql = "SELECT locations.location_name, items.item_name, items.status, 'Lost' AS report_type, lost_reports.lost_date AS event_date FROM lost_reports INNER JOIN items ON lost_reports.item_id = items.item_id INNER JOIN locations ON lost_reports.location_id = locations.location_id UNION ALL SELECT locations.location_name, items.item_name, items.status, 'Found' AS report_type, found_reports.found_date AS event_date FROM found_reports INNER JOIN items ON found_reports.item_id = items.item_id INNER JOIN locations ON found_reports.location_id = locations.location_id ORDER BY event_date DESC;";
        $demoResults = $conn->query($demoQuerySql)->fetchAll();
        break;
}

$pageTitle = "Admin Portal - Campus Lost & Found";
require_once __DIR__ . "/../includes/header.php";
?>

<div class="page-header bg-navy text-white">
    <div class="container admin-header-flex">
        <div>
            <span class="badge badge-accent">Admin Console</span>
            <h1>Campus Lost &amp; Found Administration</h1>
            <p>Verify ownership claims, update item lifecycles, configure campus taxonomies, and demonstrate DBMS SQL queries.</p>
        </div>
        <div class="admin-quick-stats">
            <div class="admin-stat-pill">
                <strong><?php echo $pendingClaimsCount; ?></strong>
                <span>Pending Claims</span>
            </div>
            <div class="admin-stat-pill">
                <strong><?php echo count($allItems); ?></strong>
                <span>Total Items</span>
            </div>
        </div>
    </div>
</div>

<div class="container py-4">
    
    <div class="dashboard-tabs">
        <button class="dashboard-tab-btn active" data-tab="overview">
            <span>&#128202;</span> Overview
        </button>
        <button class="dashboard-tab-btn" data-tab="claims">
            <span>&#128221;</span> Claim Verification 
            <?php if ($pendingClaimsCount > 0): ?>
                <span class="badge badge-danger"><?php echo $pendingClaimsCount; ?></span>
            <?php endif; ?>
        </button>
        <button class="dashboard-tab-btn" data-tab="items">
            <span>&#128230;</span> All Items (<?php echo count($allItems); ?>)
        </button>
        <button class="dashboard-tab-btn" data-tab="categories">
            <span>&#128194;</span> Categories &amp; Locations
        </button>
        <button class="dashboard-tab-btn" data-tab="users">
            <span>&#128101;</span> Campus Users (<?php echo count($allUsers); ?>)
        </button>
        <button class="dashboard-tab-btn" data-tab="dbms-lab">
            <span>&#128203;</span> SQL Queries
        </button>
    </div>

    
    
    
    <div class="dashboard-tab-pane active" id="tab-overview">
        
        <div class="stats-grid">
            <div class="stat-card stat-users">
                <div class="stat-icon">&#128101;</div>
                <div class="stat-number"><?php echo $totalUsers; ?></div>
                <div class="stat-label">Registered Users</div>
                <div class="stat-sub">Across Student, Faculty, Staff</div>
            </div>

            <div class="stat-card stat-lost">
                <div class="stat-icon">&#10060;</div>
                <div class="stat-number"><?php echo $totalLost; ?></div>
                <div class="stat-label">Lost Items</div>
                <div class="stat-sub">Reported Missing</div>
            </div>

            <div class="stat-card stat-found">
                <div class="stat-icon">&#128269;</div>
                <div class="stat-number"><?php echo $totalFound; ?></div>
                <div class="stat-label">Found Items</div>
                <div class="stat-sub">Available to Claim</div>
            </div>

            <div class="stat-card stat-claimed">
                <div class="stat-icon">&#128221;</div>
                <div class="stat-number"><?php echo $totalClaimed; ?></div>
                <div class="stat-label">Claimed Items</div>
                <div class="stat-sub">Ownership Verified</div>
            </div>

            <div class="stat-card stat-returned">
                <div class="stat-icon">&#127881;</div>
                <div class="stat-number"><?php echo $totalReturned; ?></div>
                <div class="stat-label">Returned Items</div>
                <div class="stat-sub">Case Resolved</div>
            </div>
        </div>

        <?php if ($pendingClaimsCount > 0): ?>
            <div class="alert alert-warning mt-4 d-flex-between">
                <div>
                    <strong>Action Required:</strong> There are <strong><?php echo $pendingClaimsCount; ?></strong> pending ownership claims awaiting review and verification!
                </div>
                <a href="#claims" class="btn btn-sm btn-primary dashboard-tab-btn" data-tab="claims">Review Claims &rarr;</a>
            </div>
        <?php endif; ?>

        
        <div class="card-box mt-4">
            <div class="card-box-header">
                <h3>Latest Reported Items</h3>
                <a href="#items" class="btn btn-sm btn-outline dashboard-tab-btn" data-tab="items">View All Items</a>
            </div>
            <div class="table-responsive">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Item Name</th>
                            <th>Type</th>
                            <th>Category</th>
                            <th>Campus Location</th>
                            <th>Status</th>
                            <th>Reported By</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($allItems, 0, 6) as $item): ?>
                            <tr>
                                <td>#<?php echo $item['item_id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($item['item_name']); ?></strong></td>
                                <td>
                                    <span class="badge-status badge-status-<?php echo strtolower($item['report_type']); ?>">
                                        <?php echo $item['report_type']; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['location_name']); ?></td>
                                <td>
                                    <span class="badge-status badge-status-<?php echo strtolower($item['status']); ?>">
                                        <?php echo htmlspecialchars($item['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($item['reporter_name']); ?></td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>item-details.php?id=<?php echo $item['item_id']; ?>" class="btn btn-xs btn-outline">
                                        Details
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    
    
    
    <div class="dashboard-tab-pane" id="tab-claims">
        <div class="card-box">
            <div class="card-box-header">
                <div>
                    <h2>Submitted Ownership Claims</h2>
                    <p class="text-muted">Review claimant evidence, verify ownership proof, and approve or reject claims.</p>
                </div>
            </div>

            <?php if (!empty($allClaims)): ?>
                <div class="table-responsive">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th>Claim ID</th>
                                <th>Target Item</th>
                                <th>Claimant Details</th>
                                <th>Claim Date</th>
                                <th>Submitted Ownership Proof</th>
                                <th>Claim Status</th>
                                <th>Administrator Decision</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allClaims as $claim): ?>
                                <tr class="<?php echo ($claim['claim_status'] === 'Pending') ? 'row-highlight' : ''; ?>">
                                    <td><strong>#<?php echo $claim['claim_id']; ?></strong></td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>item-details.php?id=<?php echo $claim['item_id']; ?>">
                                            <strong><?php echo htmlspecialchars($claim['item_name']); ?></strong>
                                        </a>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($claim['category_name']); ?> &bull; <?php echo htmlspecialchars($claim['color']); ?></small>
                                        <br>
                                        <small>Item Status: <span class="badge-status badge-status-<?php echo strtolower($claim['item_status']); ?>"><?php echo $claim['item_status']; ?></span></small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($claim['claimant_name']); ?></strong>
                                        <span class="role-badge role-<?php echo strtolower($claim['claimant_role']); ?>"><?php echo $claim['claimant_role']; ?></span>
                                        <br>
                                        <small>&#9993; <?php echo htmlspecialchars($claim['claimant_email']); ?></small>
                                        <?php if (!empty($claim['claimant_phone'])): ?>
                                            <br><small>&#128222; <?php echo htmlspecialchars($claim['claimant_phone']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($claim['claim_date'])); ?></td>
                                    <td style="max-width: 280px;">
                                        <div class="proof-box">
                                            <?php echo nl2br(htmlspecialchars($claim['identification_details'])); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge-status badge-status-<?php echo strtolower($claim['claim_status']); ?> badge-md">
                                            <?php echo htmlspecialchars($claim['claim_status']); ?>
                                        </span>
                                        <?php if (!empty($claim['verifier_name'])): ?>
                                            <br><small class="text-muted">By <?php echo htmlspecialchars($claim['verifier_name']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($claim['claim_status'] === 'Pending'): ?>
                                            <div class="btn-group-stacked">
                                                <a href="dashboard.php?action=verify_claim&claim_id=<?php echo $claim['claim_id']; ?>&decision=approve" 
                                                   class="btn btn-xs btn-accent" 
                                                   data-confirm="Approve this claim? This will verify claimant ownership, update item status to Claimed, and reject conflicting claims.">
                                                    <span>&#10004;</span> Approve Claim
                                                </a>
                                                <a href="dashboard.php?action=verify_claim&claim_id=<?php echo $claim['claim_id']; ?>&decision=reject" 
                                                   class="btn btn-xs btn-danger" 
                                                   data-confirm="Are you sure you want to REJECT this claim?">
                                                    <span>&#10060;</span> Reject Claim
                                                </a>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted"><small>Decision Recorded</small></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state-card">
                    <p>No claims have been submitted yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    
    
    
    <div class="dashboard-tab-pane" id="tab-items">
        <div class="card-box">
            <div class="card-box-header">
                <div>
                    <h2>Item Records &amp; Status Lifecycles</h2>
                    <p class="text-muted">Directly manage items across Lost &rarr; Found &rarr; Claimed &rarr; Returned states.</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>Item ID</th>
                            <th>Item Title</th>
                            <th>Type</th>
                            <th>Category</th>
                            <th>Color</th>
                            <th>Location</th>
                            <th>Reporter</th>
                            <th>Current Status</th>
                            <th>Update Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allItems as $item): ?>
                            <tr>
                                <td>#<?php echo $item['item_id']; ?></td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>item-details.php?id=<?php echo $item['item_id']; ?>">
                                        <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge-status badge-status-<?php echo strtolower($item['report_type']); ?>">
                                        <?php echo $item['report_type']; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['color'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($item['location_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['reporter_name']); ?></td>
                                <td>
                                    <span class="badge-status badge-status-<?php echo strtolower($item['status']); ?>">
                                        <?php echo htmlspecialchars($item['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" action="dashboard.php?action=update_item_status" class="form-inline-status">
                                        <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                        <select name="status" onchange="this.form.submit()" class="form-select-xs">
                                            <option value="Lost" <?php echo ($item['status'] === 'Lost') ? 'selected' : ''; ?>>Lost</option>
                                            <option value="Found" <?php echo ($item['status'] === 'Found') ? 'selected' : ''; ?>>Found</option>
                                            <option value="Claimed" <?php echo ($item['status'] === 'Claimed') ? 'selected' : ''; ?>>Claimed</option>
                                            <option value="Returned" <?php echo ($item['status'] === 'Returned') ? 'selected' : ''; ?>>Returned</option>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <div class="btn-group-xs">
                                        <a href="<?php echo BASE_URL; ?>item-details.php?id=<?php echo $item['item_id']; ?>" class="btn btn-xs btn-outline">
                                            View
                                        </a>
                                        <a href="dashboard.php?action=delete_item&id=<?php echo $item['item_id']; ?>" 
                                           class="btn btn-xs btn-danger" 
                                           data-confirm="Are you sure you want to permanently delete item #<?php echo $item['item_id']; ?>?">
                                            Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    
    
    
    <div class="dashboard-tab-pane" id="tab-categories">
        <div class="dashboard-two-col">
            
            <div class="card-box">
                <div class="card-box-header">
                    <h3>Item Categories</h3>
                </div>

                
                <form method="POST" action="dashboard.php?action=add_category" class="form-inline-add">
                    <input type="text" name="category_name" placeholder="New Category Name (e.g. Wallets)" required>
                    <input type="text" name="description" placeholder="Description...">
                    <button type="submit" class="btn btn-primary btn-sm">Add Category</button>
                </form>

                <div class="table-responsive mt-3">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th>Category Name</th>
                                <th>Description</th>
                                <th>Items</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allCategories as $cat): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($cat['category_name']); ?></strong></td>
                                    <td><small class="text-muted"><?php echo htmlspecialchars($cat['description']); ?></small></td>
                                    <td><span class="badge badge-info"><?php echo $cat['total_items']; ?></span></td>
                                    <td>
                                        <?php if ($cat['total_items'] == 0): ?>
                                            <a href="dashboard.php?action=delete_category&id=<?php echo $cat['category_id']; ?>" 
                                               class="btn btn-xs btn-outline-danger" 
                                               data-confirm="Delete category '<?php echo htmlspecialchars($cat['category_name']); ?>'?">
                                                Delete
                                            </a>
                                        <?php else: ?>
                                            <small class="text-muted">In use</small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            
            <div class="card-box" id="locations">
                <div class="card-box-header">
                    <h3>Campus Locations</h3>
                </div>

                
                <form method="POST" action="dashboard.php?action=add_location" class="form-inline-add">
                    <input type="text" name="location_name" placeholder="New Location Name (e.g. Bus Stand)" required>
                    <input type="text" name="description" placeholder="Description...">
                    <button type="submit" class="btn btn-accent btn-sm">Add Location</button>
                </form>

                <div class="table-responsive mt-3">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th>Location Name</th>
                                <th>Description</th>
                                <th>Reports</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allLocations as $loc): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($loc['location_name']); ?></strong></td>
                                    <td><small class="text-muted"><?php echo htmlspecialchars($loc['description']); ?></small></td>
                                    <td><span class="badge badge-info"><?php echo $loc['total_reports']; ?></span></td>
                                    <td>
                                        <?php if ($loc['total_reports'] == 0): ?>
                                            <a href="dashboard.php?action=delete_location&id=<?php echo $loc['location_id']; ?>" 
                                               class="btn btn-xs btn-outline-danger" 
                                               data-confirm="Delete location '<?php echo htmlspecialchars($loc['location_name']); ?>'?">
                                                Delete
                                            </a>
                                        <?php else: ?>
                                            <small class="text-muted">In use</small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    
    
    
    <div class="dashboard-tab-pane" id="tab-users">
        <div class="card-box">
            <div class="card-box-header">
                <div>
                    <h2>Registered Campus Users</h2>
                    <p class="text-muted">Students, Teachers, Staff, and Administrators stored in the unified USER relation.</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role / Type</th>
                            <th>Lost Reports</th>
                            <th>Found Reports</th>
                            <th>Claims</th>
                            <th>Registered On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allUsers as $u): ?>
                            <tr>
                                <td>#<?php echo $u['user_id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($u['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td><?php echo htmlspecialchars($u['phone'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="role-badge role-<?php echo strtolower($u['user_type']); ?>">
                                        <?php echo htmlspecialchars($u['user_type']); ?>
                                    </span>
                                </td>
                                <td><?php echo $u['lost_count']; ?></td>
                                <td><?php echo $u['found_count']; ?></td>
                                <td><?php echo $u['claim_count']; ?></td>
                                <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    
    
    
    <div class="dashboard-tab-pane" id="tab-dbms-lab">
        <div class="card-box">
            <div class="card-box-header">
                <div>
                    <h2>Database SQL Query Inspector</h2>
                    <p class="text-muted">Interactive demonstration of core relational database queries.</p>
                </div>
            </div>

            
            <div class="query-selector-nav mb-4">
                <a href="dashboard.php?demo_query=1#dbms-lab" class="btn btn-sm <?php echo ($selectedQuery === '1') ? 'btn-primary' : 'btn-outline'; ?>">Query 1: Search By Name</a>
                <a href="dashboard.php?demo_query=2#dbms-lab" class="btn btn-sm <?php echo ($selectedQuery === '2') ? 'btn-primary' : 'btn-outline'; ?>">Query 2: Category &amp; Color</a>
                <a href="dashboard.php?demo_query=3#dbms-lab" class="btn btn-sm <?php echo ($selectedQuery === '3') ? 'btn-primary' : 'btn-outline'; ?>">Query 3: Recently Found</a>
                <a href="dashboard.php?demo_query=4#dbms-lab" class="btn btn-sm <?php echo ($selectedQuery === '4') ? 'btn-primary' : 'btn-outline'; ?>">Query 4: Pending Claims</a>
                <a href="dashboard.php?demo_query=5#dbms-lab" class="btn btn-sm <?php echo ($selectedQuery === '5') ? 'btn-primary' : 'btn-outline'; ?>">Query 5: Items By Location</a>
            </div>

            
            <div class="sql-viewer-card">
                <h3><?php echo htmlspecialchars($demoQueryTitle); ?></h3>
                <p class="text-muted"><?php echo htmlspecialchars($demoQueryDesc); ?></p>

                <div class="sql-code-block">
                    <code><?php echo htmlspecialchars($demoQuerySql); ?></code>
                </div>
            </div>

            
            <h4 class="mt-4">Live Execution Results (<?php echo count($demoResults); ?> Rows Returned)</h4>
            <?php if (!empty($demoResults)): ?>
                <div class="table-responsive mt-2">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <?php foreach (array_keys($demoResults[0]) as $column): ?>
                                    <th><?php echo htmlspecialchars($column); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($demoResults as $row): ?>
                                <tr>
                                    <?php foreach ($row as $val): ?>
                                        <td><?php echo htmlspecialchars($val ?? 'NULL'); ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state-card">
                    <p>No rows returned for this query parameter.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>

