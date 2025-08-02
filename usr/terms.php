<?php
require_once dirname(__DIR__) . '/includes/map.php';

$page_title = $settings_data['terms_title'] . " - " . $settings_data['seo_title'];

require_once HEADER;
?>
<main class="pt-32 pb-20">
    <section id="terms" data-aos="fade-up">
        <div class="container mx-auto px-6 max-w-4xl">
            <h1 class="text-4xl font-bold text-center mb-6 section-title"><?php echo htmlspecialchars($settings_data['terms_title']); ?></h1>
            <div class="bg-gray-800/50 p-8 rounded-lg shadow-lg prose prose-invert max-w-none">
                <h2>1. Acceptance of Terms</h2>
                <p>By accessing and using the myZedora OS platform ("the Service"), you accept and agree to be bound by the terms and provisions of this agreement. If you do not agree to abide by the above, please do not use this service. myZedora OS reserves the right to change these terms from time to time at its sole discretion. Your continued use of the service indicates your agreement to the new terms.</p>

                <h2>2. User Responsibilities</h2>
                <p>You are solely responsible for all content, including text, images, and links, that you post on the platform. You agree not to upload any content that is illegal, defamatory, threatening, or otherwise objectionable. You must not use the Service to distribute malware, spam, or engage in any other harmful activities. You are responsible for maintaining the confidentiality of your account password and for all activities that occur under your account.</p>

                <h2>3. Content Ownership and License</h2>
                <p>You retain all rights to the content you post on myZedora OS. By submitting your content, you grant myZedora OS a worldwide, non-exclusive, royalty-free license to use, reproduce, modify, and display the content solely for the purpose of operating, marketing, and improving the Service. This license terminates when you remove your content from the platform.</p>

                <h2>4. Account Termination</h2>
                <p>myZedora OS may, in its sole discretion, terminate or suspend your access to all or part of the Service, without notice, for any reason, including without limitation, a breach of these Terms of Service. You may terminate your account at any time via the "Delete Account" option in your profile settings.</p>

                <h2>5. Disclaimer of Warranties</h2>
                <p>The service is provided "as is," with all faults, and myZedora OS makes no express or implied representations or warranties of any kind related to this website or the materials contained on this website. This does not affect your statutory rights.</p>

                <h2>6. Limitation of Liability</h2>
                <p>In no event shall myZedora OS, nor any of its officers, directors and employees, be held liable for anything arising out of or in any way connected with your use of this website, whether such liability is under contract, tort or otherwise.</p>

                <h2>7. Governing Law</h2>
                <p>This agreement is governed by and construed in accordance with the laws of the jurisdiction where myZedora OS is hosted, and you submit to the non-exclusive jurisdiction of the state and federal courts located in that jurisdiction for the resolution of any disputes.</p>
            </div>
        </div>
    </section>
</main>
<?php require_once FOOTER; ?>