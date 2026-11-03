<?php

session_start();
include '../includes/db.php';

if(!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') != 'customer'){
    header("Location: ../login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

/* =========================
   LOAD ADMIN-EDITABLE TERMS
========================= */
$wellness_terms = '';
$terms_query = mysqli_query($conn, "SELECT wellness_terms FROM settings WHERE id = 1 LIMIT 1");
if ($terms_query && mysqli_num_rows($terms_query) > 0) {
    $terms_row = mysqli_fetch_assoc($terms_query);
    $wellness_terms = trim($terms_row['wellness_terms'] ?? '');
}

if ($wellness_terms === '') {
    $wellness_terms = <<<'TERMS'
1. Accuracy of Information
I confirm that the wellness information I provide is accurate and complete to the best of my knowledge. I understand that I should update my wellness profile if my information or condition changes.

2. Wellness Information
The information provided in this wellness profile may be used by Mizpah Wellness Spa to better understand my needs, preferences, allergies, sensitivities, and other relevant concerns before providing a spa service.

3. Massage and Spa Services
I understand that massage and spa services are intended for relaxation and wellness purposes and are not a substitute for professional medical diagnosis, treatment, or medical care.

4. Health and Safety
I understand that I should inform the spa about relevant allergies, medical conditions, injuries, body pain, skin sensitivities, or other concerns that may affect my spa service.

5. Service Adjustments
I understand that the spa may recommend adjustments to a service when necessary based on the wellness information I provide and applicable safety considerations.

6. Privacy of Information
My wellness information will be used for spa service and customer care purposes. Access to this information should be limited to authorized personnel who need it for those purposes.

7. Emergency Contact
If I voluntarily provide emergency contact information, I understand that it may be used when reasonably necessary in connection with an emergency during my visit.
TERMS;
}

function renderWellnessTerms($text) {
    $blocks = preg_split('/\R\s*\R+/', trim($text));
    foreach ($blocks as $block) {
        $lines = preg_split('/\R/', trim($block));
        if (!$lines || trim(implode('', $lines)) === '') continue;
        $heading = trim(array_shift($lines));
        echo '<div class="term-section">';
        if (preg_match('/^\d+\.\s+/', $heading)) {
            echo '<h3>' . htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') . '</h3>';
        } else {
            echo '<h3>' . htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') . '</h3>';
        }
        $body = trim(implode("\n", $lines));
        if ($body !== '') {
            echo '<p>' . nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8')) . '</p>';
        }
        echo '</div>';
    }
}



/* =========================
   DROPDOWN OPTIONS
========================= */

$allergy_options = [
    'None',
    'Lavender',
    'Coconut Oil',
    'Essential Oils'
];

$skin_options = [
    'Normal Skin',
    'Sensitive Skin',
    'Dry Skin',
    'Oily Skin',
    'Not Sure'
];

$medical_options = [
    'None',
    'Asthma',
    'High Blood Pressure',
    'Diabetes'
];

$body_pain_options = [
    'None',
    'Lower Back Pain',
    'Shoulder Pain',
    'Neck Pain'
];

$pressure_options = [
    'Very Light',
    'Light',
    'Medium',
    'Strong',
    'No Preference'
];


/* =========================
   SAVE WELLNESS PROFILE
========================= */

if(isset($_POST['save_wellness'])){

    /* ALLERGIES */

    $allergies = trim($_POST['allergies'] ?? '');

    if($allergies === 'Other'){
        $allergies = trim($_POST['allergies_other'] ?? '');
    }


    /* SKIN */

    $skin_sensitivity =
        trim($_POST['skin_sensitivity'] ?? '');


    /* MEDICAL CONDITIONS */

    $medical_conditions =
        trim($_POST['medical_conditions'] ?? '');

    if($medical_conditions === 'Other'){
        $medical_conditions =
            trim($_POST['medical_conditions_other'] ?? '');
    }


    /* BODY PAIN */

    $body_pain_injuries =
        trim($_POST['body_pain_injuries'] ?? '');

    if($body_pain_injuries === 'Other'){
        $body_pain_injuries =
            trim($_POST['body_pain_injuries_other'] ?? '');
    }


    /* MASSAGE */

    $preferred_pressure =
        trim($_POST['preferred_pressure'] ?? '');

    $special_requests =
        trim($_POST['special_requests'] ?? '');


    /* EMERGENCY CONTACT */

    $emergency_contact_name =
        trim($_POST['emergency_contact_name'] ?? '');

    $emergency_contact_number =
        trim($_POST['emergency_contact_number'] ?? '');


    /* TERMS */

    $terms_accepted =
        isset($_POST['terms_accepted']) ? 1 : 0;


    /* =========================
       VALIDATION
    ========================= */

    $error = '';

    if(
        $allergies === '' ||
        $skin_sensitivity === '' ||
        $medical_conditions === '' ||
        $body_pain_injuries === '' ||
        $preferred_pressure === ''
    ){
        $error = "Please complete all required wellness information.";
    }

    elseif(!$terms_accepted){
        $error = "Please read and agree to the Terms and Conditions.";
    }


    /* =========================
       SAVE
    ========================= */

    if($error === ''){

        $allergies =
            mysqli_real_escape_string($conn, $allergies);

        $skin_sensitivity =
            mysqli_real_escape_string($conn, $skin_sensitivity);

        $medical_conditions =
            mysqli_real_escape_string($conn, $medical_conditions);

        $body_pain_injuries =
            mysqli_real_escape_string($conn, $body_pain_injuries);

        $preferred_pressure =
            mysqli_real_escape_string($conn, $preferred_pressure);

        $special_requests =
            mysqli_real_escape_string($conn, $special_requests);

        $emergency_contact_name =
            mysqli_real_escape_string($conn, $emergency_contact_name);

        $emergency_contact_number =
            mysqli_real_escape_string($conn, $emergency_contact_number);


        /* CHECK EXISTING PROFILE */

        $check = mysqli_query($conn, "
            SELECT id
            FROM wellness_profiles
            WHERE user_id='$user_id'
            LIMIT 1
        ");


        if($check && mysqli_num_rows($check) > 0){

            /* UPDATE */

            mysqli_query($conn, "
                UPDATE wellness_profiles SET

                    allergies='$allergies',

                    skin_sensitivity='$skin_sensitivity',

                    medical_conditions='$medical_conditions',

                    body_pain_injuries='$body_pain_injuries',

                    preferred_pressure='$preferred_pressure',

                    special_requests='$special_requests',

                    emergency_contact_name='$emergency_contact_name',

                    emergency_contact_number='$emergency_contact_number',

                    terms_accepted=1,

                    terms_accepted_at=NOW()

                WHERE user_id='$user_id'
            ");

        }else{

            /* INSERT */

            mysqli_query($conn, "
                INSERT INTO wellness_profiles
                (
                    user_id,
                    allergies,
                    skin_sensitivity,
                    medical_conditions,
                    body_pain_injuries,
                    preferred_pressure,
                    special_requests,
                    emergency_contact_name,
                    emergency_contact_number,
                    terms_accepted,
                    terms_accepted_at
                )

                VALUES
                (
                    '$user_id',
                    '$allergies',
                    '$skin_sensitivity',
                    '$medical_conditions',
                    '$body_pain_injuries',
                    '$preferred_pressure',
                    '$special_requests',
                    '$emergency_contact_name',
                    '$emergency_contact_number',
                    '1',
                    NOW()
                )
            ");

        }


        /* AFTER SAVE */

        header("Location: profile.php");
        exit();
    }
}


/* =========================
   GET EXISTING PROFILE
========================= */

$profile = [];

$result = mysqli_query($conn, "
    SELECT *
    FROM wellness_profiles
    WHERE user_id='$user_id'
    LIMIT 1
");

if($result && mysqli_num_rows($result) > 0){
    $profile = mysqli_fetch_assoc($result);
}


/* =========================
   CURRENT VALUES
========================= */

$current_allergy =
    $profile['allergies'] ?? '';

$current_skin =
    $profile['skin_sensitivity'] ?? '';

$current_medical =
    $profile['medical_conditions'] ?? '';

$current_body =
    $profile['body_pain_injuries'] ?? '';

$current_pressure =
    $profile['preferred_pressure'] ?? '';


/* CHECK IF SAVED VALUE IS "OTHER" */

$allergy_is_other =
    $current_allergy !== '' &&
    !in_array($current_allergy, $allergy_options);

$medical_is_other =
    $current_medical !== '' &&
    !in_array($current_medical, $medical_options);

$body_is_other =
    $current_body !== '' &&
    !in_array($current_body, $body_pain_options);

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Wellness Profile</title>

<link
    href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600&family=Poppins:wght@300;400;500;600&display=swap"
    rel="stylesheet"
>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Poppins,sans-serif;
}

body{
    background:
        linear-gradient(
            rgba(0,0,0,.78),
            rgba(0,0,0,.90)
        ),
        url('../assets/images/hero.jpg')
        center/cover fixed no-repeat;

    color:#fff;
    min-height:100vh;
}

/* =========================
   CONTAINER
========================= */

.container{
    width:100%;
    max-width:900px;
    margin:auto;
    padding:45px 20px;
}

.card{
    background:rgba(18,18,18,.90);

    backdrop-filter:blur(12px);

    border:
        1px solid
        rgba(214,194,156,.18);

    border-radius:20px;

    padding:38px;

    box-shadow:
        0 18px 45px
        rgba(0,0,0,.55);
}

/* =========================
   HEADER
========================= */

.profile-header{
    text-align:center;
    margin-bottom:32px;
}

.profile-header h1{
    font-family:'Playfair Display',serif;
    color:#D6C29C;
    font-size:32px;
    margin-bottom:7px;
}

.profile-header p{
    color:#999;
    font-size:12px;
    max-width:570px;
    margin:auto;
    line-height:1.7;
}

/* =========================
   ERROR
========================= */

.error{
    background:rgba(180,70,70,.12);
    border:1px solid rgba(220,100,100,.25);
    color:#e7b0b0;

    padding:12px 15px;

    border-radius:9px;

    margin-bottom:20px;

    font-size:11px;
}

/* =========================
   SECTIONS
========================= */

.section{
    margin-top:30px;
}

.section:first-of-type{
    margin-top:0;
}

.section-title{
    color:#D6C29C;

    font-size:16px;
    font-weight:600;

    padding-bottom:10px;
    margin-bottom:18px;

    border-bottom:
        1px solid
        rgba(214,194,156,.15);
}

.section-desc{
    color:#777;
    font-size:10px;

    margin-top:-11px;
    margin-bottom:18px;

    line-height:1.6;
}

/* =========================
   GRID
========================= */

.form-grid{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:18px;
}

.group{
    margin-bottom:2px;
}

.full{
    grid-column:1 / -1;
}

/* =========================
   INPUTS
========================= */

label{
    display:block;
    color:#bbb;
    font-size:11px;
    margin-bottom:7px;
}

.required{
    color:#D6C29C;
}

.optional{
    color:#666;
    font-size:10px;
}

input,
select,
textarea{
    width:100%;

    padding:12px 13px;

    background:#101010;

    border:1px solid #333;

    border-radius:9px;

    color:#fff;

    font-size:12px;

    outline:none;

    transition:.2s;
}

input::placeholder,
textarea::placeholder{
    color:#555;
}

input:focus,
select:focus,
textarea:focus{
    border-color:#D6C29C;

    box-shadow:
        0 0 8px
        rgba(214,194,156,.18);
}

select{
    cursor:pointer;
}

select option{
    background:#111;
    color:#fff;
}

textarea{
    min-height:100px;
    resize:vertical;
    line-height:1.6;
}

/* =========================
   OTHER FIELD
========================= */

.other-field{
    display:none;
    margin-top:10px;
}

.other-field.show{
    display:block;
}

/* =========================
   TERMS
========================= */

.terms-box{
    margin-top:30px;

    padding:17px;

    background:
        rgba(214,194,156,.06);

    border:
        1px solid
        rgba(214,194,156,.16);

    border-radius:10px;
}

.checkbox-row{
    display:flex;
    align-items:flex-start;
    gap:10px;
}

.checkbox-row input{
    width:16px;
    height:16px;
    margin-top:2px;
    accent-color:#D6C29C;
    flex-shrink:0;
}

.checkbox-row label{
    margin:0;
    color:#aaa;
    font-size:10px;
    line-height:1.7;
}

.terms-link{
    color:#D6C29C;
    cursor:pointer;
    text-decoration:underline;
}

.terms-link:hover{
    color:#eadbbd;
}

/* =========================
   BUTTON
========================= */

button{
    width:100%;

    padding:14px;

    margin-top:22px;

    background:#D6C29C;

    border:none;

    border-radius:10px;

    color:#111;

    font-size:13px;
    font-weight:700;

    cursor:pointer;

    transition:.2s;
}

button:hover{
    transform:translateY(-2px);

    box-shadow:
        0 8px 20px
        rgba(214,194,156,.20);
}

.back{
    display:block;

    text-align:center;

    margin-top:15px;

    color:#888;

    text-decoration:none;

    font-size:11px;
}

.back:hover{
    color:#D6C29C;
}

/* =========================
   MODAL
========================= */

.modal{
    display:none;
    position:fixed;
    z-index:9999;
    inset:0;
    width:100%;
    height:100%;
    height:100dvh;
    background:rgba(0,0,0,.82);
    padding:20px;
    overflow-y:auto;
    overflow-x:hidden;
    overscroll-behavior:contain;
}

.modal-content{
    position:relative;
    width:100%;
    max-width:700px;
    max-height:calc(100vh - 40px);
    max-height:calc(100dvh - 40px);
    margin:0 auto;
    display:flex;
    flex-direction:column;
    min-width:0;
    overflow:hidden;
    background:#151515;
    border:1px solid rgba(214,194,156,.25);
    border-radius:16px;
    padding:30px;
    box-shadow:0 20px 50px rgba(0,0,0,.65);
}

.terms-content{
    min-height:0;
    overflow-y:auto;
    overflow-x:hidden;
    overscroll-behavior:contain;
    overflow-wrap:anywhere;
    -webkit-overflow-scrolling:touch;
}

.modal-content h2{
    font-family:'Playfair Display',serif;

    color:#D6C29C;

    font-size:25px;

    margin-bottom:6px;
}

.modal-subtitle{
    color:#777;
    font-size:10px;
    margin-bottom:22px;
    overflow-wrap:anywhere;
    flex-shrink:0;
}

.term-section{
    margin-bottom:19px;
}

.term-section h3{
    color:#D6C29C;

    font-size:12px;

    margin-bottom:6px;
}

.term-section p{
    color:#aaa;

    font-size:10px;

    line-height:1.8;
}

.close-modal{
    position:absolute;

    right:20px;
    top:16px;

    color:#999;

    font-size:25px;

    cursor:pointer;
}

.close-modal:hover{
    color:#D6C29C;
}

.modal-close-button{
    margin-top:5px;
}

/* =========================
   RESPONSIVE
========================= */

@media(max-width:650px){

    .container{
        padding:25px 12px;
    }

    .card{
        padding:24px 18px;
    }

    .profile-header h1{
        font-size:27px;
    }

    .form-grid{
        grid-template-columns:1fr;
    }

    .full{
        grid-column:auto;
    }

    .modal{
        padding:12px;
    }

    .modal-content{
        max-height:calc(100vh - 24px);
        max-height:calc(100dvh - 24px);
        padding:24px 18px 18px;
        border-radius:12px;
    }

    .modal-content h2{
        font-size:22px;
        padding-right:24px;
        overflow-wrap:anywhere;
    }

    .terms-content{
        min-width:0;
    }

    .term-section p{
        overflow-wrap:anywhere;
    }

    .close-modal{
        right:13px;
        top:10px;
    }
}

</style>

</head>

<body>


<div class="container">

<div class="card">


    <!-- HEADER -->

    <div class="profile-header">

        <h1>
            Wellness Profile
        </h1>

        <p>
            Tell us about your wellness needs and massage
            preferences to help us prepare for your visit.
        </p>

    </div>


    <?php if(!empty($error)): ?>

        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>


    <form method="POST" id="wellnessForm">


        <!-- =========================
             HEALTH INFORMATION
        ========================= -->

        <div class="section">

            <div class="section-title">
                Health Information
            </div>

            <p class="section-desc">
                Please select the option that best describes
                your current wellness condition.
            </p>


            <div class="form-grid">


                <!-- ALLERGIES -->

                <div class="group">

                    <label>
                        Allergies
                        <span class="required">*</span>
                    </label>

                    <select
                        name="allergies"
                        id="allergies"
                        required
                        onchange="toggleOther(
                            'allergies',
                            'allergiesOtherBox',
                            'allergiesOther'
                        )"
                    >

                        <option value="">
                            Select an option
                        </option>

                        <option
                            value="None"
                            <?= $current_allergy === 'None'
                                ? 'selected'
                                : ''
                            ?>
                        >
                            None
                        </option>

                        <option
                            value="Lavender"
                            <?= $current_allergy === 'Lavender'
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Lavender
                        </option>

                        <option
                            value="Coconut Oil"
                            <?= $current_allergy === 'Coconut Oil'
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Coconut Oil
                        </option>

                        <option
                            value="Essential Oils"
                            <?= $current_allergy === 'Essential Oils'
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Essential Oils
                        </option>

                        <option
                            value="Other"
                            <?= $allergy_is_other
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Other
                        </option>

                    </select>


                    <div
                        class="other-field <?= $allergy_is_other ? 'show' : '' ?>"
                        id="allergiesOtherBox"
                    >

                        <input
                            type="text"
                            name="allergies_other"
                            id="allergiesOther"
                            value="<?= $allergy_is_other
                                ? htmlspecialchars($current_allergy)
                                : ''
                            ?>"
                            placeholder="Please specify your allergy"
                            <?= $allergy_is_other ? 'required' : '' ?>
                        >

                    </div>

                </div>


                <!-- SKIN -->

                <div class="group">

                    <label>
                        Skin Sensitivity
                        <span class="required">*</span>
                    </label>

                    <select
                        name="skin_sensitivity"
                        required
                    >

                        <option value="">
                            Select an option
                        </option>

                        <?php foreach($skin_options as $option): ?>

                            <option
                                value="<?= htmlspecialchars($option) ?>"
                                <?= $current_skin === $option
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                <?= htmlspecialchars($option) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- MEDICAL CONDITIONS -->

                <div class="group">

                    <label>
                        Medical Conditions
                        <span class="required">*</span>
                    </label>

                    <select
                        name="medical_conditions"
                        id="medicalConditions"
                        required
                        onchange="toggleOther(
                            'medicalConditions',
                            'medicalOtherBox',
                            'medicalOther'
                        )"
                    >

                        <option value="">
                            Select an option
                        </option>

                        <?php foreach($medical_options as $option): ?>

                            <option
                                value="<?= htmlspecialchars($option) ?>"
                                <?= $current_medical === $option
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                <?= htmlspecialchars($option) ?>
                            </option>

                        <?php endforeach; ?>

                        <option
                            value="Other"
                            <?= $medical_is_other
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Other
                        </option>

                    </select>


                    <div
                        class="other-field <?= $medical_is_other ? 'show' : '' ?>"
                        id="medicalOtherBox"
                    >

                        <input
                            type="text"
                            name="medical_conditions_other"
                            id="medicalOther"
                            value="<?= $medical_is_other
                                ? htmlspecialchars($current_medical)
                                : ''
                            ?>"
                            placeholder="Please specify your medical condition"
                            <?= $medical_is_other ? 'required' : '' ?>
                        >

                    </div>

                </div>


                <!-- BODY PAIN -->

                <div class="group">

                    <label>
                        Current Injuries or Body Pain
                        <span class="required">*</span>
                    </label>

                    <select
                        name="body_pain_injuries"
                        id="bodyPain"
                        required
                        onchange="toggleOther(
                            'bodyPain',
                            'bodyOtherBox',
                            'bodyOther'
                        )"
                    >

                        <option value="">
                            Select an option
                        </option>

                        <?php foreach($body_pain_options as $option): ?>

                            <option
                                value="<?= htmlspecialchars($option) ?>"
                                <?= $current_body === $option
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                <?= htmlspecialchars($option) ?>
                            </option>

                        <?php endforeach; ?>

                        <option
                            value="Other"
                            <?= $body_is_other
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Other
                        </option>

                    </select>


                    <div
                        class="other-field <?= $body_is_other ? 'show' : '' ?>"
                        id="bodyOtherBox"
                    >

                        <input
                            type="text"
                            name="body_pain_injuries_other"
                            id="bodyOther"
                            value="<?= $body_is_other
                                ? htmlspecialchars($current_body)
                                : ''
                            ?>"
                            placeholder="Please specify your injury or body pain"
                            <?= $body_is_other ? 'required' : '' ?>
                        >

                    </div>

                </div>


            </div>

        </div>


        <!-- =========================
             MASSAGE PREFERENCES
        ========================= -->

        <div class="section">

            <div class="section-title">
                Massage Preferences
            </div>

            <p class="section-desc">
                Let us know your preferred massage pressure
                and any special requests for your session.
            </p>


            <div class="form-grid">


                <!-- PRESSURE -->

                <div class="group full">

                    <label>
                        Preferred Massage Pressure
                        <span class="required">*</span>
                    </label>

                    <select
                        name="preferred_pressure"
                        required
                    >

                        <option value="">
                            Select an option
                        </option>

                        <?php foreach($pressure_options as $option): ?>

                            <option
                                value="<?= htmlspecialchars($option) ?>"
                                <?= $current_pressure === $option
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                <?= htmlspecialchars($option) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- SPECIAL REQUEST -->

                <div class="group full">

                    <label>
                        Special Requests
                        <span class="optional">
                            (Optional)
                        </span>
                    </label>

                    <textarea
                        name="special_requests"
                        placeholder="e.g. Avoid shoulder area, focus on lower back, avoid scented oils..."
                    ><?= htmlspecialchars($profile['special_requests'] ?? '') ?></textarea>

                </div>


            </div>

        </div>


        <!-- =========================
             EMERGENCY CONTACT
        ========================= -->

        <div class="section">

            <div class="section-title">
                Emergency Contact
            </div>

            <p class="section-desc">
                This information is optional and will only
                be used when necessary.
            </p>


            <div class="form-grid">


                <div class="group">

                    <label>
                        Emergency Contact Name
                        <span class="optional">
                            (Optional)
                        </span>
                    </label>

                    <input
                        type="text"
                        name="emergency_contact_name"
                        value="<?= htmlspecialchars(
                            $profile['emergency_contact_name'] ?? ''
                        ) ?>"
                        placeholder="Full name"
                    >

                </div>


                <div class="group">

                    <label>
                        Emergency Contact Number
                        <span class="optional">
                            (Optional)
                        </span>
                    </label>

                    <input
                        type="text"
                        name="emergency_contact_number"
                        value="<?= htmlspecialchars(
                            $profile['emergency_contact_number'] ?? ''
                        ) ?>"
                        placeholder="e.g. 09XXXXXXXXX"
                    >

                </div>


            </div>

        </div>


        <!-- =========================
             TERMS
        ========================= -->

        <div class="terms-box">

            <div class="checkbox-row">

                <input
                    type="checkbox"
                    name="terms_accepted"
                    id="termsAccepted"
                    value="1"
                    required
                >

                <label for="termsAccepted">

                    I confirm that the information I provided
                    is accurate to the best of my knowledge
                    and I have read and agree to the

                    <span
                        class="terms-link"
                        onclick="openTerms()"
                    >
                        Terms and Conditions
                    </span>.

                </label>

            </div>

        </div>


        <!-- SAVE -->

        <button
            type="submit"
            name="save_wellness"
        >
            Save Wellness Profile
        </button>


    </form>


    <a
        href="dashboard.php"
        class="back"
    >
        ← Back to Dashboard
    </a>


