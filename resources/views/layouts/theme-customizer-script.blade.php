<script>
    (function () {
        var storageKey = 'maxton-theme';
        var fallbackTheme = 'blue-theme';
        var themeMap = {
            BlueTheme: 'blue-theme',
            LightTheme: 'light',
            DarkTheme: 'dark',
            SemiDarkTheme: 'semi-dark',
            BoderedTheme: 'bodered-theme'
        };

        function getCurrentTheme() {
            return document.documentElement.getAttribute('data-bs-theme') || fallbackTheme;
        }

        function applyTheme(theme) {
            document.documentElement.setAttribute('data-bs-theme', theme);

            try {
                localStorage.setItem(storageKey, theme);
            } catch (error) {
                // Ignore storage failures and still apply the theme.
            }
        }

        function syncCustomizerInputs() {
            Object.keys(themeMap).forEach(function (inputId) {
                var input = document.getElementById(inputId);

                if (!input) {
                    return;
                }

                input.checked = themeMap[inputId] === getCurrentTheme();
            });
        }

        function bindCustomizer() {
            Object.keys(themeMap).forEach(function (inputId) {
                var input = document.getElementById(inputId);

                if (!input) {
                    return;
                }

                input.addEventListener('change', function () {
                    if (!input.checked) {
                        return;
                    }

                    applyTheme(themeMap[inputId]);
                    syncCustomizerInputs();
                });
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            syncCustomizerInputs();
            bindCustomizer();
        });
    })();
</script>
