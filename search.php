<?php
require_once __DIR__ . "/config/database.php";

$pageTitle = "Search Lost & Found Items - Metropolitan University";

$categories = $conn->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC")->fetchAll();
$locations = $conn->query("SELECT location_id, location_name FROM locations ORDER BY location_name ASC")->fetchAll();

$search = trim($_GET['search'] ?? '');
$categoryId = trim($_GET['category'] ?? '');
$locationId = trim($_GET['location'] ?? '');
$color = trim($_GET['color'] ?? '');
$itemType = trim($_GET['type'] ?? 'all'); 

$lostParams = [];
$lostWhere = ["1=1"];

if (!empty($search)) {
    $lostWhere[] = "(items.item_name LIKE :lost_search OR items.description LIKE :lost_search2 OR lost_reports.additional_details LIKE :lost_search3)";
    $lostParams[':lost_search'] = "%$search%";
    $lostParams[':lost_search2'] = "%$search%";
    $lostParams[':lost_search3'] = "%$search%";
}

if (!empty($categoryId)) {
    $lostWhere[] = "items.category_id = :lost_category";
    $lostParams[':lost_category'] = $categoryId;
}

if (!empty($locationId)) {
    $lostWhere[] = "lost_reports.location_id = :lost_location";
    $lostParams[':lost_location'] = $locationId;
}

if (!empty($color)) {
    $lostWhere[] = "items.color LIKE :lost_color";
    $lostParams[':lost_color'] = "%$color%";
}

$lostSql = "
    SELECT 
        items.item_id,
        items.item_name,
        items.description,
        items.color,
        items.status,
        categories.category_name,
        locations.location_name,
        lost_reports.lost_date AS event_date,
        lost_reports.report_date,
        lost_reports.additional_details,
        users.name AS reporter_name,
        users.user_type AS reporter_role,
        'Lost' AS report_type
    FROM lost_reports
    INNER JOIN items ON lost_reports.item_id = items.item_id
    INNER JOIN categories ON items.category_id = categories.category_id
    INNER JOIN locations ON lost_reports.location_id = locations.location_id
    INNER JOIN users ON lost_reports.user_id = users.user_id
    WHERE " . implode(" AND ", $lostWhere) . "
    ORDER BY lost_reports.report_date DESC
";

$foundParams = [];
$foundWhere = ["1=1"];

if (!empty($search)) {
    $foundWhere[] = "(items.item_name LIKE :found_search OR items.description LIKE :found_search2 OR found_reports.additional_details LIKE :found_search3)";
    $foundParams[':found_search'] = "%$search%";
    $foundParams[':found_search2'] = "%$search%";
    $foundParams[':found_search3'] = "%$search%";
}

if (!empty($categoryId)) {
    $foundWhere[] = "items.category_id = :found_category";
    $foundParams[':found_category'] = $categoryId;
}

if (!empty($locationId)) {
    $foundWhere[] = "found_reports.location_id = :found_location";
    $foundParams[':found_location'] = $locationId;
}

if (!empty($color)) {
    $foundWhere[] = "items.color LIKE :found_color";
    $foundParams[':found_color'] = "%$color%";
}

if ($itemType === 'claimed') {
    $foundWhere[] = "items.status = 'Claimed'";
} elseif ($itemType === 'returned') {
    $foundWhere[] = "items.status = 'Returned'";
} elseif ($itemType === 'found') {
    $foundWhere[] = "items.status = 'Found'";
}

$foundSql = "
    SELECT 
        items.item_id,
        items.item_name,
        items.description,
        items.color,
        items.status,
        categories.category_name,
        locations.location_name,
        found_reports.found_date AS event_date,
        found_reports.report_date,
        found_reports.additional_details,
        users.name AS reporter_name,
        users.user_type AS reporter_role,
        'Found' AS report_type
    FROM found_reports
    INNER JOIN items ON found_reports.item_id = items.item_id
    INNER JOIN categories ON items.category_id = categories.category_id
    INNER JOIN locations ON found_reports.location_id = locations.location_id
    INNER JOIN users ON found_reports.user_id = users.user_id
    WHERE " . implode(" AND ", $foundWhere) . "
    ORDER BY found_reports.report_date DESC
";

$lostItems = [];
$foundItems = [];

if ($itemType === 'all' || $itemType === 'lost') {
    $lostStmt = $conn->prepare($lostSql);
    $lostStmt->execute($lostParams);
    $lostItems = $lostStmt->fetchAll();
}

if ($itemType === 'all' || in_array($itemType, ['found', 'claimed', 'returned'], true)) {
    $foundStmt = $conn->prepare($foundSql);
    $foundStmt->execute($foundParams);
    $foundItems = $foundStmt->fetchAll();
}

$totalResults = count($lostItems) + count($foundItems);

require_once __DIR__ . "/includes/header.php";
?>

<div class="page-header bg-slate">
    <div class="container">
        <h1>Browse &amp; Search Campus Items</h1>
        <p>Use the multi-attribute filters below to search through all registered lost and found reports.</p>
    </div>
</div>

