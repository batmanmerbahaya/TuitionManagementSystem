<script>
    // Sends a POST form (with Laravel's CSRF token, and _method for PUT/PATCH/DELETE).
    // Replaces the plain GET links the JSP version used for logout / delete / drop actions.
    function submitAction(url, method, fields) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = url;
        form.style.display = 'none';
        const data = Object.assign({ _token: @json(csrf_token()) }, fields || {});
        if (method && method.toUpperCase() !== 'POST') data._method = method.toUpperCase();
        Object.keys(data).forEach(function (key) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = data[key];
            form.appendChild(input);
        });
        document.body.appendChild(form);
        form.submit();
    }
</script>