</div>

</div>


<!-- =========================
     TERMS MODAL
========================= -->

<div
    class="modal"
    id="termsModal"
>

    <div class="modal-content">

        <span
            class="close-modal"
            onclick="closeTerms()"
        >
            &times;
        </span>


        <h2>
            Terms and Conditions
        </h2>

        <p class="modal-subtitle">
            Mizpah Wellness Spa — Wellness Profile
        </p>


        <div class="terms-content">
            <?php renderWellnessTerms($wellness_terms); ?>
        </div>


        <button
            type="button"
            class="modal-close-button"
            onclick="closeTerms()"
        >
            Close
        </button>


    </div>

</div>


<script>

/* =========================
   OTHER FIELDS
========================= */

function toggleOther(
    selectId,
    boxId,
    inputId
){

    const select =
        document.getElementById(selectId);

    const box =
        document.getElementById(boxId);

    const input =
        document.getElementById(inputId);


    if(select.value === 'Other'){

        box.classList.add('show');

        input.required = true;

    }else{

        box.classList.remove('show');

        input.required = false;

        input.value = '';

    }

}


/* =========================
   TERMS MODAL
========================= */

function openTerms(){

    document.getElementById(
        'termsModal'
    ).style.display = 'block';

    document.body.style.overflow = 'hidden';

}


function closeTerms(){

    document.getElementById(
        'termsModal'
    ).style.display = 'none';

    document.body.style.overflow = 'auto';

}


/* CLICK OUTSIDE MODAL */

window.addEventListener(
    'click',
    function(event){

        const modal =
            document.getElementById(
                'termsModal'
            );

        if(event.target === modal){

            closeTerms();

        }

    }
);


/* ESC KEY */

document.addEventListener(
    'keydown',
    function(event){

        if(event.key === 'Escape'){

            closeTerms();

        }

    }
);

</script>


</body>
</html>