<div class="container py-4">
    
    <div class="filter-panel">
        <form method="GET" action="search.php" class="filter-grid-form">
            <div class="filter-group">
                <label for="search">Keywords / Item Name</label>
                <input 
                    type="text" 
                    id="search" 
                    name="search" 
                    placeholder="e.g. Phone, ID Card, Watch..." 
                    value="<?php echo htmlspecialchars($search); ?>"
                >
            </div>

            <div class="filter-group">
                <label for="category">Category</label>
                <select id="category" name="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['category_id']; ?>" <?php echo ($categoryId == $cat['category_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['category_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="location">Campus Location</label>
                <select id="location" name="location">
                    <option value="">All Locations</option>
                    <?php foreach ($locations as $loc): ?>
                        <option value="<?php echo $loc['location_id']; ?>" <?php echo ($locationId == $loc['location_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($loc['location_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="color">Color</label>
                <input 
                    type="text" 
                    id="color" 
                    name="color" 
                    placeholder="e.g. Black, Blue, Silver" 
                    value="<?php echo htmlspecialchars($color); ?>"
                >
            </div>

            <div class="filter-group">
                <label for="type">Item Status / Scope</label>
                <select id="type" name="type">
                    <option value="all" <?php echo ($itemType === 'all') ? 'selected' : ''; ?>>All Items (Lost &amp; Found)</option>
                    <option value="found" <?php echo ($itemType === 'found') ? 'selected' : ''; ?>>Available Found Items</option>
                    <option value="lost" <?php echo ($itemType === 'lost') ? 'selected' : ''; ?>>Missing Lost Items</option>
                    <option value="claimed" <?php echo ($itemType === 'claimed') ? 'selected' : ''; ?>>Claimed Items</option>
                    <option value="returned" <?php echo ($itemType === 'returned') ? 'selected' : ''; ?>>Returned Items</option>
                </select>
            </div>

            <div class="filter-group filter-actions">
                <button type="submit" class="btn btn-primary btn-block">
                    <span>&#128269;</span> Apply Filters
                </button>
                <a href="search.php" class="btn btn-outline btn-block">Reset</a>
            </div>
        </form>
    </div>

    
    <div class="results-header">
        <h2>
            Query Results 
            <span class="badge badge-primary"><?php echo $totalResults; ?> item(s) found</span>
        </h2>
        <?php if (!empty($search) || !empty($categoryId) || !empty($locationId) || !empty($color) || $itemType !== 'all'): ?>
            <div class="active-filters">
                <span>Active Filters:</span>
                <?php if (!empty($search)): ?>
                    <span class="filter-chip">Keyword: "<?php echo htmlspecialchars($search); ?>"</span>
                <?php endif; ?>
                <?php if (!empty($color)): ?>
                    <span class="filter-chip">Color: "<?php echo htmlspecialchars($color); ?>"</span>
                <?php endif; ?>
                <?php if ($itemType !== 'all'): ?>
                    <span class="filter-chip">Type: <?php echo ucfirst(htmlspecialchars($itemType)); ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    
    <?php if ($itemType === 'all' || in_array($itemType, ['found', 'claimed', 'returned'], true)): ?>
        <div class="section-title-wrap">
            <h3 class="section-subheading">
                <span class="badge-status badge-status-found">&#10004;</span> 
                Found Belongings (<?php echo count($foundItems); ?>)
            </h3>
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
                                <span>Found At: <strong><?php echo htmlspecialchars($item['location_name']); ?></strong></span>
                            </div>
                            <div class="meta-row">
                                <span class="meta-icon">&#128197;</span>
                                <span>Found Date: <strong><?php echo htmlspecialchars(date('M d, Y', strtotime($item['event_date']))); ?></strong></span>
                            </div>
                            <div class="meta-row">
                                <span class="meta-icon">&#128100;</span>
                                <span>Found By: <?php echo htmlspecialchars($item['reporter_name']); ?> (<?php echo htmlspecialchars($item['reporter_role']); ?>)</span>
                            </div>
                        </div>

                        <div class="item-card-footer">
                            <a href="<?php echo BASE_URL; ?>item-details.php?id=<?php echo $item['item_id']; ?>" class="btn btn-sm btn-outline">
                                View Full Details
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
            <div class="empty-state-card">
                <p>No found items match the selected filter criteria.</p>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    
    <?php if ($itemType === 'all' || $itemType === 'lost'): ?>
        <div class="section-title-wrap mt-5">
            <h3 class="section-subheading">
                <span class="badge-status badge-status-lost">&#10060;</span> 
                Lost Belongings (<?php echo count($lostItems); ?>)
            </h3>
        </div>

        <?php if (!empty($lostItems)): ?>
            <div class="items-grid">
                <?php foreach ($lostItems as $item): ?>
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
                                <span>Lost At: <strong><?php echo htmlspecialchars($item['location_name']); ?></strong></span>
                            </div>
                            <div class="meta-row">
                                <span class="meta-icon">&#128197;</span>
                                <span>Lost Date: <strong><?php echo htmlspecialchars(date('M d, Y', strtotime($item['event_date']))); ?></strong></span>
                            </div>
                            <div class="meta-row">
                                <span class="meta-icon">&#128100;</span>
                                <span>Reported By: <?php echo htmlspecialchars($item['reporter_name']); ?> (<?php echo htmlspecialchars($item['reporter_role']); ?>)</span>
                            </div>
                        </div>

                        <div class="item-card-footer">
                            <a href="<?php echo BASE_URL; ?>item-details.php?id=<?php echo $item['item_id']; ?>" class="btn btn-sm btn-outline">
                                View Full Details &rarr;
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state-card">
                <p>No lost items match the selected filter criteria.</p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
