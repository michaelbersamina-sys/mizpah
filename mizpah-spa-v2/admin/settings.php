```php
<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

/* =========================================
   ESCAPE OUTPUT
========================================= */
function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/* =========================================
   DEFAULT WELLNESS TERMS
   Used only if the database value is empty.
========================================= */
$default_terms = <<<'TERMS'
1. Accuracy of Information

I confirm that the wellness information I provide is accurate and complete to the best of my knowledge. I understand that I should update my wellness profile if my information or condition changes.

2. Wellness Information

I agree to disclose relevant allergies, skin sensitivities, medical conditions, injuries, and other wellness information that may affect the services provided to me.

3. Massage and Spa Services

I understand that massage and spa treatments are intended for relaxation and general wellness. These services are not a substitute for medical diagnosis, treatment, or professional medical advice.

4. Health and Safety

I understand that I should inform the spa staff of any health concerns or discomfort before and during the service. I may request that a treatment be stopped if I experience pain, discomfort, or an adverse reaction.

5. Service Adjustments

I understand that the therapist may adjust or decline a treatment when necessary for my safety and comfort. I agree to communicate my preferences and concerns honestly.

6. Privacy of Information

I understand that the wellness information I provide will be handled by Mizpah Wellness Spa for service preparation, customer care, and related operational purposes, subject to applicable privacy requirements.

7. Emergency Contact

I understand that the emergency contact information I provide may be used when necessary in the event of an emergency related to my visit or service.
TERMS;

/* =========================================
   SAVE SETTINGS
========================================= */
if (isset($_POST['save'])) {

    $site_name = trim($_POST['site_name'] ?? '');
    $tagline = trim($_POST['tagline'] ?? '');
    $contact = trim($_POST['contact_number'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $footer_text = trim($_POST['footer_text'] ?? '');
    $copyright = trim($_POST['copyright_text'] ?? '');
    $wellness_terms = trim($_POST['wellness_terms'] ?? '');

    if ($wellness_terms === '') {
        $error_message = 'Wellness Terms and Conditions cannot be empty.';
    } else {

        $stmt = mysqli_prepare($conn, "
            UPDATE settings SET
                site_name = ?,
                tagline = ?,
                contact_number = ?,
                address = ?,
                footer_text = ?,
                copyright_text = ?,
                wellness_terms = ?
            WHERE id = 1
        ");

        if (!$stmt) {
            $error_message = 'Unable to prepare settings update: ' .
                mysqli_error($conn);
        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "sssssss",
                $site_name,
                $tagline,
                $contact,
                $address,
                $footer_text,
                $copyright,
                $wellness_terms
            );

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);

                header("Location: settings.php?saved=1");
                exit;
            } else {
                $error_message = 'Unable to save settings: ' .
                    mysqli_stmt_error($stmt);
                mysqli_stmt_close($stmt);
            }
        }
    }
}

/* =========================================
   LOAD SETTINGS
========================================= */
$q = mysqli_query($conn, "
    SELECT *
    FROM settings
    WHERE id = 1
    LIMIT 1
");

if (!$q) {
    die("Unable to load settings: " . e(mysqli_error($conn)));
}

$row = mysqli_fetch_assoc($q);

if (!$row) {
    die("No settings record was found with id = 1.");
}

/* Keep existing database values */
$site_name_value = $row['site_name'] ?? '';
$tagline_value = $row['tagline'] ?? '';
$contact_value = $row['contact_number'] ?? '';
$address_value = $row['address'] ?? '';
$footer_text_value = $row['footer_text'] ?? '';
$copyright_value = $row['copyright_text'] ?? '';

$wellness_terms_value = trim($row['wellness_terms'] ?? '');

if ($wellness_terms_value === '') {
    $wellness_terms_value = $default_terms;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Website Settings | Mizpah Wellness Spa</title>

<link rel="stylesheet" href="../assets/css/admin.css">

<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">

<style>
:root {
    --gold: #D6C29C;
    --gold-light: #E8D9B9;
    --bg: #0b0b0b;
    --card: #151515;
    --input: #101010;
    --border: rgba(214,194,156,.18);
    --muted: #999;
    --text: #f5f1e9;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: 'DM Sans', sans-serif;
    background: var(--bg);
    color: var(--text);
}

.main {
    margin-left: 250px;
    padding: 32px;
    min-height: 100vh;
}

.page-header {
    margin-bottom: 28px;
}

.page-header h1 {
    font-family: 'Playfair Display', serif;
    font-size: clamp(25px, 3vw, 34px);
    color: var(--gold);
    margin: 0 0 8px;
}

.page-header p {
    color: var(--muted);
    font-size: 13px;
    line-height: 1.7;
    margin: 0;
}

.settings-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 22px;
    max-width: 1100px;
}

.settings-card {
    min-width: 0;
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 26px;
}

.card-heading {
    padding-bottom: 18px;
    margin-bottom: 20px;
    border-bottom: 1px solid rgba(255,255,255,.07);
}

.card-heading h2 {
    margin: 0 0 7px;
    font-family: 'Playfair Display', serif;
    font-size: 21px;
    font-weight: 600;
    color: var(--gold);
}

.card-heading p {
    color: var(--muted);
    font-size: 12px;
    line-height: 1.7;
    margin: 0;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 19px;
}

.field {
    min-width: 0;
}

.field.full {
    grid-column: 1 / -1;
}

label {
    display: block;
    color: #d0c9bd;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 8px;
}

input[type="text"],
input[type="tel"],
textarea {
    display: block;
    width: 100%;
    max-width: 100%;
    min-width: 0;
    padding: 13px 14px;
    color: #f5f1e9;
    background: var(--input);
    border: 1px solid #333;
    border-radius: 9px;
    font: inherit;
    font-size: 13px;
    outline: none;
    transition: border-color .2s, box-shadow .2s;
}

input:focus,
textarea:focus {
    border-color: var(--gold);
    box-shadow: 0 0 0 3px rgba(214,194,156,.08);
}

input::placeholder,
textarea::placeholder {
    color: #68645d;
}

textarea {
    resize: vertical;
    line-height: 1.75;
}

.footer-textarea {
    min-height: 100px;
}

.terms-textarea {
    min-height: 420px;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
    tab-size: 4;
}

.helper {
    margin-top: 7px;
    color: #777;
    font-size: 11px;
    line-height: 1.6;
}

.notice {
    padding: 13px 15px;
    border-radius: 9px;
    margin-bottom: 20px;
    font-size: 12px;
    line-height: 1.6;
}

.notice.success {
    background: rgba(90,150,100,.12);
    border: 1px solid rgba(90,150,100,.3);
    color: #b8dfbd;
}

.notice.error {
    background: rgba(190,80,80,.12);
    border: 1px solid rgba(190,80,80,.3);
    color: #efb5b5;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    margin-top: 22px;
}

.save-btn {
    border: none;
    border-radius: 9px;
    background: var(--gold);
    color: #17130d;
    padding: 13px 24px;
    font-family: inherit;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: background .2s, transform .2s;
}

.save-btn:hover {
    background: var(--gold-light);
    transform: translateY(-1px);
}

.save-btn:focus-visible {
    outline: 2px solid var(--gold-light);
    outline-offset: 3px;
}

@media (max-width: 1000px) {
    .main {
        margin-left: 0;
        padding: 24px;
    }
}

@media (max-width: 650px) {
    .main {
        padding: 17px 12px 28px;
    }

    .page-header {
        margin-bottom: 20px;
    }

    .settings-card {
        padding: 18px 14px;
        border-radius: 12px;
    }

    .form-grid {
        grid-template-columns: minmax(0, 1fr);
        gap: 16px;
    }

    .field.full {
        grid-column: auto;
    }

    .card-heading h2 {
        font-size: 19px;
    }

    .terms-textarea {
        min-height: 350px;
    }

    .form-actions {
        display: block;
    }

    .save-btn {
        width: 100%;
    }
}
</style>
</head>

<body>

<?php include 'includes/sidebar.php'; ?>

<main class="main">

    <header class="page-header">
        <h1>Website Settings</h1>
        <p>Manage your website information, footer content, and Wellness Profile Terms and Conditions.</p>
    </header>

    <?php if (isset($_GET['saved']) && $_GET['saved'] === '1'): ?>
        <div class="notice success">
            Settings saved successfully. Your changes are now stored in the database.
        </div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
        <div class="notice error">
            <?= e($error_message) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="settings.php">

        <!-- WEBSITE INFORMATION -->
        <section class="settings-card">
            <div class="card-heading">
                <h2>Website Information</h2>
                <p>Update the basic information displayed on your website.</p>
            </div>

            <div class="form-grid">

                <div class="field">
                    <label for="site_name">Site Name</label>
                    <input
                        type="text"
                        id="site_name"
                        name="site_name"
                        value="<?= e($site_name_value) ?>"
                        required
                    >
                </div>

                <div class="field">
                    <label for="contact_number">Contact Number</label>
                    <input
                        type="text"
                        id="contact_number"
                        name="contact_number"
                        value="<?= e($contact_value) ?>"
                    >
                </div>

                <div class="field full">
                    <label for="tagline">Tagline</label>
                    <input
                        type="text"
                        id="tagline"
                        name="tagline"
                        value="<?= e($tagline_value) ?>"
                    >
                </div>

                <div class="field full">
                    <label for="address">Address</label>
                    <input
                        type="text"
                        id="address"
                        name="address"
                        value="<?= e($address_value) ?>"
                    >
                </div>

            </div>
        </section>

        <!-- FOOTER SETTINGS -->
        <section class="settings-card" style="margin-top:22px;">
            <div class="card-heading">
                <h2>Footer Settings</h2>
                <p>These fields are retained so your existing footer content can still be managed.</p>
            </div>

            <div class="form-grid">

                <div class="field full">
                    <label for="footer_text">Footer Text</label>
                    <textarea
                        id="footer_text"
                        name="footer_text"
                        class="footer-textarea"
                    ><?= e($footer_text_value) ?></textarea>
                </div>

                <div class="field full">
                    <label for="copyright_text">Copyright Text</label>
                    <textarea
                        id="copyright_text"
                        name="copyright_text"
                        rows="3"
                    ><?= e($copyright_value) ?></textarea>
                </div>

            </div>
        </section>

        <!-- WELLNESS TERMS -->
        <section class="settings-card" style="margin-top:22px;">
            <div class="card-heading">
                <h2>Wellness Profile Terms and Conditions</h2>
                <p>Edit the complete terms displayed when a customer clicks the Terms and Conditions link on the Wellness Profile page.</p>
            </div>

            <div class="field">
                <label for="wellness_terms">Complete Terms and Conditions</label>

                <textarea
                    id="wellness_terms"
                    name="wellness_terms"
                    class="terms-textarea"
                    required
                ><?= e($wellness_terms_value) ?></textarea>

                <p class="helper">
                    You can edit the title of each section and its content. Use line breaks to separate headings and paragraphs.
                </p>
            </div>
        </section>

        <div class="form-actions">
            <button type="submit" name="save" value="1" class="save-btn">
                Save All Settings
            </button>
        </div>

    </form>

</main>

</body>
</html>
```