(function () {
    try {
        var savedTheme = localStorage.getItem('portfolio_theme');
        var introSeen = localStorage.getItem('portfolio_intro_seen') === 'true';
        var hour = new Date().getHours();
        var defaultTheme = hour >= 7 && hour < 18 ? 'light' : 'dark';
        document.documentElement.dataset.theme = savedTheme || defaultTheme;
        if (introSeen) {
            document.documentElement.dataset.introSeen = 'true';
        }
    } catch (error) {
        document.documentElement.dataset.theme = new Date().getHours() >= 7 && new Date().getHours() < 18 ? 'light' : 'dark';
    }
}());
