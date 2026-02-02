<?php
session_start();
require_once __DIR__ . '/../config.php';

$loggedInId = $_SESSION['user_id'] ?? null;
$username = null;

// If user is logged in, get their username
if ($loggedInId) {
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$loggedInId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $username = $user['username'] ?? null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - Quest</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../responsive.css">
</head>
<body>
    
    <?php include __DIR__ . '/../components/sidebar.php'; ?>
    <?php include __DIR__ . '/../components/nav.php'; ?>

    <main class="privacy-page">

        <header class="privacy-header">
            <div class="privacy-hero">
                <div class="privacy-kh">
                    <img src="../images/privacy.png" alt="Privacy Policy illustration">
                </div>
                <div>
                    <h1 class="privacy-title">Privacy Policy</h1>
                    <p class="secondary-text privacy-title">Last updated: March 2025</p>
                    <p class="secondary-text privacy-title">
                        This Privacy Policy explains what information Quest collects and how
                        it is used while you browse and discover games on our site.
                    </p>
                </div>
            </div>
        </header>

        <section class="privacy-title">
            <h2>Information We Collect</h2>
            <p class="secondary-text privacy-text">
                We collect information to provide, maintain, and improve Quest. This includes basic account
                details such as your email and username, usage data like the pages or games you view, and
                technical information including browser type, device model, IP address, and approximate
                location. We also use cookies to remember your preferences and keep features working properly.
            </p>
        </section>

        <section class="privacy-title">
            <h2>How We Use Your Information</h2>
            <p class="secondary-text">
                We use this information to personalize your experience, improve recommendations, understand
                platform performance, and maintain site security. Analytics help us learn which features users
                enjoy most. We do not sell your information, and we use it solely to operate and enhance Quest.
            </p>
        </section>

        <section class="privacy-title">
            <h2>Cookies and Tracking Technologies</h2>
            <p class="secondary-text">
                Quest uses essential cookies to keep the site running, preference cookies to remember your
                settings, and analytics cookies to understand usage patterns. Cookies help us improve the
                website and create a smoother experience. You may disable them in browser settings, but some
                features may not function properly.
            </p>
        </section>

        <section class="privacy-title">
            <h2>How We Store and Protect Your Data</h2>
            <p class="secondary-text">
                We use reasonable security measures such as encrypted connections, secure storage, and limited
                staff access to protect your information. While no system is perfect, we work to reduce risks
                of unauthorized access or misuse.
            </p>
        </section>

        <section class="privacy-title">
            <h2>Sharing Your Information</h2>
            <p class="secondary-text">
                We do not sell your information. However, we may share limited data with trusted service
                providers that help operate Quest—such as hosting platforms, analytics tools, or email
                services. These partners are required to safeguard your information and cannot use it for
                unrelated purposes.
            </p>
        </section>

        <section class="privacy-title">
            <h2>Third-Party Links</h2>
            <p class="secondary-text">
                Quest may contain links to external websites or platforms. We are not responsible for their
                privacy practices or content. Once you leave our site, their policies apply instead.
            </p>
        </section>

        <section class="privacy-title">
            <h2>Your Rights & Choices</h2>
            <p class="secondary-text">
                You may request access to your stored data, ask for corrections, or request deletion of your
                account. You can also opt out of optional communications and disable non-essential cookies.
                To exercise these rights, email us at privacy@quest.gg.
            </p>
        </section>

        <section class="privacy-title">
            <h2>Data Retention</h2>
            <p class="secondary-text">
                We retain data only as long as necessary to operate Quest, improve features, and meet any
                legal or security requirements. If you delete your account, most associated information will
                be removed unless we are required to retain certain data.
            </p>
        </section>

        <section class="privacy-title">
            <h2>Children's Privacy</h2>
            <p class="secondary-text">
                Quest is not intended for children under 13. We do not knowingly collect information from
                minors. If such data is discovered, we will delete it promptly.
            </p>
        </section>

        <section class="privacy-title">
            <h2>International Users</h2>
            <p class="secondary-text">
                If you access Quest from outside our primary region, your information may be processed in
                locations with different data laws. By using the platform, you consent to this processing.
            </p>
        </section>

        <section class="privacy-title">
            <h2>Policy Updates</h2>
            <p class="secondary-text">
                We may update this Privacy Policy when needed due to new features, legal requirements, or
                user feedback. The "Last Updated" date will always be displayed at the top of the page.
            </p>
        </section>

        <section class="privacy-title">
            <h2>Contact Us</h2>
            <p class="secondary-text">
                For questions about this Privacy Policy or your information, contact us at:
                <br><br>
                <strong>privacy@quest.gg</strong>
            </p>
        </section>

    </main>

    <?php include __DIR__ . '/../components/authModal.php'; ?>
    <?php include __DIR__ . '/../components/footer.php'; ?>

    <script src="../script.js"></script>
</body>
</html>
