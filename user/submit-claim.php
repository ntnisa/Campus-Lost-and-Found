<?php
require_once __DIR__ . "/../config/database.php";

require_login();

$userId = (int)$_SESSION["user_id"];
$message = "";
$messageType = "danger";

$preselectedItemId = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;

$itemsStmt = $conn->prepare("
    SELECT 
        items.item_id, 
        items.item_name, 
        items.color,
        categories.category_name,
        locations.location_name,
        found_reports.found_date
    FROM items
    INNER JOIN categories ON items.category_id = categories.category_id
    INNER JOIN found_reports ON items.item_id = found_reports.item_id
    INNER JOIN locations ON found_reports.location_id = locations.location_id
    WHERE items.status = 'Found'
    AND found_reports.user_id != ?
    ORDER BY found_reports.report_date DESC
");
$itemsStmt->execute([$userId]);
$availableItems = $itemsStmt->fetchAll();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $itemId = (int)($_POST["item_id"] ?? 0);
    $details = trim($_POST["identification_details"] ?? "");

    if ($itemId <= 0 || empty($details)) {
        $message = "Please select a found item and provide detailed identification proof.";
    } else {
        
        $verifyItem = $conn->prepare("
            SELECT items.item_id, items.item_name
            FROM items
            INNER JOIN found_reports ON items.item_id = found_reports.item_id
            WHERE items.item_id = ?
            AND items.status = 'Found'
            AND found_reports.user_id != ?
        ");
        $verifyItem->execute([$itemId, $userId]);
        $targetItem = $verifyItem->fetch();

        if (!$targetItem) {
            $message = "This item is not currently available for claim.";
        } else {
            
            $checkExisting = $conn->prepare("SELECT claim_id FROM claims WHERE item_id = ? AND user_id = ?");
            $checkExisting->execute([$itemId, $userId]);

            if ($checkExisting->fetch()) {
                $message = "You have already submitted an active claim for this item. Please wait for administrator verification.";
            } else {
                
                $claimQuery = $conn->prepare("
                    INSERT INTO claims (item_id, user_id, claim_date, identification_details, status)
                    VALUES (?, ?, NOW(), ?, 'Pending')
                ");

                if ($claimQuery->execute([$itemId, $userId, $details])) {
                    set_flash('success', "Your claim for '" . $targetItem['item_name'] . "' has been submitted! An administrator will review your identification details.");
                    header("Location: " . BASE_URL . "user/my-reports.php#claims");
                    exit();
                } else {
                    $message = "Failed to submit claim. Please try again.";
                }
            }
        }
    }
}

$pageTitle = "Submit Item Claim - Campus Lost & Found";
require_once __DIR__ . "/../includes/header.php";
?>

<div class="page-header bg-slate">
    <div class="container">
        <h1>Submit Ownership Claim</h1>
        <p>Found your lost item in the portal? Submit identifying evidence so the administrator can verify your ownership.</p>
    </div>
</div>

<div class="container py-4">
    <div class="form-card-container">
        <div class="form-card">
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <span>&#9888;</span> <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if (count($availableItems) > 0): ?>
                <form method="POST" action="submit-claim.php" class="form-vertical">
                    <div class="form-group">
                        <label for="item_id">Select Found Item <span class="required">*</span></label>
                        <select id="item_id" name="item_id" required>
                            <option value="">-- Choose Found Item --</option>
                            <?php foreach ($availableItems as $item): ?>
                                <?php 
                                $selected = ($preselectedItemId === (int)$item['item_id'] || ($_POST['item_id'] ?? 0) == $item['item_id']) ? 'selected' : '';
                                ?>
                                <option value="<?php echo $item['item_id']; ?>" <?php echo $selected; ?>>
                                    <?php echo htmlspecialchars($item['item_name'] . " (" . $item['color'] . ") - Found at " . $item['location_name'] . " on " . date('M d, Y', strtotime($item['found_date']))); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-hint">Items reported by you are excluded from this list.</small>
                    </div>

                    <div class="form-group">
                        <label for="identification_details">Ownership Proof &amp; Identifying Details <span class="required">*</span></label>
                        <textarea 
                            id="identification_details" 
                            name="identification_details" 
                            rows="6" 
                            placeholder="Provide details that prove this belongs to you:
- Lockscreen wallpaper description or password/pattern hint (do not give actual password)
- Specific marks, scratches, or custom stickers
- Contents inside the bag, wallet, or case
- Serial number, purchase invoice, or university ID card match"
                            required
                        ><?php echo htmlspecialchars($_POST['identification_details'] ?? ''); ?></textarea>
                    </div>

                    <div class="claim-warning-box">
                        <span>&#128712;</span>
                        <small>False claims are subject to university disciplinary policy. Your submitted information and campus user ID are logged and verified by university administrators.</small>
                    </div>

                    <div class="form-actions-inline mt-3">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <span>&#128221;</span> Submit Claim for Verification
                        </button>
                        <a href="<?php echo BASE_URL; ?>search.php" class="btn btn-outline">Cancel</a>
                    </div>
                </form>
            <?php else: ?>
                <div class="empty-state">
                    <span class="empty-icon">&#128237;</span>
                    <h3>No items currently available to claim</h3>
                    <p>There are no unclaimed found items reported by other campus members right now.</p>
                    <a href="<?php echo BASE_URL; ?>search.php" class="btn btn-primary">Browse All Items</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
