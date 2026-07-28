<script>
    try {
        const storedTheme = localStorage.getItem('rentadrive-theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

        if (storedTheme === 'dark' || (storedTheme === null && prefersDark)) {
            document.documentElement.classList.add('dark');
        }
    } catch {
        // The interface remains usable when browser storage is unavailable.
    }
</script>
