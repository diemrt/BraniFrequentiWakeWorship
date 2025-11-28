    </main>
    <script src="js/scripts.js"></script>
    <script>
        // Mobile menu toggle
        const userMenuToggle = document.getElementById('user-menu-toggle');
        const userMenuMobile = document.getElementById('user-menu-mobile');
        
        if (userMenuToggle && userMenuMobile) {
            userMenuToggle.addEventListener('click', function(e) {
                e.preventDefault();
                userMenuMobile.classList.toggle('hidden');
            });
            
            // Close menu when clicking outside
            document.addEventListener('click', function(e) {
                if (!userMenuToggle.contains(e.target) && !userMenuMobile.contains(e.target)) {
                    userMenuMobile.classList.add('hidden');
                }
            });
        }
    </script>
</body>
</html>