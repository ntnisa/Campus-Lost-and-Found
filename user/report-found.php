<?php
require_once __DIR__ . "/../config/database.php";

require_login();

$message = "";
$messageType = "danger";

$categories = $conn->query("SELECT * FROM categories ORDER BY category_name ASC")->fetchAll();
$locations = $conn->query("SELECT * FROM locations ORDER BY location_name ASC")->fetchAll();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $itemName = trim($_POST["item_name"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $color = trim($_POST["color"] ?? "");
    $categoryId = (int)($_POST["category_id"] ?? 0);
    $locationId = (int)($_POST["location_id"] ?? 0);
    $foundDate = $_POST["found_date"] ?? "";
    $details = trim($_POST["additional_details"] ?? "");

    if (empty($itemName) || empty($color) || $categoryId <= 0 || $locationId <= 0 || empty($foundDate)) {
        $message = "Please fill in all required fields indicated with (*).";
    } else {
        try {
            $conn->beginTransaction();

            
            $itemQuery = $conn->prepare("
                INSERT INTO items (item_name, description, color, category_id, status)
                VALUES (?, ?, ?, ?, 'Found')
            ");
            $itemQuery->execute([$itemName, $description, $color, $categoryId]);
            $itemId = $conn->lastInsertId();

            
            $reportQuery = $conn->prepare("
                INSERT INTO found_reports (item_id, user_id, location_id, found_date, additional_details)
                VALUES (?, ?, ?, ?, ?)
            ");
            $reportQuery->execute([$itemId, $_SESSION["user_id"], $locationId, $foundDate, $details]);

            $conn->commit();

            set_flash('success', "Found item report for '$itemName' submitted successfully! It is now listed for owner identification.");
            header("Location: " . BASE_URL . "item-details.php?id=" . $itemId);
            exit();

        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $message = "An error occurred while saving your report: " . $e->getMessage();
        }
    }
}

$pageTitle = "Report Found Item - Campus Lost & Found";
require_once __DIR__ . "/../includes/header.php";
?>

<div class="page-header bg-slate">
    <div class="container">
        <h1>Report a Found Item</h1>
        <p>Found someone's property on campus? Record it here so the rightful owner can submit an ownership claim.</p>
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

            <form method="POST" action="report-found.php" class="form-vertical">
                <div class="form-group">
                    <label for="item_name">Item Name / Title <span class="required">*</span></label>
                    <input 
                        type="text" 
                        id="item_name" 
                        name="item_name" 
                        placeholder="e.g. Black Backpack, Wireless Earbuds, Casio Watch"
                        value="<?php echo htmlspecialchars($_POST['item_name'] ?? ''); ?>"
                        required
                    >
                    <small class="form-hint">Do not mention confidential security codes or PINs.</small>
                </div>

                <div class="form-row">
                    <div class="form-group col-half">
                        <label for="category_id">Category <span class="required">*</span></label>
                        <select id="category_id" name="category_id" required>
                            <option value="">-- Select Category --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['category_id']; ?>" <?php echo (($_POST['category_id'] ?? '') == $cat['category_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group col-half">
                        <label for="color">Primary Color <span class="required">*</span></label>
                        <input 
                            type="text" 
                            id="color" 
                            name="color" 
                            placeholder="e.g. Red, Black, Blue, Silver"
                            value="<?php echo htmlspecialchars($_POST['color'] ?? ''); ?>"
                            required
                        >
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-half">
                        <label for="location_id">Found Location on Campus <span class="required">*</span></label>
                        <select id="location_id" name="location_id" required>
                            <option value="">-- Select Location --</option>
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?php echo $loc['location_id']; ?>" <?php echo (($_POST['location_id'] ?? '') == $loc['location_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($loc['location_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group col-half">
                        <label for="found_date">Date Found <span class="required">*</span></label>
                        <input 
                            type="date" 
                            id="found_date" 
                            name="found_date" 
                            value="<?php echo htmlspecialchars($_POST['found_date'] ?? date('Y-m-d')); ?>"
                            max="<?php echo date('Y-m-d'); ?>"
                            required
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">General Description</label>
                    <textarea 
                        id="description" 
                        name="description" 
                        rows="3" 
                        placeholder="Brief overview of the item (e.g. brand, approximate size, external look)..."
                    ><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="additional_details">Additional Circumstances / Hand-off Location</label>
                    <textarea 
                        id="additional_details" 
                        name="additional_details" 
                        rows="2" 
                        placeholder="e.g. Found on table 4 in the cafeteria, currently deposited at the department office..."
                    ><?php echo htmlspecialchars($_POST['additional_details'] ?? ''); ?></textarea>
                </div>

                <div class="form-actions-inline">
                    <button type="submit" class="btn btn-accent btn-lg">
                        <span>&#10004;</span> Submit Found Item Report
                    </button>
                    <a href="<?php echo BASE_URL; ?>index.php" class="btn btn-outline">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
