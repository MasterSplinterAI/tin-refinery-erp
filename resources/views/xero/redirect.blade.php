<!DOCTYPE html>
<html>
<head>
    <title>Redirecting to Xero...</title>
    <script>
        // Redirect immediately when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            window.location.replace("{{ $url }}");
        });
    </script>
</head>
<body>
    <p>Redirecting to Xero... If you are not redirected automatically, <a href="{{ $url }}">click here</a>.</p>
</body>
</html> 