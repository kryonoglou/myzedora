<?php
if (!defined('MYZEDORA_URL')) {
    define('MYZEDORA_URL', 'https://www.myzedora.com/');
}
?>
    <footer id="footer" class="bg-slate-900 border-t border-gray-800">
        <div class="container mx-auto px-6 py-8 text-center text-gray-400">
            <p>&copy; <?php echo date("Y"); ?> <?php echo htmlspecialchars($settings_data['footer_copyright'] ?? ''); ?></p>
            <p class="text-sm mt-2">
                <?php
                    $credits = htmlspecialchars($settings_data['footer_credits'] ?? '');
                    
                    $brand_name_to_link = 'myZedora CMS';

                    $link = '<a href="' . MYZEDORA_URL . '" class="hover:text-sky-400" target="_blank">' . $brand_name_to_link . '</a>';

                    echo str_replace($brand_name_to_link, $link, $credits);
                ?>
                <span class="mx-2 text-gray-600">|</span>
                <a href="<?php echo TERMS_URL; ?>" class="hover:text-sky-400 transition-colors duration-300">
                    <?php echo htmlspecialchars($settings_data['menu_terms'] ?? ''); ?>
                </a>
            </p>
        </div>
    </footer>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/typed.js@2.0.12"></script>

    <script>
        AOS.init({
            duration: 800,
            once: false,
        });

        if (document.getElementById('typed-text')) {
            new Typed('#typed-text', {
                strings: "<?php echo htmlspecialchars($settings_data['hero_subtitle_typed'] ?? ''); ?>".split(','),
                typeSpeed: 50,
                backSpeed: 25,
                backDelay: 1500,
                loop: true,
            });
        }

        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');
        if (mobileMenuButton) {
            mobileMenuButton.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
            });
        }
        const mobileNavLinks = document.querySelectorAll('#mobile-menu a');
        mobileNavLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (mobileMenu) {
                    mobileMenu.classList.add('hidden');
                }
            });
        });

        const currentPagePath = window.location.pathname;
        const allNavLinks = document.querySelectorAll('#desktop-nav a, #mobile-menu a');
        
        allNavLinks.forEach(link => {
            const linkPath = new URL(link.href).pathname;
            if (linkPath === currentPagePath && !link.href.includes('#')) {
                link.classList.add('active');
            }
        });
        
        const sections = document.querySelectorAll('section[id]');
        const desktopNavLinks = document.querySelectorAll('#desktop-nav a');

        const homeUrl = '<?php echo HOME_URL; ?>';
        const isHomePage = (window.location.pathname === homeUrl || window.location.pathname === homeUrl + 'index.php');
        
        if (isHomePage) {
            if (sections.length > 0 && desktopNavLinks.length > 0) {
                window.addEventListener('scroll', () => {
                    let currentSectionId = '';
                    
                    sections.forEach(section => {
                        const sectionTop = section.offsetTop;
                        if (pageYOffset >= sectionTop - 150) {
                            currentSectionId = section.getAttribute('id');
                        }
                    });

                    if ((window.innerHeight + window.pageYOffset) >= document.body.offsetHeight - 2) {
                        currentSectionId = 'contact';
                    }

                    desktopNavLinks.forEach(link => {
                        link.classList.remove('active');
                        if (link.href.includes('#' + currentSectionId)) {
                            link.classList.add('active');
                        }
                    });
                });
            }
        }
    </script>

</body>
</html>