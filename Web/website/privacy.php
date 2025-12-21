<?php
$pageTitle = 'Privacy Policy';
$activePage = 'privacy';
require_once __DIR__ . '/header.php';
?>

<div class="content-container" style="max-width: 800px; margin: 0 auto; color: var(--sp-text-secondary);">
    <h1 style="color: var(--sp-text-primary); margin-bottom: 30px;">Privacy Policy</h1>

    <section style="margin-bottom: 40px;">
        <h2 style="color: var(--sp-text-primary); margin-bottom: 15px;">Core Philosophy</h2>
        <p style="margin-bottom: 15px; line-height: 1.6;">
            ShadowPulse was designed with a "privacy-first" architecture. The name "Shadow" is a deliberate nod to our
            operational philosophy: to remain completely unseen and unobtrusive, leaving no digital footprint. We
            believe analytical tools should respect the user's anonymity and the integrity of the platform they operate
            on.
        </p>
        <p style="margin-bottom: 15px; line-height: 1.6;">
            Our extension operates as an overlay. It does not scrape your private data, it does not read your private
            messages, and it does not track your browsing history at all.
        </p>
    </section>

    <section style="margin-bottom: 40px;">
        <h2 style="color: var(--sp-text-primary); margin-bottom: 15px;">Data Collection & Usage</h2>

        <h3 style="color: var(--sp-text-primary); font-size: 1.2rem; margin-bottom: 10px;">User Identity</h3>
        <p style="margin-bottom: 15px; line-height: 1.6;">
            We do not associate your ShadowPulse usage with your Bitcointalk user profile. <strong>It is impossible for
                us to tie your extension usage to your forum account or real-world identity.</strong>
        </p>
        <p style="margin-bottom: 15px; line-height: 1.6;">
            We <strong>do not</strong> track, log, or store your IP address. While the web server may collect standard
            connection logs for security (as all websites do), this data is never linked to your unique extension
            identifier. Your activity remains completely anonymous.
        </p>

        <h4 style="color: var(--sp-text-primary); font-size: 1.1rem; margin-bottom: 10px; margin-top: 15px;">Custom
            Privacy</h4>
        <p style="margin-bottom: 15px; line-height: 1.6;">
            By default, your activity is reported under a generic alias (e.g., 'Member 421') derived seamlessly from
            your internal sequential ID (which is solely used for database indexing and has <strong>no relation</strong>
            to your Bitcointalk Forum ID).
        </p>
        <p style="margin-bottom: 15px; line-height: 1.6;">
            You have the option to set a <strong>Custom Display Name</strong> in the extension settings at any time.
            This allows you to obfuscate your internal Member ID and personalize your experience while maintaining full
            anonymity.
        </p>

        <h3 style="color: var(--sp-text-primary); font-size: 1.2rem; margin-bottom: 10px;">Page Interaction</h3>
        <p style="margin-bottom: 15px; line-height: 1.6;">
            <strong>We do not read the DOM (Document Object Model) of the pages you visit for content
                extraction.</strong>
        </p>
        <p style="margin-bottom: 15px; line-height: 1.6;">
        <p style="margin-bottom: 15px; line-height: 1.6;">
            The extension reads the browser URL <strong>only</strong> to identify the topic ID. It reads the Page Title
            to provide a human-readable label (e.g., "Bitcoin Discussion") for the statistics database when you visit a
            page. We do not analyze the text of posts, signatures, or user profiles.
        </p>
        <p
            style="margin-bottom: 15px; line-height: 1.6; font-style: italic; border-left: 3px solid #4ade80; padding-left: 10px;">
            <strong>Privacy Exemption:</strong> When you visit your own profile page (e.g. <code>action=profile</code>
            without a user ID), the extension intentionally <strong>ignores</strong> the page title (which contains your
            username) and instead reports a generic label ("Profile (Self)"). This prevents your username from being
            associated with your anonymous ShadowPulse UUID.
        </p>

        <h3 style="color: var(--sp-text-primary); font-size: 1.2rem; margin-bottom: 10px;">Search Privacy</h3>
        <p style="margin-bottom: 15px; line-height: 1.6;">
            When using the search feature, we do <strong>not</strong> record your UUID or link the individual search
            query to your profile. We only aggregate the search term and its timestamp to identify platform trends and
            statistics. Individual search history is never tracked.
        </p>
        </p>
    </section>

    <section style="margin-bottom: 40px;">
        <h2 style="color: var(--sp-text-primary); margin-bottom: 15px;">Cookies & Storage</h2>

        <h3 style="color: var(--sp-text-primary); font-size: 1.2rem; margin-bottom: 10px;">Browser Extension</h3>
        <p style="margin-bottom: 15px; line-height: 1.6;">
            The ShadowPulse extension uses <strong>Local Storage</strong> (not tracking cookies) for functional purposes
            only:
        </p>
        <ul style="margin-left: 20px; list-style-type: disc; margin-bottom: 15px; line-height: 1.6;">
            <li style="margin-bottom: 5px;"><strong>member_uuid:</strong> Your anonymous identifier.</li>
            <li style="margin-bottom: 5px;"><strong>sp_vote_*:</strong> Caching your own votes for instant UI feedback.
            </li>
            <li style="margin-bottom: 5px;"><strong>sp_btc_stats:</strong> Caching the Bitcoin price to reduce server
                load.</li>
        </ul>
        <p style="margin-bottom: 15px; line-height: 1.6;">
            The extension <strong>does not</strong> use third-party tracking cookies or external advertising trackers
            (like Google or Facebook). Extension ads, Bitcoin price data, and all other dynamic content are served
            directly from our own infrastructure for privacy.
        </p>

        <h3 style="color: var(--sp-text-primary); font-size: 1.2rem; margin-bottom: 10px;">Website (vod.fan)</h3>
        <p style="margin-bottom: 15px; line-height: 1.6;">
            On our website, we utilize <strong>Google AdSense</strong> to support hosting costs. Google may use cookies
            to serve ads based on your prior visits to this website or other websites. You can opt out of personalized
            advertising by visiting <a href="https://www.google.com/settings/ads" target="_blank"
                style="color: #4ade80;">Google Ad Settings</a>.
        </p>
    </section>

    <section style="margin-bottom: 40px;">
        <h2 style="color: var(--sp-text-primary); margin-bottom: 15px;">Technical Implementation</h2>
        <p style="margin-bottom: 15px; line-height: 1.6;">
            <strong>DOM Injection:</strong> To display the ShadowPulse interface, we inject an isolated overlay element
            into the webpage.
            This process is strictly additive and "blind" to the page content—meaning the extension does not inspect,
            read, or depend on the existing structure of the forum to render its overlay.
            This ensures that your browsing context remains private and the extension remains lightweight.
        </p>
    </section>


    <section style="margin-bottom: 40px;">
        <h2 style="color: var(--sp-text-primary); margin-bottom: 15px;">Data Retention</h2>
        <p style="margin-bottom: 15px; line-height: 1.6;">
            To maintain the accuracy and relevancy of our rating system, the system automatically removes all votes
            associated with any UUID that has not been active for 90 days. This keeps the ecosystem fresh and ensures
            rankings reflect the current active user base.
        </p>
    </section>

    <div style="text-align: center; margin-top: 50px; color: #666; font-size: 0.8em;">v6</div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>