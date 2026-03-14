<script>
    (function () {
        var storageKey = 'maxton-theme';
        var fallbackTheme = 'blue-theme';
        var allowedThemes = ['blue-theme', 'light', 'dark', 'semi-dark', 'bodered-theme'];

        try {
            var storedTheme = localStorage.getItem(storageKey);
            var theme = allowedThemes.indexOf(storedTheme) !== -1 ? storedTheme : fallbackTheme;

            document.documentElement.setAttribute('data-bs-theme', theme);
        } catch (error) {
            document.documentElement.setAttribute('data-bs-theme', fallbackTheme);
        }
    })();
</script>
